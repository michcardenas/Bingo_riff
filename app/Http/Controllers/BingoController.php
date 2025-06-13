<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Reserva;
use Illuminate\Support\Facades\Log;
use App\Models\Bingo;
use Illuminate\Support\Facades\Storage;
use App\Models\Serie;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RechazadosExport;

class BingoController extends Controller
{
    public function store(Request $request)
    {
        Log::info('Iniciando proceso de reserva', ['request' => $request->all()]);
    
        try {
            // Validar datos
            $validated = $request->validate([
                'bingo_id'      => 'required|exists:bingos,id',
                'cartones'      => 'required|integer|min:1',
                'nombre'        => 'required|string|max:255',
                'celular'       => 'required|string|max:20',
                'comprobante'   => 'required',
                'comprobante.*' => 'image|max:5120',
            ]);
    
            Log::info('Datos validados correctamente', ['validated' => $validated]);
    
            $bingo = Bingo::findOrFail($validated['bingo_id']);
            $precioCarton = (float) $bingo->precio;
            $totalPagar = $validated['cartones'] * $precioCarton;
    
            $rutasArchivos = [];
            $metadatosArchivos = [];
            $hayDuplicados = false;
    
            // Subir comprobantes
            if ($request->hasFile('comprobante')) {
                foreach ($request->file('comprobante') as $index => $file) {
    
                    Log::info("Procesando archivo adjunto", [
                        'index' => $index,
                        'original_name' => $file->getClientOriginalName(),
                        'size' => $file->getSize()
                    ]);
    
                    // Verificación de duplicado
                    $verificacion = $this->verificarComprobanteUnico($file);
                    $metadatosArchivos[] = $verificacion['metadatos'];
    
                    if (!$verificacion['es_unico']) {
                        $hayDuplicados = true;
                        $metadatosArchivos[count($metadatosArchivos) - 1]['posible_duplicado'] = true;
                        $metadatosArchivos[count($metadatosArchivos) - 1]['reserva_coincidente_id'] = $verificacion['reserva_coincidente'] ? $verificacion['reserva_coincidente']->id : null;
                        $metadatosArchivos[count($metadatosArchivos) - 1]['similaridad'] = $verificacion['similaridad'];
    
                        Log::warning("Posible comprobante duplicado detectado", [
                            'archivo' => $file->getClientOriginalName(),
                            'similaridad' => $verificacion['similaridad'] . '%'
                        ]);
                    }
    
                  // Ruta de destino para comprobantes en producción
$pathProduccion = '/home/u861598707/domains/white-dragonfly-473649.hostingersite.com/public_html/comprobantes';

// Verificar si estamos en producción o local con base en el path base real
$isProduccion = strpos(base_path(), '/home/u861598707/domains/white-dragonfly-473649.hostingersite.com') !== false;

$destino = $isProduccion ? $pathProduccion : public_path('comprobantes');

Log::info("Destino para guardar imagen", [
    'isProduccion' => $isProduccion,
    'destino' => $destino
]);

$filename = time() . '_' . $file->getClientOriginalName();
$file->move($destino, $filename);
$rutaRelativa = 'comprobantes/' . $filename;
$rutasArchivos[] = $rutaRelativa;

Log::info('Archivo subido correctamente', [
    'archivo' => $rutaRelativa
]);

                }
            } else {
                Log::warning('No se encontraron archivos adjuntos en la solicitud');
            }
    
            // Avisar por duplicados
            if ($hayDuplicados) {
                if ($request->has('desde_admin') && $request->desde_admin == 1) {
                    session()->flash('warning', 'Se detectaron comprobantes posiblemente duplicados.');
                } else {
                    Log::warning('Usuario normal subió un comprobante posiblemente duplicado');
                }
            }
    
            // Guardar reserva en la base de datos
            $comprobanteStr = json_encode($rutasArchivos);
            $metadatosStr = json_encode($metadatosArchivos);
    
            DB::transaction(function () use ($validated, &$series, $totalPagar, $comprobanteStr, $metadatosStr, $bingo, &$reservaCreada, $request) {
    
                $cantidad = $validated['cartones'];
                $series = $this->asignarSeries($bingo->id, $cantidad);
    
                $maxOrdenBingo = Reserva::where('bingo_id', $bingo->id)->max('orden_bingo') ?? 0;
                $nuevoOrdenBingo = $maxOrdenBingo + 1;
    
                $estadoInicial = 'revision';
                $numeroComprobante = null;
    
                if ($request->has('desde_admin') && $request->desde_admin == 1 && $request->has('auto_approve')) {
                    $estadoInicial = 'aprobado';
                    $numeroComprobante = 'AUTO-' . time();
                }
    
                $reservaData = [
                    'nombre'             => $validated['nombre'],
                    'celular'            => $validated['celular'],
                    'cantidad'           => $cantidad,
                    'comprobante'        => $comprobanteStr,
                    'comprobante_metadata' => $metadatosStr,
                    'total'              => $totalPagar,
                    'series'             => $series,
                    'estado'             => $estadoInicial,
                    'numero_comprobante' => $numeroComprobante,
                    'bingo_id'           => $bingo->id,
                    'orden_bingo'        => $nuevoOrdenBingo,
                ];
    
                $reservaCreada = Reserva::create($reservaData);
    
                Log::info('Reserva creada', [
                    'id' => $reservaCreada->id,
                    'orden_bingo' => $reservaCreada->orden_bingo
                ]);
            });
    
            // Respuesta AJAX
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => '¡Participante añadido correctamente!',
                    'reserva_id' => $reservaCreada->id,
                    'series' => $series
                ]);
            }
    
            // Redirección
            if ($request->has('desde_admin') && $request->desde_admin == 1) {
                return redirect()->route('bingos.reservas.rapidas', $bingo->id)
                    ->with('success', '¡Participante añadido correctamente!');
            }
    
            session()->put('celular_comprador', $validated['celular']);
    
            return redirect()->route('cartones.buscar')
                ->with('success', '¡Reserva realizada correctamente!')
                ->with('series', $series)
                ->with('bingo', $bingo->nombre)
                ->with('celular', $validated['celular'])
                ->with('orden', $reservaCreada->orden_bingo);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación', ['errors' => $e->errors()]);
            throw $e;
    
        } catch (\Exception $e) {
            Log::error('Error general en reserva', ['error' => $e->getMessage()]);
    
            return redirect()->back()
                ->withInput()
                ->with('error', 'Ocurrió un error al procesar tu reserva. ' . $e->getMessage());
        }
    }
    
    public function exportarRechazadosExcel($bingoId)
{
    return Excel::download(new RechazadosExport($bingoId), 'reservas_rechazadas.xlsx');
}


    public function rechazarReserva($id)
    {
        DB::table('reservas')
            ->where('id', $id)
            ->update([
                'estado' => 'rechazado',
                'eliminado' => 1,
            ]);
    
        return back()->with('status', 'Reserva rechazada correctamente.');
    }

    public function buscarGanador(Request $request, $bingoId) 
    { 
        $serie = $request->input('serie'); 
        $datos = null; 
        
        Log::info('--------- INICIO BÚSQUEDA DE GANADOR ---------');
        Log::info('Buscando serie: ' . $serie . ' en bingo ID: ' . $bingoId);
        
        if ($serie) { 
            // Normalizar la serie (quitar ceros iniciales para comparaciones adicionales)
            $serieNormalizada = ltrim($serie, '0');
            Log::info('Serie normalizada (sin ceros iniciales): ' . $serieNormalizada);
            
            // 1. Buscar en la tabla Series para obtener el cartón correspondiente
            Log::info('Buscando serie en la tabla Series');
            
            $serieQuery1 = "SELECT * FROM series WHERE JSON_CONTAINS(series, '\"" . $serie . "\"') LIMIT 1";
            Log::info('Consulta SQL 1: ' . $serieQuery1);
            $serieResultado1 = DB::select($serieQuery1);
            
            if (empty($serieResultado1)) {
                $serieQuery2 = "SELECT * FROM series WHERE JSON_CONTAINS(series, '\"" . $serieNormalizada . "\"') LIMIT 1";
                Log::info('Consulta SQL 2: ' . $serieQuery2);
                $serieResultado2 = DB::select($serieQuery2);
                
                if (!empty($serieResultado2)) {
                    $serieResultado = $serieResultado2[0];
                    Log::info('Serie encontrada con búsqueda normalizada (sin ceros iniciales)');
                } else {
                    $seriesQuery = "SELECT id, series, carton FROM series LIMIT 10";
                    $ejemploSeries = DB::select($seriesQuery);
                    Log::info('No se encontró la serie. Mostrando ejemplos de series en la BD:');
                    foreach ($ejemploSeries as $s) {
                        Log::info('Serie ID: ' . $s->id . ', Series: ' . $s->series . ', Cartón: ' . $s->carton);
                    }
                    
                    Log::info('No se encontró la serie: ' . $serie);
                    $serieResultado = null;
                }
            } else {
                $serieResultado = $serieResultado1[0];
                Log::info('Serie encontrada con búsqueda exacta');
            }
            
            if ($serieResultado) {
                $carton = $serieResultado->carton;
                Log::info('Cartón encontrado: ' . $carton);
                
                // Buscar en reservas que tengan el cartón EN ESTE BINGO
                // SOLUCIÓN: Usar LIKE en vez de JSON_CONTAINS debido al formato especial
                $reservaQuery = "SELECT * FROM reservas WHERE bingo_id = ? AND series LIKE ? LIMIT 1";
                Log::info('Consulta SQL Reserva: ' . $reservaQuery);
                $reservaResultado = DB::select($reservaQuery, [$bingoId, '%' . $carton . '%']);
                
                if (empty($reservaResultado)) {
                    // Intentar con la versión normalizada
                    $cartonNormalizado = ltrim($carton, '0');
                    $reservaQuery2 = "SELECT * FROM reservas WHERE bingo_id = ? AND series LIKE ? LIMIT 1";
                    Log::info('Consulta SQL Reserva 2: ' . $reservaQuery2);
                    $reservaResultado = DB::select($reservaQuery2, [$bingoId, '%' . $cartonNormalizado . '%']);
                }
                
                // Nueva alternativa: Probar extrayendo el primer valor del array
                if (empty($reservaResultado)) {
                    // Buscar todas las reservas para este bingo y hacer un filtrado manual
                    $reservasQuery = "SELECT * FROM reservas WHERE bingo_id = ?";
                    $todasReservas = DB::select($reservasQuery, [$bingoId]);
                    
                    foreach ($todasReservas as $r) {
                        Log::info('Analizando reserva ID: ' . $r->id . ', Series: ' . $r->series);
                        
                        // Intentar decodificar el JSON, considerando que puede estar doblemente codificado
                        $seriesStr = $r->series;
                        
                        // Intento 1: Decodificar una vez
                        $decodedOnce = json_decode($seriesStr, true);
                        
                        // Intento 2: Decodificar dos veces si es necesario
                        $decodedTwice = null;
                        if (is_string($decodedOnce)) {
                            $decodedTwice = json_decode($decodedOnce, true);
                        }
                        
                        $seriesArray = is_array($decodedOnce) ? $decodedOnce : 
                                     (is_array($decodedTwice) ? $decodedTwice : []);
                        
                        Log::info('Series decodificadas: ' . json_encode($seriesArray));
                        
                        // Buscar si contiene el cartón
                        if (in_array($carton, $seriesArray) || in_array($cartonNormalizado, $seriesArray)) {
                            Log::info('¡Reserva encontrada manualmente! ID: ' . $r->id);
                            $reservaResultado = [$r];
                            break;
                        }
                    }
                }
                
                if (!empty($reservaResultado)) {
                    Log::info('Reserva encontrada');
                    $reservaEncontrada = $reservaResultado[0];
                    
                    // Convertir el objeto StdClass a un modelo Reserva
                    $datos = new Reserva();
                    foreach ((array)$reservaEncontrada as $key => $value) {
                        $datos->$key = $value;
                    }
                } else {
                    // Diagnóstico: Listar todas las reservas para este bingo
                    $reservasQuery = "SELECT id, series, nombre FROM reservas WHERE bingo_id = ? LIMIT 10";
                    $todasReservas = DB::select($reservasQuery, [$bingoId]);
                    Log::info('No se encontró reserva. Mostrando reservas en el bingo ' . $bingoId . ':');
                    foreach ($todasReservas as $r) {
                        Log::info('Reserva ID: ' . $r->id . ', Series: ' . $r->series . ', Nombre: ' . $r->nombre);
                    }
                }
            }
        } 
        
        // Obtener ganadores de este bingo para mostrar en tabla 
        $ganadores = Reserva::where('bingo_id', $bingoId) 
            ->where('ganador', 1) 
            ->orderByDesc('fecha_ganador') 
            ->get(); 
        
        Log::info('Total de ganadores encontrados: ' . $ganadores->count());
        Log::info('--------- FIN BÚSQUEDA DE GANADOR ---------');
        
        return view('admin.bingos.buscar-ganador', [ 
            'datos' => $datos, 
            'serieBuscada' => $serie, 
            'bingoId' => $bingoId, 
            'ganadores' => $ganadores,
        ]); 
    }
    

    public function buscarGanadorConBingo(Request $request, $bingoId)
    {
        $serie = $request->input('serie');
        $datos = null;
    
        if ($serie) {
            // Buscar en la tabla series → obtener el cartón relacionado a esa serie
            $serieEncontrada = Serie::whereJsonContains('series', $serie)->first();
    
            if ($serieEncontrada) {
    
                $carton = $serieEncontrada->carton;
    
                // Buscar en reservas → en este bingo → si alguna reserva tiene ese cartón en sus series
                $reserva = Reserva::where('bingo_id', $bingoId)
                    ->whereJsonContains('series', $carton)
                    ->first();
    
                if ($reserva) {
                    $datos = $reserva;
                }
            }
        }
    
        // Obtener ganadores de este bingo para mostrar en tabla
        $ganadores = Reserva::where('bingo_id', $bingoId)
            ->where('ganador', 1)
            ->orderByDesc('fecha_ganador')
            ->get();
    
        return view('admin.bingos.buscar-ganador', [
            'datos' => $datos,
            'serieBuscada' => $serie,
            'bingoId' => $bingoId,
            'ganadores' => $ganadores,
        ]);
    }
    

public function buscarGanadorGlobal(Request $request)
{
    // Este es para buscar en todos los bingos (sin bingoId)

    $serie = $request->input('serie');
    $datos = null;

    if ($serie) {

        $serieEncontrada = Serie::whereJsonContains('series', $serie)->first();

        if ($serieEncontrada) {

            $carton = $serieEncontrada->carton;

            $reserva = Reserva::whereJsonContains('series', $carton)->first();

            if ($reserva) {
                $datos = $reserva;
            }
        }
    }

    return view('admin.bingos.buscar-ganador', [
        'datos' => $datos,
        'serieBuscada' => $serie,
        'bingoId' => null,
    ]);
}


public function marcarGanador(Request $request, $id)
{
    $reserva = Reserva::findOrFail($id);
    $reserva->ganador = 1;
    $reserva->premio = $request->input('premio');
    $reserva->fecha_ganador = now();
    $reserva->save();

    return redirect()->back()->with('success', 'Ganador marcado exitosamente');
}
    public function aprobarReserva($id)
    {
        \DB::table('reservas')->where('id', $id)->update([
            'estado' => 'aprobado',
            'eliminado' => 0,
        ]);
        return back()->with('status', 'Reserva aprobada correctamente.');
    }


    public function create($bingoId)
{
    $bingo = \App\Models\Bingo::findOrFail($bingoId);

    return view('admin.bingos.reservas-crear', [
        'bingoId' => $bingoId,
        'bingo' => $bingo,
    ]);
}
public function comprobantesDuplicados($bingoId)
{
    $bingo = Bingo::findOrFail($bingoId);

    // Obtener las reservas con perceptual_hash
    $reservas = Reserva::where('bingo_id', $bingoId)
        ->whereIn('eliminado', [0, 1])
        ->get()
        ->filter(function ($reserva) {
            $metadatos = json_decode($reserva->comprobante_metadata, true);
            return is_array($metadatos) && !empty($metadatos[0]['perceptual_hash']);
        });

    // Agrupar por perceptual_hash
    $agrupados = $reservas->groupBy(function ($reserva) {
        $metadatos = json_decode($reserva->comprobante_metadata, true);
        return $metadatos[0]['perceptual_hash'] ?? 'nohash';
    })->filter(function ($grupo) {
        return $grupo->count() > 1;
    });

    // Afinar duplicados SOLO SI histogramas son IGUALES (idénticos)
    $agrupados = $agrupados->map(function ($grupo) {
        return $grupo->filter(function ($reservaA) use ($grupo) {
            $metaA = json_decode($reservaA->comprobante_metadata, true)[0] ?? null;
            if (!$metaA) return false;

            foreach ($grupo as $reservaB) {
                if ($reservaA->id === $reservaB->id) continue;

                $metaB = json_decode($reservaB->comprobante_metadata, true)[0] ?? null;
                if (!$metaB) continue;

                // Validar que los histogramas sean IGUALES
                if ($this->histogramEquals($metaA['histograma'], $metaB['histograma'])) {
                    return true; // Duplicado exacto
                }
            }

            return false;
        });
    })->filter(function ($grupo) {
        return $grupo->count() > 0;
    });

    // ---- 📌 PAGINACIÓN DE GRUPOS ----
    $page = request()->get('page', 1);  // Página actual
    $perPage = 3; // Grupos por página

    // Crear paginator manual
    $paginador = new \Illuminate\Pagination\LengthAwarePaginator(
        $agrupados->slice(($page - 1) * $perPage, $perPage),
        $agrupados->count(),
        $perPage,
        $page,
        ['path' => request()->url(), 'query' => request()->query()]
    );

    return view('admin.bingos.reservas-duplicadas', compact('bingo', 'paginador'));
}

// 📌 Este método compara que dos histogramas sean IGUALES valor a valor
protected function histogramEquals($histA, $histB)
{
    if (count($histA) !== count($histB)) {
        return false;
    }

    foreach ($histA as $i => $valA) {
        if ($valA != $histB[$i]) {
            return false;
        }
    }

    return true;
}


public function pedidosDuplicados($bingoId)
{
    $bingo = \App\Models\Bingo::findOrFail($bingoId);

    // Obtener reservas con número de comprobante duplicado
    $duplicados = \App\Models\Reserva::where('bingo_id', $bingoId)
        ->whereNotNull('numero_comprobante')
        ->groupBy('numero_comprobante')
        ->havingRaw('COUNT(*) > 1')
        ->pluck('numero_comprobante');

    $reservas = \App\Models\Reserva::where('bingo_id', $bingoId)
        ->whereIn('numero_comprobante', $duplicados)
        ->get()
        ->groupBy('numero_comprobante');

    return view('admin.bingos.pedidos-duplicados', compact('bingo', 'reservas'));
}

    /**
     * Verifica si un comprobante es único basado en sus metadatos
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

            // Si la similitud es mayor a 90%, consideramos alta coincidencia
            if ($similitudHash > 90) {
                $coincidencias += 3;
            } elseif ($similitudHash > 80) {
                $coincidencias += 2;
            } elseif ($similitudHash > 70) {
                $coincidencias += 1;
            }

            // Si la coincidencia de hash perceptual es muy alta, es probable que sea la misma imagen
            if ($similitudHash > 95) {
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

            // Añadir a coincidencias según nivel de similitud
            if ($similitudHistograma > 90) {
                $coincidencias += 2;
            } elseif ($similitudHistograma > 75) {
                $coincidencias += 1;
            }
        }

        // Si hay hash de contenido en ambos (peso 3)
        if (isset($metadatosA['contenido_hash']) && isset($metadatosB['contenido_hash'])) {
            $totalComparaciones += 3;
            $ponderacion += 3;

            if ($metadatosA['contenido_hash'] === $metadatosB['contenido_hash']) {
                $coincidencias += 3;

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

            // Si las dimensiones son iguales, es un indicio fuerte
            if (
                $metadatosA['dimensions']['width'] == $metadatosB['dimensions']['width'] &&
                $metadatosA['dimensions']['height'] == $metadatosB['dimensions']['height']
            ) {
                $coincidencias += 1;
            }
        }

        // Fecha y hora (peso 2) - muy útil para fotos originales
        if (
            isset($metadatosA['datetime']) && isset($metadatosB['datetime']) &&
            !empty($metadatosA['datetime']) && !empty($metadatosB['datetime'])
        ) {
            $totalComparaciones += 2;
            $ponderacion += 2;

            if ($metadatosA['datetime'] === $metadatosB['datetime']) {
                $coincidencias += 2;
            }
        }

        // Marca y modelo de cámara (peso 1)
        if (
            isset($metadatosA['make']) && isset($metadatosB['make']) &&
            isset($metadatosA['model']) && isset($metadatosB['model'])
        ) {
            $totalComparaciones += 1;
            $ponderacion += 1;

            if ($metadatosA['make'] === $metadatosB['make'] && $metadatosA['model'] === $metadatosB['model']) {
                $coincidencias += 1;
            }
        }

        // Tamaño del archivo (peso 1)
        if (isset($metadatosA['tamaño']) && isset($metadatosB['tamaño'])) {
            $totalComparaciones += 1;
            $ponderacion += 1;

            // Calcular diferencia de tamaño en porcentaje
            $maxTamaño = max($metadatosA['tamaño'], $metadatosB['tamaño']);
            $minTamaño = min($metadatosA['tamaño'], $metadatosB['tamaño']);

            if ($maxTamaño > 0) {
                $diferenciaPorcentaje = 100 - (($minTamaño / $maxTamaño) * 100);

                // Si la diferencia es menor al 5%, consideramos que son similares
                if ($diferenciaPorcentaje < 5) {
                    $coincidencias += 1;
                }
            }
        }

        // Si no hay suficientes comparaciones, consideramos que no hay suficientes datos
        if ($totalComparaciones < 3) {
            return [
                'es_duplicado' => false,
                'similaridad' => 0
            ];
        }

        // Calcular similitud ponderada
        $similitud = $ponderacion > 0 ? ($coincidencias / $ponderacion) * 100 : 0;

        // Si hay más de 75% de coincidencia ponderada, consideramos que es el mismo comprobante
        $esDuplicado = $similitud > 75;

        return [
            'es_duplicado' => $esDuplicado,
            'similaridad' => round($similitud, 1)
        ];
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
            $seriesLiberadasDisponibles = array_filter($seriesLiberadas, function ($serie) use ($seriesExistentes) {
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
                // Cambiar el padding a 6 cifras en lugar de 4
                $seriePadded = str_pad($numero, 6, '0', STR_PAD_LEFT);

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
            \Log::info('Aplicando filtro por serie exacta: ' . $serie);

            // Crear el patrón exacto que buscamos en la base de datos
            // Básicamente buscamos: ["0001"] o algo que incluya ese patrón exacto
            $serieFormateada = '"[\\"' . $serie . '\\"]"';
            $serieEnArray = '[\\"' . $serie . '\\"';  // Para cuando es parte de un array más grande

            $query->where(function ($q) use ($serie, $serieFormateada, $serieEnArray) {
                // Opción 1: Serie exacta - coincide con todo el campo (para series individuales)
                $q->where('series', $serieFormateada);

                // Opción 2: Serie como parte de un array más grande
                $q->orWhere('series', 'LIKE', '%' . $serieEnArray . '%');

                // Registrar los patrones que estamos buscando
                \Log::info('Patrones de búsqueda:', [
                    'serie_original' => $serie,
                    'patron_exacto' => $serieFormateada,
                    'patron_array' => $serieEnArray
                ]);
            });

            // Log para ver la consulta generada
            \Log::info('SQL después del filtro de serie: ' . $query->toSql(), [
                'bindings' => $query->getBindings()
            ]);
        }

        // Ordenar por orden_bingo para mostrar en orden de reserva
        $reservas = $query->orderBy('orden_bingo', 'asc')->paginate(1000);

        // Si es una solicitud AJAX, devolver solo la tabla
        if ($request->ajax()) {
            return view('admin.bingos.reservas-tabla', compact('reservas', 'bingo'));
        }

        // De lo contrario, redirigir a la vista completa
        return redirect()->route('bingos.reservas', $id);
    }


    public function verRechazados($bingoId)
    {
        // Verificar si el bingo existe
        $bingo = Bingo::findOrFail($bingoId);
    
        // Obtener las reservas rechazadas para este bingo
        $reservasRechazadas = Reserva::where('bingo_id', $bingoId)
            ->where('estado', 'rechazado')
            ->get();
    
        // Obtener los cartones rechazados individuales
        $cartonesRechazados = DB::table('cartones_rechazados')
            ->where('bingo_id', $bingoId)
            ->orderBy('fecha_rechazo', 'desc')
            ->get();
    
        // Procesar datos para la vista
        $reservasProcesadas = [];
        foreach ($reservasRechazadas as $reserva) {
            $series = [];
            
            // Procesar las series (si es JSON, convertirlo a array)
            if (!empty($reserva->series)) {
                if (is_string($reserva->series)) {
                    $seriesArray = json_decode($reserva->series, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($seriesArray)) {
                        $series = $seriesArray;
                    }
                } elseif (is_array($reserva->series)) {
                    $series = $reserva->series;
                }
            }
            
            // Procesar series para cada cartón si es necesario
            $cartonesSeries = [];
            foreach ($series as $carton) {
                // Obtener información de la tabla series para este cartón
                $infoSerie = DB::table('series')
                    ->where('carton', $carton)
                    ->orWhere('carton', ltrim($carton, '0'))
                    ->first();
                
                if ($infoSerie && isset($infoSerie->series)) {
                    $seriesInfo = $infoSerie->series;
                    
                    // Si es JSON, decodificarlo
                    if (is_string($seriesInfo)) {
                        $seriesData = json_decode($seriesInfo, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($seriesData)) {
                            $cartonesSeries[$carton] = $seriesData;
                        } else {
                            $cartonesSeries[$carton] = $seriesInfo;
                        }
                    } else {
                        $cartonesSeries[$carton] = $seriesInfo;
                    }
                } else {
                    $cartonesSeries[$carton] = "No encontrado";
                }
            }
            
            // Añadir a la lista procesada
            $reservasProcesadas[] = [
                'reserva' => $reserva,
                'cartones' => $series,
                'cartonesSeries' => $cartonesSeries
            ];
        }
        
        // Procesar datos de cartones individuales rechazados
        $cartonesProcesados = [];
        foreach ($cartonesRechazados as $carton) {
            // Obtener información de la reserva
            $reservaInfo = null;
            if ($carton->reserva_id) {
                $reservaInfo = Reserva::find($carton->reserva_id);
            }
            
            // Obtener información de series para este cartón
            $seriesInfo = null;
            $info = DB::table('series')
                ->where('carton', $carton->serie_rechazada)
                ->orWhere('carton', ltrim($carton->serie_rechazada, '0'))
                ->first();
                
            if ($info && isset($info->series)) {
                $seriesData = $info->series;
                
                // Si es JSON, decodificarlo
                if (is_string($seriesData)) {
                    $seriesArray = json_decode($seriesData, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($seriesArray)) {
                        $seriesInfo = $seriesArray;
                    } else {
                        $seriesInfo = $seriesData;
                    }
                } else {
                    $seriesInfo = $seriesData;
                }
            }
            
            $cartonesProcesados[] = [
                'carton' => $carton,
                'reserva' => $reservaInfo,
                'series' => $seriesInfo
            ];
        }
        
        return view('admin.bingos.rechazados', [
            'bingo' => $bingo,
            'reservasRechazadas' => $reservasProcesadas,
            'cartonesRechazados' => $cartonesProcesados
        ]);
    }


    /**
     * Comando para actualizar el orden_bingo en reservas existentes
     */
    public function actualizarOrdenBingo($bingoId)
    {
        try {
            DB::beginTransaction();

            // Obtener todas las reservas del bingo ordenadas por fecha
            $reservas = Reserva::where('bingo_id', $bingoId)
                ->orderBy('created_at')
                ->get();

            $contador = 1;

            foreach ($reservas as $reserva) {
                $reserva->orden_bingo = $contador;
                $reserva->save();
                $contador++;
            }

            DB::commit();

            return redirect()->back()->with('success', "Se actualizó el orden de {($contador-1)} reservas para este bingo.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando orden_bingo', [
                'bingo_id' => $bingoId,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Ocurrió un error al actualizar el orden de las reservas.');
        }
    }
}
