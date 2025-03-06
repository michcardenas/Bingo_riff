<?php

namespace App\Services;

use setasign\Fpdi\Fpdi;

class PdfWatermarkService
{
    /**
     * Añade una marca de agua a un PDF con el nombre del bingo
     * 
     * @param string $inputPath Ruta al archivo PDF original
     * @param string $outputPath Ruta donde guardar el PDF con marca de agua
     * @param string $bingoName Nombre del bingo para la marca de agua
     * @param string $bingoDate Fecha del bingo (opcional)
     * @return bool
     */
    public function addWatermark($inputPath, $outputPath, $bingoName, $bingoDate = null)
    {
        // Registrar inicio del proceso
        \Log::info('Iniciando proceso de marca de agua. Input: ' . $inputPath . ', Output: ' . $outputPath);
        
        // Crear instancia de FPDI
        $pdf = new Fpdi();
        
        try {
            // Obtener el número de páginas
            \Log::info('Leyendo archivo PDF original');
            $pageCount = $pdf->setSourceFile($inputPath);
            \Log::info('Número de páginas: ' . $pageCount);
            
            // Para cada página del PDF original
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                \Log::info('Procesando página ' . $pageNo);
                
                // Importar página
                $templateId = $pdf->importPage($pageNo);
                
                // Obtener tamaño de la página
                $size = $pdf->getTemplateSize($templateId);
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                
                // Añadir página con la orientación correcta
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                
                // Usar la página importada
                $pdf->useTemplate($templateId);
                
                // Configurar texto de marca de agua
                $watermarkText = "BINGO: " . strtoupper($bingoName);
                if ($bingoDate) {
                    $watermarkText .= " - FECHA: " . $bingoDate;
                }
                \Log::info('Texto de marca de agua: ' . $watermarkText);
                
                // Configuración para marca de agua
                $pdf->SetFont('Helvetica', 'B', 16);
                $pdf->SetTextColor(220, 120, 50); // Naranja similar al de la web
                
                // Intentar usar SetAlpha si está disponible (para transparencia)
                if (method_exists($pdf, 'SetAlpha')) {
                    $pdf->SetAlpha(0.5); // Transparencia
                }
                
                // Posicionar en la parte superior
                $pdf->SetXY(10, 10);
                $pdf->Write(0, $watermarkText);
                
                // Posicionar también en la parte inferior
                $pdf->SetXY(10, $size['height'] - 15);
                $pdf->Write(0, $watermarkText);
            }
            
            // Guardar el archivo modificado
            \Log::info('Guardando PDF con marca de agua en: ' . $outputPath);
            $pdf->Output('F', $outputPath);
            
            \Log::info('Proceso de marca de agua completado con éxito');
            return true;
        } catch (\Exception $e) {
            // Registrar el error detalladamente
            \Log::error('Error al añadir marca de agua: ' . $e->getMessage());
            \Log::error('Archivo de entrada: ' . $inputPath);
            \Log::error('Archivo de salida: ' . $outputPath);
            \Log::error('Trace: ' . $e->getTraceAsString());
            return false;
        }
    }
}