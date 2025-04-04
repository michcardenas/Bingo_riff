<?php

namespace App\Http\Controllers;

use App\Models\Bingo;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BingoGanadoresController extends Controller
{
    /**
     * Muestra la página de buscador de serie.
     *
     * @param  Bingo  $bingo
     * @return \Illuminate\View\View
     */
    public function index(Bingo $bingo)
    {
        // Verificar si hay un bingo activo o cerrado (no archivado)
        if (!$bingo || $bingo->estado === 'archivado') {
            return redirect()->route('bingos.index')
                ->with('error', 'No hay un bingo disponible para gestionar ganadores.');
        }

        // Usar la ruta de vista especificada
        return view('admin.vistabuscadordeserie', compact('bingo'));
    }

    public function buscarPorSerie(Bingo $bingo, $serie)
    {
        Log::channel('debug')->info('===============================================');
        Log::channel('debug')->info('INICIO BÚSQUEDA POR SERIE', [
            'bingo_id' => $bingo->id,
            'bingo_nombre' => $bingo->nombre,
            'serie_buscada' => $serie,
            'ip_solicitante' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'headers' => request()->headers->all(),
        ]);
    
        if ($bingo->estado === 'archivado') {
            Log::channel('debug')->warning('Intento de búsqueda en bingo archivado', [
                'bingo_id' => $bingo->id,
                'serie' => $serie
            ]);
            return response()->json(['error' => 'Este bingo ha sido archivado.'], 403);
        }
    
        // Asegúrate de que el formato de la serie coincida con como está guardado
        $seriePadded = str_pad($serie, 6, '0', STR_PAD_LEFT);
        
        Log::channel('debug')->info('Serie formateada', [
            'original' => $serie,
            'formateada' => $seriePadded
        ]);
    
        try {
            // Obtienes las reservas del bingo actual
            $reservas = Reserva::where('bingo_id', $bingo->id)
                ->where('eliminado', false)
                ->get();
    
            Log::channel('debug')->info('Reservas encontradas para el bingo', [
                'cantidad_reservas' => $reservas->count(),
                'ids_reservas' => $reservas->pluck('id')->toArray()
            ]);
    
            $reservaEncontrada = null;
            $numeroCarton = null;
            $serieEncontrada = null;
            $detalleBusqueda = [];
    
            foreach ($reservas as $index => $reserva) {
                // Decodificamos las series JSON a un array PHP
                $seriesJSON = $reserva->series;
                
                Log::channel('debug')->info("Analizando reserva #{$index}", [
                    'id' => $reserva->id,
                    'nombre' => $reserva->nombre,
                    'series_raw' => $seriesJSON
                ]);
                
                $series = json_decode($seriesJSON, true);
                
                if (!is_array($series)) {
                    Log::channel('debug')->warning("Series no es un array para reserva", [
                        'id' => $reserva->id, 
                        'series_raw' => $seriesJSON,
                        'type' => gettype($series),
                        'decode_result' => $series
                    ]);
                    $detalleBusqueda[] = [
                        'reserva_id' => $reserva->id,
                        'error' => 'Series no es un array',
                        'series_raw' => $seriesJSON
                    ];
                    continue;
                }
                
                Log::channel('debug')->info("Series decodificadas para reserva #{$index}", [
                    'id' => $reserva->id,
                    'series_decoded' => $series
                ]);
                
                // Buscamos tanto con la serie original como con la formateada
                $encontrado = false;
                $posicion = null;
                
                if (in_array($serie, $series)) {
                    $encontrado = true;
                    $posicion = array_search($serie, $series);
                    $serieEncontrada = $serie;
                    Log::channel('debug')->info("Serie original encontrada en posición {$posicion}");
                } elseif (in_array($seriePadded, $series)) {
                    $encontrado = true;
                    $posicion = array_search($seriePadded, $series);
                    $serieEncontrada = $seriePadded;
                    Log::channel('debug')->info("Serie con padding encontrada en posición {$posicion}");
                }
                
                $detalleBusqueda[] = [
                    'reserva_id' => $reserva->id,
                    'series' => $series,
                    'encontrado' => $encontrado,
                    'posicion' => $posicion,
                    'serie_buscada_original' => $serie,
                    'serie_buscada_padded' => $seriePadded
                ];
                
                if ($encontrado) {
                    $reservaEncontrada = $reserva;
                    $numeroCarton = $serieEncontrada;
                    Log::channel('debug')->info("Serie encontrada en reserva", [
                        'reserva_id' => $reserva->id,
                        'nombre' => $reserva->nombre,
                        'serie' => $serieEncontrada,
                        'posicion' => $posicion
                    ]);
                    break;
                }
            }
    
            if (!$reservaEncontrada) {
                Log::channel('debug')->warning("No se encontró ningún participante", [
                    'serie_original' => $serie,
                    'serie_padded' => $seriePadded,
                    'detalle_busqueda' => $detalleBusqueda
                ]);
                
                return response()->json(['error' => 'No se encontró ningún participante con ese número de serie.'], 404);
            }
    
            $telefono = $this->censurarTelefono($reservaEncontrada->celular);
            $esGanador = isset($reservaEncontrada->ganador) && $reservaEncontrada->ganador;
            $premio = $reservaEncontrada->premio ?? '';
    
            $respuesta = [
                'id' => $reservaEncontrada->id,
                'serie' => $serieEncontrada,
                'nombre' => $reservaEncontrada->nombre,
                'telefono' => $telefono,
                'carton' => $numeroCarton,
                'premio' => $premio,
                'ganador' => $esGanador
            ];
            
            Log::channel('debug')->info('Respuesta final de búsqueda', $respuesta);
            Log::channel('debug')->info('FIN BÚSQUEDA POR SERIE EXITOSA');
            
            return response()->json($respuesta);
        } catch (\Exception $e) {
            Log::channel('debug')->error('Error en búsqueda por serie', [
                'error' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'traza' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Ocurrió un error al buscar el participante: ' . $e->getMessage()], 500);
        }
    }
    

    /**
     * Marca a un participante como ganador y registra el premio.
     *
     * @param  Request  $request
     * @param  Bingo  $bingo
     * @param  Reserva  $participante
     * @return \Illuminate\Http\JsonResponse
     */
    public function marcarGanador(Request $request, Bingo $bingo, Reserva $participante)
    {
        // Si el bingo está archivado, no permitir cambios
        if ($bingo->estado === 'archivado') {
            return response()->json(['error' => 'Este bingo ha sido archivado y no se puede modificar.'], 403);
        }

        // Validar el premio
        $request->validate([
            'premio' => 'required|string|max:255',
        ]);

        try {
            // Verificar que la reserva pertenece al bingo
            if ($participante->bingo_id !== $bingo->id) {
                return response()->json(['error' => 'El participante no pertenece a este bingo.'], 400);
            }

            // Actualizar la reserva para marcarla como ganadora
            $participante->update([
                'premio' => $request->premio,
                'ganador' => true,
                'fecha_ganador' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => "El participante {$participante->nombre} ha sido marcado como ganador del premio: {$request->premio}"
            ]);
        } catch (\Exception $e) {
            Log::error('Error al marcar ganador: ' . $e->getMessage());
            return response()->json(['error' => 'Ocurrió un error al marcar el ganador.'], 500);
        }
    }

    /**
     * Censura los últimos 4 dígitos del número de teléfono.
     *
     * @param  string  $telefono
     * @return string
     */
    private function censurarTelefono($telefono)
    {
        if (!$telefono || strlen($telefono) <= 4) {
            return '****';
        }
        
        return substr($telefono, 0, -4) . '****';
    }
}