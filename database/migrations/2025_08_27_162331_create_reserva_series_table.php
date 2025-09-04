<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reserva_series', function (Blueprint $table) {
            $table->id();

            // Relación con reserva
            $table->foreignId('reserva_id')
                  ->constrained('reservas')
                  ->onDelete('cascade');

            // Relación con bingo
            $table->foreignId('bingo_id')
                  ->constrained('bingos')
                  ->onDelete('cascade');

            // Serie (ejemplo: 001106, 001107, etc.)
            $table->string('serie');

            $table->timestamps();

            // Restricción única para evitar duplicados dentro de un bingo
            $table->unique(['bingo_id', 'serie'], 'uniq_bingo_serie');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reserva_series');
    }
};
