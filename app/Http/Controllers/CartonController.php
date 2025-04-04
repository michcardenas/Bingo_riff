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
        // Usar el nuevo campo telefono_atencion con respaldo al número de contacto antiguo
        $numeroContacto = $enlaces ? ($enlaces->telefono_atencion ?: $enlaces->numero_contacto) : '3235903774';
        
        return view('buscarcartones', compact('numeroContacto'));
    }

    /**
     * Busca cartones por número de teléfono y filtra los bingos archivados.
     */
    public function buscar(Request $request)
    {
        $request->validate([
            'celular' => 'required|numeric',
        ]);

        $telefono = $request->input('celular');
        Log::info('Búsqueda iniciada para teléfono: ' . $telefono);

        // Buscar reservas asociadas al número de teléfono
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
            $bingoEstado = null;
            
            if ($reserva->bingo_id && $reserva->bingo) {
                $bingoNombre = $reserva->bingo->nombre;
                $bingoId = $reserva->bingo_id;
                $bingoEstado = $reserva->bingo->estado;
                
                // Saltar esta reserva si el bingo está archivado
                if (strtolower($bingoEstado) === 'archivado') {
                    Log::info('Saltando reserva para bingo archivado: ' . $bingoNombre);
                    continue;
                }
            }
            
            Log::info('Bingo asociado: ' . $bingoNombre . ', Estado: ' . ($bingoEstado ?? 'N/A'));

            // Si hay series registradas, procesarlas
            if (!empty($reserva->series)) {
                $seriesArray = json_decode($reserva->series, true);
                Log::info('Series decodificadas: ' . json_encode($seriesArray));

                if (is_array($seriesArray)) {
                    foreach ($seriesArray as $serie) {
                        // Solo agregar si no está asociado a un bingo archivado
                        $cartones->push([
                            'numero' => $serie,
                            'estado' => $reserva->estado,
                            'nombre' => $reserva->nombre,
                            'fecha_creacion' => $reserva->created_at->format('d/m/Y'),
                            'tipo_sorteo' => 'Principal',
                            'id_reserva' => $reserva->id,
                            'bingo_nombre' => $bingoNombre,
                            'bingo_id' => $bingoId,
                            'bingo_estado' => $bingoEstado, // Agregamos el estado del bingo para referencia en la vista
                            'eliminado' => $reserva->eliminado
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

        Log::info('Total de cartones encontrados (después de filtrar archivados): ' . $cartones->count());
        
        // Obtener número de contacto para WhatsApp
        $enlaces = Enlace::first();
        // Usar el nuevo campo telefono_atencion con respaldo al número de contacto antiguo
        $numeroContacto = $enlaces ? ($enlaces->telefono_atencion ?: $enlaces->numero_contacto) : '3235903774';

        return view('buscarcartones', [
            'cartones' => $cartones,
            'numeroContacto' => $numeroContacto
        ]);
    }

    public function descargar($numero, $bingoId = null)
    {
        Log::info("Iniciando descarga de cartón: $numero, Bingo ID: $bingoId");
    
        // Eliminar ceros a la izquierda
        $numeroSinCeros = ltrim($numero, '0');
    
        // Preparar la consulta base para las reservas
        $query = Reserva::where(function($q) {
            $q->where('reservas.estado', 'aprobado')
              ->orWhere('reservas.estado', 'revision');
        })->where('reservas.eliminado', 0);
        
        // Si se proporciona un bingoId específico, priorizar ese bingo
        if ($bingoId) {
            $query->where('reservas.bingo_id', $bingoId);
        } else {
            // Si no se proporciona un bingoId, unir con la tabla de bingos para ordenar
            $query->join('bingos', 'reservas.bingo_id', '=', 'bingos.id')
                  ->where('bingos.estado', '!=', 'archivado')
                  ->orderBy('bingos.created_at', 'desc'); // Ordenar por fecha de creación descendente
        }
    
        $reservas = $query->get();
        $reservaEncontrada = null;
    
        // Buscar manualmente en las series
        foreach ($reservas as $reserva) {
            if (!empty($reserva->series)) {
                $seriesArray = json_decode($reserva->series, true);
                if (is_array($seriesArray) && in_array($numero, $seriesArray)) {
                    $reservaEncontrada = $reserva;
                    break; // Romper el ciclo al encontrar la primera reserva (la más reciente debido al orderBy)
                }
            }
        }
    
        if (!$reservaEncontrada) {
            Log::warning("Cartón no encontrado o no disponible: $numero");
            return redirect()->back()->with('error', 'El cartón no existe o no está disponible para descarga.');
        }
    
        // Verificar el estado del bingo
        if ($reservaEncontrada->bingo_id && $reservaEncontrada->bingo) {
            $bingo = $reservaEncontrada->bingo;
            $bingoEstado = strtolower($bingo->estado);
            
            // Verificar si el bingo está archivado
            if ($bingoEstado === 'archivado') {
                Log::warning("Intento de descarga de cartón {$numero} para bingo archivado");
                return redirect()->back()->with('error', 'Este cartón pertenece a un bingo archivado y no puede ser descargado.');
            }
            
            // Ya no verificamos si el bingo está cerrado, permitiendo descargas sin importar el estado
            // Se eliminó la condición que impedía descargar cartones de bingos cerrados
        }
    
        // Convertir el número a entero para quitar ceros iniciales
        $numeroSinCeros = intval($numero);
        $rutaCompleta = storage_path('app/private/public/TablasbingoRIFFY/' . $numeroSinCeros . '.pdf');
    
        if (!file_exists($rutaCompleta)) {
            Log::warning("Archivo de cartón no encontrado: $rutaCompleta");
            return redirect()->back()->with('error', 'No se encontró el archivo del cartón.');
        }
    
        // Preparar el nombre del archivo
        $nombreArchivo = "Carton-RIFFY-{$numeroSinCeros}";
    
        // Descargar directamente
        Log::info("Descargando cartón: $numeroSinCeros");
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