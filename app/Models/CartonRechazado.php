<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartonRechazado extends Model
{
    use HasFactory;
    
    protected $table = 'cartones_rechazados';
    
    protected $fillable = [
        'reserva_id',
        'bingo_id',
        'serie_rechazada',
        'fecha_rechazo',
        'motivo',
        'usuario'
    ];
    
    // Relaciones
    public function reserva()
    {
        return $this->belongsTo(Reserva::class);
    }
    
    public function bingo()
    {
        return $this->belongsTo(Bingo::class);
    }
}