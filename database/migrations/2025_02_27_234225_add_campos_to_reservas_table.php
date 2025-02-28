<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('reservas', function (Blueprint $table) {
            // series: se puede guardar como string largo o JSON
            $table->text('series')->nullable()->after('cantidad');

            // total: monto total pagado
            $table->integer('total')->default(0)->after('series');

            // numero_comprobante: para guardar un número ingresado manualmente
            $table->string('numero_comprobante')->nullable()->after('comprobante');

            // estado: para manejar la aprobación (revision, aprobado, rechazado)
            $table->enum('estado', ['revision', 'aprobado', 'rechazado'])
                  ->default('revision')
                  ->after('numero_comprobante');
        });
    }

    public function down()
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn('series');
            $table->dropColumn('total');
            $table->dropColumn('numero_comprobante');
            $table->dropColumn('estado');
        });
    }
};
