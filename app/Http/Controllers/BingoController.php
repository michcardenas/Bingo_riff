<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Reserva;
use App\Models\Bingo;
use Illuminate\Support\Facades\Storage;

class BingoController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validar los datos, permitiendo múltiples imágenes
        $validated = $request->validate([
            'bingo_id'      => 'required|exists:bingos,id',
            'cartones'      => 'required|integer|min:1',
            'nombre'        => 'required|string|max:255',
            'celular'       => 'required|string|max:20',
            'comprobante'   => 'required',       // Asegura que al menos se suba 1 archivo
            'comprobante.*' => 'image|max:2048', // Cada archivo debe ser una imagen de máx 2 MB
        ]);

        // 2. Obtener el precio del cartón desde la base de datos como decimal
        $bingo = Bingo::findOrFail($validated['bingo_id']);
        $precioCarton = (float)$bingo->precio;
        $totalPagar = $validated['cartones'] * $precioCarton;

        // 3. Guardar las imágenes en storage y recolectar sus rutas usando el disco "public"
        $rutasArchivos = [];
        if ($request->hasFile('comprobante')) {
            foreach ($request->file('comprobante') as $file) {
                // Guarda en "storage/app/public/comprobantes" y retorna "comprobantes/archivo.png"
                $ruta = $file->store('comprobantes', 'public');
                $rutasArchivos[] = $ruta;
            }
        }
        // Convertir el array a JSON para almacenarlo en la BD
        $comprobanteStr = json_encode($rutasArchivos);

        // 4. Asignar series automáticamente en orden
        $series = [];
        DB::transaction(function () use ($validated, &$series, $totalPagar, $comprobanteStr, $bingo) {
            // Se obtiene el total de cartones vendidos hasta el momento
            $lastNumber = Reserva::selectRaw('COALESCE(SUM(cantidad), 0) as total')->value('total');

            $cantidad = $validated['cartones'];
            // Generar la serie para cada cartón de la reserva actual
            for ($i = 1; $i <= $cantidad; $i++) {
                $nextNumber = $lastNumber + $i;
                // Formatea el número con 5 dígitos (por ejemplo, "00001")
                $series[] = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
            }

            // 5. Crear la reserva, guardando también las series (como JSON)
            Reserva::create([
                'nombre'             => $validated['nombre'],
                'celular'            => $validated['celular'],
                'cantidad'           => $cantidad,
                'comprobante'        => $comprobanteStr,
                'total'              => $totalPagar,
                'series'             => json_encode($series),
                'estado'             => 'revision',
                'numero_comprobante' => null,
                'bingo_id'           => $bingo->id,
            ]);
        });

        // 6. Redirigir a la vista "reservado" con mensaje de éxito
        return redirect()->route('reservado')
            ->with('success', '¡Reserva realizada correctamente!');
    }
}
