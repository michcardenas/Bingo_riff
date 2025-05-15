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

class RechazadosExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    protected $bingoId;

    public function __construct($bingoId)
    {
        $this->bingoId = $bingoId;
    }

    /**
     * Define la consulta para obtener los datos
     */
    public function query()
    {
        return CartonRechazado::query()
            ->where('bingo_id', $this->bingoId)
            ->with(['reserva', 'bingo']) // Cargar relaciones para acceder a sus datos
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
            
            $nombreTitular = $reserva->nombre ?? "N/A";
            $telefono = $reserva->celular ?? "N/A";
            $cantidad = $reserva->cantidad ?? 0;
            $total = $reserva->total ?? "0.00";
            $estado = $reserva->estado ?? "N/A";
            
            // Obtener las series de la reserva (columna series de la tabla reservas)
            $seriesReserva = $this->formatearSeriesReserva($reserva->series);
            
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
                }
            }
        }
        
        // Obtener información de la tabla series para el cartón rechazado
        $seriesTabla = $this->obtenerSeriesTabla($cartonRechazado->serie_rechazada);
        
        return [
            $cartonRechazado->id,
            $cartonRechazado->serie_rechazada,
            $nombreTitular,
            $telefono,
            $cantidad,
            $seriesReserva, // Series de la tabla reservas
            $seriesTabla,   // Series de la tabla series
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
     * Formatea las series de la reserva para mostrarlas legiblemente
     */
    private function formatearSeriesReserva($series)
    {
        if (empty($series)) {
            return "No hay series";
        }
        
        // Si es JSON, decodifica
        if (is_string($series)) {
            $decoded = json_decode($series, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $series = $decoded;
            } else {
                // Si viene mal formado, intenta dividirlo manualmente
                $series = preg_split('/[",\s]+/', $series);
            }
        }
        
        // Si sigue siendo un string después de intentar decodificar
        if (is_string($series)) {
            return $series;
        }
        
        // Si es un array, formatearlo para mostrarlo legiblemente
        if (is_array($series)) {
            // Filtrar valores vacíos y valores duplicados
            $series = array_filter(array_unique($series));
            return implode(", ", $series);
        }
        
        return "Formato no reconocido";
    }

    /**
     * Obtiene los datos de la columna series de la tabla series
     */
    private function obtenerSeriesTabla($numeroSerie)
    {
        // Eliminar ceros a la izquierda para la búsqueda
        $numeroSinCeros = ltrim($numeroSerie, '0');
        
        try {
            // Buscar el cartón en la tabla series
            $serieBD = DB::table('series')
                ->where('carton', $numeroSerie)
                ->orWhere('carton', $numeroSinCeros)
                ->first();
            
            if ($serieBD) {
                // Devolver el contenido de la columna "series" de la tabla series
                return $serieBD->series ?? "N/A";
            } else {
                return "No encontrado";
            }
        } catch (\Exception $e) {
            return "Error: " . $e->getMessage();
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
        $sheet->getStyle('A1:N'.$sheet->getHighestRow())->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
        
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
        // Intentar obtener el nombre del bingo para usarlo como título
        try {
            $bingo = DB::table('bingos')->where('id', $this->bingoId)->first();
            $bingoName = $bingo ? $bingo->nombre : "Bingo ".$this->bingoId;
            
            // Limitar longitud a 31 caracteres (límite de Excel)
            if (mb_strlen($bingoName) > 25) {
                $bingoName = mb_substr($bingoName, 0, 25).'...';
            }
            
            return "Rechazados - ".$bingoName;
        } catch (\Exception $e) {
            return "Cartones Rechazados";
        }
    }
}