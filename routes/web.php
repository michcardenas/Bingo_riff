<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\BingoController;
use App\Http\Controllers\CartonController;
use App\Http\Controllers\BingoGanadoresController;
use App\Models\Bingo;
use App\Http\Controllers\Admin\BingoAdminController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Rutas públicas
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/bingos/get', [HomeController::class, 'getBingos'])->name('bingos.get');
Route::get('/bingos/all', [HomeController::class, 'getAllBingos'])->name('bingos.all');
Route::get('/bingo/{id}', [HomeController::class, 'show'])->name('bingo.ver');
Route::get('/bingo/activo', [HomeController::class, 'getBingoActivo'])->name('bingo.activo');
// Rutas para los cartones
Route::get('/cartones/buscar', [CartonController::class, 'index'])->name('cartones.index');
Route::post('/cartones/buscar', [CartonController::class, 'buscar'])->name('cartones.buscar');
Route::get('/cartones/descargar/{numero}', [CartonController::class, 'descargar'])->name('cartones.descargar');

// Ruta del grupo de WhatsApp
Route::get('/whatsapp/grupo', function () {
    return redirect('https://chat.whatsapp.com/tu-enlace-aqui');
})->name('whatsapp.grupo');

// Rutas para usuarios regulares
Route::post('/reservar', [BingoController::class, 'store'])->name('bingo.store');
Route::get('/reservado', function () {
    return view('reservado');
})->name('reservado');

// Redirección del dashboard al panel de bingos
Route::get('/dashboard', function () {
    return redirect()->route('bingos.index');
})->middleware(['auth'])->name('dashboard');

// Redirección para errores 419 (sesión CSRF expirada)
Route::fallback(function ($e = null) {
    if (request()->is('419') || $e instanceof \Illuminate\Session\TokenMismatchException) {
        return redirect()->route('home');
    }
});

// Rutas protegidas por autenticación
Route::middleware('auth')->group(function () {
    // Perfil de usuario
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::delete('/admin/borrar-clientes', [BingoAdminController::class, 'truncateClientes'])->name('admin.borrarClientes');


    // Rutas de administrador (ahora dentro del middleware auth para protegerlas)
    Route::prefix('admin')->group(function () {
        Route::get('/bingos', [BingoAdminController::class, 'index'])->name('bingos.index');
        Route::get('/bingos/create', [BingoAdminController::class, 'create'])->name('bingos.create');
        Route::post('/bingos', [BingoAdminController::class, 'store'])->name('bingos.store');
        Route::patch('/bingos/{id}/cerrar', [BingoAdminController::class, 'cerrar'])->name('bingos.cerrar');
        Route::patch('/bingos/{id}', [BingoAdminController::class, 'update'])->name('bingos.update');
        Route::patch('/bingos/{id}/abrir', [BingoAdminController::class, 'abrir'])->name('bingos.abrir');
        Route::patch('/bingos/{id}/archivar', [BingoAdminController::class, 'archivar'])->name('bingos.archivar');
        Route::patch('/bingos/{id}/limpiarSolo', [BingoAdminController::class, 'limpiarSolo'])->name('bingos.limpiarSolo');
        Route::delete('/bingos/limpiar', [BingoAdminController::class, 'limpiar'])->name('bingos.limpiar');
        Route::get('/admin/bingos/verificar-duplicados', [BingoAdminController::class, 'verificarDuplicados'])
            ->name('admin.bingos.verificar-duplicados');

        Route::post('/admin/bingos/marcar-como-unico/{id}', [BingoAdminController::class, 'marcarComoUnico'])
            ->name('admin.bingos.marcar-como-unico');

        // Nueva ruta para actualizar series
        Route::patch('/reservas/{id}/update-series', [BingoAdminController::class, 'updateSeries'])->name('reservas.update-series');

        // Rutas para gestionar reservas de un bingo específico
        Route::get('/bingos/{id}/reservas', [BingoAdminController::class, 'reservasPorBingo'])->name('bingos.reservas');

       // NUEVAS RUTAS para la página de búsqueda de serie
    Route::get('/bingos/{bingo}/buscador-serie', [BingoGanadoresController::class, 'index'])->name('bingos.buscador.serie');
    
    // API para búsqueda de participantes por número de serie
    Route::get('/api/bingos/{bingo}/participantes/serie/{serie}', [BingoGanadoresController::class, 'buscarPorSerie']);
    
    // API para marcar como ganador
    Route::patch('/api/bingos/{bingo}/participantes/{participante}/ganador', [BingoGanadoresController::class, 'marcarGanador']);

        

        // NUEVA RUTA: Carga parcial de tabla de reservas para un bingo específico
        Route::get('/bingos/{id}/reservas-tabla', [BingoAdminController::class, 'reservasPorBingoTabla'])->name('bingos.reservas-tabla');

        // Rutas para gestionar todas las reservas
        Route::get('/reservas', [BingoAdminController::class, 'reservasIndex'])->name('reservas.index');
        Route::patch('/reservas/{id}/aprobar', [BingoAdminController::class, 'reservasAprobar'])->name('reservas.aprobar');
        Route::patch('/reservas/{id}/rechazar', [BingoAdminController::class, 'reservasRechazar'])->name('reservas.rechazar');

        // Rutas para vistas especiales de reservas
        Route::get('/reservas/comprobantes-duplicados', [BingoAdminController::class, 'comprobantesDuplicados'])->name('admin.comprobantesDuplicados');
        Route::get('/reservas/pedidos-duplicados', [BingoAdminController::class, 'pedidosDuplicados'])->name('admin.pedidosDuplicados');
        Route::get('/reservas/cartones-eliminados', [BingoAdminController::class, 'cartonesEliminados'])->name('admin.cartonesEliminados');

        // NUEVA RUTA: Actualizar número de comprobante vía AJAX
        Route::post('/reservas/{id}/update-comprobante', [BingoAdminController::class, 'updateNumeroComprobante'])->name('reservas.update-comprobante');
        Route::patch('/reservas/{id}/numero-comprobante', [BingoAdminController::class, 'updateNumeroComprobante'])->name('reservas.updateNumeroComprobante');

        // Rutas para enlaces
        Route::get('/enlaces', [App\Http\Controllers\EnlaceController::class, 'edit'])->name('enlaces.edit');
        Route::patch('/enlaces/update', [App\Http\Controllers\EnlaceController::class, 'update'])->name('enlaces.update');
    });
});

// Rutas de API para bingos
Route::prefix('api')->group(function () {
    Route::get('/bingos/by-name', function (Request $request) {
        $nombre = $request->query('nombre');

        if (!$nombre) {
            return response()->json(['error' => 'Nombre de bingo requerido'], 400);
        }

        $bingo = Bingo::where('nombre', $nombre)->first();

        if (!$bingo) {
            return response()->json(['error' => 'Bingo no encontrado'], 404);
        }

        return response()->json($bingo);
    });

    Route::get('/bingos/{id}', function ($id) {
        $bingo = Bingo::find($id);

        if (!$bingo) {
            return response()->json(['error' => 'Bingo no encontrado'], 404);
        }

        return response()->json($bingo);
    });
});

// Importar rutas de autenticación (sin registro)
require __DIR__ . '/auth.php';
