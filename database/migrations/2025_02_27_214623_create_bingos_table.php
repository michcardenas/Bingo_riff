<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bingos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');          // Nombre del bingo
            $table->date('fecha');             // Fecha del bingo
            $table->decimal('precio', 8, 2);   // Precio del cartón
            $table->enum('estado', ['abierto', 'cerrado'])->default('abierto');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bingos');
    }
};
