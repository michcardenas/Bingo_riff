<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('enlaces', 'numero_transfiya') && !Schema::hasColumn('enlaces', 'numero_breb')) {
            Schema::table('enlaces', function (Blueprint $table) {
                $table->renameColumn('numero_transfiya', 'numero_breb');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('enlaces', 'numero_breb') && !Schema::hasColumn('enlaces', 'numero_transfiya')) {
            Schema::table('enlaces', function (Blueprint $table) {
                $table->renameColumn('numero_breb', 'numero_transfiya');
            });
        }
    }
};
