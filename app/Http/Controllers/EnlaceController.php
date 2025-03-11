<?php

namespace App\Http\Controllers;

use App\Models\Enlace;
use Illuminate\Http\Request;

class EnlaceController extends Controller
{
    /**
     * Mostrar el formulario para editar los enlaces
     */
    public function edit()
    {
        // Obtenemos el primer registro o creamos uno nuevo si no existe
        $enlaces = Enlace::first() ?? new Enlace();
        
        // Ruta ajustada a la ubicación real de la vista
        return view('admin.enlaces', compact('enlaces'));
    }

    /**
     * Actualizar los enlaces en la base de datos
     */
    public function update(Request $request)
    {
        // Validar los datos
        $validated = $request->validate([
            'numero_contacto' => 'nullable|string|max:20',
            'telefono_atencion' => 'nullable|string|max:20',
            'video_1' => 'nullable|url|max:255',
            'video_2' => 'nullable|url|max:255',
            'grupo_whatsapp' => 'nullable|url|max:255',
        ]);

        // Formatear el número de WhatsApp con prefijo +57 si está presente
        if (!empty($validated['telefono_atencion']) && !str_starts_with($validated['telefono_atencion'], '+57')) {
            // Eliminar cualquier espacio o carácter no numérico
            $numero = preg_replace('/[^0-9]/', '', $validated['telefono_atencion']);
            // Añadir el prefijo +57
            $validated['telefono_atencion'] = '+57' . $numero;
        }

        // Actualizar o crear el registro de enlaces
        Enlace::updateOrCreate(
            ['id' => 1], // Criterio para buscar el registro
            $validated   // Datos a actualizar
        );

        return redirect()->route('enlaces.edit')
            ->with('success', 'Enlaces actualizados correctamente');
    }

    /**
     * Obtener los enlaces para usar en otras vistas
     */
    public static function getEnlaces()
    {
        return Enlace::first() ?? new Enlace();
    }
}