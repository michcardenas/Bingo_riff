<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use App\Models\Bingo;
use App\Models\ReservaSerie;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\Utils;

class ReservaApiConcurrencyTest extends TestCase
{
    // use RefreshDatabase;

    protected $bingo = null; // Sembrar la base de datos para pruebas
    protected $baseUrl;
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();

        // ✅ Crear un bingo de prueba con ID 67
        $this->bingo = Bingo::create([
            'nombre' => 'Bingo Test',
            'precio' => 1000,
            'fecha' => now(),
            'series_liberadas' => null,
        ]);

        // URL base de la API (ajusta según tu entorno)
        $this->baseUrl = 'http://host.docker.internal:8000/api/v1';
        
        // Cliente HTTP para hacer las peticiones
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'verify' => false, // Solo para desarrollo
        ]);
    }

    public function test_reservas_concurrentes_no_generan_series_duplicadas()
    {
        $client = new Client([
            'base_uri' => 'http://host.docker.internal:8000', // asegúrate que tengas php artisan serve corriendo
            'http_errors' => false,
        ]);

        $requests = 50;
        $promises = [];

        for ($i = 0; $i < $requests; $i++) {
            $promises[] = $client->postAsync('/api/v1/reservas', [
                'json' => [
                    'bingo_id' => $this->bingo->id,
                    'cartones' => rand(2, 8),
                    'nombre' => 'Usuario Test ' . $i,
                    'celular' => (string) rand(3000000000, 3999999999),
                    'auto_approve' => false,
                ]
            ]);
        }

        // 🔹 Ejecutar todas las solicitudes en paralelo
        $responses = Promise\Utils::settle($promises)->wait();

        foreach ($responses as $res) {
            // dd($res);
            $this->assertEquals('fulfilled', $res['state']);
        }

        // 🔹 Revisar duplicados
        $series = ReservaSerie::pluck('serie')->toArray();
        $unique = array_unique($series);

        $this->assertCount(
            count($series),
            $unique,
            "❌ Se detectaron series duplicadas en reservas concurrentes"
        );
    }


    // /**
    //  * Test para simular múltiples usuarios haciendo reservas al mismo tiempo.
    //  */
    // public function test_concurrent_reservas_no_generan_series_duplicadas()
    // {
    //     $requests = 20; // 🔹 cantidad de llamadas concurrentes
    //     $responses = [];

    //     // Simular múltiples llamadas en paralelo
    //     $this->runConcurrent($requests, function () use (&$responses) {
    //         $response = $this->postJson('/api/v1/reservas', [
    //             'bingo_id' => $this->bingo->id,
    //             'cartones' => rand(2, 8),
    //             'nombre' => 'Usuario Test ' . rand(1, 1000),
    //             'celular' => (string) rand(3000000000, 3999999999),
    //             'auto_approve' => false,
    //         ]);

    //         $responses[] = $response;
    //     });

    //     // Validar que todas fueron exitosas
    //     foreach ($responses as $res) {
    //         $res->assertStatus(201);
    //     }

    //     // ✅ Revisar que no haya series duplicadas
    //     $series = ReservaSerie::pluck('serie')->toArray();
    //     $unique = array_unique($series);

    //     $this->assertCount(count($series), $unique, "❌ Se detectaron series duplicadas en reservas concurrentes");
    // }

    // /**
    //  * Ejecutar funciones en paralelo (threads simulados).
    //  */
    // private function runConcurrent(int $times, callable $callback)
    // {
    //     $pool = [];

    //     for ($i = 0; $i < $times; $i++) {
    //         $pool[] = function () use ($callback) {
    //             return $callback();
    //         };
    //     }

    //     // Ejecutar las funciones como "hilos"
    //     collect($pool)->map(function ($fn) {
    //         return $fn();
    //     });
    // }
}
