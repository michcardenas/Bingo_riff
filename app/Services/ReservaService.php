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

                $estadoInicial = $data['auto_approve'] ?? false ? 'aprobado' : 'revision';
                $numeroComprobante = ($data['auto_approve'] ?? false) ? 'AUTO-' . time() : null;

                $reservaData = [
                    'nombre'               => $data['nombre'],
                    'celular'              => $data['celular'],
                    'cantidad'             => $cantidad,
                    'comprobante'          => $data['comprobante'] ?? null,
                    'comprobante_metadata' => $data['comprobante_metadata'] ?? null,
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