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
        if ($bingo->estado === 'archivado') {
            return response()->json(['error' => 'Este bingo ha sido archivado.'], 403);
        }
    
        try {
            // Obtienes las reservas del bingo actual
            $reservas = Reserva::where('bingo_id', $bingo->id)
                ->where('eliminado', false)
                ->get();
    
            $reservaEncontrada = null;
            $numeroCarton = null;
            
            // Registra lo que estamos buscando para depuración
            Log::info("Buscando serie: '$serie' en el bingo ID: " . $bingo->id);
    
            foreach ($reservas as $reserva) {
                // Aquí aseguramos que las series sean siempre un array
                $series = is_string($reserva->series)
                    ? json_decode($reserva->series, true)
                    : $reserva->series;
    
                if (!is_array($series)) {
                    Log::warning("Series no es un array para reserva ID: " . $reserva->id);
                    continue;
                }
                
                // Registra las series para depuración
                Log::info("Reserva ID: " . $reserva->id . ", Series: " . json_encode($series));
    
                // IMPORTANTE: La búsqueda debe incluir conversión a cadena y eliminar ceros a la izquierda
                foreach ($series as $indice => $serieBD) {
                    // Convertimos ambos a string y eliminamos ceros a la izquierda para comparar
                    $serieLimpia = ltrim($serie, '0');
                    $serieBDLimpia = ltrim($serieBD, '0');
                    
                    // También compara con la serie original por si acaso
                    if ($serieBDLimpia === $serieLimpia || $serieBD === $serie) {
                        $reservaEncontrada = $reserva;
                        $numeroCarton = str_pad($indice + 1, 6, '0', STR_PAD_LEFT);
                        Log::info("Serie encontrada! Reserva ID: " . $reserva->id . ", Índice: " . $indice);
                        break 2; // Salir de ambos bucles
                    }
                }
            }
    
            if (!$reservaEncontrada) {
                Log::warning("No se encontró ningún participante con la serie: '$serie'");
                return response()->json(['error' => 'No se encontró ningún participante con ese número de serie.'], 404);
            }
    
            $telefono = $this->censurarTelefono($reservaEncontrada->celular);
            $esGanador = isset($reservaEncontrada->ganador) && $reservaEncontrada->ganador;
            $premio = $reservaEncontrada->premio ?? '';
    
            return response()->json([
                'id' => $reservaEncontrada->id,
                'serie' => $serie,
                'nombre' => $reservaEncontrada->nombre,
                'telefono' => $telefono,
                'carton' => $numeroCarton,
                'premio' => $premio,
                'ganador' => $esGanador
            ]);
        } catch (\Exception $e) {
            Log::error('Error al buscar participante: ' . $e->getMessage());
            return response()->json(['error' => 'Ocurrió un error al buscar el participante.'], 500);
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