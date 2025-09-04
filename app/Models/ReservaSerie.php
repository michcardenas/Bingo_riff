<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservaSerie extends Model
{
    use HasFactory;

    protected $table = 'reserva_series';

    protected $fillable = [
        'reserva_id',
        'bingo_id',
        'serie',
    ];

    // Relación con Reserva
    public function reserva()
    {
        return $this->belongsTo(Reserva::class);
    }

    // Relación con Bingo
    public function bingo()
    {
        return $this->belongsTo(Bingo::class);
    }
}
