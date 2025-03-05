<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enlace extends Model
{
    use HasFactory;

    protected $table = 'enlaces';
    
    protected $fillable = [
        'numero_contacto',
        'video_1',
        'video_2',
        'grupo_whatsapp',
    ];
}