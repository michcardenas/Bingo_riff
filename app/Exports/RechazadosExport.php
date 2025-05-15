<?php

namespace App\Exports;

use App\Models\Reserva;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RechazadosExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $bingoId;

    public function __construct($bingoId)
    {
        $this->bingoId = $bingoId;
    }

    public function collection()
    {
        return Reserva::where('bingo_id', $this->bingoId)
            ->where('estado', 'rechazado')
            ->select('id', 'nombre', 'celular', 'cantidad', 'total', 'series', 'estado', 'created_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Celular',
            'Cantidad Cartones',
            'Total',
            'Cartones',
            'Info Series',
            'Estado',
            'Fecha de Registro'
        ];
    }

    /**
     * Mapea los datos de cada fila
     */
    public function map($reserva): array
    {
        // Formatear la columna series (convertir JSON a texto legible)
        $cartonesFormateados = $this->formatearCartones($reserva->series);
        
        // Obtener información adicional de la tabla series
        $infoSeries = $this->obtenerInfoSeries($reserva->series);
        
        // Formatear fecha
        $fechaFormateada = $reserva->created_at ? date('d/m/Y H:i', strtotime($reserva->created_at)) : "N/A";
        
        return [
            $reserva->id,
            $reserva->nombre,
            $reserva->celular,
            $reserva->cantidad,
            $reserva->total,
            $cartonesFormateados,
            $infoSeries,
            $reserva->estado,
            $fechaFormateada
        ];
    }

    /**
     * Formatea los cartones desde JSON a texto legible
     */
    private function formatearCartones($seriesJson)
    {
        if (empty($seriesJson)) {
            return "No hay cartones";
        }
        
        try {
            // Si ya es un array, usarlo directamente
            if (is_array($seriesJson)) {
                return implode(", ", $seriesJson);
            }
            
            // Intentar decodificar JSON
            $series = json_decode($seriesJson, true);
            
            // Si la decodificación fue exitosa
            if (json_last_error() === JSON_ERROR_NONE && is_array($series)) {
                return implode(", ", $series);
            }
            
            // Si falla la decodificación, mostrar el string tal cual
            return $seriesJson;
        } catch (\Exception $e) {
            Log::error("Error al formatear cartones: " . $e->getMessage());
            return "Error: " . substr($e->getMessage(), 0, 50);
        }
    }

    /**
     * Obtiene información de la tabla series para cada cartón
     */
    private function obtenerInfoSeries($seriesJson)
    {
        if (empty($seriesJson)) {
            return "No hay información";
        }
        
        try {
            // Convertir a array si es necesario
            $seriesArray = $seriesJson;
            if (!is_array($seriesJson)) {
                $seriesArray = json_decode($seriesJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return "Error: Formato JSON inválido";
                }
            }
            
            // Información de cada serie
            $infoDetalles = [];
            
            foreach ($seriesArray as $carton) {
                // Buscar el cartón en la tabla series
                $info = DB::table('series')
                    ->where('carton', $carton)
                    ->orWhere('carton', ltrim($carton, '0'))
                    ->first();
                
                if ($info) {
                    // Obtener y formatear la información de la tabla series
                    $seriesData = "";
                    if (isset($info->series)) {
                        // Si la columna series es JSON, decodificarla
                        if (is_string($info->series)) {
                            $decoded = json_decode($info->series, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $seriesData = count($decoded) . " series";
                            } else {
                                $seriesData = $info->series;
                            }
                        } else {
                            $seriesData = $info->series;
                        }
                    }
                    
                    $infoDetalles[] = "$carton: $seriesData";
                } else {
                    $infoDetalles[] = "$carton: No encontrado";
                }
            }
            
            // Si hay demasiadas series, limitar la salida para evitar celdas enormes
            if (count($infoDetalles) > 5) {
                $infoMostrada = array_slice($infoDetalles, 0, 5);
                return implode("\n", $infoMostrada) . "\n(+" . (count($infoDetalles) - 5) . " más)";
            }
            
            return implode("\n", $infoDetalles);
        } catch (\Exception $e) {
            Log::error("Error al obtener info series: " . $e->getMessage());
            return "Error al procesar información de series";
        }
    }
}