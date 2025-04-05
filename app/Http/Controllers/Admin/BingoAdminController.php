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
        $reservas = $query->orderBy('orden_bingo', 'asc')->paginate(1000);

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
                'estado' => 'cerrado'
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

            // Actualizar el bingo para marcarlo como oculto y cerrado
            $bingo->update([
                'visible' => 0,
                'estado' => 'cerrado'
            ]);

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
    set_time_limit(120); // 2 minutos
{
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
        
        // Parte 2: Duplicados por metadatos
        $duplicadosPorMetadatos = $this->verificarDuplicadosInterno($bingoId);
        
        // Añadir los duplicados por metadatos a la lista general
        foreach ($duplicadosPorMetadatos as $grupo) {
            $duplicados[] = $grupo;
        }
        
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

    
        // Iterar sobre cada reserva
        foreach ($reservas as $reserva) {
            // Omitir si ya ha sido procesada
            if (in_array($reserva->id, $procesados)) {
                continue;
            }
    
            $metadatosA = json_decode($reserva->comprobante_metadata, true);
            if (!is_array($metadatosA)) {
                continue;
            }
    
            $grupo = [];
            $similitudesEncontradas = false;
    
            // Comparar con las demás reservas del mismo bingo
            foreach ($reservas as $otraReserva) {
                // Saltar si es la misma reserva, ya fue procesada, o no pertenece al mismo bingo
                if ($reserva->id == $otraReserva->id || 
                    in_array($otraReserva->id, $procesados)) {
                    continue;
                }
    
                $metadatosB = json_decode($otraReserva->comprobante_metadata, true);
                if (!is_array($metadatosB)) {
                    continue;
                }
    
                // Comparar metadatos
                $resultado = $this->compararMetadatos($metadatosA, $metadatosB);
    
                // Si son similares
                if ($resultado['es_duplicado'] && $resultado['similaridad'] > 75) {
                    // Si es el primer duplicado, añadir la reserva original
                    if (empty($grupo)) {
                        $reserva->similaridad = 100;
                        $grupo[] = $reserva;
                        $procesados[] = $reserva->id;
                    }
    
                    // Añadir la reserva con su similaridad
                    $otraReserva->similaridad = $resultado['similaridad'];
                    $grupo[] = $otraReserva;
                    $procesados[] = $otraReserva->id;
    
                    $similitudesEncontradas = true;
                }
            }
    
            // Añadir grupo si tiene más de una reserva
            if (count($grupo) > 1) {
                $posiblesDuplicados[] = $grupo;
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
    /**
     * Compara dos conjuntos de metadatos para determinar si son similares
     * 
     * @param array $metadatosA
     * @param array $metadatosB
     * @return array
     */
    private function compararMetadatos($metadatosA, $metadatosB)
    {
        $coincidencias = 0;
        $totalComparaciones = 0;
        $ponderacion = 0;

        // Si tiene marca de verificación manual, lo respetamos
        if (isset($metadatosB['verificado_manualmente']) && $metadatosB['verificado_manualmente']) {
            \Log::debug("Metadatos B verificados manualmente, ignorando comparación");
            return [
                'es_duplicado' => false,
                'similaridad' => 0
            ];
        }

        // Hash perceptual (alta prioridad - peso 3)
        if (isset($metadatosA['perceptual_hash']) && isset($metadatosB['perceptual_hash'])) {
            $totalComparaciones += 3;
            $ponderacion += 3;

            // Calcular distancia Hamming entre los hashes perceptuales
            $hashA = $metadatosA['perceptual_hash'];
            $hashB = $metadatosB['perceptual_hash'];

            \Log::debug("Comparando hashes perceptuales", [
                'hashA' => $hashA,
                'hashB' => $hashB
            ]);

            // Convertir a binario para comparar bit a bit
            $hashBinA = '';
            $hashBinB = '';

            for ($i = 0; $i < strlen($hashA); $i++) {
                $binA = str_pad(decbin(hexdec($hashA[$i])), 4, '0', STR_PAD_LEFT);
                $binB = str_pad(decbin(hexdec($hashB[$i])), 4, '0', STR_PAD_LEFT);
                $hashBinA .= $binA;
                $hashBinB .= $binB;
            }

            // Contar bits diferentes (distancia Hamming)
            $distancia = 0;
            for ($i = 0; $i < strlen($hashBinA); $i++) {
                if ($hashBinA[$i] !== $hashBinB[$i]) {
                    $distancia++;
                }
            }

            // Calcular similitud en porcentaje (0 distancia = 100% similaridad)
            $maxDistancia = strlen($hashBinA); // Máxima distancia posible
            $similitudHash = 100 - (($distancia / $maxDistancia) * 100);

            \Log::debug("Similitud de hash perceptual: {$similitudHash}%", [
                'distancia' => $distancia,
                'maxDistancia' => $maxDistancia
            ]);

            // Si la similitud es mayor a 90%, consideramos alta coincidencia
            if ($similitudHash > 90) {
                $coincidencias += 3;
                \Log::debug("Alta coincidencia de hash perceptual (>90%)");
            } elseif ($similitudHash > 80) {
                $coincidencias += 2;
                \Log::debug("Media coincidencia de hash perceptual (>80%)");
            } elseif ($similitudHash > 70) {
                $coincidencias += 1;
                \Log::debug("Baja coincidencia de hash perceptual (>70%)");
            }

            // Si la coincidencia de hash perceptual es muy alta, es probable que sea la misma imagen
            if ($similitudHash > 95) {
                \Log::info("Coincidencia muy alta de hash perceptual: {$similitudHash}%");
                return [
                    'es_duplicado' => true,
                    'similaridad' => $similitudHash
                ];
            }
        }

        // Histograma de colores (peso 2)
        if (isset($metadatosA['histograma']) && isset($metadatosB['histograma'])) {
            $totalComparaciones += 2;
            $ponderacion += 2;

            $histogramaA = $metadatosA['histograma'];
            $histogramaB = $metadatosB['histograma'];

            \Log::debug("Comparando histogramas de colores");

            // Calcular distancia entre histogramas (diferencia cuadrática media)
            $sumaDiferencias = 0;
            for ($i = 0; $i < count($histogramaA); $i++) {
                $diferencia = $histogramaA[$i] - $histogramaB[$i];
                $sumaDiferencias += $diferencia * $diferencia;
            }

            $distanciaHistograma = sqrt($sumaDiferencias / count($histogramaA));

            // Convertir distancia a similitud (menor distancia = mayor similitud)
            $maxDistancia = 100; // Valor teórico máximo si los histogramas son completamente diferentes
            $similitudHistograma = 100 - ($distanciaHistograma * 100 / $maxDistancia);

            \Log::debug("Similitud de histograma: {$similitudHistograma}%");

            // Añadir a coincidencias según nivel de similitud
            if ($similitudHistograma > 90) {
                $coincidencias += 2;
                \Log::debug("Alta coincidencia de histograma (>90%)");
            } elseif ($similitudHistograma > 75) {
                $coincidencias += 1;
                \Log::debug("Media coincidencia de histograma (>75%)");
            }
        }

        // Si hay hash de contenido en ambos (peso 3)
        if (isset($metadatosA['contenido_hash']) && isset($metadatosB['contenido_hash'])) {
            $totalComparaciones += 3;
            $ponderacion += 3;

            \Log::debug("Comparando hashes de contenido", [
                'hashA' => $metadatosA['contenido_hash'],
                'hashB' => $metadatosB['contenido_hash']
            ]);

            if ($metadatosA['contenido_hash'] === $metadatosB['contenido_hash']) {
                $coincidencias += 3;
                \Log::info("Coincidencia exacta de hash de contenido");

                // Si el hash de contenido coincide exactamente, es definitivamente la misma imagen
                return [
                    'es_duplicado' => true,
                    'similaridad' => 100
                ];
            }
        }

        // Dimensiones de la imagen (peso 1)
        if (isset($metadatosA['dimensions']) && isset($metadatosB['dimensions'])) {
            $totalComparaciones += 1;
            $ponderacion += 1;

            \Log::debug("Comparando dimensiones de imagen", [
                'A' => $metadatosA['dimensions'],
                'B' => $metadatosB['dimensions']
            ]);

            // Si las dimensiones son iguales, es un indicio fuerte
            if (
                $metadatosA['dimensions']['width'] == $metadatosB['dimensions']['width'] &&
                $metadatosA['dimensions']['height'] == $metadatosB['dimensions']['height']
            ) {
                $coincidencias += 1;
                \Log::debug("Dimensiones idénticas");
            }
        }

        // Fecha y hora (peso 2) - muy útil para fotos originales
        if (
            isset($metadatosA['datetime']) && isset($metadatosB['datetime']) &&
            !empty($metadatosA['datetime']) && !empty($metadatosB['datetime'])
        ) {
            $totalComparaciones += 2;
            $ponderacion += 2;

            \Log::debug("Comparando fechas de imagen", [
                'A' => $metadatosA['datetime'],
                'B' => $metadatosB['datetime']
            ]);

            if ($metadatosA['datetime'] === $metadatosB['datetime']) {
                $coincidencias += 2;
                \Log::debug("Fechas idénticas");
            }
        }

        // Marca y modelo de cámara (peso 1)
        if (
            isset($metadatosA['make']) && isset($metadatosB['make']) &&
            isset($metadatosA['model']) && isset($metadatosB['model'])
        ) {
            $totalComparaciones += 1;
            $ponderacion += 1;

            \Log::debug("Comparando marca y modelo", [
                'makeA' => $metadatosA['make'],
                'makeB' => $metadatosB['make'],
                'modelA' => $metadatosA['model'],
                'modelB' => $metadatosB['model']
            ]);

            if ($metadatosA['make'] === $metadatosB['make'] && $metadatosA['model'] === $metadatosB['model']) {
                $coincidencias += 1;
                \Log::debug("Marca y modelo idénticos");
            }
        }

        // Comparar tipo MIME y extensión (peso 2)
        if (
            isset($metadatosA['mime_type']) && isset($metadatosB['mime_type']) &&
            isset($metadatosA['extension']) && isset($metadatosB['extension'])
        ) {

            $totalComparaciones += 2;
            $ponderacion += 2;

            \Log::debug("Comparando MIME y extensión", [
                'mimeA' => $metadatosA['mime_type'],
                'mimeB' => $metadatosB['mime_type'],
                'extensionA' => $metadatosA['extension'],
                'extensionB' => $metadatosB['extension']
            ]);

            // Si el tipo MIME base es el mismo (ej: ambos son image/*)
            $mimeBaseA = explode('/', $metadatosA['mime_type'])[0];
            $mimeBaseB = explode('/', $metadatosB['mime_type'])[0];

            if ($mimeBaseA === $mimeBaseB) {
                $coincidencias += 1;
                \Log::debug("Mismo tipo base de MIME: {$mimeBaseA}");
            }

            // Si ambos son imágenes, agregar otra coincidencia
            if ($mimeBaseA === 'image' && $mimeBaseB === 'image') {
                $coincidencias += 1;
                \Log::debug("Ambos son imágenes");
            }
        }

        // Comparar nombres de archivo (peso 1)
        if (isset($metadatosA['nombre_original']) && isset($metadatosB['nombre_original'])) {
            $totalComparaciones += 1;
            $ponderacion += 1;

            $nombreA = pathinfo($metadatosA['nombre_original'], PATHINFO_FILENAME);
            $nombreB = pathinfo($metadatosB['nombre_original'], PATHINFO_FILENAME);

            \Log::debug("Comparando nombres de archivo", [
                'nombreA' => $nombreA,
                'nombreB' => $nombreB
            ]);

            // Si los nombres son idénticos o muy similares
            similar_text($nombreA, $nombreB, $porcentajeSimilitud);

            if ($porcentajeSimilitud > 80) {
                $coincidencias += 1;
                \Log::debug("Nombres muy similares: {$porcentajeSimilitud}%");
            }
        }

        // Tamaño del archivo (peso 1)
        if (isset($metadatosA['tamaño']) && isset($metadatosB['tamaño'])) {
            $totalComparaciones += 1;
            $ponderacion += 1;

            \Log::debug("Comparando tamaños de archivo", [
                'A' => $metadatosA['tamaño'],
                'B' => $metadatosB['tamaño']
            ]);

            // Calcular diferencia de tamaño en porcentaje
            $maxTamaño = max($metadatosA['tamaño'], $metadatosB['tamaño']);
            $minTamaño = min($metadatosA['tamaño'], $metadatosB['tamaño']);

            if ($maxTamaño > 0) {
                $diferenciaPorcentaje = 100 - (($minTamaño / $maxTamaño) * 100);

                \Log::debug("Diferencia de tamaño: {$diferenciaPorcentaje}%");

                // Ser más permisivo con las diferencias de tamaño
                if ($diferenciaPorcentaje < 10) {
                    $coincidencias += 1;
                    \Log::debug("Tamaños muy similares (<10% diferencia)");
                } else if ($diferenciaPorcentaje < 50) {
                    $coincidencias += 0.5;
                    \Log::debug("Tamaños moderadamente diferentes (<50% diferencia)");
                } else if ($diferenciaPorcentaje < 90) {
                    $coincidencias += 0.2;
                    \Log::debug("Tamaños bastante diferentes pero aún considerados (<90% diferencia)");
                }
            }
        }

        // Si no hay suficientes comparaciones, consideramos que no hay suficientes datos
        if ($totalComparaciones < 1) {  // Reducido de 3 a 1
            \Log::debug("No hay suficientes comparaciones: {$totalComparaciones}");
            return [
                'es_duplicado' => false,
                'similaridad' => 0
            ];
        }

        // Calcular similitud ponderada
        $similitud = $ponderacion > 0 ? ($coincidencias / $ponderacion) * 100 : 0;

        \Log::debug("Similitud calculada: {$similitud}%", [
            'coincidencias' => $coincidencias,
            'ponderacion' => $ponderacion,
            'totalComparaciones' => $totalComparaciones
        ]);

        // Reducimos el umbral de 75% a 50%
        $esDuplicado = $similitud > 50;

        if ($esDuplicado) {
            \Log::info("Duplicado detectado con similitud: {$similitud}%");
        }

        return [
            'es_duplicado' => $esDuplicado,
            'similaridad' => round($similitud, 1)
        ];
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
