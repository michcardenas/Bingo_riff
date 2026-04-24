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
- Los bancos/apps comunes son: Nequi, Daviplata, Bancolombia, llave Bre-B (Banco de Bogotá / BBVA), PSE, Transfiya, Nu, Davivienda.
- Si ves el logo o interfaz de Nequi, el banco SIEMPRE es "nequi".
- Si el comprobante menciona "Bre-B", "Pasaste Plata por Bre-B", "transferencia a llave", "Vía Bre-B", muestra un número de llave larga (ej: 0091706852), o la entidad destino es BBVA → banco = "llave bre-b" (aunque el banco origen sea Nequi, Davivienda, Nu, etc).
- La REFERENCIA es el código único de la transacción. Puede aparecer con distintos nombres según el banco:
  * Nequi: "Referencia" (empieza con "M" + números, ej: M3565348)
  * Nu / Nubank: "Número de comprobante" (secuencia larga de ~30+ dígitos, ej: 9772671581233832307834512434079507)
  * Davivienda / Daviplata: "Número de aprobación" o "Referencia No."
  * Bancolombia: "Número de transacción" o "Referencia"
  * BBVA / Bre-B: "Código de operación" o "Comprobante No."
  * Transfiya: "Referencia"
  Extraer SIEMPRE el número completo. NUNCA confundir con números de celular (10 dígitos que empiezan con 3). Si solo ves un celular y no hay código de referencia claro, poner null.
- El MONTO es el valor transferido. En Colombia los pagos de bingo suelen ser entre \$6.000 y \$60.000.
  FORMATO COLOMBIANO: el PUNTO es separador de miles y la COMA es decimal.
  * "\$100,00" = cien pesos (100, la parte ",00" son centavos)
  * "\$100,40" = cien pesos con 40 centavos (100, NO 10.040)
  * "\$6.000" = seis mil pesos (6000)
  * "\$12.000,00" = doce mil pesos (12000)
  IMPORTANTE: ignorar los centavos (lo que va después de la coma). El monto debe ser entero en pesos.
  Si hay varios montos en el comprobante (ej: "Monto total" e "Impuesto 4x1.000"), usa el valor principal SIN impuestos, o el que aparece más destacado (normalmente en letras grandes).
  Si el número parece demasiado grande (millones), revisar si estás confundiendo decimal con miles.
- telefono_emisor: SOLO extraer en Daviplata (aparece como "Desde" con un número de celular). En otros bancos poner null.

CAMPOS DE LLAVE BRE-B (solo si es transferencia Bre-B):
- llave_destino: el número de llave al que se transfirió (ej: "0091706852"). Suele aparecer como "Llave", "Código de negocio", "a la llave BBVA". Poner null si no es Bre-B.
- nombre_receptor: nombre COMPLETO de la persona que RECIBE (dueño de la llave destino). En Bre-B aparece como "Para", "Enviado a", "Pasaste Plata por Bre-B [NOMBRE]", "a la llave BBVA X de [NOMBRE]". Poner null si no aparece o no es Bre-B.
- nombre_pagador: nombre del que envía el dinero, SI aparece. En la mayoría de comprobantes Bre-B NO aparece el pagador (solo aparece el receptor). Si no es visible, poner null.

Responde SOLO con un JSON válido (sin markdown, sin texto adicional, sin ```json) con esta estructura exacta:
{
    "banco": "nequi/daviplata/bancolombia/llave bre-b/transfiya/otro",
    "monto": numero_entero_sin_formato (ejemplo: 6000, no "$6.000"),
    "referencia": "código de referencia o ID de transacción (NO celulares)",
    "fecha": "YYYY-MM-DD HH:mm:ss",
    "telefono_emisor": "SOLO para Daviplata, null para otros bancos",
    "llave_destino": "número de llave Bre-B destino, o null",
    "nombre_receptor": "nombre de quien RECIBE la transferencia Bre-B, o null",
    "nombre_pagador": "nombre de quien ENVÍA, o null (raro en Bre-B)",
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
            'llave_destino' => $parsed['llave_destino'] ?? null,
            'nombre_receptor' => $parsed['nombre_receptor'] ?? null,
            'nombre_pagador' => $parsed['nombre_pagador'] ?? null,
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
