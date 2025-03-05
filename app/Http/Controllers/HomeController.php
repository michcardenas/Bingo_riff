<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bingo;
use App\Models\Enlace;

class HomeController extends Controller
{
    public function index() {
        // Primero intentar obtener un bingo abierto
        $bingo = Bingo::where('estado', 'abierto')
                      ->orderBy('fecha', 'asc')
                      ->first();
        
        // Si no hay bingos abiertos, obtener el primer bingo cerrado
        if (!$bingo) {
            $bingo = Bingo::orderBy('fecha', 'asc')->first();
        }
        
        // Comprobar si el bingo está cerrado
        $esBingoCerrado = $bingo && $bingo->estado !== 'abierto';
        
        // Obtener enlaces para WhatsApp
        $enlaces = Enlace::first() ?? new Enlace();
        
        return view('home', compact('bingo', 'esBingoCerrado', 'enlaces'));
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