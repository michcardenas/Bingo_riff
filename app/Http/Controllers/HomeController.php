<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bingo;
use App\Models\Enlace;

class HomeController extends Controller
{
    public function index() {
        \Log::info('Iniciando carga de página principal');
    
        try {
            // Primero intentar obtener un bingo abierto (ordenando por fecha descendente para tener el más reciente)
            $bingo = Bingo::where('estado', 'abierto')
                          ->orderBy('fecha', 'desc')
                          ->first();
            
            \Log::info('Búsqueda de bingo abierto', [
                'bingo_encontrado' => $bingo ? $bingo->id : 'No encontrado'
            ]);
            
            // Si no hay bingos abiertos, obtener el primer bingo cerrado más reciente
            if (!$bingo) {
                $bingo = Bingo::orderBy('fecha', 'desc')->first();
                
                \Log::info('Búsqueda de bingo más reciente', [
                    'bingo_encontrado' => $bingo ? $bingo->id : 'No encontrado'
                ]);
            }
            
            // Comprobar si el bingo está cerrado
            $esBingoCerrado = $bingo && $bingo->estado !== 'abierto';
            
            \Log::info('Estado del bingo', [
                'bingo_id' => $bingo ? $bingo->id : null,
                'estado' => $bingo ? $bingo->estado : 'Sin bingo',
                'es_bingo_cerrado' => $esBingoCerrado
            ]);
            
            // Obtener enlaces para WhatsApp
            $enlaces = Enlace::first() ?? new Enlace();
            
            \Log::info('Carga de enlaces para WhatsApp', [
                'enlaces_encontrados' => $enlaces->id ? 'Sí' : 'No'
            ]);
            
            return view('home', compact('bingo', 'esBingoCerrado', 'enlaces'));
        } catch (\Exception $e) {
            \Log::error('Error al cargar la página principal', [
                'mensaje' => $e->getMessage(),
                'traza' => $e->getTraceAsString()
            ]);
            
            // Puedes manejar el error como prefieras, por ejemplo:
            return view('home')->with('error', 'Ocurrió un error al cargar la página');
        }
    }
    
    // Método para obtener solo bingos abiertos (para compatibilidad)
    public function getBingos() {
        $bingos = Bingo::where('estado', 'abierto')
            ->orderBy('fecha', 'asc')
            ->get()
            ->map(function ($bingo) {
                return [
                    'id' => $bingo->id,
                    'nombre' => $bingo->nombre,
                    'fecha_formateada' => \Carbon\Carbon::parse($bingo->fecha)->format('d/m/Y'),
                    'precio' => $bingo->precio,
                    'estado' => $bingo->estado
                ];
            });
    
        return response()->json($bingos);
    }
    
    // Método para obtener todos los bingos, tanto abiertos como cerrados
    public function getAllBingos() {
        $bingos = Bingo::orderBy('fecha', 'asc')
            ->get()
            ->map(function ($bingo) {
                return [
                    'id' => $bingo->id,
                    'nombre' => $bingo->nombre,
                    'fecha_formateada' => \Carbon\Carbon::parse($bingo->fecha)->format('d/m/Y'),
                    'precio' => $bingo->precio,
                    'estado' => $bingo->estado
                ];
            });
    
        return response()->json($bingos);
    }
    
    // Método para obtener un bingo específico por ID
    public function getBingo($id) {
        $bingo = Bingo::find($id);
        
        if (!$bingo) {
            return response()->json(['error' => 'Bingo no encontrado'], 404);
        }
        
        return response()->json([
            'id' => $bingo->id,
            'nombre' => $bingo->nombre,
            'fecha_formateada' => \Carbon\Carbon::parse($bingo->fecha)->format('d/m/Y'),
            'precio' => $bingo->precio,
            'estado' => $bingo->estado
        ]);
    }
}