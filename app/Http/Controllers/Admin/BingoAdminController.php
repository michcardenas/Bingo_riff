<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bingo;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\CartonRechazado;


class BingoAdminController extends Controller
{
    public function index()
    {
        // Cargar los bingos con el conteo de reservas y filtrar por visible = 1
        $bingos = Bingo::withCount('reservas')
            ->where('visible', 1)  // Añadir esta línea para filtrar solo los visibles
            ->orderBy('created_at', 'desc')
            ->get();

        // Asegurar que tengamos la información completa de participantes
        foreach ($bingos as $bingo) {
            // Si necesitamos información más detallada
            $reservas = Reserva::where('bingo_id', $bingo->id)
                ->where(function ($query) {
                    $query->where('estado', 'aprobado')
                        ->orWhere('estado', 'revision');
                })
                ->where('eliminado', false)
                ->get();

            // Asignar el conteo real (solo contamos las que no están rechazadas o eliminadas)
            $bingo->participantes_count = $reservas->count();
            $bingo->cartones_count = $reservas->sum('cantidad');
        }

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

        // Verificar si ya existe un bingo abierto
        $bingoAbierto = Bingo::where('estado', 'abierto')
            ->where('visible', 1)
            ->first();

        if ($bingoAbierto) {
            return redirect()->route('bingos.index')
                ->with('error', 'Ya existe un bingo abierto. Debe cerrar el bingo actual antes de crear uno nuevo.');
        }

        Bingo::create([
            'nombre' => $request->nombre,
            'fecha'  => $request->fecha,
            'precio' => $request->precio,
            'estado' => 'abierto',  // Añade el estado predeterminado
            'reabierto' => false,   // Inicializa este valor también
            'visible' => 1          // Asegurar que es visible por defecto
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

        // Verificar si ya existe otro bingo abierto
        $bingoAbierto = Bingo::where('estado', 'abierto')
            ->where('visible', 1)
            ->where('id', '!=', $id) // Excluir el bingo actual
            ->first();

        if ($bingoAbierto) {
            return redirect()->route('bingos.index')
                ->with('error', 'Ya existe un bingo abierto. Debe cerrar el bingo actual antes de reabrir este.');
        }

        // Permitir reabrir por emergencia
        $bingo->estado = 'abierto';
        $bingo->reabierto = true;
        $bingo->save();

        return redirect()->route('bingos.index')->with('success', '¡Bingo reabierto exitosamente (por emergencia)!');
    }

    public function reservasIndex(Request $request)
    {
        $reservas = Reserva::orderBy('id', 'desc')->limit(100)->orderBy('id', 'desc')->get();

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
        set_time_limit(900);
    
        // Obtener el bingo
        $bingo = Bingo::findOrFail($id);
    
        // Obtener todas las reservas del bingo
        $reservas = Reserva::where('bingo_id', $id)->get();
    
        // Calcular estadísticas
        $totalParticipantes = $reservas->count();
        $totalCartones = $reservas->sum('cantidad');
        $totalAprobadas = $reservas->where('estado', 'aprobado')->count();
        $totalPendientes = $reservas->where('estado', 'revision')->count();
    
        // Vista parcial para AJAX
        if ($request->ajax()) {
            return view('admin.bingo-reservas', [
                'bingo' => $bingo,
                'reservas' => $reservas,
            ]);
        }
    
        // Vista completa
        return view('admin.bingo-reservas', [
            'bingo' => $bingo,
            'reservas' => $reservas,
            'totalParticipantes' => $totalParticipantes,
            'totalCartones' => $totalCartones,
            'totalAprobadas' => $totalAprobadas,
            'totalPendientes' => $totalPendientes,
        ]);
    }
    

    public function verReservasRapidas($bingoId)
    {
        $bingo = Bingo::findOrFail($bingoId);
    
        // Obtenemos las reservas (sin paginar aún)
        $reservaRaw = DB::table('reservas')
        ->select(
            'id',
            'nombre',
            'celular',
            'created_at as fecha',
            'cantidad as cartones',
            'series',
            'total',
            'comprobante',
            'numero_comprobante',
            'estado',
            'ocr_data',
            'ocr_status',
            DB::raw('COALESCE(orden_bingo, 0) as orden_bingo')
        )
        ->where('bingo_id', $bingoId)
        ->orderByDesc('id')
        ->get();
    
    
        // Limpiar comprobantes sin romper stdClass
        $reservaLimpias = $reservaRaw->map(function ($item) {
            $comprobante = $item->comprobante;

            // Quitar caracteres indeseados
            $comprobante = str_replace(['\\"', '"', '[', ']'], '', $comprobante);
            
            // Normalizar slashes
            $comprobante = str_replace(['\\', '\\', '//'], '', $comprobante);
            
            // Eliminar posibles dobles al principio
            $comprobante = preg_replace('#/+#', '/', $comprobante); // normaliza slashes intermedios
            $comprobante = ltrim($comprobante, '/'); // elimina slash inicial si quedó            
            $item->ruta_comprobante = $comprobante;
            return $item;
        });
    
        // Paginación manual para que no falle $reservas->links()
        $currentPage = request()->get('page', 1);
        $perPage = 25;
        $currentItems = $reservaLimpias->slice(($currentPage - 1) * $perPage, $perPage)->values();
    
        $paginator = new LengthAwarePaginator(
            $currentItems,
            $reservaLimpias->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    
        // Calcular referencias OCR duplicadas
        $refsDuplicadas = $this->getReferenciasDuplicadas($bingoId);

        return view('admin.bingos.reservas-rapidas', [
            'reservas' => $paginator,
            'bingo' => $bingo,
            'bingoId' => $bingoId,
            'refsDuplicadas' => $refsDuplicadas,
        ]);
    }
   
    
    public function filtrarReservasRapidas(Request $request, $bingoId)
    {
        // Obtener modelo bingo para mostrar su nombre
        $bingo = \App\Models\Bingo::findOrFail($bingoId);
    
        // Base query sin paginar
        $query = DB::table('reservas')
        ->select(
            'id',
            'nombre',
            'celular',
            'created_at as fecha',
            'cantidad as cartones',
            'series',
            'total',
            'comprobante',
            'numero_comprobante',
            'estado',
            'ocr_data',
            'ocr_status',
            DB::raw('COALESCE(orden_bingo, 0) as orden_bingo')
        )

        ->where('bingo_id', $bingoId);

        // Filtro por campo (nombre, celular, series o datos OCR)
        $campo = $request->input('campo', 'nombre');
        $valor = $request->input('search');

        if (!empty($valor)) {
            $query->where(function ($q) use ($campo, $valor) {
                if ($campo === 'nombre') {
                    $q->where('nombre', 'like', "%{$valor}%");
                } elseif ($campo === 'celular') {
                    $q->where('celular', 'like', "%{$valor}%");
                } elseif ($campo === 'series') {
                    $q->where('series', 'like', "%{$valor}%");
                }
            });
        }
        
    
        // Filtro OCR independiente
        $campoOcr = $request->input('campo_ocr');
        $valorOcr = $request->input('search_ocr');

        if (!empty($valorOcr) && !empty($campoOcr)) {
            $query->where(function ($q) use ($campoOcr, $valorOcr) {
                if ($campoOcr === 'referencia_ocr') {
                    $q->where('ocr_data', 'like', '%"referencia":"%' . $valorOcr . '%"%');
                } elseif ($campoOcr === 'banco_ocr') {
                    $q->where('ocr_data', 'like', '%"banco":"%' . $valorOcr . '%"%');
                } elseif ($campoOcr === 'fecha_ocr') {
                    $q->where('ocr_data', 'like', '%"fecha":"%' . $valorOcr . '%"%');
                }
            });
        }

        // Filtro por rango de fecha de registro
        if ($request->filled('fecha_desde')) {
            $query->where('created_at', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->where('created_at', '<=', $request->fecha_hasta);
        }

        // Filtro por estado
        if ($request->filled('estado') && $request->estado !== 'todos') {
            $query->whereRaw('LOWER(estado) = ?', [strtolower($request->estado)]);
        }
        // Obtener resultados sin paginar aún
        $resultados = $query->orderByDesc('id')->get();
    
        // Limpieza del campo comprobante y generación de ruta limpia
        $reservasLimpias = $resultados->map(function ($item) {
            $comprobante = $item->comprobante;
    
            // Si tiene comillas dobles extra
            if (str_starts_with($comprobante, '""') && str_ends_with($comprobante, '""')) {
                $comprobante = trim($comprobante, '"');
            }
    
            // Limpiar elementos de JSON
            $comprobante = str_replace(['\\"', '"', '[', ']'], '', $comprobante);
            $comprobante = str_replace(['\\/', '\\'], '/', $comprobante);
            $comprobante = preg_replace('#/+#', '/', $comprobante);
            $comprobante = ltrim($comprobante, '/');
    
            $item->ruta_comprobante = $comprobante;
            return $item;
        });
    
        // Paginar manualmente
        $currentPage = $request->get('page', 1);
        $perPage = 25;
        $currentItems = $reservasLimpias->slice(($currentPage - 1) * $perPage, $perPage)->values();
    
        $reservas = new LengthAwarePaginator(
            $currentItems,
            $reservasLimpias->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    
        // Calcular referencias OCR duplicadas
        $refsDuplicadas = $this->getReferenciasDuplicadas($bingoId);

        // Retornar vista con filtros aplicados
        return view('admin.bingos.reservas-rapidas', [
            'reservas' => $reservas,
            'bingo' => $bingo,
            'bingoId' => $bingoId,
            'searchTerm' => $valor ?? '',
            'estadoFilter' => $request->estado ?? 'todos',
            'campoFiltro' => $campo,
            'campoOcr' => $campoOcr ?? '',
            'searchOcr' => $valorOcr ?? '',
            'fechaDesde' => $request->fecha_desde ?? '',
            'fechaHasta' => $request->fecha_hasta ?? '',
            'refsDuplicadas' => $refsDuplicadas,
        ]);
    }
    
    /**
     * Obtiene IDs de reservas con referencia OCR duplicada en un bingo.
     */
    private function getReferenciasDuplicadas($bingoId): array
    {
        $reservas = DB::table('reservas')
            ->select('id', 'ocr_data')
            ->where('bingo_id', $bingoId)
            ->where('ocr_status', 'procesado')
            ->whereNotNull('ocr_data')
            ->get();

        $porRef = [];
        foreach ($reservas as $r) {
            $ocr = json_decode($r->ocr_data, true);
            $ref = $ocr['referencia'] ?? null;
            if (!empty($ref)) {
                $porRef[$ref][] = $r->id;
            }
        }

        $idsDuplicados = [];
        foreach ($porRef as $ids) {
            if (count($ids) > 1) {
                foreach ($ids as $id) {
                    $idsDuplicados[] = $id;
                }
            }
        }

        return $idsDuplicados;
    }

    /**
     * Lista comprobantes de Llave Bre-B del bingo específico (con filtros avanzados).
     */
    public function comprobantesLlave($bingoId)
    {
        $bingo = Bingo::findOrFail($bingoId);

        $query = \App\Models\PagoLlave::with(['reserva', 'bingo'])
            ->where('bingo_id', $bingoId)
            ->orderByDesc('id');

        $this->aplicarFiltrosPagosLlave($query);

        $paginador = $query->paginate(20)->withQueryString();

        return view('admin.bingos.comprobantes-llave', [
            'bingo'      => $bingo,
            'bingoId'    => $bingoId,
            'paginador'  => $paginador,
            'general'    => false,
            'filtros'    => request()->only(['search', 'estado', 'desde', 'hasta', 'monto_min', 'monto_max', 'codigo', 'pagador', 'cliente']),
        ]);
    }

    /**
     * Lista TODOS los pagos de Llave Bre-B (general, sin filtrar por bingo).
     * Permite trazabilidad completa de los correos que llegan al webhook.
     */
    public function comprobantesLlaveGeneral()
    {
        $query = \App\Models\PagoLlave::with(['reserva', 'bingo'])
            ->orderByDesc('id');

        $this->aplicarFiltrosPagosLlave($query);

        $paginador = $query->paginate(20)->withQueryString();

        $bingosDisponibles = Bingo::orderBy('nombre')->get(['id', 'nombre']);

        return view('admin.bingos.comprobantes-llave', [
            'paginador'         => $paginador,
            'general'           => true,
            'bingosDisponibles' => $bingosDisponibles,
            'filtros'           => request()->only(['search', 'estado', 'desde', 'hasta', 'monto_min', 'monto_max', 'codigo', 'pagador', 'cliente', 'bingo_id']),
        ]);
    }

    /**
     * Aplica filtros comunes para la tabla pagos_llave (vista por bingo y general).
     */
    private function aplicarFiltrosPagosLlave($query): void
    {
        $r = request();

        // Búsqueda general (busca en varios campos)
        if ($r->filled('search')) {
            $term = '%' . $r->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('codigo_operacion', 'like', $term)
                  ->orWhere('nombre_pagador', 'like', $term)
                  ->orWhere('cuenta_destino', 'like', $term)
                  ->orWhere('mensaje', 'like', $term)
                  ->orWhereHas('reserva', function ($qq) use ($term) {
                      $qq->where('nombre', 'like', $term)
                         ->orWhere('celular', 'like', $term);
                  });
            });
        }

        // Filtro por estado
        if ($r->filled('estado')) {
            $query->where('estado', $r->estado);
        }

        // Filtro por bingo (solo en vista general)
        if ($r->filled('bingo_id')) {
            $query->where('bingo_id', $r->bingo_id);
        }

        // Filtro por código de operación
        if ($r->filled('codigo')) {
            $query->where('codigo_operacion', 'like', '%' . $r->codigo . '%');
        }

        // Filtro por nombre del pagador
        if ($r->filled('pagador')) {
            $query->where('nombre_pagador', 'like', '%' . $r->pagador . '%');
        }

        // Filtro por nombre del cliente del bingo
        if ($r->filled('cliente')) {
            $cliente = '%' . $r->cliente . '%';
            $query->whereHas('reserva', function ($q) use ($cliente) {
                $q->where('nombre', 'like', $cliente);
            });
        }

        // Filtro por rango de monto
        if ($r->filled('monto_min')) {
            $query->where('monto', '>=', (float) $r->monto_min);
        }
        if ($r->filled('monto_max')) {
            $query->where('monto', '<=', (float) $r->monto_max);
        }

        // Filtro por rango de fecha (fecha del correo)
        if ($r->filled('desde')) {
            $query->where('fecha_correo', '>=', $r->desde);
        }
        if ($r->filled('hasta')) {
            $query->where('fecha_correo', '<=', $r->hasta);
        }
    }

    public function duplicadosOcr($bingoId)
    {
        $bingo = Bingo::findOrFail($bingoId);

        $reservas = \App\Models\Reserva::where('bingo_id', $bingoId)
            ->whereNotNull('ocr_data')
            ->where('ocr_status', 'procesado')
            ->get();

        // Extraer referencia de cada reserva y agrupar
        $porReferencia = $reservas->groupBy(function ($reserva) {
            $ocr = is_string($reserva->ocr_data) ? json_decode($reserva->ocr_data, true) : $reserva->ocr_data;
            return $ocr['referencia'] ?? null;
        })->filter(function ($grupo, $key) {
            return $key !== null && $key !== '' && $grupo->count() > 1;
        });

        // Paginacion de grupos
        $page = request()->get('page', 1);
        $perPage = 3;
        $paginador = new LengthAwarePaginator(
            $porReferencia->slice(($page - 1) * $perPage, $perPage),
            $porReferencia->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('admin.bingos.duplicados-ocr', compact('bingo', 'paginador', 'bingoId'));
    }

    public function actualizarEstadoReserva(Request $request)
    {
        $request->validate([
            'reserva_id' => 'required|integer|exists:reservas,id',
            'estado' => 'required|string|in:aprobado,rechazado,revision',
        ]);
    
        $updateData = [
            'estado' => $request->estado,
        ];
    
        // Si es rechazado, marcar como eliminado
        if ($request->estado === 'rechazado') {
            $updateData['eliminado'] = 1;
        }
    
        // Si es aprobado, eliminar debe volver a 0
        if ($request->estado === 'aprobado') {
            $updateData['eliminado'] = 0;
        }
    
        DB::table('reservas')
            ->where('id', $request->reserva_id)
            ->update($updateData);
    
        return response()->json(['success' => true]);
    }
    public function actualizarDatos(Request $request, $id)
{
    $reserva = \App\Models\Reserva::findOrFail($id);

    $validated = $request->validate([
        'nombre' => 'required|string|max:255',
        'celular' => 'required|string|max:20',
        'total' => 'required|numeric|min:0',
    ]);

    $reserva->update($validated);

    return response()->json(['message' => 'Datos actualizados correctamente.']);
}

    public function actualizarNumeroComprobante(Request $request)
    {
        $request->validate([
            'reserva_id' => 'required|integer|exists:reservas,id',
            'numero_comprobante' => 'nullable|string|max:255',
        ]);

        DB::table('reservas')
            ->where('id', $request->reserva_id)
            ->update(['numero_comprobante' => $request->numero_comprobante]);

        return response()->json(['success' => true]);
    }
   public function updateComprobante(Request $request, $id)
{
    $inicio = microtime(true);
    Log::info('📥 Inicio actualización de comprobante', ['reserva_id' => $id]);

    try {
        $request->validate([
            'comprobante' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $reserva = Reserva::findOrFail($id);
        $file = $request->file('comprobante');

        if (!$file) {
            throw new \Exception('No se recibió ningún archivo');
        }

        Log::info("📦 Procesando comprobante", [
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize()
        ]);

        // Hash rápido para comparar archivos por contenido
        $hashArchivo = sha1_file($file->getRealPath());

        // Si ya existe comprobante, podemos saltarnos análisis pesado
        if (!empty($reserva->comprobante)) {
            Log::info('📌 Ya existe comprobante en reserva. Se omitirá análisis de duplicado.');

            $metadatos = [
                'sha1' => $hashArchivo,
                'fecha_subida' => now()->toDateTimeString(),
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
            ];
        } else {
            // Verificación de duplicado perceptual
            $verificacion = $this->verificarComprobanteUnico($file);
            $metadatos = $verificacion['metadatos'];
            $metadatos['sha1'] = $hashArchivo;
            $metadatos['fecha_subida'] = now()->toDateTimeString();
            $metadatos['mime_type'] = $file->getMimeType();
            $metadatos['extension'] = $file->getClientOriginalExtension();

            if (!$verificacion['es_unico']) {
                $metadatos['posible_duplicado'] = true;
                $metadatos['reserva_coincidente_id'] = optional($verificacion['reserva_coincidente'])->id;
                $metadatos['similaridad'] = $verificacion['similaridad'];

                Log::warning("⚠️ Posible comprobante duplicado", [
                    'archivo' => $file->getClientOriginalName(),
                    'similaridad' => $verificacion['similaridad'] . '%'
                ]);
            }
        }

        // Ruta destino: siempre public_path() que Laravel resuelve bien en local y producción
        $destino = public_path('comprobantes');

        if (!file_exists($destino)) {
            mkdir($destino, 0775, true);
        }

        // Guardar archivo (sanitizar nombre: solo letras, números, guiones y puntos)
        $nombreOriginal = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $nombreLimpio = preg_replace('/[^A-Za-z0-9_-]/', '_', $nombreOriginal);
        $nombreLimpio = preg_replace('/_+/', '_', $nombreLimpio);
        $filename = time() . '_' . $nombreLimpio . '.' . $extension;
        $file->move($destino, $filename);
        $rutaRelativa = 'comprobantes/' . $filename;

        Log::info('✅ Comprobante guardado', ['ruta' => $rutaRelativa]);

        // Guardar en base de datos
        $reserva->comprobante = json_encode([$rutaRelativa]);
        $reserva->comprobante_metadata = json_encode([$metadatos]);
        $reserva->save();

        $duracion = round(microtime(true) - $inicio, 2);
        Log::info("⏱ Tiempo total de ejecución updateComprobante: {$duracion}s");

        return response()->json([
            'success' => true,
            'ruta' => $rutaRelativa,
            'posible_duplicado' => $metadatos['posible_duplicado'] ?? false,
        ]);

    } catch (\Exception $e) {
        Log::error('❌ Error al actualizar comprobante', [
            'error' => $e->getMessage(),
            'linea' => $e->getLine(),
            'archivo' => $e->getFile(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error al actualizar comprobante',
            'error' => $e->getMessage(),
        ]);
    }
}


    
public function eliminarSerie(Request $request)
{
    $request->validate([
        'reserva_id' => 'required|exists:reservas,id',
        'serie' => 'required|string',
        'motivo' => 'nullable|string'
    ]);
    
    $reserva = Reserva::findOrFail($request->reserva_id);
    
    $series = $reserva->series;
    // Si es JSON, decodifica
    if (is_string($series)) {
        $decoded = json_decode($series, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $series = $decoded;
        } else {
            // Si viene mal formado, intenta dividirlo manualmente
            $series = preg_split('/[",\s]+/', $series);
        }
    }
    
    // Asegurar que sea array limpio
    $series = array_filter(array_map('trim', $series));
    
    $serieAEliminar = preg_replace('/[^0-9]/', '', $request->serie);
    
    // Verificar si la serie existe en las series de la reserva
    $serieExiste = false;
    foreach ($series as $serie) {
        if (trim($serie) === $serieAEliminar) {
            $serieExiste = true;
            break;
        }
    }
    
    if (!$serieExiste) {
        return response()->json([
            'success' => false,
            'message' => 'La serie no existe en esta reserva'
        ]);
    }
    
    $nuevasSeries = array_filter($series, fn($s) => trim($s) !== $serieAEliminar);
    
    if (count($nuevasSeries) < 1) {
        return response()->json([
            'success' => false,
            'message' => 'No se puede dejar la reserva sin cartones'
        ]);
    }
    
    // Iniciar transacción para asegurar la integridad de los datos
    DB::beginTransaction();
    
    try {
        // Actualizar la reserva
        $reserva->series = json_encode(array_values($nuevasSeries));
        $reserva->cantidad = count($nuevasSeries);
        $reserva->total = $reserva->bingo->precio * count($nuevasSeries);
        $reserva->save();
        
        // Registrar el cartón rechazado
        CartonRechazado::create([
            'reserva_id' => $reserva->id,
            'bingo_id' => $reserva->bingo_id,
            'serie_rechazada' => $serieAEliminar,
            'fecha_rechazo' => now(),
            'motivo' => $request->motivo ?? 'Eliminación manual',
            'usuario' => auth()->check() ? auth()->user()->name : null
        ]);
        
        // Confirmar transacción
        DB::commit();
        
        return response()->json([
            'success' => true,
            'message' => 'Serie eliminada correctamente'
        ]);
    } catch (\Exception $e) {
        // Revertir transacción en caso de error
        DB::rollBack();
        
        return response()->json([
            'success' => false,
            'message' => 'Error al eliminar la serie: ' . $e->getMessage()
        ]);
    }
}
    


    /**
     * Mostrar tabla parcial de reservas filtradas
     */
    public function reservasPorBingoTabla(Request $request, $id)
    {
        set_time_limit(900);
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

            // Crear el patrón exacto que buscamos en la base de datos
            // Básicamente buscamos: ["0001"] o algo que incluya ese patrón exacto
            $serieFormateada = '"[\\"' . $serie . '\\"]"';
            $serieEnArray = '[\\"' . $serie . '\\"';  // Para cuando es parte de un array más grande

            $query->where(function ($q) use ($serie, $serieFormateada, $serieEnArray) {
                // Opción 1: Serie exacta - coincide con todo el campo (para series individuales)
                $q->where('series', $serieFormateada);

                // Opción 2: Serie como parte de un array más grande
                $q->orWhere('series', 'LIKE', '%' . $serieEnArray . '%');
            });
        }

        // Cambiar el orden para usar orden_bingo en lugar de created_at
        $reservas = $query->orderBy('orden_bingo', 'asc')->paginate(3000);

        // Si es una solicitud AJAX, devolver solo la tabla
        if ($request->ajax()) {
            return view('admin.reservas-tabla', compact('reservas', 'bingo'));
        }

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
            \Log::info('Iniciando proceso de ocultamiento y cierre de bingos');

            // Verificar si hay bingos para ocultar
            $bingoCount = Bingo::count();
            \Log::info("Número de bingos encontrados antes de ocultar: {$bingoCount}");

            // Comprobar conexión a la base de datos
            try {
                \DB::connection()->getPdo();
                \Log::info('Conexión a la base de datos establecida correctamente');
            } catch (\Exception $e) {
                \Log::error('Error de conexión a la base de datos: ' . $e->getMessage());
                return redirect()->route('bingos.index')
                    ->with('error', 'Error de conexión a la base de datos: ' . $e->getMessage());
            }

            // Actualizar todos los bingos para marcarlos como ocultos (visible = 0) y cerrados
            $actualizados = Bingo::query()->update([
                'visible' => 0,
                'estado' => 'archivado'
            ]);
            \Log::info("Se han ocultado y cerrado {$actualizados} bingos");

            // Verificar si se ocultaron los bingos
            $bingosVisibles = Bingo::where('visible', 1)->count();
            \Log::info("Número de bingos visibles después de ocultar: {$bingosVisibles}");

            // Verificar si se cerraron los bingos
            $bingosAbiertos = Bingo::where('estado', 'abierto')->count();
            \Log::info("Número de bingos abiertos después de cerrar: {$bingosAbiertos}");

            return redirect()->route('bingos.index')
                ->with('success', 'Todos los bingos han sido ocultados y cerrados correctamente.');
        } catch (\Exception $e) {
            // Log detallado del error
            \Log::error('Error al ocultar y cerrar los bingos: ' . $e->getMessage());
            \Log::error('Línea: ' . $e->getLine() . ' en ' . $e->getFile());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            return redirect()->route('bingos.index')
                ->with('error', 'Error al ocultar y cerrar los bingos: ' . $e->getMessage());
        }
    }

    public function limpiarSolo($id)
{
    try {
        // Buscar el bingo específico
        $bingo = Bingo::findOrFail($id);

        // Guardar el nombre para el mensaje de éxito
        $bingoNombre = $bingo->nombre;

        // Si el bingo está archivado, solo cambiamos visible a 0
        if (strtolower($bingo->estado) == 'archivado') {
            $bingo->update([
                'visible' => 0
            ]);
        } else {
            // Para otros estados, marcar como oculto y cerrado
            $bingo->update([
                'visible' => 0,
                'estado' => 'archivado'
            ]);
        }

        return redirect()->route('bingos.index')
            ->with('success', "El bingo '{$bingoNombre}' ha sido ocultado correctamente.");
    } catch (\Exception $e) {
        return redirect()->route('bingos.index')
            ->with('error', 'Error al ocultar el bingo: ' . $e->getMessage());
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
        set_time_limit(120); // 2 minutos
        try {
            // Obtener bingo_id si está presente
            $bingoId = $request->input('bingo_id');
            
            \Log::info("Buscando comprobantes duplicados", [
                'bingo_id' => $bingoId ? $bingoId : 'Todos los bingos'
            ]);
    
            // Consulta base para número de comprobante
            $query = Reserva::select('numero_comprobante')
                ->whereNotNull('numero_comprobante');
            
            // Añadir filtro de bingo_id si está presente
            if ($bingoId) {
                $query->where('bingo_id', $bingoId);
            }
            
            $duplicadosPorNumero = $query
                ->groupBy('numero_comprobante')
                ->havingRaw('COUNT(*) > 1')
                ->limit(500)
                ->pluck('numero_comprobante')
                ->toArray();
            
            // Consulta de reservas
            $queryReservas = Reserva::whereIn('numero_comprobante', $duplicadosPorNumero);
            
            // Filtrar por bingo_id si está presente
            if ($bingoId) {
                $queryReservas->where('bingo_id', $bingoId);
            }
            
            $reservasPorNumero = $queryReservas
                ->limit(1000)
                ->get();
            
            // Preparar grupos de duplicados por número
            $duplicados = [];
            foreach ($duplicadosPorNumero as $numeroComprobante) {
                $grupo = $reservasPorNumero->filter(function ($reserva) use ($numeroComprobante) {
                    return $reserva->numero_comprobante === $numeroComprobante;
                })->values()->all();
            
                // Solo considerar grupos con más de una reserva
                if (count($grupo) > 1) {
                    // Añadir similaridad del 100% a cada reserva del grupo
                    foreach ($grupo as $reserva) {
                        $reserva->similaridad = 100;
                    }
            
                    $duplicados[] = $grupo;
                }
            }
            
            \Log::info('Encontrados ' . count($duplicados) . ' grupos de duplicados por número de comprobante');
            
            // Parte 2: Duplicados por metadatos
            $duplicadosPorMetadatos = $this->verificarDuplicadosInterno($bingoId);
            
            \Log::info('Encontrados ' . count($duplicadosPorMetadatos) . ' grupos de duplicados por metadatos');
            
            // Añadir los duplicados por metadatos a la lista general
            foreach ($duplicadosPorMetadatos as $grupo) {
                $duplicados[] = $grupo;
            }
            
            \Log::info('Total de ' . count($duplicados) . ' grupos de duplicados encontrados');
            
            // Limitar el número total de duplicados
            $duplicados = array_slice($duplicados, 0, 200);
            
            return view('admin.comprobantes-duplicados-table', compact('duplicados'));
        } catch (\Exception $e) {
            \Log::error("Error en comprobantesDuplicados: " . $e->getMessage());
            \Log::error("Trace: " . $e->getTraceAsString());
            
            return response()->json([
                'error' => 'Error interno al buscar comprobantes duplicados: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Versión interna de verificarDuplicados que devuelve los resultados 
     * en lugar de renderizar una vista, con opción de filtrar por bingo_id
     * 
     * @param int|null $bingoId ID del bingo a filtrar, o null para todos
     * @return array Grupos de duplicados encontrados
     */
    private function verificarDuplicadosInterno($bingoId = null)
    {
        set_time_limit(120); // 2 minutos
        
        // Consulta base para reservas con metadatos
        $query = Reserva::whereNotNull('comprobante_metadata')
            ->orderBy('created_at', 'desc');
        
        // Añadir filtro de bingo_id si está presente
        if ($bingoId) {
            $query->where('bingo_id', $bingoId);
        }
    
        $reservas = $query
            ->limit(2000) // Limitar para prevenir timeout
            ->get();
    
        // Si no hay reservas, retornar vacío
        if ($reservas->isEmpty()) {
            \Log::info("No hay reservas para verificar duplicados");
            return [];
        }
    
        $posiblesDuplicados = [];
        $procesados = [];
    
        // Primera pasada: Identificar reservas que tienen explícitamente la marca de "posible_duplicado"
        foreach ($reservas as $reserva) {
            if (in_array($reserva->id, $procesados)) {
                continue;
            }
    
            $metadatos = json_decode($reserva->comprobante_metadata, true);
            if (!is_array($metadatos)) {
                \Log::warning("Reserva ID {$reserva->id} tiene metadatos no válidos: " . $reserva->comprobante_metadata);
                continue;
            }
    
            $hayDuplicadoMarcado = false;
            $reservasRelacionadas = [];
    
            // Revisar si alguno de los archivos está marcado como posible duplicado
            if (isset($metadatos[0]) && is_array($metadatos[0])) {
                // Caso de múltiples archivos
                foreach ($metadatos as $archivoMetadatos) {
                    if (isset($archivoMetadatos['posible_duplicado']) && $archivoMetadatos['posible_duplicado']) {
                        $hayDuplicadoMarcado = true;
                        \Log::info("Reserva ID {$reserva->id} tiene archivo marcado como posible duplicado");
    
                        // Si hay una reserva coincidente específica
                        if (isset($archivoMetadatos['reserva_coincidente_id'])) {
                            $reservaCoincidente = Reserva::find($archivoMetadatos['reserva_coincidente_id']);
                            // Verificar que la reserva coincidente exista, no esté procesada y cumpla con el filtro de bingo_id
                            if ($reservaCoincidente && 
                                !in_array($reservaCoincidente->id, $procesados) &&
                                (!$bingoId || $reservaCoincidente->bingo_id == $bingoId)) {
                                $reservasRelacionadas[] = $reservaCoincidente;
                                $procesados[] = $reservaCoincidente->id;
                            }
                        }
                    }
                }
            } else {
                // Caso de un solo archivo
                if (isset($metadatos['posible_duplicado']) && $metadatos['posible_duplicado']) {
                    $hayDuplicadoMarcado = true;
                    \Log::info("Reserva ID {$reserva->id} está marcada como posible duplicado (archivo único)");
    
                    // Si hay una reserva coincidente específica
                    if (isset($metadatos['reserva_coincidente_id'])) {
                        $reservaCoincidente = Reserva::find($metadatos['reserva_coincidente_id']);
                        // Verificar que la reserva coincidente exista, no esté procesada y cumpla con el filtro de bingo_id
                        if ($reservaCoincidente && 
                            !in_array($reservaCoincidente->id, $procesados) &&
                            (!$bingoId || $reservaCoincidente->bingo_id == $bingoId)) {
                            $reservasRelacionadas[] = $reservaCoincidente;
                            $procesados[] = $reservaCoincidente->id;
                        }
                    }
                }
            }
    
            if ($hayDuplicadoMarcado) {
                // Asignamos la similitud a la reserva para mostrarla en la vista
                $reserva->similaridad = 100; // Esta es la reserva "original" para este grupo
    
                // Crear grupo de duplicados
                $grupo = [$reserva];
                $procesados[] = $reserva->id;
    
                // Añadir reservas relacionadas
                foreach ($reservasRelacionadas as $relacionada) {
                    // Obtener la similitud desde los metadatos
                    $similitud = 80; // Valor predeterminado
                    $metadatosRelacionados = json_decode($relacionada->comprobante_metadata, true);
    
                    if (is_array($metadatosRelacionados)) {
                        if (isset($metadatosRelacionados[0]) && is_array($metadatosRelacionados[0])) {
                            foreach ($metadatosRelacionados as $metadatoArchivo) {
                                if (isset($metadatoArchivo['similaridad'])) {
                                    $similitud = max($similitud, $metadatoArchivo['similaridad']);
                                }
                            }
                        } elseif (isset($metadatosRelacionados['similaridad'])) {
                            $similitud = $metadatosRelacionados['similaridad'];
                        }
                    }
    
                    $relacionada->similaridad = $similitud;
                    $grupo[] = $relacionada;
                }
    
                if (count($grupo) > 1) {
                    $posiblesDuplicados[] = $grupo;
                }
            }
        }
    
        // Segunda pasada: Comparación directa para encontrar más duplicados no marcados
        foreach ($reservas as $reserva) {
            if (in_array($reserva->id, $procesados)) {
                continue;
            }
    
            $metadatosA = json_decode($reserva->comprobante_metadata, true);
            if (!is_array($metadatosA)) {
                continue;
            }
    
            $grupo = [];
            $similitudesEncontradas = false;
    
            // Comparar con las demás reservas del mismo bingo si se especificó uno
            foreach ($reservas as $otraReserva) {
                // Saltar si es la misma reserva, ya fue procesada, o no pertenece al mismo bingo
                if ($reserva->id == $otraReserva->id || 
                    in_array($otraReserva->id, $procesados) ||
                    ($bingoId && $reserva->bingo_id != $otraReserva->bingo_id)) {
                    continue;
                }
    
                $metadatosB = json_decode($otraReserva->comprobante_metadata, true);
                if (!is_array($metadatosB)) {
                    continue;
                }
    
                // Comparar metadatos
                $similitudMax = 0;
    
                // Manejo de múltiples archivos en ambas reservas
                if (isset($metadatosA[0]) && is_array($metadatosA[0])) {
                    foreach ($metadatosA as $metadatoA) {
                        if (isset($metadatosB[0]) && is_array($metadatosB[0])) {
                            foreach ($metadatosB as $metadatoB) {
                                $resultado = $this->compararMetadatos($metadatoA, $metadatoB);
                                if ($resultado['es_duplicado']) {
                                    $similitudMax = max($similitudMax, $resultado['similaridad']);
                                    \Log::info("Similitud encontrada entre reservas {$reserva->id} y {$otraReserva->id}: {$resultado['similaridad']}%");
                                    $similitudesEncontradas = true;
                                }
                            }
                        } else {
                            $resultado = $this->compararMetadatos($metadatoA, $metadatosB);
                            if ($resultado['es_duplicado']) {
                                $similitudMax = max($similitudMax, $resultado['similaridad']);
                                \Log::info("Similitud encontrada entre reservas {$reserva->id} y {$otraReserva->id}: {$resultado['similaridad']}%");
                                $similitudesEncontradas = true;
                            }
                        }
                    }
                } else {
                    if (isset($metadatosB[0]) && is_array($metadatosB[0])) {
                        foreach ($metadatosB as $metadatoB) {
                            $resultado = $this->compararMetadatos($metadatosA, $metadatoB);
                            if ($resultado['es_duplicado']) {
                                $similitudMax = max($similitudMax, $resultado['similaridad']);
                                \Log::info("Similitud encontrada entre reservas {$reserva->id} y {$otraReserva->id}: {$resultado['similaridad']}%");
                                $similitudesEncontradas = true;
                            }
                        }
                    } else {
                        $resultado = $this->compararMetadatos($metadatosA, $metadatosB);
                        if ($resultado['es_duplicado']) {
                            $similitudMax = max($similitudMax, $resultado['similaridad']);
                            \Log::info("Similitud encontrada entre reservas {$reserva->id} y {$otraReserva->id}: {$resultado['similaridad']}%");
                            $similitudesEncontradas = true;
                        }
                    }
                }
    
                // Si encontramos similitud alta
                if ($similitudMax > 75) {
                    // Si es el primer duplicado, añadir la reserva original
                    if (empty($grupo)) {
                        $reserva->similaridad = 100;
                        $grupo[] = $reserva;
                        $procesados[] = $reserva->id;
                    }
    
                    // Añadir la reserva con su similaridad
                    $otraReserva->similaridad = $similitudMax;
                    $grupo[] = $otraReserva;
                    $procesados[] = $otraReserva->id;
                }
            }
    
            if (!empty($grupo)) {
                $posiblesDuplicados[] = $grupo;
                \Log::info("Creado grupo de duplicados por comparación con " . count($grupo) . " reservas");
            } else if ($similitudesEncontradas) {
                \Log::warning("Se encontraron similitudes, pero no se creó ningún grupo para la reserva {$reserva->id}");
            }
        }
    
        return $posiblesDuplicados;
    }

    /**
 * Compara dos conjuntos de metadatos para determinar si son similares
 * 
 * @param array $metadatosA
 * @param array $metadatosB
 * @return array
 */
private function compararMetadatos($metadatosA, $metadatosB)
{
    \Log::debug("Comparando metadatos", [
        'A' => array_keys($metadatosA),
        'B' => array_keys($metadatosB)
    ]);
    
    $coincidencias = 0;
    $totalComparaciones = 0;
    $ponderacion = 0;
    $debugInfo = [];

    // Si tiene marca de verificación manual, lo respetamos
    if (isset($metadatosB['verificado_manualmente']) && $metadatosB['verificado_manualmente']) {
        \Log::debug("Metadatos B verificados manualmente, ignorando comparación");
        return [
            'es_duplicado' => false,
            'similaridad' => 0
        ];
    }

    // Hash perceptual (alta prioridad - peso 4)
    if (isset($metadatosA['perceptual_hash']) && isset($metadatosB['perceptual_hash']) &&
        !empty($metadatosA['perceptual_hash']) && !empty($metadatosB['perceptual_hash'])) {
        $totalComparaciones += 4;
        $ponderacion += 4;

        // Calcular distancia Hamming entre los hashes perceptuales
        $hashA = $metadatosA['perceptual_hash'];
        $hashB = $metadatosB['perceptual_hash'];

        // Asegurar que los hashes tienen la misma longitud
        $longMinima = min(strlen($hashA), strlen($hashB));
        if ($longMinima > 0) {
            $hashA = substr($hashA, 0, $longMinima);
            $hashB = substr($hashB, 0, $longMinima);
            
            // Convertir a binario para comparar bit a bit
            $hashBinA = '';
            $hashBinB = '';

            for ($i = 0; $i < $longMinima; $i++) {
                // Manejar posibles caracteres no hexadecimales
                if (ctype_xdigit($hashA[$i]) && ctype_xdigit($hashB[$i])) {
                    $binA = str_pad(decbin(hexdec($hashA[$i])), 4, '0', STR_PAD_LEFT);
                    $binB = str_pad(decbin(hexdec($hashB[$i])), 4, '0', STR_PAD_LEFT);
                    $hashBinA .= $binA;
                    $hashBinB .= $binB;
                }
            }

            // Contar bits diferentes (distancia Hamming)
            $distancia = 0;
            $bitsComparados = strlen($hashBinA);
            
            if ($bitsComparados > 0) {
                for ($i = 0; $i < $bitsComparados; $i++) {
                    if ($hashBinA[$i] !== $hashBinB[$i]) {
                        $distancia++;
                    }
                }

                // Calcular similitud en porcentaje (0 distancia = 100% similaridad)
                $similitudHash = 100 - (($distancia / $bitsComparados) * 100);
                $debugInfo['hash_perceptual'] = [
                    'distancia' => $distancia,
                    'bits_comparados' => $bitsComparados,
                    'similitud' => $similitudHash
                ];

                // Mejorado el sistema de puntuación para hash perceptual
                if ($similitudHash > 95) {
                    $coincidencias += 4; // Máxima puntuación - coincidencia casi perfecta
                    $debugInfo['hash_perceptual']['puntos'] = 4;
                } elseif ($similitudHash > 90) {
                    $coincidencias += 3;
                    $debugInfo['hash_perceptual']['puntos'] = 3;
                } elseif ($similitudHash > 85) {
                    $coincidencias += 2;
                    $debugInfo['hash_perceptual']['puntos'] = 2;
                } elseif ($similitudHash > 75) {
                    $coincidencias += 1;
                    $debugInfo['hash_perceptual']['puntos'] = 1;
                } else {
                    $debugInfo['hash_perceptual']['puntos'] = 0;
                }

                // Si la coincidencia de hash perceptual es extremadamente alta, es casi seguro la misma imagen
                if ($similitudHash > 98) {
                    \Log::info("Coincidencia extremadamente alta de hash perceptual: {$similitudHash}%");
                    return [
                        'es_duplicado' => true,
                        'similaridad' => $similitudHash,
                        'debug' => $debugInfo
                    ];
                }
            }
        }
    }

    // Hash de contenido (peso 5) - Máxima prioridad
    if (isset($metadatosA['contenido_hash']) && isset($metadatosB['contenido_hash']) &&
        !empty($metadatosA['contenido_hash']) && !empty($metadatosB['contenido_hash'])) {
        $totalComparaciones += 5;
        $ponderacion += 5;

        $similitudHash = 0;
        
        if ($metadatosA['contenido_hash'] === $metadatosB['contenido_hash']) {
            $coincidencias += 5;
            $similitudHash = 100;
            $debugInfo['contenido_hash'] = [
                'coincidencia' => true,
                'puntos' => 5
            ];
            
            // Si el hash de contenido coincide exactamente, es definitivamente la misma imagen
            return [
                'es_duplicado' => true,
                'similaridad' => 100,
                'debug' => $debugInfo
            ];
        } else {
            $debugInfo['contenido_hash'] = [
                'coincidencia' => false,
                'puntos' => 0
            ];
        }
    }

    // Histograma de colores (peso 3)
    if (isset($metadatosA['histograma']) && isset($metadatosB['histograma']) &&
        !empty($metadatosA['histograma']) && !empty($metadatosB['histograma']) &&
        is_array($metadatosA['histograma']) && is_array($metadatosB['histograma'])) {
        
        $totalComparaciones += 3;
        $ponderacion += 3;

        $histogramaA = $metadatosA['histograma'];
        $histogramaB = $metadatosB['histograma'];

        // Asegurar que ambos histogramas tienen la misma longitud
        $minLength = min(count($histogramaA), count($histogramaB));
        
        if ($minLength > 0) {
            // Normalizar histogramas para que sumen 1
            $sumaA = array_sum(array_slice($histogramaA, 0, $minLength));
            $sumaB = array_sum(array_slice($histogramaB, 0, $minLength));
            
            $histogramaA_norm = $sumaA > 0 ? array_map(function($v) use ($sumaA) { return $v / $sumaA; }, array_slice($histogramaA, 0, $minLength)) : array_slice($histogramaA, 0, $minLength);
            $histogramaB_norm = $sumaB > 0 ? array_map(function($v) use ($sumaB) { return $v / $sumaB; }, array_slice($histogramaB, 0, $minLength)) : array_slice($histogramaB, 0, $minLength);
            
            // Calcular distancia entre histogramas usando Chi-cuadrado
            $distanciaHistograma = 0;
            for ($i = 0; $i < $minLength; $i++) {
                $suma = $histogramaA_norm[$i] + $histogramaB_norm[$i];
                if ($suma > 0) {
                    $diff = $histogramaA_norm[$i] - $histogramaB_norm[$i];
                    $distanciaHistograma += ($diff * $diff) / $suma;
                }
            }
            $distanciaHistograma /= 2; // Normalizar
            
            // Convertir distancia a similitud (menor distancia = mayor similitud)
            $similitudHistograma = 100 * (1 - min(1, $distanciaHistograma));
            
            $debugInfo['histograma'] = [
                'similitud' => $similitudHistograma
            ];

            // Añadir a coincidencias según nivel de similitud
            if ($similitudHistograma > 95) {
                $coincidencias += 3;
                $debugInfo['histograma']['puntos'] = 3;
            } elseif ($similitudHistograma > 90) {
                $coincidencias += 2;
                $debugInfo['histograma']['puntos'] = 2;
            } elseif ($similitudHistograma > 80) {
                $coincidencias += 1;
                $debugInfo['histograma']['puntos'] = 1;
            } else {
                $debugInfo['histograma']['puntos'] = 0;
            }
        }
    }

    // Dimensiones de la imagen (peso 2)
    if (isset($metadatosA['dimensions']) && isset($metadatosB['dimensions']) &&
        isset($metadatosA['dimensions']['width']) && isset($metadatosB['dimensions']['width']) &&
        isset($metadatosA['dimensions']['height']) && isset($metadatosB['dimensions']['height'])) {
        
        $totalComparaciones += 2;
        $ponderacion += 2;

        $widthA = (int) $metadatosA['dimensions']['width'];
        $heightA = (int) $metadatosA['dimensions']['height'];
        $widthB = (int) $metadatosB['dimensions']['width'];
        $heightB = (int) $metadatosB['dimensions']['height'];
        
        $areaA = $widthA * $heightA;
        $areaB = $widthB * $heightB;
        
        $ratioA = $widthA > 0 ? $heightA / $widthA : 0;
        $ratioB = $widthB > 0 ? $heightB / $widthB : 0;
        
        $debugInfo['dimensiones'] = [
            'A' => [$widthA, $heightA],
            'B' => [$widthB, $heightB]
        ];

        // Si las dimensiones son exactamente iguales
        if ($widthA == $widthB && $heightA == $heightB) {
            $coincidencias += 2;
            $debugInfo['dimensiones']['puntos'] = 2;
            $debugInfo['dimensiones']['tipo'] = 'idénticas';
        } 
        // Si tienen la misma relación de aspecto (proporción) y tamaños similares
        elseif (abs($ratioA - $ratioB) < 0.05 && max($areaA, $areaB) > 0) {
            $relacion = min($areaA, $areaB) / max($areaA, $areaB);
            if ($relacion > 0.9) {
                $coincidencias += 1.5; // Las dimensiones son muy similares
                $debugInfo['dimensiones']['puntos'] = 1.5;
                $debugInfo['dimensiones']['tipo'] = 'muy_similares';
            } elseif ($relacion > 0.7) {
                $coincidencias += 1; // Las dimensiones son algo similares
                $debugInfo['dimensiones']['puntos'] = 1;
                $debugInfo['dimensiones']['tipo'] = 'similares';
            } else {
                $debugInfo['dimensiones']['puntos'] = 0;
                $debugInfo['dimensiones']['tipo'] = 'diferente_tamaño';
            }
        } else {
            $debugInfo['dimensiones']['puntos'] = 0;
            $debugInfo['dimensiones']['tipo'] = 'diferentes';
        }
    }

    // Fecha y hora (peso 3) - muy útil para fotos originales
    if (isset($metadatosA['datetime']) && isset($metadatosB['datetime']) &&
        !empty($metadatosA['datetime']) && !empty($metadatosB['datetime'])) {
        
        $totalComparaciones += 3;
        $ponderacion += 3;

        $fechaA = $metadatosA['datetime'];
        $fechaB = $metadatosB['datetime'];
        
        $debugInfo['datetime'] = [
            'A' => $fechaA,
            'B' => $fechaB
        ];

        // Comprobar coincidencia exacta
        if ($fechaA === $fechaB) {
            $coincidencias += 3;
            $debugInfo['datetime']['puntos'] = 3;
            $debugInfo['datetime']['tipo'] = 'exactas';
        } 
        // Comprobar si las fechas están muy cercanas (dentro de 10 segundos)
        else {
            try {
                $dateA = new \DateTime($fechaA);
                $dateB = new \DateTime($fechaB);
                $diff = abs($dateA->getTimestamp() - $dateB->getTimestamp());
                
                if ($diff <= 10) { // 10 segundos o menos
                    $coincidencias += 2.5;
                    $debugInfo['datetime']['puntos'] = 2.5;
                    $debugInfo['datetime']['tipo'] = 'muy_cercanas';
                    $debugInfo['datetime']['diff_segundos'] = $diff;
                } elseif ($diff <= 60) { // 1 minuto o menos
                    $coincidencias += 2;
                    $debugInfo['datetime']['puntos'] = 2;
                    $debugInfo['datetime']['tipo'] = 'cercanas';
                    $debugInfo['datetime']['diff_segundos'] = $diff;
                } elseif ($diff <= 300) { // 5 minutos o menos
                    $coincidencias += 1;
                    $debugInfo['datetime']['puntos'] = 1;
                    $debugInfo['datetime']['tipo'] = 'próximas';
                    $debugInfo['datetime']['diff_segundos'] = $diff;
                } else {
                    $debugInfo['datetime']['puntos'] = 0;
                    $debugInfo['datetime']['tipo'] = 'diferentes';
                    $debugInfo['datetime']['diff_segundos'] = $diff;
                }
            } catch (\Exception $e) {
                $debugInfo['datetime']['error'] = 'No se pudieron parsear las fechas';
                $debugInfo['datetime']['puntos'] = 0;
            }
        }
    }

    // Marca y modelo de cámara (peso 2)
    if (isset($metadatosA['make']) && isset($metadatosB['make']) &&
        isset($metadatosA['model']) && isset($metadatosB['model']) &&
        !empty($metadatosA['make']) && !empty($metadatosB['make'])) {
        
        $totalComparaciones += 2;
        $ponderacion += 2;

        $makeA = strtolower(trim($metadatosA['make']));
        $makeB = strtolower(trim($metadatosB['make']));
        $modelA = strtolower(trim($metadatosA['model']));
        $modelB = strtolower(trim($metadatosB['model']));
        
        $debugInfo['camara'] = [
            'makeA' => $makeA,
            'makeB' => $makeB,
            'modelA' => $modelA,
            'modelB' => $modelB
        ];

        // Si coinciden tanto marca como modelo
        if ($makeA === $makeB && $modelA === $modelB) {
            $coincidencias += 2;
            $debugInfo['camara']['puntos'] = 2;
            $debugInfo['camara']['tipo'] = 'marca_modelo_iguales';
        }
        // Si solo coincide la marca
        elseif ($makeA === $makeB) {
            $coincidencias += 1;
            $debugInfo['camara']['puntos'] = 1;
            $debugInfo['camara']['tipo'] = 'marca_igual';
        } else {
            $debugInfo['camara']['puntos'] = 0;
            $debugInfo['camara']['tipo'] = 'diferentes';
        }
    }

    // Comparar tipo MIME y extensión (peso 1)
    if (isset($metadatosA['mime_type']) && isset($metadatosB['mime_type'])) {
        $totalComparaciones += 1;
        $ponderacion += 1;

        $mimeA = strtolower($metadatosA['mime_type']);
        $mimeB = strtolower($metadatosB['mime_type']);
        
        $mimeBaseA = explode('/', $mimeA)[0];
        $mimeBaseB = explode('/', $mimeB)[0];
        
        $debugInfo['mime'] = [
            'A' => $mimeA,
            'B' => $mimeB
        ];

        // Si el MIME es exactamente el mismo
        if ($mimeA === $mimeB) {
            $coincidencias += 1;
            $debugInfo['mime']['puntos'] = 1;
            $debugInfo['mime']['tipo'] = 'igual';
        }
        // Si al menos el tipo base es el mismo (image, video, etc)
        elseif ($mimeBaseA === $mimeBaseB) {
            $coincidencias += 0.5;
            $debugInfo['mime']['puntos'] = 0.5;
            $debugInfo['mime']['tipo'] = 'base_igual';
        } else {
            $debugInfo['mime']['puntos'] = 0;
            $debugInfo['mime']['tipo'] = 'diferentes';
        }
    }

    // Comparar nombres de archivo (peso 2)
    if (isset($metadatosA['nombre_original']) && isset($metadatosB['nombre_original']) &&
        !empty($metadatosA['nombre_original']) && !empty($metadatosB['nombre_original'])) {
        
        $totalComparaciones += 2;
        $ponderacion += 2;

        $nombreA = pathinfo($metadatosA['nombre_original'], PATHINFO_FILENAME);
        $nombreB = pathinfo($metadatosB['nombre_original'], PATHINFO_FILENAME);
        
        $debugInfo['nombre_archivo'] = [
            'A' => $nombreA,
            'B' => $nombreB
        ];

        // Si los nombres son idénticos
        if ($nombreA === $nombreB) {
            $coincidencias += 2;
            $debugInfo['nombre_archivo']['puntos'] = 2;
            $debugInfo['nombre_archivo']['tipo'] = 'identicos';
        } else {
            // Calcular similitud entre los nombres
            similar_text($nombreA, $nombreB, $porcentajeSimilitud);
            $debugInfo['nombre_archivo']['similitud'] = $porcentajeSimilitud;
            
            if ($porcentajeSimilitud > 90) {
                $coincidencias += 1.5;
                $debugInfo['nombre_archivo']['puntos'] = 1.5;
                $debugInfo['nombre_archivo']['tipo'] = 'muy_similares';
            } elseif ($porcentajeSimilitud > 75) {
                $coincidencias += 1;
                $debugInfo['nombre_archivo']['puntos'] = 1;
                $debugInfo['nombre_archivo']['tipo'] = 'similares';
            } elseif ($porcentajeSimilitud > 60) {
                $coincidencias += 0.5;
                $debugInfo['nombre_archivo']['puntos'] = 0.5;
                $debugInfo['nombre_archivo']['tipo'] = 'algo_similares';
            } else {
                $debugInfo['nombre_archivo']['puntos'] = 0;
                $debugInfo['nombre_archivo']['tipo'] = 'diferentes';
            }
        }
    }

    // Tamaño del archivo (peso 1)
    if (isset($metadatosA['tamaño']) && isset($metadatosB['tamaño']) &&
        is_numeric($metadatosA['tamaño']) && is_numeric($metadatosB['tamaño']) && 
        $metadatosA['tamaño'] > 0 && $metadatosB['tamaño'] > 0) {
        
        $totalComparaciones += 1;
        $ponderacion += 1;

        $tamañoA = (float) $metadatosA['tamaño'];
        $tamañoB = (float) $metadatosB['tamaño'];
        
        $debugInfo['tamaño'] = [
            'A' => $tamañoA,
            'B' => $tamañoB
        ];

        // Calcular diferencia de tamaño en porcentaje
        $maxTamaño = max($tamañoA, $tamañoB);
        $minTamaño = min($tamañoA, $tamañoB);
        $diferenciaPorcentaje = 100 - (($minTamaño / $maxTamaño) * 100);
        
        $debugInfo['tamaño']['diferencia'] = $diferenciaPorcentaje . '%';

        // Puntuar según la diferencia de tamaño
        if ($diferenciaPorcentaje < 1) {
            $coincidencias += 1;
            $debugInfo['tamaño']['puntos'] = 1;
            $debugInfo['tamaño']['tipo'] = 'identicos';
        } elseif ($diferenciaPorcentaje < 5) {
            $coincidencias += 0.8;
            $debugInfo['tamaño']['puntos'] = 0.8;
            $debugInfo['tamaño']['tipo'] = 'muy_similares';
        } elseif ($diferenciaPorcentaje < 15) {
            $coincidencias += 0.5;
            $debugInfo['tamaño']['puntos'] = 0.5;
            $debugInfo['tamaño']['tipo'] = 'similares';
        } elseif ($diferenciaPorcentaje < 30) {
            $coincidencias += 0.2;
            $debugInfo['tamaño']['puntos'] = 0.2;
            $debugInfo['tamaño']['tipo'] = 'algo_similares';
        } else {
            $debugInfo['tamaño']['puntos'] = 0;
            $debugInfo['tamaño']['tipo'] = 'diferentes';
        }
    }

    // Si no hay suficientes comparaciones, consideramos que no hay suficientes datos
    if ($totalComparaciones < 3 || $ponderacion < 2) {
        \Log::debug("No hay suficientes comparaciones: {$totalComparaciones}, ponderación: {$ponderacion}");
        return [
            'es_duplicado' => false,
            'similaridad' => 0,
            'debug' => [
                'comparaciones' => $totalComparaciones,
                'ponderacion' => $ponderacion,
                'mensaje' => 'Datos insuficientes para comparación'
            ]
        ];
    }

    // Calcular similitud ponderada
    $similitud = $ponderacion > 0 ? ($coincidencias / $ponderacion) * 100 : 0;
    
    // Ajustar umbral de detección a 60%
    $esDuplicado = $similitud > 60;
    
    $resultado = [
        'es_duplicado' => $esDuplicado,
        'similaridad' => round($similitud, 1),
        'debug' => $debugInfo + [
            'coincidencias' => $coincidencias,
            'ponderacion' => $ponderacion,
            'umbral' => 60
        ]
    ];
    
    if ($esDuplicado) {
        \Log::info("Duplicado detectado con similitud: {$similitud}%", [
            'coincidencias' => $coincidencias,
            'ponderacion' => $ponderacion,
            'total_comparaciones' => $totalComparaciones
        ]);
    }

    return $resultado;
}

    /**
     * Marca un comprobante como verificado manualmente
     */
    public function marcarComoUnico(Request $request, $id)
    {
        try {
            $reserva = Reserva::findOrFail($id);

            // Obtener los metadatos actuales
            $metadatos = json_decode($reserva->comprobante_metadata, true);

            // Si los metadatos son un array de archivos, los procesamos uno a uno
            if (is_array($metadatos) && isset($metadatos[0]) && is_array($metadatos[0])) {
                foreach ($metadatos as $index => $metadatoArchivo) {
                    $metadatos[$index]['verificado_manualmente'] = true;
                    $metadatos[$index]['verificado_por'] = auth()->user()->name ?? 'admin';
                    $metadatos[$index]['verificado_fecha'] = now()->toDateTimeString();

                    // Quitar la marca de posible duplicado si existe
                    if (isset($metadatos[$index]['posible_duplicado'])) {
                        unset($metadatos[$index]['posible_duplicado']);
                    }
                }
            } else {
                // En caso de que sea un objeto simple
                $metadatos['verificado_manualmente'] = true;
                $metadatos['verificado_por'] = auth()->user()->name ?? 'admin';
                $metadatos['verificado_fecha'] = now()->toDateTimeString();

                // Quitar la marca de posible duplicado si existe
                if (isset($metadatos['posible_duplicado'])) {
                    unset($metadatos['posible_duplicado']);
                }
            }

            // Actualizar los metadatos en la base de datos
            $reserva->comprobante_metadata = json_encode($metadatos);
            $reserva->save();

            Log::info('Comprobante marcado como único', [
                'reserva_id' => $id,
                'usuario' => auth()->user()->name ?? 'admin'
            ]);

            return redirect()->back()->with('success', 'Comprobante marcado como verificado manualmente.');
        } catch (\Exception $e) {
            Log::error('Error al marcar comprobante como único', [
                'reserva_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Error al procesar la solicitud: ' . $e->getMessage());
        }
    }

    /**
     * Verifica si un comprobante es único basado en sus metadatos y características visuales
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @return array Arreglo con la información de si es único y los metadatos
     */
    private function verificarComprobanteUnico($file)
    {
        // Extraer metadatos EXIF si está disponible
        $metadatos = [];
        if (function_exists('exif_read_data')) {
            try {
                $exif = @exif_read_data($file->getPathname(), 'ANY_TAG', true);
                if ($exif !== false) {
                    // Extraer información relevante
                    if (isset($exif['COMPUTED'])) {
                        $metadatos['hash'] = md5(json_encode($exif['COMPUTED']));
                    }
                    if (isset($exif['IFD0'])) {
                        $metadatos['make'] = $exif['IFD0']['Make'] ?? null;
                        $metadatos['model'] = $exif['IFD0']['Model'] ?? null;
                    }
                    if (isset($exif['EXIF'])) {
                        $metadatos['datetime'] = $exif['EXIF']['DateTimeOriginal'] ?? null;
                        $metadatos['dimensions'] = [
                            'width' => $exif['EXIF']['ExifImageWidth'] ?? null,
                            'height' => $exif['EXIF']['ExifImageLength'] ?? null
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error al leer metadatos EXIF', [
                    'filename' => $file->getClientOriginalName(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Generar hashes perceptuales que son resistentes a cambios de formato
        try {
            // Crear un hash perceptual usando GD (disponible en la mayoría de instalaciones PHP)
            $image = imagecreatefromstring(file_get_contents($file->getPathname()));
            if ($image !== false) {
                // Reducir la imagen a 8x8 píxeles (64 bits para el hash)
                $smallImage = imagecreatetruecolor(8, 8);

                // Redimensionar sin suavizado para preservar las diferencias
                imagecopyresized(
                    $smallImage,
                    $image,
                    0,
                    0,
                    0,
                    0,
                    8,
                    8,
                    imagesx($image),
                    imagesy($image)
                );

                // Convertir a escala de grises
                $totalGrises = 0;
                $pixeles = [];

                for ($y = 0; $y < 8; $y++) {
                    for ($x = 0; $x < 8; $x++) {
                        $colorIndex = imagecolorat($smallImage, $x, $y);
                        $color = imagecolorsforindex($smallImage, $colorIndex);

                        // Convertir a escala de grises (promedio de RGB)
                        $gris = (int)(($color['red'] + $color['green'] + $color['blue']) / 3);
                        $pixeles[] = $gris;
                        $totalGrises += $gris;
                    }
                }

                // Calcular promedio para el umbral
                $promedio = $totalGrises / 64;

                // Generar hash binario basado en si el pixel es mayor o menor que el promedio
                $hash = '';
                foreach ($pixeles as $pixel) {
                    $hash .= ($pixel >= $promedio) ? '1' : '0';
                }

                // Convertir hash binario a hexadecimal
                $hashHex = '';
                for ($i = 0; $i < 64; $i += 4) {
                    $nibble = substr($hash, $i, 4);
                    $hashHex .= dechex(bindec($nibble));
                }

                $metadatos['perceptual_hash'] = $hashHex;

                // Histograma de colores simplificado (dividido en 8 segmentos)
                $histograma = [0, 0, 0, 0, 0, 0, 0, 0];

                // Usar la imagen original para el histograma
                for ($y = 0; $y < imagesy($image); $y++) {
                    for ($x = 0; $x < imagesx($image); $x++) {
                        $colorIndex = imagecolorat($image, $x, $y);
                        $color = imagecolorsforindex($image, $colorIndex);

                        // Calcular brillo (0-255)
                        $brillo = (int)(($color['red'] + $color['green'] + $color['blue']) / 3);

                        // Asignar a uno de los 8 segmentos
                        $segmento = min(7, (int)($brillo / 32));
                        $histograma[$segmento]++;
                    }
                }

                // Normalizar histograma (convertir a porcentajes)
                $totalPixeles = imagesx($image) * imagesy($image);
                for ($i = 0; $i < 8; $i++) {
                    $histograma[$i] = round(($histograma[$i] / $totalPixeles) * 100, 2);
                }

                $metadatos['histograma'] = $histograma;

                // Liberar memoria
                imagedestroy($smallImage);
                imagedestroy($image);
            }
        } catch (\Exception $e) {
            Log::warning('Error al generar hash perceptual', [
                'filename' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
        }

        // Si no hay metadatos EXIF, generamos hash del contenido
        if (empty($metadatos) || (!isset($metadatos['hash']) && !isset($metadatos['datetime']) && !isset($metadatos['perceptual_hash']))) {
            $metadatos['contenido_hash'] = md5_file($file->getPathname());
            $metadatos['tamaño'] = $file->getSize();
            $metadatos['nombre_original'] = $file->getClientOriginalName();
        }

        // Añadir información básica de la imagen
        $metadatos['mime_type'] = $file->getMimeType();
        $metadatos['extension'] = $file->getClientOriginalExtension();
        $metadatos['fecha_subida'] = now()->toDateTimeString();

        // Buscar si existe un comprobante con metadatos similares
        $existeComprobante = false;
        $similaridad = 0;
        $reservaCoincidente = null;

        // Buscar en reservas existentes
        $reservas = Reserva::whereNotNull('comprobante_metadata')->get();
        foreach ($reservas as $reserva) {
            if (empty($reserva->comprobante_metadata)) {
                continue;
            }

            $metadatosExistentes = json_decode($reserva->comprobante_metadata, true);
            if (!is_array($metadatosExistentes)) {
                continue;
            }

            // Si los metadatos son un array de archivos, los procesamos uno a uno
            if (isset($metadatosExistentes[0]) && is_array($metadatosExistentes[0])) {
                foreach ($metadatosExistentes as $metadatoExistente) {
                    $coincidenciasArchivo = $this->compararMetadatos($metadatos, $metadatoExistente);

                    if ($coincidenciasArchivo['es_duplicado']) {
                        $existeComprobante = true;
                        $similaridad = max($similaridad, $coincidenciasArchivo['similaridad']);
                        $reservaCoincidente = $reserva;
                        break;
                    }
                }
            } else {
                // Caso de un solo archivo
                $coincidenciasArchivo = $this->compararMetadatos($metadatos, $metadatosExistentes);

                if ($coincidenciasArchivo['es_duplicado']) {
                    $existeComprobante = true;
                    $similaridad = $coincidenciasArchivo['similaridad'];
                    $reservaCoincidente = $reserva;
                }
            }

            if ($existeComprobante) {
                break;
            }
        }

        if ($existeComprobante) {
            Log::warning('Posible comprobante duplicado detectado', [
                'nueva_imagen' => $file->getClientOriginalName(),
                'reserva_existente_id' => $reservaCoincidente ? $reservaCoincidente->id : null,
                'similaridad' => $similaridad . '%'
            ]);
        }

        return [
            'es_unico' => !$existeComprobante,
            'metadatos' => $metadatos,
            'similaridad' => $similaridad,
            'reserva_coincidente' => $reservaCoincidente
        ];
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

    public function truncateClientes()
    {
        try {
            // Iniciar transacción
            DB::beginTransaction();

            // Ejecutar truncate
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('reservas')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Confirmar transacción
            DB::commit();

            // Registrar acción en el log
            Log::warning('Un administrador ha eliminado todos los registros de clientes');

            return redirect()->route('reservas.index')
                ->with('success', 'Todos los registros de clientes han sido eliminados correctamente.');
        } catch (\Exception $e) {
            // Revertir transacción
            DB::rollBack();

            // Registrar error
            Log::error('Error al truncar tabla clientes: ' . $e->getMessage());

            return redirect()->route('reservas.index')
                ->with('error', 'Ha ocurrido un error al intentar eliminar los registros: ' . $e->getMessage());
        }
    }
}
