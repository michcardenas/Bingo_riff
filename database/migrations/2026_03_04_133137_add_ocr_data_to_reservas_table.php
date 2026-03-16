<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->text('ocr_data')->nullable()->after('comprobante_metadata');
            $table->string('ocr_status', 20)->nullable()->default('pendiente')->after('ocr_data');
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn(['ocr_data', 'ocr_status']);
        });
    }
};
