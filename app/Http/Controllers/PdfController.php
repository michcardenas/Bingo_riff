<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PdfController extends Controller
{
    /**
     * Añade una marca de agua al PDF y lo devuelve para su descarga.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addWatermark(Request $request)
    {
        // Ruta del PDF original y destino del PDF final.
        // Puedes adaptar estas rutas según tus necesidades o usarlas desde el request.
        $inputPath = storage_path('app/public/original.pdf');
        $outputPath = storage_path('app/public/watermarked.pdf');

        try {
            // Crear una instancia de Imagick y configurar la resolución para mayor calidad.
            $imagick = new \Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($inputPath);

            // Configurar el objeto para dibujar la marca de agua.
            $draw = new \ImagickDraw();
            $draw->setFillColor('rgba(255, 0, 0, 0.3)'); // Color rojo semitransparente.
            $draw->setFontSize(50);

            // Añadir la marca de agua a cada página.
            foreach ($imagick as $page) {
                // Los parámetros (x, y, ángulo) se pueden ajustar según el diseño deseado.
                $page->annotateImage($draw, 100, 100, 45, "Bingo: Nombre del Evento");
            }

            // Convertir las páginas modificadas a formato PDF.
            $imagick->setImageFormat('pdf');
            $imagick->writeImages($outputPath, true);

            // Retornar el PDF final para descarga.
            return response()->download($outputPath);
        } catch (\Exception $e) {
            // Manejo de errores: devuelve un error 500 con el mensaje correspondiente.
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
