<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    use HasFactory;

    protected $table = 'reservas';

    protected $fillable = [
        'nombre',
        'eliminado',
        'celular',
        'cantidad',
        'comprobante',
        'series',
        'total',
        'numero_comprobante',
        'estado',
    ];
}
