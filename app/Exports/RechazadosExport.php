<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RechazadosExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $datos;
    protected $bingo;

    public function __construct($datos, $bingo)
    {
        $this->datos = $datos;
        $this->bingo = $bingo;
    }

    public function array(): array
    {
        return $this->datos;
    }

    public function headings(): array
    {
        return [
            'Nombre',
            'Celular',
            'Cartón',
            'Series',
            'Fecha Reserva',
            'Estado'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para el encabezado
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DC3545'] // Rojo para rechazados
                ]
            ],
        ];
    }

    public function title(): string
    {
        return 'Rechazados - ' . $this->bingo->nombre;
    }
}