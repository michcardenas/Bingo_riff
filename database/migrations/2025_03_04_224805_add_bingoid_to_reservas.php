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
        Schema::table('reservas', function (Blueprint $table) {
            // Check if column exists first to avoid errors on re-run
            if (!Schema::hasColumn('reservas', 'bingo_id')) {
                $table->unsignedBigInteger('bingo_id')->after('numero_comprobante')->nullable();
                $table->foreign('bingo_id')
                      ->references('id')
                      ->on('bingos')
                      ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['bingo_id']);
            // Then drop the column
            $table->dropColumn('bingo_id');
        });
    }
};