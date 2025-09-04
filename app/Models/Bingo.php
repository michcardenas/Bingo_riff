<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bingo extends Model
{
    use HasFactory;

    protected $table = 'bingos';

    protected $fillable = [
        'nombre',
        'fecha',
        'precio',
        'estado',
        'visible',
        'reabierto',
    ];

    /**
     * Obtiene las reservas asociadas a este bingo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reservas()
    {
        return $this->hasMany(Reserva::class);
    }

    public function reservaSeries()
    {
        return $this->hasMany(ReservaSerie::class);
    }
}