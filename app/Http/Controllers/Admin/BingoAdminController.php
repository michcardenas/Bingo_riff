<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bingo;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;

class BingoAdminController extends Controller
{
    /**
     * Muestra el listado de bingos con el conteo de reservas.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Obtener todos los bingos con el conteo de reservas, ordenados por ID descendente
        $bingos = Bingo::withCount('reservas')->orderBy('id', 'desc')->get();

        return view('admin.index', compact('bingos'));
    }

    public function create()
    {
        return view('admin.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'fecha'  => 'required|date',
            'precio' => 'required|numeric|min:0',
        ]);

        Bingo::create([
            'nombre' => $request->nombre,
            'fecha'  => $request->fecha,
            'precio' => $request->precio,
            'estado' => 'abierto',  // Añade el estado predeterminado
            'reabierto' => false    // Inicializa este valor también
        ]);

        return redirect()->route('bingos.index')
            ->with('success', '¡Bingo creado exitosamente!');
    }

    public function abrir($id)
    {
        $bingo = Bingo::findOrFail($id);

        // Si el bingo ya está abierto, no se hace nada
        if ($bingo->estado === 'abierto') {
            return redirect()->route('bingos.index')->with('error', 'El bingo ya está abierto.');
        }

        // Si ya se reabrió previamente, no se permite volver a abrir
        if ($bingo->reabierto) {
            return redirect()->route('bingos.index')->with('error', 'Este bingo ya se reabrió una vez y no se puede reabrir nuevamente.');
        }

        // Permitir reabrir por emergencia
        $bingo->estado = 'abierto';
        $bingo->reabierto = true;
        $bingo->save();

        return redirect()->route('bingos.index')->with('success', '¡Bingo reabierto exitosamente (por emergencia)!');
    }

    public function reservasIndex(Request $request)
    {
        $reservas = Reserva::orderBy('id', 'desc')->get();

        // Si la solicitud es AJAX, solo devolver la tabla
        if ($request->ajax()) {
            return view('admin.table', compact('reservas'))->render();
        }

        // Si no es AJAX, devolver la vista completa
        return view('admin.indexclientes', compact('reservas'));
    }

    /**
     * Aprueba una reserva y actualiza su estado a "aprobado".
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reservasAprobar(Request $request, $id)
    {
        $reserva = Reserva::findOrFail($id);
        $reserva->estado = 'aprobado';

        // Si se envió un número de comprobante, actualizarlo
        if ($request->has('numero_comprobante')) {
            $reserva->numero_comprobante = $request->numero_comprobante;
        }

        $reserva->save();

        // Si se solicitó redireccionar a la vista de bingo específico
        if ($request->has('redirect_to_bingo') && $request->has('bingo_id')) {
            return redirect()->route('bingos.reservas', $request->bingo_id)
                ->with('success', 'Reserva aprobada correctamente.');
        }

        return redirect()->back()->with('success', 'Reserva aprobada correctamente.');
    }

    /**
     * Rechaza una reserva y actualiza su estado a "rechazado".
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reservasRechazar(Request $request, $id)
    {
        $reserva = Reserva::findOrFail($id);
        $reserva->estado = 'rechazado';
        $reserva->eliminado = 1; // Marca la reserva como eliminada
        $reserva->save();

        // Si se solicitó redireccionar a la vista de bingo específico
        if ($request->has('redirect_to_bingo') && $request->has('bingo_id')) {
            return redirect()->route('bingos.reservas', $request->bingo_id)
                ->with('success', 'Reserva rechazada correctamente.');
        }

        return redirect()->back()->with('success', 'Reserva rechazada correctamente.');
    }

    public function updateSeries(Request $request, $id)
    {
        // Validar datos de entrada
        $request->validate([
            'new_quantity' => 'required|integer|min:1',
            'selected_series' => 'required|array|min:1',
            'selected_series.*' => 'required',
            'bingo_id' => 'required|exists:bingos,id',
        ]);

        // Obtener la reserva y el bingo
        $reserva = Reserva::findOrFail($id);
        $bingo = Bingo::findOrFail($request->bingo_id);

        // Verificar que la cantidad coincide con las series seleccionadas
        if (count($request->selected_series) != $request->new_quantity) {
            return redirect()->back()->with('error', 'La cantidad de cartones debe coincidir con el número de series seleccionadas.');
        }

        // Obtener series actuales
        $seriesActuales = is_string($reserva->series) ? json_decode($reserva->series, true) : $reserva->series;

        // Identificar series que se están eliminando
        $seriesEliminadas = array_diff($seriesActuales, $request->selected_series);

        // Verificar que no hay duplicados en las series seleccionadas
        $seriesUnicas = array_unique($request->selected_series);
        if (count($seriesUnicas) !== count($request->selected_series)) {
            return redirect()->back()->with('error', 'No se pueden seleccionar series duplicadas.');
        }

        // Verificar que las series seleccionadas pertenecen a esta reserva
        $seriesNoAutorizadas = array_diff($request->selected_series, $seriesActuales);
        if (!empty($seriesNoAutorizadas)) {
            return redirect()->back()->with('error', 'Algunas series seleccionadas no pertenecen a esta reserva.');
        }

        // Añadir las series eliminadas al campo series_liberadas del bingo
        if (!empty($seriesEliminadas)) {
            $seriesLiberadas = $bingo->series_liberadas ? json_decode($bingo->series_liberadas, true) : [];
            $seriesLiberadas = array_merge($seriesLiberadas, $seriesEliminadas);
            $bingo->series_liberadas = json_encode($seriesLiberadas);
            $bingo->save();

            // Registrar la acción en el log
            \Log::info("Series liberadas del bingo {$bingo->id}: " . implode(', ', $seriesEliminadas));
        }

        // Actualizar la reserva
        $reserva->series = json_encode($request->selected_series);
        $reserva->cantidad = $request->new_quantity;
        $reserva->total = $request->new_quantity * $bingo->precio;
        $reserva->save();

        return redirect()->back()->with('success', 'Series actualizadas correctamente. Las series no seleccionadas estarán disponibles para futuras reservas.');
    }

    public function reservasPorBingo(Request $request, $id)
{
    // Obtener el bingo
    $bingo = Bingo::findOrFail($id);

    // Obtener estadísticas
    $reservas = Reserva::where('bingo_id', $id)->get();
    $totalParticipantes = $reservas->count();
    $totalCartones = $reservas->sum('cantidad');
    $totalAprobadas = $reservas->where('estado', 'aprobado')->count();
    $totalPendientes = $reservas->where('estado', 'revision')->count();

    // Si está utilizando la nueva vista parcial y AJAX
    if ($request->ajax()) {
        return view('admin.bingo-reservas', compact('reservas', 'bingo'));
    }

    // De lo contrario, devolver la vista completa
    return view('admin.bingo-reservas', compact(
        'bingo',
        'reservas',
        'totalParticipantes',
        'totalCartones',
        'totalAprobadas',
        'totalPendientes'
    ));
}

    /**
     * Mostrar tabla parcial de reservas filtradas
     */
    public function reservasPorBingoTabla(Request $request, $id)
    {
        $bingo = Bingo::findOrFail($id);
        $query = Reserva::where('bingo_id', $id);
    
        // Filtrar por tipo
        $tipo = $request->tipo ?? 'todas';
        if ($tipo === 'aprobadas') {
            $query->where('estado', 'aprobado');
        } elseif ($tipo === 'pendientes') {
            $query->where('estado', 'revision');
        } elseif ($tipo === 'rechazadas') {
            $query->where('estado', 'rechazado');
        }
    
        // Aplicar filtros adicionales
        if ($request->filled('nombre')) {
            $query->where('nombre', 'LIKE', '%' . $request->nombre . '%');
        }
    
        if ($request->filled('celular')) {
            $query->where('celular', 'LIKE', '%' . $request->celular . '%');
        }
    
        if ($request->filled('serie')) {
            $serie = $request->serie;
            $query->where(function ($q) use ($serie) {
                $q->where('series', 'LIKE', '%"' . $serie . '"%')
                    ->orWhere('series', 'LIKE', '%' . $serie . '%');
            });
        }
    
        // Ordenar por fecha de creación descendente
        $reservas = $query->orderBy('created_at', 'desc')->paginate(15);
    
        // Si es una solicitud AJAX, devolver solo la tabla
        if ($request->ajax()) {
            return view('admin.reservas-tabla', compact('reservas', 'bingo'));
        }
    
        // De lo contrario, redirigir a la vista completa
        return redirect()->route('bingos.reservas', $id);
    }


    public function cerrar($id)
    {
        $bingo = Bingo::findOrFail($id);
        $bingo->estado = 'cerrado';
        $bingo->save();

        return redirect()->route('bingos.index')
            ->with('success', '¡Bingo cerrado!');
    }

    public function archivar($id)
    {
        $bingo = Bingo::findOrFail($id);
        $bingo->estado = 'archivado';
        $bingo->save();

        return redirect()->back()->with('success', 'Bingo archivado correctamente');
    }

    public function limpiar()
    {
        try {
            // Añadir logs para depuración
            \Log::info('Iniciando proceso de limpieza de bingos');

            // Verificar si hay bingos para eliminar
            $bingoCount = Bingo::count();
            \Log::info("Número de bingos encontrados antes de limpiar: {$bingoCount}");

            // Comprobar conexión a la base de datos
            try {
                \DB::connection()->getPdo();
                \Log::info('Conexión a la base de datos establecida correctamente');
            } catch (\Exception $e) {
                \Log::error('Error de conexión a la base de datos: ' . $e->getMessage());
                return redirect()->route('bingos.index')
                    ->with('error', 'Error de conexión a la base de datos: ' . $e->getMessage());
            }

            // Verificar restricciones de clave foránea
            $foreignKeyChecks = \DB::select("SHOW CREATE TABLE bingos");
            \Log::info('Estructura de la tabla bingos: ', $foreignKeyChecks);

            // Intentar desactivar temporalmente las restricciones de clave foránea
            \DB::statement('SET FOREIGN_KEY_CHECKS=0');
            \Log::info('Restricciones de clave foránea desactivadas');

            // Ejecutar el truncate
            Bingo::truncate();
            \Log::info('Truncate ejecutado correctamente');

            // Reactivar las restricciones
            \DB::statement('SET FOREIGN_KEY_CHECKS=1');
            \Log::info('Restricciones de clave foránea reactivadas');

            // Verificar si se eliminaron los bingos
            $bingoCountAfter = Bingo::count();
            \Log::info("Número de bingos después de limpiar: {$bingoCountAfter}");

            return redirect()->route('bingos.index')
                ->with('success', 'Todos los registros de bingos han sido eliminados correctamente.');
        } catch (\Exception $e) {
            // Log detallado del error
            \Log::error('Error al limpiar los registros: ' . $e->getMessage());
            \Log::error('Línea: ' . $e->getLine() . ' en ' . $e->getFile());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            return redirect()->route('bingos.index')
                ->with('error', 'Error al limpiar los registros: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'precio' => 'required|numeric|min:0',
        ]);

        $bingo = Bingo::findOrFail($id);
        $bingo->precio = $request->precio;
        $bingo->save();

        return redirect()->route('bingos.index')->with('success', 'Precio actualizado correctamente.');
    }

    public function cartonesEliminados(Request $request)
    {
        // Obtener las reservas con cartones eliminados
        $reservas = Reserva::where('eliminado', 1)->get();

        // Si la solicitud es AJAX, devolver solo la tabla
        if ($request->ajax()) {
            return view('admin.cartones-eliminados-table', compact('reservas'))->render();
        }

        // Si no es AJAX, devolver la vista completa
        return view('admin.indexclientes', compact('reservas'));
    }

    public function comprobantesDuplicados(Request $request)
    {
        $duplicados = Reserva::select('numero_comprobante')
            ->whereNotNull('numero_comprobante')
            ->groupBy('numero_comprobante')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('numero_comprobante')
            ->toArray();

        $reservas = Reserva::whereIn('numero_comprobante', $duplicados)->get();

        // Si la solicitud es AJAX, devolver solo la tabla
        if ($request->ajax()) {
            return view('admin.comprobantes-duplicados-table', compact('reservas'))->render();
        }

        // Si no es AJAX, devolver la vista completa
        return view('admin.indexclientes', compact('reservas'));
    }

    public function pedidosDuplicados(Request $request)
    {
        $duplicados = Reserva::select('celular')
            ->groupBy('celular')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('celular')
            ->toArray();

        $reservas = Reserva::whereIn('celular', $duplicados)->get();

        // Si la solicitud es AJAX, devolver solo la tabla
        if ($request->ajax()) {
            return view('admin.pedidos-duplicados-table', compact('reservas'))->render();
        }

        // Si no es AJAX, devolver la vista completa
        return view('admin.indexclientes', compact('reservas'));
    }

    /**
     * Actualiza el número de comprobante de una reserva.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateNumeroComprobante(Request $request, $id)
    {
        $request->validate([
            'numero_comprobante' => 'required|string|max:255',
        ]);

        $reserva = Reserva::findOrFail($id);
        $reserva->numero_comprobante = $request->numero_comprobante;
        $reserva->save();

        return redirect()->back()->with('success', 'Número de comprobante actualizado correctamente.');
    }
}
