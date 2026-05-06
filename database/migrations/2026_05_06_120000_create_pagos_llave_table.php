<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_llave', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_operacion')->nullable()->index();
            $table->string('nombre_pagador')->nullable();
            $table->string('cuenta_destino', 10)->nullable();
            $table->decimal('monto', 12, 2)->nullable();
            $table->dateTime('fecha_correo')->nullable();
            $table->unsignedBigInteger('reserva_id')->nullable()->index();
            $table->unsignedBigInteger('bingo_id')->nullable()->index();
            $table->string('estado', 50)->default('pendiente'); // aprobado / no_match / error
            $table->string('metodo_match', 50)->nullable();
            $table->text('mensaje')->nullable();
            $table->json('payload_raw')->nullable();
            $table->timestamps();

            $table->foreign('reserva_id')->references('id')->on('reservas')->nullOnDelete();
            $table->foreign('bingo_id')->references('id')->on('bingos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_llave');
    }
};
