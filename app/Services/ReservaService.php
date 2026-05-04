<?php

namespace App\Services;

use App\Models\Bingo;
use App\Models\Reserva;
use App\Models\ReservaSerie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservaService
{
    // obtener reserva por id
    public function obtenerReservaPorId($id)
    {
        return Reserva::with('bingo', 'reservaSeries')->find($id);
    }
    /**
     * Crear una reserva de bingo
     */
    public function crearReserva(array $data)
    {
        Log::info('Iniciando proceso de reserva desde service', ['data' => $data]);

        try {
            DB::transaction(function () use ($data, &$series, &$reservaCreada) {
                $bingo = Bingo::where('id', $data['bingo_id'])
                    ->lockForUpdate()
                    ->firstOrFail();
                $precioCarton = (float) $bingo->precio;
                $totalPagar = $data['cartones'] * $precioCarton;
    
                $reservaCreada = null;
                $series = [];
                $cantidad = $data['cartones'];
                $series = $this->asignarSeries($bingo->id, $cantidad);

                $maxOrdenBingo = Reserva::where('bingo_id', $bingo->id)->max('orden_bingo') ?? 0;
                $nuevoOrdenBingo = $maxOrdenBingo + 1;

                // Auto-aprobación manual desde admin
                $autoApproveManual = $data['auto_approve'] ?? false;

                // Auto-aprobación por OCR
                $autoApproveOcr = false;
                $ocrData = $data['ocr_data'] ?? null;
                $ocrStatus = $data['ocr_status'] ?? 'pendiente';

                if ($ocrData && $ocrStatus === 'procesado' && !$autoApproveManual) {
                    $ocr = is_string($ocrData) ? json_decode($ocrData, true) : (is_array($ocrData) ? $ocrData : null);

                    if ($ocr) {
                        $banco = strtolower($ocr['banco'] ?? '');
                        $montoOcr = (float) ($ocr['monto'] ?? 0);
                        $referenciaOcr = $ocr['referencia'] ?? null;
                        $fechaOcr = $ocr['fecha'] ?? null;

                        // Validación Bre-B: llave y nombre receptor deben coincidir con el bingo
                        $bancoBingo = true;
                        if ($banco === 'llave bre-b') {
                            $llaveBingo = config('services.webhook.llave_bingo');
                            $nombreReceptorBingo = strtoupper(config('services.webhook.nombre_receptor_bingo'));
                            $llaveOcr = trim($ocr['llave_destino'] ?? '');
                            $nombreReceptorOcr = strtoupper(trim($ocr['nombre_receptor'] ?? ''));

                            // La llave debe coincidir exactamente
                            $llaveCoincide = !empty($llaveBingo) && $llaveOcr === $llaveBingo;

                            // El nombre del receptor debe contener al menos una palabra clave
                            $nombreCoincide = false;
                            if (!empty($nombreReceptorBingo) && !empty($nombreReceptorOcr)) {
                                $palabrasBingo = array_filter(explode(' ', $nombreReceptorBingo), fn($p) => strlen($p) >= 3);
                                foreach ($palabrasBingo as $palabra) {
                                    if (str_contains($nombreReceptorOcr, $palabra)) {
                                        $nombreCoincide = true;
                                        break;
                                    }
                                }
                            }

                            $bancoBingo = $llaveCoincide && $nombreCoincide;

                            Log::info('Validación Bre-B', [
                                'llave_bingo' => $llaveBingo,
                                'llave_ocr' => $llaveOcr,
                                'llave_coincide' => $llaveCoincide,
                                'nombre_receptor_bingo' => $nombreReceptorBingo,
                                'nombre_receptor_ocr' => $nombreReceptorOcr,
                                'nombre_coincide' => $nombreCoincide,
                            ]);
                        }

                        // 1. Banco verificado — SOLO Nequi/Daviplata se auto-aprueban con OCR.
                        //    Llave Bre-B queda en revisión hasta que llegue el webhook del correo del banco.
                        $bancoValido = in_array($banco, ['nequi', 'daviplata']);

                        // 2. Monto coincide (tolerancia 1%)
                        $montoCoincide = $montoOcr > 0 && abs($montoOcr - $totalPagar) <= ($totalPagar * 0.01);

                        // 3. Sin duplicado por referencia OCR
                        $sinDuplicado = true;
                        if (!empty($referenciaOcr)) {
                            $existeRef = Reserva::where('bingo_id', $bingo->id)
                                ->where('ocr_data', 'like', '%"referencia":"' . $referenciaOcr . '"%')
                                ->exists();
                            $sinDuplicado = !$existeRef;
                        }

                        // 4. Fecha/hora dentro de margen de 30 minutos
                        $fechaValida = false;
                        if ($fechaOcr) {
                            try {
                                $fechaComprobante = \Carbon\Carbon::parse($fechaOcr);
                                $ahora = now();
                                $fechaValida = $fechaComprobante->diffInMinutes($ahora) <= 30;
                            } catch (\Exception $e) {
                                $fechaValida = false;
                            }
                        }

                        $esDuplicadoOcr = !$sinDuplicado;
                        $autoApproveOcr = $bancoValido && $montoCoincide && $sinDuplicado && $fechaValida;

                        Log::info('Auto-aprobación OCR evaluada', [
                            'banco_valido' => $bancoValido,
                            'monto_coincide' => $montoCoincide,
                            'sin_duplicado' => $sinDuplicado,
                            'fecha_valida' => $fechaValida,
                            'es_duplicado' => $esDuplicadoOcr,
                            'resultado' => $autoApproveOcr,
                        ]);
                    }
                }

                $esDuplicadoOcr = $esDuplicadoOcr ?? false;
                $estadoInicial = ($autoApproveManual || $autoApproveOcr) ? 'aprobado' : 'revision';
                $numeroComprobante = $autoApproveManual ? 'AUTO-' . time() : ($esDuplicadoOcr ? 'Duplicado' : null);

                $reservaData = [
                    'nombre'               => $data['nombre'],
                    'celular'              => $data['celular'],
                    'cantidad'             => $cantidad,
                    'comprobante'          => $data['comprobante'] ?? null,
                    'comprobante_metadata' => $data['comprobante_metadata'] ?? null,
                    'ocr_data'             => $data['ocr_data'] ?? null,
                    'ocr_status'           => $data['ocr_status'] ?? 'pendiente',
                    'total'                => $totalPagar,
                    'series'               => $series,
                    'estado'               => $estadoInicial,
                    'numero_comprobante'   => $numeroComprobante,
                    'bingo_id'             => $bingo->id,
                    'orden_bingo'          => $nuevoOrdenBingo,
                ];

                $reservaCreada = Reserva::create($reservaData);

                foreach ($series as $serie) {
                    $this->guardarSerieUnica($reservaCreada->id, $bingo->id, $serie);
                }

                Log::info('Reserva creada desde service', [
                    'id' => $reservaCreada->id,
                    'orden_bingo' => $reservaCreada->orden_bingo
                ]);
            });
            $bingo = Bingo::find($data['bingo_id']);
            $precioCarton = (float) $bingo->precio;
            $totalPagar = $data['cartones'] * $precioCarton;

            return [
                'success' => true,
                'reserva' => $reservaCreada,
                'series' => $series,
                'bingo' => $bingo,
                'total' => $totalPagar
            ];

        } catch (\Exception $e) {
            Log::error('Error en service de reserva', ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'message' => 'Error al procesar la reserva: ' . $e->getMessage(),
                'error' => $e
            ];
        }
    }

    /**
     * Asignar series para el bingo (debes implementar esta lógica)
     */
    private function asignarSeries($bingoId, $cantidad)
    {
        $bingo = Bingo::findOrFail($bingoId);
        $seriesAsignadas = [];

        // 🔹 Obtener series liberadas disponibles más rápido
        $seriesLiberadasDisponibles = [];
        if ($bingo->series_liberadas) {
            $seriesLiberadas = json_decode($bingo->series_liberadas, true) ?: [];
            $seriesLiberadasDisponibles = array_slice($seriesLiberadas, 0, $cantidad);

            $seriesAsignadas = $seriesLiberadasDisponibles;

            // Actualizar las que quedan
            $seriesRestantes = array_slice($seriesLiberadas, $cantidad);
            $bingo->series_liberadas = $seriesRestantes ? json_encode($seriesRestantes) : null;
            $bingo->save();
        }

        // 🔹 Faltan más series → generar nuevas
        $faltantes = $cantidad - count($seriesAsignadas);
        if ($faltantes > 0) {
            $maxNumero = ReservaSerie::where('bingo_id', $bingoId)
                ->max(DB::raw('CAST(serie AS UNSIGNED)')) ?? 0;

            $nuevosNumeros = array_map(function($num) {
                return str_pad($num, 6, '0', STR_PAD_LEFT);
            }, range($maxNumero + 1, $maxNumero + $faltantes));

            $seriesAsignadas = array_merge($seriesAsignadas, $nuevosNumeros);
        }

        return $seriesAsignadas;
    }

    /**
     * Generar una nueva serie única
     */
    private function generarNuevaSerie($bingoId)
    {
        $maxNumero = \App\Models\ReservaSerie::where('bingo_id', $bingoId)
            ->max(DB::raw('CAST(serie AS UNSIGNED)'));

        $nuevoNumero = $maxNumero ? $maxNumero + 1 : 1;

        return str_pad($nuevoNumero, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Guardar serie única con reintentos
     */
    private function guardarSerieUnica($reservaId, $bingoId, $serie, $intento = 0)
    {
        // Seguridad: evitar loops infinitos (máx 10 intentos)
        if ($intento > 10) {
            throw new \Exception("No se pudo asignar una serie única después de varios intentos.");
        }

        try {
            return \App\Models\ReservaSerie::create([
                'reserva_id' => $reservaId,
                'bingo_id'   => $bingoId,
                'serie'      => $serie,
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                // Generar una nueva serie y reintentar
                $nuevaSerie = $this->generarNuevaSerie($bingoId);
                return $this->guardarSerieUnica($reservaId, $bingoId, $nuevaSerie, $intento + 1);
            }

            throw $e; // si no es duplicado, lanzar el error real
        }
    }
}