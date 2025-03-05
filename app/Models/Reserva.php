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
        'bingo_id', // Added bingo_id to fillable attributes
    ];

    // Optional: Add casts to handle array-type columns and proper decimal handling
    protected $casts = [
        'series' => 'array',
        'comprobante' => 'array',
        'total' => 'decimal:2',
        'eliminado' => 'boolean',
    ];

    /**
     * Get the bingo that this reservation belongs to
     */
    public function bingo()
    {
        return $this->belongsTo(Bingo::class);
    }
}