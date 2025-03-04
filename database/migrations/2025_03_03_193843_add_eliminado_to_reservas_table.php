<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->boolean('eliminado')->default(false)->after('id'); // Ubica la columna después del ID
        });
    }

    public function down()
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn('eliminado');
        });
    }
};
