<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enlace extends Model
{
    use HasFactory;

    protected $table = 'enlaces';
    
    protected $fillable = [
        'telefono_atencion',
        'numero_contacto',
        'video_1',
        'video_2',
        'grupo_whatsapp',
        'numero_nequi',
        'numero_daviplata',
        'numero_transfiya',
        'mostrar_boton_whatsapp'
    ];
}