<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateBingosTableAddArchivadoEstado extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Si es ENUM, cambiamos el tipo
        DB::statement("ALTER TABLE bingos MODIFY COLUMN estado ENUM('abierto', 'cerrado', 'archivado')");
        
        // Alternativa si es VARCHAR
        // Schema::table('bingos', function (Blueprint $table) {
        //     $table->string('estado', 20)->change();
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Si quieres revertir a ENUM original
        DB::statement("ALTER TABLE bingos MODIFY COLUMN estado ENUM('abierto', 'cerrado')");
        
        // Alternativa si es VARCHAR
        // Schema::table('bingos', function (Blueprint $table) {
        //     $table->string('estado', 10)->change(); // volvemos al tamaño original
        // });
    }
}