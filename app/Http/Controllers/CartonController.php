<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reserva;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class CartonController extends Controller
{
    /**
     * Muestra la vista para buscar cartones
     */
    public function index()
    {
        return view('buscarcartones');
    }

    /**
     * Busca cartones por número de teléfono
     */
    public function buscar(Request $request)
    {
        $request->validate([
            'celular' => 'required|numeric',
        ]);
    
        $telefono = $request->input('celular');
        \Log::info('Búsqueda iniciada para teléfono: ' . $telefono);
    
        // Buscar reservas asociadas al número de teléfono
        $reservas = Reserva::where('celular', $telefono)
            ->where('eliminado', 0)
            ->get();
    
        \Log::info('Reservas encontradas: ' . $reservas->count());
    
        // Preparar los datos de cartones a partir de las reservas
        $cartones = collect();
    
        foreach ($reservas as $reserva) {
            \Log::info('Procesando reserva ID: ' . $reserva->id . ', Series: ' . $reserva->series);
    
            // Obtener información del bingo asociado
            $bingoNombre = 'No asignado';
            if ($reserva->bingo_id && $reserva->bingo) {
                $bingoNombre = $reserva->bingo->nombre;
            }
            \Log::info('Bingo asociado: ' . $bingoNombre);
    
            // Si hay series registradas, procesarlas
            if (!empty($reserva->series)) {
                $seriesArray = json_decode($reserva->series, true);
                \Log::info('Series decodificadas: ' . json_encode($seriesArray));
    
                if (is_array($seriesArray)) {
                    foreach ($seriesArray as $serie) {
                        $cartones->push([
                            'numero' => $serie,
                            'estado' => $reserva->estado,
                            'nombre' => $reserva->nombre,
                            'fecha_creacion' => $reserva->created_at->format('d/m/Y'),
                            'tipo_sorteo' => 'Principal',
                            'id_reserva' => $reserva->id,
                            'bingo_nombre' => $bingoNombre // Añadido el nombre del bingo
                        ]);
                        \Log::info('Cartón agregado: ' . $serie . ' para bingo: ' . $bingoNombre);
                    }
                } else {
                    \Log::warning('El formato de series no es un array para la reserva ID: ' . $reserva->id);
                }
            } else {
                \Log::info('No hay series para la reserva ID: ' . $reserva->id);
            }
        }
    
        \Log::info('Total de cartones encontrados: ' . $cartones->count());
    
        return view('buscarcartones', [
            'cartones' => $cartones
        ]);
    }

/**
 * Descarga el cartón si está aprobado
 */
public function descargar($numero)
{
    // Buscar la reserva que contiene este número de serie
    $reservas = Reserva::where('estado', 'aprobado')
                      ->where('eliminado', 0)
                      ->get();
    
    $reservaEncontrada = null;
    
    // Buscar manualmente en las series de cada reserva
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
        return redirect()->back()->with('error', 'El cartón no existe o no está aprobado.');
    }
    
    // Convertir el número a entero para quitar ceros iniciales
    $numeroSinCeros = intval($numero);
    
    // Ruta completa al archivo (ya sabemos que esta ruta funciona)
    $rutaCompleta = storage_path('app/private/public/Tablas bingo RIFFY/' . $numeroSinCeros . '.pdf');
    
    // Verificar si el archivo existe
    if (!file_exists($rutaCompleta)) {
        return redirect()->back()->with('error', 'No se encontró el archivo del cartón.');
    }
    
    // Descargar el archivo usando response()->download()
    return response()->download($rutaCompleta, 'Carton-RIFFY-' . $numero . '.pdf');
}
}
