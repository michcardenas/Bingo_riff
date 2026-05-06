<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookLlaveController extends Controller
{
    /**
     * Recibe datos de n8n cuando llegan correos de transferencias por Llave Bre-B.
     *
     * Acepta dos formatos:
     *
     * A) Objeto único (legacy):
     * {
     *   "token": "SECRET",
     *   "monto": 100,
     *   "nombre_pagador": "MARTIN RODRIGUEZ CAUSIL",
     *   "cuenta_destino": "9988",
     *   "codigo_operacion": "112384...",
     *   "fecha": "2026-04-10 13:45:00"
     * }
     *
     * B) Array de transacciones (recomendado):
     * {
     *   "token": "SECRET",
     *   "transacciones": [
     *     { "monto": 100, "nombre_pagador": "...", ... },
     *     { "monto": 200, "nombre_pagador": "...", ... }
     *   ]
     * }
     */
    public function handle(Request $request)
    {
        // 1. Validar token
        $tokenEsperado = config('services.webhook.llave_token');
        if ($request->input('token') !== $tokenEsperado) {
            Log::warning('Webhook Llave: Token inválido', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // 2. Normalizar payload a array de transacciones
        $transacciones = $request->input('transacciones');
        if (!is_array($transacciones) || empty($transacciones)) {
            // Fallback: tratar el body como una sola transacción
            $transacciones = [$request->except('token')];
        }

        Log::info('Webhook Llave: Batch recibido', [
            'total_transacciones' => count($transacciones),
        ]);

        $resultados = [];
        $aprobadas  = 0;
        $reservasYaMatcheadas = []; // evitar matchear 2 correos con la misma reserva

        foreach ($transacciones as $tx) {
            $resultado = $this->procesarTransaccion($tx, $reservasYaMatcheadas);
            $resultados[] = $resultado;
            if ($resultado['success']) {
                $aprobadas++;
                $reservasYaMatcheadas[] = $resultado['reserva_id'];
            }
        }

        return response()->json([
            'success'     => true,
            'total'       => count($transacciones),
            'aprobadas'   => $aprobadas,
            'no_matched'  => count($transacciones) - $aprobadas,
            'resultados'  => $resultados,
        ]);
    }

    /**
     * Procesa una sola transacción y busca la reserva correspondiente.
     */
    private function procesarTransaccion(array $tx, array $reservasYaMatcheadas): array
    {
        $monto           = (float) ($tx['monto'] ?? 0);
        $nombrePagador   = trim((string) ($tx['nombre_pagador'] ?? ''));
        $cuentaDestino   = trim((string) ($tx['cuenta_destino'] ?? ''));
        $codigoOperacion = $tx['codigo_operacion'] ?? null;
        $fecha           = $tx['fecha'] ?? null;

        // Validar cuenta destino
        $cuentaEsperada = config('services.webhook.cuenta_bingo');
        if (!empty($cuentaEsperada) && $cuentaDestino !== $cuentaEsperada) {
            Log::warning('Webhook Llave: Cuenta destino no coincide', [
                'recibida' => $cuentaDestino,
                'esperada' => $cuentaEsperada,
            ]);
            return [
                'success' => false,
                'codigo_operacion' => $codigoOperacion,
                'message' => 'Cuenta destino no corresponde al bingo.',
            ];
        }

        if ($monto <= 0) {
            return [
                'success' => false,
                'codigo_operacion' => $codigoOperacion,
                'message' => 'Monto requerido.',
            ];
        }

        // Buscar reserva candidata
        $bingosActivos = \App\Models\Bingo::whereIn('estado', ['activo', 'en_curso', 'abierto'])
            ->pluck('id');

        $llaveBingo = config('services.webhook.llave_bingo');
        $nombreReceptorBingo = strtoupper(config('services.webhook.nombre_receptor_bingo'));

        $query = Reserva::where('estado', 'revision')
            ->whereIn('bingo_id', $bingosActivos)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(ocr_data, '$.banco')) = 'llave bre-b'");

        // Excluir reservas ya matcheadas en este mismo batch
        if (!empty($reservasYaMatcheadas)) {
            $query->whereNotIn('id', $reservasYaMatcheadas);
        }

        // La llave destino o el nombre receptor deben coincidir con los del bingo.
        // (basta con uno de los dos: si Claude OCR no leyó la llave pero sí el receptor, igual valida)
        $palabrasReceptor = !empty($nombreReceptorBingo)
            ? array_filter(explode(' ', $nombreReceptorBingo), fn($p) => strlen($p) >= 3)
            : [];

        if (!empty($llaveBingo) || !empty($palabrasReceptor)) {
            $query->where(function ($q) use ($llaveBingo, $palabrasReceptor) {
                if (!empty($llaveBingo)) {
                    $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(ocr_data, '\$.llave_destino')) = ?", [$llaveBingo]);
                }
                if (!empty($palabrasReceptor)) {
                    $q->orWhere(function ($qq) use ($palabrasReceptor) {
                        foreach ($palabrasReceptor as $palabra) {
                            $qq->orWhereRaw("UPPER(JSON_UNQUOTE(JSON_EXTRACT(ocr_data, '\$.nombre_receptor'))) LIKE ?", ['%' . $palabra . '%']);
                        }
                    });
                }
            });
        }

        // Filtro por ventana de tiempo: la fecha del OCR del comprobante debe estar
        // dentro de ±1 minuto de la fecha del correo (ventana mínima para máxima precisión)
        $minutosTolerancia = 1;
        if (!empty($fecha)) {
            try {
                $fechaCorreo = \Carbon\Carbon::parse($fecha);
                $fechaMin = $fechaCorreo->copy()->subMinutes($minutosTolerancia)->format('Y-m-d H:i:s');
                $fechaMax = $fechaCorreo->copy()->addMinutes($minutosTolerancia)->format('Y-m-d H:i:s');

                $query->whereRaw(
                    "STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(ocr_data, '$.fecha')), '%Y-%m-%d %H:%i:%s') BETWEEN ? AND ?",
                    [$fechaMin, $fechaMax]
                );
            } catch (\Exception $ex) {
                Log::warning('Webhook Llave: Fecha del correo inválida, se omite filtro de ventana', [
                    'fecha' => $fecha,
                    'error' => $ex->getMessage(),
                ]);
            }
        }

        // Filtro por monto del OCR: el monto que leyó Claude del comprobante
        // debe coincidir con el monto del correo (tolerancia 1%)
        $query->whereRaw(
            "ABS(CAST(JSON_UNQUOTE(JSON_EXTRACT(ocr_data, '$.monto')) AS DECIMAL(15,2)) - ?) <= ?",
            [$monto, max(1, $monto * 0.01)]
        );

        // FILTRO OBLIGATORIO: el nombre del pagador (correo BBVA) debe coincidir con
        // el nombre del cliente registrado en la reserva. Al menos UNA palabra de 3+ letras
        // (ignorando palabras vacías como DE, LA, EL, DEL, etc.) debe estar presente.
        $palabrasIgnoradas = ['DE', 'LA', 'EL', 'DEL', 'LOS', 'LAS', 'Y', 'DA', 'DO'];
        $palabras = array_filter(
            explode(' ', strtoupper($nombrePagador)),
            fn($p) => strlen($p) >= 3 && !in_array($p, $palabrasIgnoradas)
        );

        if (empty($palabras)) {
            Log::warning('Webhook Llave: nombre_pagador vacío o sin palabras útiles, no se puede validar', [
                'nombre_pagador' => $nombrePagador,
            ]);
            return [
                'success' => false,
                'codigo_operacion' => $codigoOperacion,
                'monto' => $monto,
                'message' => 'Nombre del pagador no válido para validar.',
            ];
        }

        // Aplicar filtro: el nombre del comprador debe contener al menos una palabra del pagador
        $query->where(function ($q) use ($palabras) {
            foreach ($palabras as $palabra) {
                $q->orWhereRaw('UPPER(nombre) LIKE ?', ['%' . $palabra . '%']);
            }
        });

        $reserva = null;
        $metodoMatch = null;

        $queryConMonto = (clone $query)->where('total', $monto);

        // PRIORIDAD 1: la reserva con fecha OCR más cercana a la fecha del correo
        if (!empty($fecha)) {
            $reserva = (clone $queryConMonto)
                ->orderByRaw(
                    "ABS(TIMESTAMPDIFF(SECOND, STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(ocr_data, '\$.fecha')), '%Y-%m-%d %H:%i:%s'), ?))",
                    [$fecha]
                )
                ->first();
            if ($reserva) {
                $metodoMatch = 'nombre_comprador_y_fecha';
            }
        }

        // PRIORIDAD 2 (fallback): primera reserva que cumpla todos los filtros
        if (!$reserva) {
            $reserva = $queryConMonto->orderBy('id')->first();
            if ($reserva) {
                $metodoMatch = 'nombre_comprador';
            }
        }

        if (!$reserva) {
            Log::warning('Webhook Llave: No se encontró reserva para aprobar', [
                'monto' => $monto,
                'nombre_pagador' => $nombrePagador,
                'codigo_operacion' => $codigoOperacion,
            ]);
            return [
                'success' => false,
                'codigo_operacion' => $codigoOperacion,
                'monto' => $monto,
                'nombre_pagador' => $nombrePagador,
                'message' => 'No se encontró reserva para este pago.',
            ];
        }

        // Aprobar reserva — conservar los datos originales del OCR y agregar los del correo
        // como campos separados (prefijo "correo_") para no sobreescribir la info del comprobante
        $ocrActual = is_array($reserva->ocr_data) ? $reserva->ocr_data : json_decode($reserva->ocr_data, true) ?? [];

        $ocrActualizado = array_merge($ocrActual, [
            'correo_codigo_operacion' => $codigoOperacion,
            'correo_nombre_pagador'   => $nombrePagador,
            'correo_cuenta_destino'   => $cuentaDestino,
            'correo_fecha'            => $fecha,
            'correo_monto'            => $monto,
            'estado_transaccion'      => 'exitosa',
            'metodo'                  => 'webhook_n8n',
        ]);

        $reserva->update([
            'estado'     => 'aprobado',
            'ocr_data'   => $ocrActualizado,
            'ocr_status' => 'procesado',
        ]);

        Log::info('Webhook Llave: Reserva aprobada', [
            'reserva_id'   => $reserva->id,
            'nombre'       => $reserva->nombre,
            'monto'        => $monto,
            'metodo_match' => $metodoMatch,
        ]);

        return [
            'success'      => true,
            'reserva_id'   => $reserva->id,
            'nombre'       => $reserva->nombre,
            'monto'        => $monto,
            'metodo_match' => $metodoMatch,
        ];
    }
}
