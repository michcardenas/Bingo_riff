<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\BingoController;



Route::get('/', [HomeController::class, 'index']);
Route::post('/reservar', [BingoController::class, 'store'])->name('bingo.store');
Route::get('/reservado', function () {
    return view('reservado');
})->name('reservado');
