<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWhatsappButtonVisibilityToEnlacesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('enlaces', function (Blueprint $table) {
            $table->boolean('mostrar_boton_whatsapp')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('enlaces', function (Blueprint $table) {
            $table->dropColumn('mostrar_boton_whatsapp');
        });
    }
}