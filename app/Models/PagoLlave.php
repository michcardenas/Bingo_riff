<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoLlave extends Model
{
    protected $table = 'pagos_llave';

    protected $fillable = [
        'codigo_operacion',
        'nombre_pagador',
        'cuenta_destino',
        'monto',
        'fecha_correo',
        'reserva_id',
        'bingo_id',
        'estado',
        'metodo_match',
        'mensaje',
        'payload_raw',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_correo' => 'datetime',
        'payload_raw' => 'array',
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class);
    }

    public function bingo()
    {
        return $this->belongsTo(Bingo::class);
    }
}
