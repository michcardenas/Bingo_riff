<?php

use App\Http\Controllers\Api\ReservaApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartonController;
use App\Models\Bingo;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {
    Route::post('reservas', [ReservaApiController::class, 'store']);
    Route::get('reservas/{id}', [ReservaApiController::class, 'show']);
    Route::get('reservas', [ReservaApiController::class, 'index']);
});

// Ruta para el bingo por nombre (sintaxis moderna de Laravel)
Route::get('/bingos/by-name', [CartonController::class, 'getBingoByName']);

Route::get('/bingos/{id}', function($id) {
    try {
        // Verificar si el modelo existe
        if (!class_exists('App\\Models\\Bingo')) {
            return response()->json(['error' => 'Modelo Bingo no encontrado'], 500);
        }
        
        $bingo = App\Models\Bingo::find($id);
        if (!$bingo) {
            return response()->json(['error' => 'Bingo no encontrado', 'id' => $id], 404);
        }
        return response()->json($bingo);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Error en la API',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
});

// Ruta para verificar que la API funciona (para pruebas)
Route::get('/ping', function() {
    return response()->json([
        'message' => 'API funcionando correctamente',
        'time' => now()->toIso8601String()
    ]);
});