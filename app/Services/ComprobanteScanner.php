<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ComprobanteScanner
{
    /**
     * Escanea un comprobante usando Claude Haiku Vision.
     */
    public function scan(string $filePath, float $totalEsperado = 0): array
    {
        Log::info('ComprobanteScanner: Iniciando escaneo', ['file' => $filePath]);

        $resultado = $this->scanWithClaude($filePath);

        if (!$resultado) {
            return [
                'banco' => null,
                'monto' => null,
                'referencia' => null,
                'fecha' => null,
                'telefono_emisor' => null,
                'metodo' => 'fallido',
                'confianza' => 0,
                'texto_crudo' => '',
                'validacion' => [
                    'monto_coincide' => false,
                    'alertas' => ['No se pudo extraer información del comprobante'],
                ],
            ];
        }

        if ($totalEsperado > 0) {
            $resultado['validacion'] = $this->validar($resultado, $totalEsperado);
        }

        return $resultado;
    }

    /**
     * Escaneo con Claude Haiku (Anthropic) Vision API.
     */
    public function scanWithClaude(string $filePath): ?array
    {
        try {
            $apiKey = config('services.anthropic.api_key');

            if (empty($apiKey)) {
                Log::warning('ComprobanteScanner: No hay API key de Anthropic configurada');
                return null;
            }

            $imageData = file_get_contents($filePath);
            if ($imageData === false) {
                Log::error('ComprobanteScanner: No se pudo leer el archivo', ['file' => $filePath]);
                return null;
            }

            $mimeType = mime_content_type($filePath) ?: 'image/jpeg';
            $base64 = base64_encode($imageData);

            $prompt = $this->getPrompt();

            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => 'claude-haiku-4-5-20251001',
                    'max_tokens' => 500,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'image',
                                    'source' => [
                                        'type' => 'base64',
                                        'media_type' => $mimeType,
                                        'data' => $base64,
                                    ],
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('ComprobanteScanner: Error en Claude API', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $content = $response->json('content.0.text');
            Log::info('ComprobanteScanner: Respuesta de Claude', ['content' => $content]);

            return $this->parseResponse($content, 'claude');
        } catch (\Exception $e) {
            Log::error('ComprobanteScanner: Error en Claude', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Prompt para la API.
     */
    private function getPrompt(): string
    {
        return <<<EOT
Analiza este comprobante de pago colombiano y extrae la siguiente información en formato JSON estricto.

REGLAS IMPORTANTES:
- Los bancos/apps comunes son: Nequi, Daviplata, Bancolombia, llave Bre-B (Banco de Bogotá), PSE, Transfiya.
- Si ves el logo o interfaz de Nequi, el banco SIEMPRE es "nequi" (no bancolombia).
- La REFERENCIA es un código de transacción (en Nequi empieza con "M" seguido de números, ej: M3565348). NUNCA confundir con números de celular (10 dígitos que empiezan con 3). Si solo ves un celular y no hay código de referencia, poner null.
- El MONTO es el valor transferido. En Colombia los pagos de bingo suelen ser entre $6.000 y $60.000. Si el número parece demasiado grande (millones), revisar si hay punto de miles (ej: 12.000 = doce mil, NO doce millones).
- telefono_emisor: SOLO extraer en Daviplata (aparece como "Desde" con un número de celular). En Nequi y otros bancos SIEMPRE poner null.

Responde SOLO con un JSON válido (sin markdown, sin texto adicional, sin ```json) con esta estructura exacta:
{
    "banco": "nequi/daviplata/bancolombia/transfiya/otro",
    "monto": numero_entero_sin_formato (ejemplo: 6000, no "$6.000"),
    "referencia": "código de referencia o ID de transacción (NO celulares)",
    "fecha": "YYYY-MM-DD HH:mm:ss",
    "telefono_emisor": "SOLO para Daviplata, null para otros bancos",
    "estado_transaccion": "exitosa/pendiente/fallida"
}

Si no puedes extraer algún campo, usa null.
EOT;
    }

    /**
     * Parsea la respuesta JSON de la API.
     */
    private function parseResponse(?string $content, string $metodo): ?array
    {
        if (empty($content)) {
            return null;
        }

        // Limpiar respuesta
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*/', '', $content);
        $content = trim($content);

        $parsed = json_decode($content, true);

        if (!$parsed) {
            Log::error('ComprobanteScanner: No se pudo parsear JSON', ['content' => $content]);
            return null;
        }

        $banco = strtolower($parsed['banco'] ?? '');
        $referencia = $parsed['referencia'] ?? null;

        // Si la referencia parece un celular colombiano (10 dígitos, empieza con 3), descartarla
        if ($referencia && preg_match('/^3\d{9}$/', $referencia)) {
            $referencia = null;
        }

        // telefono_emisor solo aplica para Daviplata
        $telefonoEmisor = ($banco === 'daviplata') ? ($parsed['telefono_emisor'] ?? null) : null;

        return [
            'banco' => $banco,
            'monto' => is_numeric($parsed['monto'] ?? null) ? (float) $parsed['monto'] : null,
            'referencia' => $referencia,
            'fecha' => $parsed['fecha'] ?? null,
            'telefono_emisor' => $telefonoEmisor,
            'estado_transaccion' => $parsed['estado_transaccion'] ?? null,
            'metodo' => $metodo,
            'confianza' => $this->calcularConfianza($parsed),
            'texto_crudo' => $content,
        ];
    }

    /**
     * Valida los datos extraídos contra el total esperado.
     */
    public function validar(array $datos, float $totalEsperado): array
    {
        $validacion = [
            'monto_coincide' => false,
            'alertas' => [],
        ];

        if ($datos['monto'] !== null) {
            $diferencia = abs($datos['monto'] - $totalEsperado);
            $tolerancia = $totalEsperado * 0.01;

            if ($diferencia <= $tolerancia) {
                $validacion['monto_coincide'] = true;
            } else {
                $validacion['alertas'][] = sprintf(
                    'Monto no coincide: comprobante $%s vs esperado $%s',
                    number_format($datos['monto'], 0, ',', '.'),
                    number_format($totalEsperado, 0, ',', '.')
                );
            }
        } else {
            $validacion['alertas'][] = 'No se pudo extraer el monto del comprobante';
        }

        if (isset($datos['estado_transaccion'])) {
            $estado = strtolower($datos['estado_transaccion']);
            if (in_array($estado, ['fallida', 'rechazada', 'cancelada'])) {
                $validacion['alertas'][] = 'La transacción aparece como: ' . $datos['estado_transaccion'];
            }
        }

        if (empty($datos['referencia'])) {
            $validacion['alertas'][] = 'No se encontró número de referencia en el comprobante';
        }

        return $validacion;
    }

    private function calcularConfianza(array $datos): float
    {
        $score = 0;

        if (!empty($datos['banco'])) $score += 25;
        if (!empty($datos['monto']) && $datos['monto'] > 0) $score += 30;
        if (!empty($datos['referencia'])) $score += 25;
        if (!empty($datos['fecha'])) $score += 20;

        return max(70, $score);
    }
}
