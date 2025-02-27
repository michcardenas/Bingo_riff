<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reserva;
use Illuminate\Support\Facades\Storage;

class BingoController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validar los datos, permitiendo múltiples imágenes
        $validated = $request->validate([
            'cartones'      => 'required|integer|min:1',
            'nombre'        => 'required|string|max:255',
            'celular'       => 'required|string|max:20',
            'comprobante'   => 'required',       // Asegura que al menos se suba 1 archivo
            'comprobante.*' => 'image|max:2048', // Cada archivo debe ser una imagen de máx 2 MB
        ]);

        // 2. Calcular el total a pagar
        $precioCarton = 6000;
        $totalPagar   = $validated['cartones'] * $precioCarton;

        // 3. Guardar las imágenes en storage y recolectar sus rutas
        $rutasArchivos = [];
        if ($request->hasFile('comprobante')) {
            foreach ($request->file('comprobante') as $file) {
                $ruta = $file->store('public/comprobantes');
                $rutasArchivos[] = str_replace('public/', '', $ruta);
            }
        }

        // 4. Guardar en la base de datos
        // Se guarda el array de rutas como JSON para almacenar múltiples imágenes
        $comprobanteStr = json_encode($rutasArchivos);

        $reserva = Reserva::create([
            'nombre'      => $validated['nombre'],
            'celular'     => $validated['celular'],
            'cantidad'    => $validated['cartones'],
            'comprobante' => $comprobanteStr,
        ]);

        // 5. Redirigir a la vista "reservado" con mensaje de éxito
        return redirect()->route('reservado')
            ->with('success', '¡Reserva realizada correctamente!');
    }
}
