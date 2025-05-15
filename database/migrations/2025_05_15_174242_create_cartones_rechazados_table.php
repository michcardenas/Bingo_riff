<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartonesRechazadosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cartones_rechazados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reserva_id');
            $table->unsignedBigInteger('bingo_id');
            $table->string('serie_rechazada');
            $table->timestamp('fecha_rechazo')->useCurrent();
            $table->string('motivo')->nullable();
            $table->string('usuario')->nullable(); // Si quieres registrar quién lo rechazó
            $table->timestamps();
            
            // Índices y claves foráneas
            $table->foreign('reserva_id')->references('id')->on('reservas')->onDelete('cascade');
            $table->foreign('bingo_id')->references('id')->on('bingos')->onDelete('cascade');
            
            // Índices para búsquedas eficientes
            $table->index('serie_rechazada');
            $table->index('fecha_rechazo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cartones_rechazados');
    }
}