<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reserva;
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
        return view('buscarcartones');
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
        $reservas = Reserva::where('celular', $telefono)
            ->where('eliminado', 0)
            ->get();

        Log::info('Reservas encontradas: ' . $reservas->count());

        // Preparar los datos de cartones a partir de las reservas
        $cartones = collect();

        foreach ($reservas as $reserva) {
            Log::info('Procesando reserva ID: ' . $reserva->id . ', Series: ' . $reserva->series);

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
                            'estado' => $reserva->estado,
                            'nombre' => $reserva->nombre,
                            'fecha_creacion' => $reserva->created_at->format('d/m/Y'),
                            'tipo_sorteo' => 'Principal',
                            'id_reserva' => $reserva->id,
                            'bingo_nombre' => $bingoNombre,
                            'bingo_id' => $bingoId
                        ]);
                        Log::info('Cartón agregado: ' . $serie . ' para bingo: ' . $bingoNombre);
                    }
                } else {
                    Log::warning('El formato de series no es un array para la reserva ID: ' . $reserva->id);
                }
            } else {
                Log::info('No hay series para la reserva ID: ' . $reserva->id);
            }
        }

        Log::info('Total de cartones encontrados: ' . $cartones->count());

        return view('buscarcartones', [
            'cartones' => $cartones
        ]);
    }

  
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
    
        // 🔹 **Corrección: Ajustar la nueva ruta de la carpeta**
        $rutaCompleta = storage_path("app/private/public/TablasbingoRIFFY/{$numeroSinCeros}.pdf");
    
        // 🔹 **Verificar si el archivo existe**
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
    
    /**
     * Agrega una página extra al PDF original (como segunda página) que contiene la marca de agua.
     *
     * Se importa la primera página del PDF original, luego se inserta una nueva página con
     * la información de marca de agua (por ejemplo, el nombre del bingo y la fecha) y, si
     * existen más páginas en el documento original, se agregan a continuación.
     *
     * @param string $inputPath Ruta al PDF original.
     * @param string $outputPath Ruta donde se guardará el nuevo PDF.
     * @param string $bingoName Nombre del bingo (para la marca de agua).
     * @param string|null $bingoDate Fecha del bingo (opcional).
     * @return bool
     */
    private function addWatermarkPageToPdf($inputPath, $outputPath, $bingoName, $bingoDate = null)
    {
        try {
            // Crear una instancia de FPDI con TCPDF.
            $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Abrir el PDF original y obtener el número de páginas.
            $pageCount = $pdf->setSourceFile($inputPath);
            if ($pageCount < 1) {
                throw new \Exception("El PDF original no tiene páginas.");
            }

            // --- Primera página: se importa la primera página del PDF original.
            $templateId = $pdf->importPage(1);
            $size = $pdf->getTemplateSize($templateId);
            $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            // --- Segunda página: se inserta una nueva página con la marca de agua.
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 20);
            $pdf->SetTextColor(150, 150, 150);
            $watermarkText = "BINGO: " . strtoupper($bingoName);
            if ($bingoDate) {
                $watermarkText .= " - FECHA: " . $bingoDate;
            }
            // Centramos el texto en la página (ajusta la posición según lo necesites)
            $pdf->SetXY(10, 40);
            $pdf->Cell(0, 10, $watermarkText, 0, 1, 'C');

            // --- Resto de páginas: se agregan las páginas restantes del PDF original (si las hay).
            if ($pageCount > 1) {
                for ($pageNo = 2; $pageNo <= $pageCount; $pageNo++) {
                    $templateId = $pdf->importPage($pageNo);
                    $size = $pdf->getTemplateSize($templateId);
                    $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                    $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                    $pdf->useTemplate($templateId);
                }
            }

            // Guardar el nuevo PDF con la página adicional.
            $pdf->Output($outputPath, 'F');
            return true;
        } catch (\Exception $e) {
            Log::error("Error al agregar página de marca de agua: " . $e->getMessage());
            return false;
        }
    }
}
