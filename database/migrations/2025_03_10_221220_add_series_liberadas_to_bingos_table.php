<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSeriesLiberadasToBingosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bingos', function (Blueprint $table) {
            $table->text('series_liberadas')->nullable()->after('reabierto');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bingos', function (Blueprint $table) {
            $table->dropColumn('series_liberadas');
        });
    }
}