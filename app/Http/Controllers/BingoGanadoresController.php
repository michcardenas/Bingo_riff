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

// Y el método en BingoGanadoresController.php debe ser:
public function buscarPorSerie(Bingo $bingo, $serie)
{
    if ($bingo->estado === 'archivado') {
        return response()->json(['error' => 'Este bingo ha sido archivado.'], 403);
    }

    // Asegúrate de que el formato de la serie coincida con como está guardado
    $seriePadded = str_pad($serie, 6, '0', STR_PAD_LEFT);
    
    Log::info("Buscando serie: '$serie' (Formateada: '$seriePadded') en el bingo ID: " . $bingo->id);

    try {
        // Obtienes las reservas del bingo actual
        $reservas = Reserva::where('bingo_id', $bingo->id)
            ->where('eliminado', false)
            ->get();

        if ($reservas->isEmpty()) {
            Log::warning("No hay reservas para este bingo");
            return response()->json(['error' => 'No hay participantes registrados en este bingo.'], 404);
        }

        $reservaEncontrada = null;
        $numeroCarton = null;

        foreach ($reservas as $reserva) {
            // Decodificamos las series JSON a un array PHP
            $series = json_decode($reserva->series, true);
            
            if (!is_array($series)) {
                Log::warning("Series no es un array para reserva ID: " . $reserva->id . ", Valor: " . $reserva->series);
                continue;
            }
            
            // Verificamos si la serie (con o sin padding) está en el array
            if (in_array($serie, $series) || in_array($seriePadded, $series)) {
                // Encontramos la serie en esta reserva
                $reservaEncontrada = $reserva;
                $numeroCarton = $seriePadded; // Usamos la serie con el padding
                break; // Salir del bucle
            }
        }

        if (!$reservaEncontrada) {
            Log::warning("No se encontró ningún participante con la serie: '$serie' o '$seriePadded'");
            return response()->json(['error' => 'No se encontró ningún participante con ese número de serie.'], 404);
        }

        $telefono = substr_replace($reservaEncontrada->celular, '****', 3, 4); // Censurar parte del número
        $esGanador = isset($reservaEncontrada->ganador) && $reservaEncontrada->ganador;
        $premio = $reservaEncontrada->premio ?? '';

        return response()->json([
            'id' => $reservaEncontrada->id,
            'serie' => $seriePadded,
            'nombre' => $reservaEncontrada->nombre,
            'telefono' => $telefono,
            'carton' => $numeroCarton,
            'premio' => $premio,
            'ganador' => $esGanador
        ]);
    } catch (\Exception $e) {
        Log::error('Error en búsqueda por serie: ' . $e->getMessage());
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