<?php

namespace App\Exports;

use App\Models\Reserva;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RechazadosExport implements WithMultipleSheets
{
    protected $bingoId;

    public function __construct($bingoId)
    {
        $this->bingoId = $bingoId;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [
            new ReservasRechazadasSheet($this->bingoId),
            new CartonesRechazadosSheet($this->bingoId),
        ];

        return $sheets;
    }
}

// Hoja 1: Reservas Rechazadas
class ReservasRechazadasSheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithTitle, WithStyles
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

    public function title(): string
    {
        return 'Reservas Rechazadas';
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Celular',
            'Cantidad Cartones',
            'Total',
            'Números de Cartón',
            'Series por Cartón',
            'Estado',
            'Fecha de Registro'
        ];
    }

    public function map($reserva): array
    {
        // Separar números de cartón y series
        $numerosCartones = $this->formatearNumerosCartones($reserva->series);
        $seriesPorCarton = $this->obtenerInfoSeries($reserva->series);
        
        // Formatear fecha
        $fechaFormateada = $reserva->created_at ? date('d/m/Y H:i', strtotime($reserva->created_at)) : "N/A";
        
        return [
            $reserva->id,
            $reserva->nombre,
            $reserva->celular,
            $reserva->cantidad,
            $reserva->total,
            $numerosCartones,      // Solo los números de cartón
            $seriesPorCarton,      // Las series detalladas
            $reserva->estado,
            $fechaFormateada
        ];
    }

    private function formatearNumerosCartones($seriesJson)
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
            Log::error("Error al formatear números de cartones: " . $e->getMessage());
            return "Error: " . substr($e->getMessage(), 0, 50);
        }
    }

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
            
            // Recolectar todas las series sin prefijos
            $todasLasSeries = [];
            
            foreach ($seriesArray as $carton) {
                // Buscar el cartón en la tabla series
                $info = DB::table('series')
                    ->where('carton', $carton)
                    ->orWhere('carton', ltrim($carton, '0'))
                    ->first();
                
                if ($info && isset($info->series)) {
                    // Obtener y formatear las series de la tabla series
                    $seriesInfo = $info->series;
                    
                    // Si es JSON, decodificarlo y agregarlo al array
                    if (is_string($seriesInfo)) {
                        $seriesData = json_decode($seriesInfo, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($seriesData)) {
                            $todasLasSeries = array_merge($todasLasSeries, $seriesData);
                        } else {
                            $todasLasSeries[] = $seriesInfo;
                        }
                    } else {
                        $todasLasSeries[] = $seriesInfo;
                    }
                }
            }
            
            // Devolver todas las series separadas por comas
            return !empty($todasLasSeries) ? implode(", ", $todasLasSeries) : "No encontrado";
        } catch (\Exception $e) {
            Log::error("Error al obtener info series: " . $e->getMessage());
            return "Error al procesar información de series";
        }
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para la fila de encabezados
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
            ],
            // Ajustar la altura de filas para mostrar múltiples líneas
            'A' => ['alignment' => ['wrapText' => true]],
            'F' => ['alignment' => ['wrapText' => true]], // Números de cartón
            'G' => ['alignment' => ['wrapText' => true]], // Series por cartón
        ];
    }
}

// Hoja 2: Cartones Rechazados Individuales
class CartonesRechazadosSheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithTitle, WithStyles
{
    protected $bingoId;

    public function __construct($bingoId)
    {
        $this->bingoId = $bingoId;
    }

    public function collection()
    {
        return DB::table('cartones_rechazados')
            ->where('bingo_id', $this->bingoId)
            ->orderBy('fecha_rechazo', 'desc')
            ->get();
    }

    public function title(): string
    {
        return 'Cartones Individuales';
    }

    public function headings(): array
    {
        return [
            'ID',
            'Número de Cartón',
            'Series del Cartón',
            'Nombre Titular',
            'Celular',
            'ID Reserva',
            'Fecha Rechazo',
            'Motivo',
            'Usuario',
            'Fecha Registro'
        ];
    }

    public function map($carton): array
    {
        // Obtener información de la reserva
        $nombreTitular = "N/A";
        $celular = "N/A";
        
        if ($carton->reserva_id) {
            $reserva = DB::table('reservas')->find($carton->reserva_id);
            if ($reserva) {
                $nombreTitular = $reserva->nombre ?? "N/A";
                $celular = $reserva->celular ?? "N/A";
                
                // Si el nombre es igual al nombre del bingo, buscar el nombre real
                if (!empty($reserva->celular) && 
                   (!empty($reserva->nombre) && $reserva->bingo_id)) {
                    
                    $bingo = DB::table('bingos')->find($reserva->bingo_id);
                    if ($bingo && $reserva->nombre == $bingo->nombre) {
                        // Buscar otra reserva con el mismo celular
                        $otraReserva = DB::table('reservas')
                            ->where('celular', $reserva->celular)
                            ->where('nombre', '!=', $bingo->nombre)
                            ->whereNotNull('nombre')
                            ->where('nombre', '!=', '')
                            ->orderBy('id', 'desc')
                            ->first();
                        
                        if ($otraReserva && !empty($otraReserva->nombre)) {
                            $nombreTitular = $otraReserva->nombre;
                        }
                    }
                }
            }
        }
        
        // Obtener información de series para este cartón específico
        $seriesInfo = $this->obtenerInfoSeries($carton->serie_rechazada);
        
        // Formatear fechas
        $fechaRechazo = $carton->fecha_rechazo ? date('d/m/Y H:i', strtotime($carton->fecha_rechazo)) : "N/A";
        $fechaRegistro = $carton->created_at ? date('d/m/Y H:i', strtotime($carton->created_at)) : "N/A";
        
        return [
            $carton->id,
            $carton->serie_rechazada,  // Número del cartón
            $seriesInfo,               // Series del cartón
            $nombreTitular,
            $celular,
            $carton->reserva_id,
            $fechaRechazo,
            $carton->motivo ?? "No especificado",
            $carton->usuario ?? "Sistema",
            $fechaRegistro
        ];
    }

    private function obtenerInfoSeries($numeroCarton)
    {
        try {
            // Buscar el cartón en la tabla series
            $info = DB::table('series')
                ->where('carton', $numeroCarton)
                ->orWhere('carton', ltrim($numeroCarton, '0'))
                ->first();
            
            if ($info && isset($info->series)) {
                // Obtener y formatear las series de la tabla series
                $seriesInfo = $info->series;
                
                // Si es JSON, decodificarlo y formatearlo como una lista separada por comas
                if (is_string($seriesInfo)) {
                    $seriesData = json_decode($seriesInfo, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($seriesData)) {
                        return implode(", ", $seriesData);
                    } else {
                        return $seriesInfo;
                    }
                } else {
                    return $seriesInfo;
                }
            } else {
                return "No encontrado";
            }
        } catch (\Exception $e) {
            Log::error("Error al obtener info series para cartón {$numeroCarton}: " . $e->getMessage());
            return "Error al procesar información";
        }
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para la fila de encabezados
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
            ],
            // Ajustar la altura de filas para mostrar múltiples líneas
            'C' => ['alignment' => ['wrapText' => true]], // Series del cartón
        ];
    }
}