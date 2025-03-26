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
        'comprobante_metadata', // Nuevo campo añadido
        'series',
        'total',
        'numero_comprobante',
        'estado',
        'bingo_id',
        'orden_bingo',
    ];

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

    /**
     * Obtiene el ID relativo al bingo (posición en la lista de reservas del bingo)
     */
    public function getOrdenBingoAttribute()
    {
        if (!$this->bingo_id) {
            return null;
        }

        // Obtener todas las reservas de este bingo ordenadas por fecha o ID
        $reservas = Reserva::where('bingo_id', $this->bingo_id)
                          ->where('eliminado', false)
                          ->orderBy('created_at')
                          ->get();

        // Buscar la posición de esta reserva en la lista
        foreach ($reservas as $index => $reserva) {
            if ($reserva->id === $this->id) {
                return $index + 1; // +1 para que empiece en 1 en lugar de 0
            }
        }

        return null;
    }
}