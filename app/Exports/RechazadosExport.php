<?php

namespace App\Exports;

use App\Models\CartonRechazado;
use App\Models\Serie;
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
            'Serie/Cartón',
            'Datos Serie', // Nueva columna para mostrar datos de la tabla series
            'Nombre Titular',
            'Teléfono',
            'Nombre del Bingo',
            'Fecha Rechazo',
            'Motivo',
            'Usuario que Rechazó',
            'Fecha Creación'
        ];
    }

    /**
     * Mapea los datos de cada fila
     */
    public function map($cartonRechazado): array
    {
        // Encontrar el nombre del titular de la reserva
        $nombreTitular = "N/A";
        $telefono = "N/A";
        
        if ($cartonRechazado->reserva) {
            $nombreTitular = $cartonRechazado->reserva->nombre ?? "N/A";
            $telefono = $cartonRechazado->reserva->celular ?? "N/A";
            
            // Si el nombre es igual al nombre del bingo, intentar buscar el nombre real
            if (!empty($cartonRechazado->reserva->celular) && 
                (!empty($cartonRechazado->reserva->nombre) && 
                 $cartonRechazado->reserva->nombre == $cartonRechazado->bingo->nombre)) {
                
                // Buscar otra reserva del mismo celular con nombre diferente
                $otraReserva = DB::table('reservas')
                    ->where('celular', $cartonRechazado->reserva->celular)
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
        
        // Obtener información de la serie desde la tabla series
        $datosSerie = $this->obtenerDatosSerie($cartonRechazado->serie_rechazada);
        
        return [
            $cartonRechazado->id,
            $cartonRechazado->serie_rechazada,
            $datosSerie, // Nueva columna con datos de la serie
            $nombreTitular,
            $telefono,
            $cartonRechazado->bingo->nombre ?? "Bingo ID: ".$cartonRechazado->bingo_id,
            $cartonRechazado->fecha_rechazo ? date('d/m/Y H:i', strtotime($cartonRechazado->fecha_rechazo)) : "N/A",
            $cartonRechazado->motivo ?? "No especificado",
            $cartonRechazado->usuario ?? "Sistema",
            $cartonRechazado->created_at ? date('d/m/Y H:i', strtotime($cartonRechazado->created_at)) : "N/A"
        ];
    }

    /**
     * Obtiene los datos de la serie desde la tabla series
     */
    private function obtenerDatosSerie($numeroSerie)
    {
        // Eliminar ceros a la izquierda para la búsqueda
        $numeroSinCeros = ltrim($numeroSerie, '0');
        
        try {
            // Buscar la serie en la tabla series
            $serie = DB::table('series')
                ->where('carton', $numeroSerie)
                ->orWhere('carton', $numeroSinCeros)
                ->first();
            
            if ($serie) {
                // Devuelve la información de la columna 'series' de la tabla series
                // Ajusta esto según la estructura real de tu tabla
                return $serie->series ?? "Serie encontrada: ID #".$serie->id;
            } else {
                return "Serie no encontrada en tabla series";
            }
        } catch (\Exception $e) {
            return "Error al buscar serie: " . $e->getMessage();
        }
    }

    /**
     * Aplica estilos a la hoja de Excel
     */
    public function styles(Worksheet $sheet)
    {
        // Estilo para la fila de encabezados
        $sheet->getStyle('A1:J1')->applyFromArray([
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
        $sheet->getStyle('A1:J'.$sheet->getHighestRow())->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
        
        // Centrar algunas columnas
        $sheet->getStyle('A:A')->getAlignment()->setHorizontal('center'); // ID
        $sheet->getStyle('B:B')->getAlignment()->setHorizontal('center'); // Serie
        $sheet->getStyle('E:E')->getAlignment()->setHorizontal('center'); // Teléfono
        $sheet->getStyle('G:G')->getAlignment()->setHorizontal('center'); // Fecha rechazo
        $sheet->getStyle('J:J')->getAlignment()->setHorizontal('center'); // Fecha creación
        
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
            if (mb_strlen($bingoName) > 28) {
                $bingoName = mb_substr($bingoName, 0, 28).'...';
            }
            
            return "Rechazados - ".$bingoName;
        } catch (\Exception $e) {
            return "Cartones Rechazados";
        }
    }
}