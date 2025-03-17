<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentMethodsToEnlacesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('enlaces', function (Blueprint $table) {
            $table->string('numero_nequi')->nullable()->after('telefono_atencion');
            $table->string('numero_daviplata')->nullable()->after('numero_nequi');
            $table->string('numero_transfiya')->nullable()->after('numero_daviplata');
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
            $table->dropColumn(['numero_nequi', 'numero_daviplata', 'numero_transfiya']);
        });
    }
}