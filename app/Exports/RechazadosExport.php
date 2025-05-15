<?php

namespace App\Exports;

use App\Models\CartonRechazado;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RechazadosExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected $bingoId;

    public function __construct($bingoId)
    {
        $this->bingoId = $bingoId;
        Log::info("RechazadosExport inicializado con bingoId: " . $bingoId);
        
        // Verificar si la tabla existe
        $tableExists = DB::select("SHOW TABLES LIKE 'cartones_rechazados'");
        Log::info("¿Existe la tabla cartones_rechazados?: " . (count($tableExists) > 0 ? 'Sí' : 'No'));
        
        // Verificar la estructura de la tabla
        if (count($tableExists) > 0) {
            $columns = DB::select("DESCRIBE cartones_rechazados");
            $columnNames = array_map(function($col) {
                return $col->Field;
            }, $columns);
            Log::info("Columnas en cartones_rechazados: " . implode(", ", $columnNames));
        }
        
        // Verificar todos los IDs de bingo en la tabla
        $bingoIds = DB::table('cartones_rechazados')->distinct('bingo_id')->pluck('bingo_id');
        Log::info("IDs de bingo en cartones_rechazados: " . implode(", ", $bingoIds->toArray()));
    }

    /**
     * Define la consulta para obtener los datos
     */
    public function query()
    {
        // Verificar si hay cartones rechazados para cualquier bingo
        $totalCartones = DB::table('cartones_rechazados')->count();
        Log::info("Total de cartones rechazados en la tabla: " . $totalCartones);
        
        // Verificar cuántos cartones hay con este bingoId específico
        $cartones = DB::table('cartones_rechazados')
            ->where('bingo_id', $this->bingoId)
            ->count();
        Log::info("Cartones rechazados para el bingo {$this->bingoId}: {$cartones}");
        
        // Hacer una consulta más específica para ver si hay algún problema
        $cartonesDetalle = DB::select("SELECT id, bingo_id FROM cartones_rechazados WHERE bingo_id = ?", [$this->bingoId]);
        Log::info("Detalles de cartones encontrados: " . json_encode($cartonesDetalle));
        
        // Verificar si el bingo existe
        $bingo = DB::table('bingos')->where('id', $this->bingoId)->first();
        Log::info("¿Existe el bingo {$this->bingoId}?: " . ($bingo ? 'Sí' : 'No'));
        
        return CartonRechazado::query()
            ->where('bingo_id', $this->bingoId)
            ->with(['reserva', 'bingo'])
            ->orderBy('fecha_rechazo', 'desc');
    }

    /**
     * Define los encabezados de las columnas
     */
    public function headings(): array
    {
        return [
            'ID',
            'Cartón Rechazado',
            'Nombre Titular',
            'Celular',
            'Cantidad',
            'Series Reserva',
            'Series Tabla Series',
            'Total',
            'Estado',
            'Nombre del Bingo',
            'Fecha Rechazo',
            'Motivo',
            'Usuario que Rechazó',
            'Fecha de Registro'
        ];
    }

    /**
     * Mapea los datos de cada fila
     */
    public function map($cartonRechazado): array
    {
        Log::info("Mapeando cartón rechazado ID: " . $cartonRechazado->id);
        
        // Valores por defecto
        $nombreTitular = "N/A";
        $telefono = "N/A";
        $cantidad = 0;
        $seriesReserva = "N/A";
        $total = "0.00";
        $estado = "N/A";
        
        // Obtener datos de la reserva si existe
        if ($cartonRechazado->reserva) {
            $reserva = $cartonRechazado->reserva;
            Log::info("Reserva encontrada ID: " . $reserva->id);
            
            $nombreTitular = $reserva->nombre ?? "N/A";
            $telefono = $reserva->celular ?? "N/A";
            $cantidad = $reserva->cantidad ?? 0;
            $total = $reserva->total ?? "0.00";
            $estado = $reserva->estado ?? "N/A";
            
            // Obtener las series de la reserva
            $seriesReserva = $this->formatearSeriesJson($reserva->series);
            
            // Si el nombre es igual al nombre del bingo, intentar buscar el nombre real
            if (!empty($reserva->celular) && 
                (!empty($reserva->nombre) && 
                 $reserva->nombre == $cartonRechazado->bingo->nombre)) {
                
                // Buscar otra reserva del mismo celular con nombre diferente
                $otraReserva = DB::table('reservas')
                    ->where('celular', $reserva->celular)
                    ->where('nombre', '!=', $cartonRechazado->bingo->nombre)
                    ->whereNotNull('nombre')
                    ->where('nombre', '!=', '')
                    ->orderBy('id', 'desc')
                    ->first();
                
                if ($otraReserva && !empty($otraReserva->nombre)) {
                    $nombreTitular = $otraReserva->nombre;
                    Log::info("Nombre real encontrado para el celular {$telefono}: {$nombreTitular}");
                }
            }
        } else {
            Log::warning("Reserva no encontrada para el cartón rechazado ID: " . $cartonRechazado->id);
        }
        
        // Obtener información de la tabla series para el cartón rechazado
        $seriesTabla = $this->obtenerSeriesTabla($cartonRechazado->serie_rechazada);
        
        return [
            $cartonRechazado->id,
            $cartonRechazado->serie_rechazada,
            $nombreTitular,
            $telefono,
            $cantidad,
            $seriesReserva,
            $seriesTabla,
            $total,
            $estado,
            $cartonRechazado->bingo->nombre ?? "Bingo ID: ".$cartonRechazado->bingo_id,
            $cartonRechazado->fecha_rechazo ? date('d/m/Y H:i', strtotime($cartonRechazado->fecha_rechazo)) : "N/A",
            $cartonRechazado->motivo ?? "No especificado",
            $cartonRechazado->usuario ?? "Sistema",
            $cartonRechazado->created_at ? date('d/m/Y H:i', strtotime($cartonRechazado->created_at)) : "N/A"
        ];
    }

    /**
     * Formatea las series almacenadas como JSON
     */
    private function formatearSeriesJson($jsonSeries)
    {
        if (empty($jsonSeries)) {
            return "No hay series";
        }
        
        try {
            // Si ya es un array, usarlo directamente
            if (is_array($jsonSeries)) {
                return implode(", ", $jsonSeries);
            }
            
            // Intentar decodificar JSON
            $series = json_decode($jsonSeries, true);
            
            // Si la decodificación fue exitosa
            if (json_last_error() === JSON_ERROR_NONE && is_array($series)) {
                return implode(", ", $series);
            }
            
            // Si falla la decodificación, mostrar el string tal cual
            return $jsonSeries;
        } catch (\Exception $e) {
            Log::error("Error al formatear series JSON: " . $e->getMessage());
            return "Error: " . substr($e->getMessage(), 0, 50) . "...";
        }
    }

    /**
     * Obtiene los datos de la columna series de la tabla series
     */
    private function obtenerSeriesTabla($numeroSerie)
    {
        try {
            // Buscar el cartón en la tabla series
            $serieBD = DB::table('series')
                ->where('carton', $numeroSerie)
                ->orWhere('carton', ltrim($numeroSerie, '0'))
                ->first();
            
            if (!$serieBD) {
                return "No encontrado";
            }
            
            // Loguear los datos encontrados
            Log::info("Serie encontrada para el cartón {$numeroSerie}: " . json_encode($serieBD));
            
            // Si existe la columna series en la tabla, formatearla
            if (isset($serieBD->series)) {
                return $this->formatearSeriesJson($serieBD->series);
            }
            
            return "Columna 'series' no encontrada";
        } catch (\Exception $e) {
            Log::error("Error al obtener series: " . $e->getMessage());
            return "Error: " . substr($e->getMessage(), 0, 50) . "...";
        }
    }

    /**
     * Aplica estilos a la hoja de Excel
     */
    public function styles(Worksheet $sheet)
    {
        // Estilo para la fila de encabezados
        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
        ]);
        
        // Agregar bordes a todas las celdas con datos
        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 1) {
            $sheet->getStyle('A1:N'.$lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);
        }
        
        // Centrar algunas columnas
        $sheet->getStyle('A:A')->getAlignment()->setHorizontal('center'); // ID
        $sheet->getStyle('B:B')->getAlignment()->setHorizontal('center'); // Cartón
        $sheet->getStyle('D:D')->getAlignment()->setHorizontal('center'); // Celular
        $sheet->getStyle('E:E')->getAlignment()->setHorizontal('center'); // Cantidad
        $sheet->getStyle('H:H')->getAlignment()->setHorizontal('center'); // Total
        $sheet->getStyle('I:I')->getAlignment()->setHorizontal('center'); // Estado
        $sheet->getStyle('K:K')->getAlignment()->setHorizontal('center'); // Fecha rechazo
        $sheet->getStyle('N:N')->getAlignment()->setHorizontal('center'); // Fecha registro
        
        return $sheet;
    }

    /**
     * Personaliza el nombre de la hoja
     */
    public function title(): string
    {
        try {
            $bingo = DB::table('bingos')->where('id', $this->bingoId)->first();
            $bingoName = $bingo ? $bingo->nombre : "Bingo ".$this->bingoId;
            
            // Limitar longitud a 31 caracteres (límite de Excel)
            if (mb_strlen($bingoName) > 25) {
                $bingoName = mb_substr($bingoName, 0, 25).'...';
            }
            
            return "Rechazados - ".$bingoName;
        } catch (\Exception $e) {
            Log::error("Error al obtener título: " . $e->getMessage());
            return "Cartones Rechazados";
        }
    }
}