<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reserva;
use App\Models\Bingo;
use App\Models\Enlace;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class CartonController extends Controller
{
/**
     * Muestra la vista para buscar cartones.
     */
    public function index()
    {
        // Obtener número de contacto para WhatsApp
        $enlaces = Enlace::first();
        $numeroContacto = $enlaces ? $enlaces->numero_contacto : '3235903774';
        
        return view('buscarcartones', compact('numeroContacto'));
    }

    /**
     * Busca cartones por número de teléfono.
     */
    public function buscar(Request $request)
    {
        $request->validate([
            'celular' => 'required|numeric',
        ]);

        $telefono = $request->input('celular');
        Log::info('Búsqueda iniciada para teléfono: ' . $telefono);

        // Buscar reservas asociadas al número de teléfono
        // IMPORTANTE: Quitamos el filtro de eliminado=0 para mostrar todos
        $reservas = Reserva::where('celular', $telefono)
            ->get();

        Log::info('Reservas encontradas: ' . $reservas->count());

        // Preparar los datos de cartones a partir de las reservas
        $cartones = collect();

        foreach ($reservas as $reserva) {
            Log::info('Procesando reserva ID: ' . $reserva->id . ', Series: ' . $reserva->series . ', Estado: ' . $reserva->estado);

            // Obtener información del bingo asociado
            $bingoNombre = 'No asignado';
            $bingoId = null;
            if ($reserva->bingo_id && $reserva->bingo) {
                $bingoNombre = $reserva->bingo->nombre;
                $bingoId = $reserva->bingo_id;
            }
            Log::info('Bingo asociado: ' . $bingoNombre);

            // Si hay series registradas, procesarlas
            if (!empty($reserva->series)) {
                $seriesArray = json_decode($reserva->series, true);
                Log::info('Series decodificadas: ' . json_encode($seriesArray));

                if (is_array($seriesArray)) {
                    foreach ($seriesArray as $serie) {
                        $cartones->push([
                            'numero' => $serie,
                            'estado' => $reserva->estado, // Incluimos el estado tal cual viene de la base de datos
                            'nombre' => $reserva->nombre,
                            'fecha_creacion' => $reserva->created_at->format('d/m/Y'),
                            'tipo_sorteo' => 'Principal',
                            'id_reserva' => $reserva->id,
                            'bingo_nombre' => $bingoNombre,
                            'bingo_id' => $bingoId,
                            'eliminado' => $reserva->eliminado // Añadimos el campo eliminado para referencia
                        ]);
                        Log::info('Cartón agregado: ' . $serie . ' para bingo: ' . $bingoNombre . ', Estado: ' . $reserva->estado);
                    }
                } else {
                    Log::warning('El formato de series no es un array para la reserva ID: ' . $reserva->id);
                }
            } else {
                Log::info('No hay series para la reserva ID: ' . $reserva->id);
            }
        }

        Log::info('Total de cartones encontrados: ' . $cartones->count());
        
        // Obtener número de contacto para WhatsApp
        $enlaces = Enlace::first();
        $numeroContacto = $enlaces ? $enlaces->numero_contacto : '3235903774';

        return view('buscarcartones', [
            'cartones' => $cartones,
            'numeroContacto' => $numeroContacto
        ]);
    }

  /**
     * Descarga el cartón si está aprobado, agregando una segunda página con la marca de agua.
     * Incluye verificación para bingos cerrados (máx 24 horas de disponibilidad) y archivados (no permite descarga)
     */
    public function descargar($numero, $bingoId = null)
    {
        Log::info("Iniciando descarga de cartón: $numero, Bingo ID: $bingoId");
    
        // 🔹 **Eliminar ceros a la izquierda**
        $numeroSinCeros = ltrim($numero, '0');
    
        // Buscar reservas aprobadas
        $query = Reserva::where('estado', 'aprobado')->where('eliminado', 0);
        if ($bingoId) {
            $query->where('bingo_id', $bingoId);
        }
    
        $reservas = $query->get();
        $reservaEncontrada = null;
    
        // Buscar manualmente en las series
        foreach ($reservas as $reserva) {
            if (!empty($reserva->series)) {
                $seriesArray = json_decode($reserva->series, true);
                if (is_array($seriesArray) && in_array($numero, $seriesArray)) {
                    $reservaEncontrada = $reserva;
                    break;
                }
            }
        }
    
        if (!$reservaEncontrada) {
            Log::warning("Cartón no encontrado o no aprobado: $numero");
            return redirect()->back()->with('error', 'El cartón no existe o no está aprobado.');
        }

        // Verificar si el bingo está archivado - no permite descarga en ningún caso
        if ($reservaEncontrada->bingo_id && $reservaEncontrada->bingo) {
            $bingo = $reservaEncontrada->bingo;
            
            // Verificar si el bingo está archivado
            if (strtolower($bingo->estado) === 'archivado') {
                Log::warning("Intento de descarga de cartón {$numero} para bingo archivado");
                return redirect()->back()->with('error', 'Este cartón pertenece a un bingo archivado y no puede ser descargado.');
            }
            
            // VERIFICACIÓN EXISTENTE: Verificar si el bingo está cerrado y el tiempo desde su cierre
            if (strtolower($bingo->estado) !== 'abierto') {
                // Determinar la fecha de cierre (usando fecha_cierre o updated_at como respaldo)
                $fechaCierre = $bingo->fecha_cierre ? Carbon::parse($bingo->fecha_cierre) : Carbon::parse($bingo->updated_at);
                $ahora = Carbon::now();
                $diferenciaHoras = $fechaCierre->diffInHours($ahora);
                
                // Si han pasado más de 24 horas desde el cierre
                if ($diferenciaHoras > 24) {
                    Log::warning("Intento de descarga de cartón {$numero} para bingo cerrado hace más de 24 horas");
                    return redirect()->back()->with('error', 'La descarga de este cartón ha expirado. Los cartones solo están disponibles por 24 horas después del cierre del bingo.');
                }
                
                // Si está dentro del período válido, registrar en el log
                Log::info("Descarga de cartón {$numero} para bingo cerrado dentro del período válido ({$diferenciaHoras} horas desde el cierre)");
            }
        }

        // Convertir el número a entero para quitar ceros iniciales
        $numeroSinCeros = intval($numero);
        $rutaCompleta = storage_path('app/private/public/Tablas bingo RIFFY/' . $numeroSinCeros . '.pdf');

        if (!file_exists($rutaCompleta)) {
            Log::warning("Archivo de cartón no encontrado: $rutaCompleta");
            return redirect()->back()->with('error', 'No se encontró el archivo del cartón.');
        }
    
        // 🔹 **Preparar el nombre del archivo**
        $nombreArchivo = "Carton-RIFFY-{$numeroSinCeros}";
    
        // 🔹 **Descargar directamente**
        Log::info("Descargando cartón sin página adicional de marca de agua: $numeroSinCeros");
        return response()->download($rutaCompleta, "{$nombreArchivo}.pdf");
    }

    public function getBingoByName(Request $request)
    {
        $nombre = $request->nombre;
        $bingo = Bingo::where('nombre', $nombre)->first();
        if (!$bingo) {
            return response()->json(['error' => 'Bingo no encontrado'], 404);
        }
        return response()->json($bingo);
    }
    
    /**
     * Obtener información del bingo por ID para la API
     */
    public function getBingoById($id)
    {
        $bingo = Bingo::find($id);
        if (!$bingo) {
            return response()->json(['error' => 'Bingo no encontrado'], 404);
        }
        return response()->json($bingo);
    }
}
