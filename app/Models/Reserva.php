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
        'comprobante_metadata',
        'series',
        'total',
        'numero_comprobante',
        'estado',
        'bingo_id',
        'orden_bingo',
        // Nuevos campos para gestión de ganadores
        'ganador',
        'premio',
        'fecha_ganador',
    ];

    protected $casts = [
        'series' => 'array',
        'comprobante' => 'array',
        'total' => 'decimal:2',
        'eliminado' => 'boolean',
        'ganador' => 'boolean',        // Cast para el campo ganador
        'fecha_ganador' => 'datetime', // Cast para la fecha de ganador
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

    /**
     * Método para verificar si un número de serie específico pertenece a esta reserva
     */
    public function tieneSerie($serie)
    {
        if (!is_array($this->series)) {
            return false;
        }
        
        return in_array($serie, $this->series);
    }

    /**
     * Método para obtener el número de cartón a partir de un número de serie
     */
    public function getNumeroCarton($serie)
    {
        if (!is_array($this->series)) {
            return null;
        }
        
        $indice = array_search($serie, $this->series);
        if ($indice === false) {
            return null;
        }
        
        // Formatea el número de cartón con ceros a la izquierda (formato 000001)
        return str_pad($indice + 1, 6, '0', STR_PAD_LEFT);
    }

    // Relación con ReservaSerie
    public function reservaSeries()
    {
        return $this->hasMany(ReservaSerie::class);
    }
}