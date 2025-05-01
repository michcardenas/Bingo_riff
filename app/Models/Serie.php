<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Serie extends Model
{
    protected $table = 'series';

    protected $fillable = [
        'id_reserva',
        'id_bingo',
        'carton',
        'series',
    ];

    protected $casts = [
        'series' => 'array', // Esto hace que automáticamente se maneje como array en Laravel
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'id_reserva');
    }
}
