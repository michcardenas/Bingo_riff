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
    ];
}