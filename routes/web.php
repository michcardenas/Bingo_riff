<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\BingoController;
use App\Http\Controllers\CartonController;
use App\Http\Controllers\Admin\BingoAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/bingos/get', [HomeController::class, 'getBingos'])->name('bingos.get');
Route::get('/bingo/{id}', [HomeController::class, 'show'])->name('bingo.ver');
Route::get('/bingo/activo', [HomeController::class, 'getBingoActivo'])->name('bingo.activo');


// Rutas para los cartones
Route::get('/cartones/buscar', [CartonController::class, 'index'])->name('cartones.index');
Route::post('/cartones/buscar', [CartonController::class, 'buscar'])->name('cartones.buscar');
Route::get('/cartones/descargar/{numero}', [CartonController::class, 'descargar'])->name('cartones.descargar');

// Ruta del grupo de WhatsApp (ajusta según necesites)
Route::get('/whatsapp/grupo', function() {
    return redirect('https://chat.whatsapp.com/tu-enlace-aqui');
})->name('whatsapp.grupo');


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Rutas para usuarios regulares
Route::post('/reservar', [BingoController::class, 'store'])->name('bingo.store');
Route::get('/reservado', function () {
    return view('reservado');
})->name('reservado'); // Agrega esto

// Rutas de administrador
Route::prefix('admin')->group(function () {
    Route::get('/bingos', [BingoAdminController::class, 'index'])->name('bingos.index');
    Route::get('/bingos/create', [BingoAdminController::class, 'create'])->name('bingos.create');
    Route::post('/bingos', [BingoAdminController::class, 'store'])->name('bingos.store');
    Route::patch('/bingos/{id}/cerrar', [BingoAdminController::class, 'cerrar'])->name('bingos.cerrar');
    Route::patch('/bingos/{id}', [BingoAdminController::class, 'update'])->name('bingos.update');
    Route::patch('/bingos/{id}/abrir', [BingoAdminController::class, 'abrir'])->name('bingos.abrir');
    Route::get('/reservas', [BingoAdminController::class, 'reservasIndex'])->name('reservas.index');
    Route::patch('/reservas/{id}/aprobar', [BingoAdminController::class, 'reservasAprobar'])->name('reservas.aprobar');
    Route::patch('/reservas/{id}/rechazar', [BingoAdminController::class, 'reservasRechazar'])->name('reservas.rechazar');
    Route::get('/reservas/comprobantes-duplicados', [BingoAdminController::class, 'comprobantesDuplicados'])->name('admin.comprobantesDuplicados');
    Route::get('/reservas/pedidos-duplicados', [BingoAdminController::class, 'pedidosDuplicados'])->name('admin.pedidosDuplicados');
    Route::get('/reservas/cartones-eliminados', [BingoAdminController::class, 'cartonesEliminados'])->name('admin.cartonesEliminados');
    Route::patch('/reservas/{id}/numero-comprobante', [BingoAdminController::class, 'updateNumeroComprobante'])->name('reservas.updateNumeroComprobante');
});

require __DIR__ . '/auth.php';
