<?php

namespace App\Exports;

use App\Models\Reserva;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RechazadosExport implements FromCollection, WithHeadings
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
            ->select('id', 'nombre', 'celular', 'cantidad', 'total','series', 'estado', 'created_at')
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
            'Series',
            'Estado',
            'Fecha de Registro'
        ];
    }
}
