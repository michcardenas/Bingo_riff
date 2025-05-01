<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeriesSeeder extends Seeder
{
    public function run()
    {
        $cartonInicial = 1;
        $cartonFinal = 10000;

        $serieActual = 1;

        for ($carton = $cartonInicial; $carton <= $cartonFinal; $carton++) {

            $cartonFormato = str_pad($carton, 6, '0', STR_PAD_LEFT);

            $series = [];

            for ($i = 0; $i < 6; $i++) {
                $series[] = str_pad($serieActual, 6, '0', STR_PAD_LEFT);
                $serieActual++;
            }

            DB::table('series')->insert([
             
                'carton' => $cartonFormato,
                'series' => json_encode($series),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        echo "Series generadas exitosamente.";
    }
}
