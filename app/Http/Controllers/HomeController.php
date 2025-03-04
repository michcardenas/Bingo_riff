<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bingo; // Asegúrate de importar el modelo Bingo

class HomeController extends Controller
{
    public function index() {
        // Obtener todos los bingos disponibles (puedes filtrar por estado si es necesario)
        $bingos = Bingo::orderBy('fecha', 'asc')->get();
        return view('home', compact('bingos'));
    }

    public function getBingos() {
        $bingos = Bingo::where('estado', 'abierto')
            ->orderBy('fecha', 'asc')
            ->get()
            ->map(function ($bingo) {
                return [
                    'id' => $bingo->id,
                    'nombre' => $bingo->nombre,
                    'fecha_formateada' => \Carbon\Carbon::parse($bingo->fecha)->format('d/m/Y'),
                    'precio' => $bingo->precio
                ];
            });
    
        return response()->json($bingos);
    }
    
    public function getBingoActivo() {
        $bingo = Bingo::where('estado', 'abierto')
            ->orderBy('fecha', 'asc')
            ->first();
    
        if (!$bingo) {
            return response()->json(['error' => 'No hay bingos activos'], 404);
        }
    
        return response()->json([
            'id' => $bingo->id,
            'nombre' => $bingo->nombre,
            'fecha' => \Carbon\Carbon::parse($bingo->fecha)->format('d/m/Y'),
            'precio' => $bingo->precio
        ]);
    }
    
}
