<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrdenBingoToReservasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reservas', function (Blueprint $table) {
            // Añadimos un campo para el orden específico por bingo
            $table->integer('orden_bingo')->after('bingo_id')->nullable();
            
            // Índice compuesto para optimizar consultas
            $table->index(['bingo_id', 'orden_bingo']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropIndex(['bingo_id', 'orden_bingo']);
            $table->dropColumn('orden_bingo');
        });
    }
}