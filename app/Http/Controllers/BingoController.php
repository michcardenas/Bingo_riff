<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Reserva;
use Illuminate\Support\Facades\Log;
use App\Models\Bingo;
use Illuminate\Support\Facades\Storage;

class BingoController extends Controller
{
    public function store(Request $request)
    {
        // Log inicial
        Log::info('Iniciando proceso de reserva', ['request' => $request->all()]);

        try {
            // 1. Validar los datos, permitiendo múltiples imágenes
            $validated = $request->validate([
                'bingo_id'      => 'required|exists:bingos,id',
                'cartones'      => 'required|integer|min:1',
                'nombre'        => 'required|string|max:255',
                'celular'       => 'required|string|max:20',
                'comprobante'   => 'required',       // Asegura que al menos se suba 1 archivo
                'comprobante.*' => 'image|max:5120', // Cada archivo debe ser una imagen de máx 5 MB
            ]);

            Log::info('Datos validados correctamente', ['validated' => $validated]);

            // 2. Obtener el precio del cartón desde la base de datos como decimal
            $bingo = Bingo::findOrFail($validated['bingo_id']);
            $precioCarton = (float)$bingo->precio;
            $totalPagar = $validated['cartones'] * $precioCarton;

            Log::info('Información del bingo y cálculo de precios', [
                'bingo_id' => $bingo->id,
                'bingo_nombre' => $bingo->nombre,
                'precio_carton' => $precioCarton,
                'cantidad_cartones' => $validated['cartones'],
                'total_pagar' => $totalPagar
            ]);

            // 3. Guardar las imágenes en storage y recolectar sus rutas usando el disco "public"
            $rutasArchivos = [];
            if ($request->hasFile('comprobante')) {
                Log::info('Procesando archivos adjuntos', [
                    'cantidad_archivos' => count($request->file('comprobante'))
                ]);

                foreach ($request->file('comprobante') as $index => $file) {
                    try {
                        // Guardar con un nombre único para evitar colisiones
                        $filename = time() . '_' . $file->getClientOriginalName();
                        // Guarda en "storage/app/public/comprobantes" y retorna "comprobantes/archivo.png"
                        $ruta = $file->storeAs('comprobantes', $filename, 'public');
                        $rutasArchivos[] = $ruta;

                        Log::info('Archivo guardado correctamente', [
                            'index' => $index,
                            'filename' => $filename,
                            'ruta' => $ruta
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Error al guardar archivo', [
                            'index' => $index,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        throw $e;
                    }
                }
            } else {
                Log::warning('No se encontraron archivos adjuntos en la solicitud');
            }

            // Convertir el array a JSON para almacenarlo en la BD
            $comprobanteStr = json_encode($rutasArchivos);
            Log::info('Rutas de archivos procesadas', [
                'rutas' => $rutasArchivos,
                'json' => $comprobanteStr
            ]);

            // 4. Asignar series automáticamente - usando nuestro método mejorado para evitar duplicados
            $series = [];
            $reservaCreada = null;

            try {
                DB::transaction(function () use ($validated, &$series, $totalPagar, $comprobanteStr, $bingo, &$reservaCreada) {
                    Log::info('Iniciando transacción DB');

                    $cantidad = $validated['cartones'];
                    // Usamos nuestro método mejorado que garantiza series únicas
                    $series = $this->asignarSeries($bingo->id, $cantidad);

                    Log::info('Series asignadas para los cartones', ['series' => $series]);

                    // 5. Crear la reserva, guardando también las series (como JSON)
                    $reservaData = [
                        'nombre'             => $validated['nombre'],
                        'celular'            => $validated['celular'],
                        'cantidad'           => $cantidad,
                        'comprobante'        => $comprobanteStr,
                        'total'              => $totalPagar,
                        'series'             => json_encode($series),
                        'estado'             => 'revision',
                        'numero_comprobante' => null,
                        'bingo_id'           => $bingo->id,
                    ];

                    Log::info('Datos para crear la reserva', $reservaData);

                    $reservaCreada = Reserva::create($reservaData);

                    Log::info('Reserva creada correctamente', ['reserva_id' => $reservaCreada->id]);
                });
            } catch (\Exception $e) {
                Log::error('Error en la transacción DB', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

            Log::info('Proceso de reserva completado exitosamente', [
                'reserva_id' => $reservaCreada ? $reservaCreada->id : null,
                'series' => $series
            ]);

            // 6. Redirigir a la vista "reservado" con mensaje de éxito
            return redirect()->route('reservado')
                ->with('success', '¡Reserva realizada correctamente!')
                ->with('series', $series)
                ->with('bingo', $bingo->nombre);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación', [
                'errors' => $e->errors(),
            ]);
            throw $e; // Re-lanzar para que Laravel maneje la respuesta
        } catch (\Exception $e) {
            Log::error('Error general en el proceso de reserva', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Puedes redirigir con un mensaje de error o relanzar la excepción
            return redirect()->back()
                ->withInput()
                ->with('error', 'Ocurrió un error al procesar tu reserva. Por favor, intenta nuevamente.');
        }
    }
        private function asignarSeries($bingoId, $cantidad)
    {
        $bingo = Bingo::findOrFail($bingoId);
        $seriesAsignadas = [];
        
        // Obtener todas las series ya asignadas para este bingo
        $seriesExistentes = $this->getSeriesAsignadas($bingoId);
        
        // Verificar si hay series liberadas disponibles
        if ($bingo->series_liberadas) {
            $seriesLiberadas = json_decode($bingo->series_liberadas, true) ?: [];
            
            // Filtrar series liberadas que no estén ya asignadas
            $seriesLiberadasDisponibles = array_filter($seriesLiberadas, function($serie) use ($seriesExistentes) {
                return !in_array($serie, $seriesExistentes);
            });
            
            // Tomar las series liberadas que necesitamos
            $seriesNecesarias = min($cantidad, count($seriesLiberadasDisponibles));
            
            for ($i = 0; $i < $seriesNecesarias; $i++) {
                $seriesAsignadas[] = array_shift($seriesLiberadasDisponibles);
            }
            
            // Actualizar el campo series_liberadas del bingo
            $seriesLiberadasRestantes = array_diff($seriesLiberadas, $seriesAsignadas);
            $bingo->series_liberadas = !empty($seriesLiberadasRestantes) ? json_encode(array_values($seriesLiberadasRestantes)) : null;
            $bingo->save();
            
            \Log::info("Series liberadas asignadas", [
                'bingo_id' => $bingoId,
                'series_asignadas' => $seriesAsignadas,
                'series_liberadas_restantes' => $seriesLiberadasRestantes
            ]);
        }
        
        // Si aún necesitamos más series, generar nuevas
        $seriesFaltantes = $cantidad - count($seriesAsignadas);
        
        if ($seriesFaltantes > 0) {
            // Encontrar el número más alto utilizado
            $maxNumero = 0;
            foreach ($seriesExistentes as $serie) {
                $numeroSerie = (int)$serie;
                if ($numeroSerie > $maxNumero) {
                    $maxNumero = $numeroSerie;
                }
            }
            
            // Generar nuevas series únicas consecutivas
            $nuevosNumeros = [];
            $numero = $maxNumero + 1;
            
            while (count($nuevosNumeros) < $seriesFaltantes) {
                $seriePadded = str_pad($numero, 4, '0', STR_PAD_LEFT);
                
                // Verificar si esta serie ya existe o ya fue asignada
                if (!in_array($seriePadded, $seriesExistentes) && !in_array($seriePadded, $seriesAsignadas)) {
                    $nuevosNumeros[] = $seriePadded;
                }
                
                $numero++;
            }
            
            $seriesAsignadas = array_merge($seriesAsignadas, $nuevosNumeros);
            
            \Log::info("Nuevas series generadas", [
                'bingo_id' => $bingoId,
                'nuevas_series' => $nuevosNumeros
            ]);
        }
        
        // Verificación final para evitar duplicados
        $verificacionFinal = array_unique($seriesAsignadas);
        if (count($verificacionFinal) != count($seriesAsignadas)) {
            \Log::warning("Se detectaron series duplicadas antes de la asignación final", [
                'bingo_id' => $bingoId,
                'series_con_duplicados' => $seriesAsignadas,
                'series_sin_duplicados' => $verificacionFinal
            ]);
            $seriesAsignadas = $verificacionFinal;
        }
        
        return $seriesAsignadas;
    }

    private function getSeriesAsignadas($bingoId)
    {
        // Obtener todas las reservas para este bingo que tienen series asignadas
        $reservas = Reserva::where('bingo_id', $bingoId)
                        ->whereNotNull('series')
                        ->get();
        
        $todasLasSeries = [];
        
        foreach ($reservas as $reserva) {
            $series = is_string($reserva->series) ? json_decode($reserva->series, true) : $reserva->series;
            if (is_array($series)) {
                $todasLasSeries = array_merge($todasLasSeries, $series);
            }
        }
        
        return $todasLasSeries;
    }

    public function reservas(Request $request, $id)
    {
        $bingo = Bingo::findOrFail($id);
        
        // Obtener estadísticas
        $reservas = Reserva::where('bingo_id', $id)->get();
        $totalParticipantes = $reservas->count();
        $totalCartones = $reservas->sum('cantidad');
        $totalAprobadas = $reservas->where('estado', 'aprobado')->count();
        $totalPendientes = $reservas->where('estado', 'revision')->count();
        
        return view('admin.bingos.reservas', compact(
            'bingo', 
            'totalParticipantes', 
            'totalCartones', 
            'totalAprobadas', 
            'totalPendientes'
        ));
    }
    
    /**
     * Mostrar tabla parcial de reservas filtradas
     */
    public function reservasTabla(Request $request, $id)
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
            $query->where(function($q) use ($serie) {
                $q->where('series', 'LIKE', '%"' . $serie . '"%')
                  ->orWhere('series', 'LIKE', '%' . $serie . '%');
            });
        }
        
        // Ordenar por fecha de creación descendente
        $reservas = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // Si es una solicitud AJAX, devolver solo la tabla
        if ($request->ajax()) {
            return view('admin.bingos.reservas-tabla', compact('reservas', 'bingo'));
        }
        
        // De lo contrario, redirigir a la vista completa
        return redirect()->route('bingos.reservas', $id);
    }
}


