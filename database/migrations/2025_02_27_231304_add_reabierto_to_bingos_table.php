<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bingos', function (Blueprint $table) {
            $table->boolean('reabierto')->default(false)->after('estado');
        });
    }

    public function down()
    {
        Schema::table('bingos', function (Blueprint $table) {
            $table->dropColumn('reabierto');
        });
    }
};
