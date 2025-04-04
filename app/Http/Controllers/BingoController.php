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
            $metadatosArchivos = []; // Array para guardar los metadatos de cada archivo
            $hayDuplicados = false;  // Flag para detectar duplicados
            
            if ($request->hasFile('comprobante')) {
                Log::info('Procesando archivos adjuntos', [
                    'cantidad_archivos' => count($request->file('comprobante'))
                ]);
    
                foreach ($request->file('comprobante') as $index => $file) {
                    try {
                        // Verificar si el comprobante es único utilizando la función de verificación
                        $verificacion = $this->verificarComprobanteUnico($file);
                        $metadatosArchivos[] = $verificacion['metadatos']; // Guardar los metadatos extraídos
                        
                        // Registrar si hay duplicados, pero no bloquear la subida
                        if (!$verificacion['es_unico']) {
                            $hayDuplicados = true;
                            
                            Log::warning('Comprobante duplicado detectado', [
                                'index' => $index,
                                'filename' => $file->getClientOriginalName(),
                                'similaridad' => $verificacion['similaridad'] . '%',
                                'reserva_coincidente' => $verificacion['reserva_coincidente'] ? $verificacion['reserva_coincidente']->id : null
                            ]);
                            
                            // Marcamos los metadatos con una bandera de posible duplicado
                            $metadatosArchivos[count($metadatosArchivos) - 1]['posible_duplicado'] = true;
                            $metadatosArchivos[count($metadatosArchivos) - 1]['reserva_coincidente_id'] = $verificacion['reserva_coincidente'] ? $verificacion['reserva_coincidente']->id : null;
                            $metadatosArchivos[count($metadatosArchivos) - 1]['similaridad'] = $verificacion['similaridad'];
                        }
                        
                        // Guardar con un nombre único para evitar colisiones
                        $filename = time() . '_' . $file->getClientOriginalName();
                        // Guarda en "storage/app/public/comprobantes" y retorna "comprobantes/archivo.png"
                        $ruta = $file->storeAs('comprobantes', $filename, 'public');
                        $rutasArchivos[] = $ruta;
    
                        Log::info('Archivo guardado correctamente', [
                            'index' => $index,
                            'filename' => $filename,
                            'ruta' => $ruta,
                            'es_unico' => $verificacion['es_unico'],
                            'desde_admin' => $request->has('desde_admin') ? 'Sí' : 'No'
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
    
            // Después del ciclo forEach, si hay duplicados:
            if ($hayDuplicados) {
                if ($request->has('desde_admin') && $request->desde_admin == 1) {
                    // Mensaje para el administrador
                    session()->flash('warning', 'Se detectaron comprobantes posiblemente duplicados. Se ha registrado esta información para revisión posterior.');
                } else {
                    // Dejar un mensaje en el log pero no mostrar advertencia al usuario normal
                    Log::warning('Usuario normal ha subido un comprobante potencialmente duplicado', [
                        'reserva_data' => [
                            'nombre' => $validated['nombre'],
                            'celular' => $validated['celular'],
                            'bingo_id' => $validated['bingo_id']
                        ]
                    ]);
                    
                    // Opcionalmente, puedes enviar una notificación a los administradores
                    // Notification::send($admins, new PosibleComprobanteDuplicado($metadatosArchivos));
                }
            }
    
            // Convertir los arrays a JSON para almacenarlos en la BD
            $comprobanteStr = json_encode($rutasArchivos);
            $metadatosStr = json_encode($metadatosArchivos);
            
            Log::info('Datos de comprobantes procesados', [
                'rutas' => $rutasArchivos,
                'json_rutas' => $comprobanteStr,
                'metadatos' => $metadatosArchivos,
                'json_metadatos' => $metadatosStr
            ]);
    
            // 4. Asignar series automáticamente y orden_bingo
            $series = [];
            $reservaCreada = null;
    
            try {
                DB::transaction(function () use ($validated, &$series, $totalPagar, $comprobanteStr, $metadatosStr, $bingo, &$reservaCreada, $request) {
                    Log::info('Iniciando transacción DB');
    
                    $cantidad = $validated['cartones'];
                    // Usamos nuestro método mejorado que garantiza series únicas
                    $series = $this->asignarSeries($bingo->id, $cantidad);
    
                    Log::info('Series asignadas para los cartones', ['series' => $series]);
    
                    // Calculamos el próximo valor de orden_bingo para este bingo específico
                    $maxOrdenBingo = Reserva::where('bingo_id', $bingo->id)->max('orden_bingo') ?? 0;
                    $nuevoOrdenBingo = $maxOrdenBingo + 1;
    
                    Log::info('Calculado nuevo orden_bingo', [
                        'bingo_id' => $bingo->id,
                        'max_orden_actual' => $maxOrdenBingo,
                        'nuevo_orden' => $nuevoOrdenBingo
                    ]);
    
                    // Determinar el estado inicial (si hay auto-aprobación desde admin)
                    $estadoInicial = 'revision';
                    $numeroComprobante = null;
                    
                    // Si es desde el panel de administración y se marcó auto-aprobación
                    if ($request->has('desde_admin') && $request->desde_admin == 1 && $request->has('auto_approve')) {
                        $estadoInicial = 'aprobado';
                        $numeroComprobante = 'AUTO-' . time();
                        Log::info('Reserva auto-aprobada desde panel admin', ['numero_comprobante' => $numeroComprobante]);
                    }
    
                    // 5. Crear la reserva, guardando también las series (como JSON), orden_bingo y metadatos
                    $reservaData = [
                        'nombre'             => $validated['nombre'],
                        'celular'            => $validated['celular'],
                        'cantidad'           => $cantidad,
                        'comprobante'        => $comprobanteStr,
                        'comprobante_metadata' => $metadatosStr, // Nuevo campo para metadatos
                        'total'              => $totalPagar,
                        'series'             => json_encode($series),
                        'estado'             => $estadoInicial,
                        'numero_comprobante' => $numeroComprobante,
                        'bingo_id'           => $bingo->id,
                        'orden_bingo'        => $nuevoOrdenBingo, // Nuevo campo
                    ];
    
                    Log::info('Datos para crear la reserva', $reservaData);
    
                    $reservaCreada = Reserva::create($reservaData);
    
                    Log::info('Reserva creada correctamente', [
                        'reserva_id' => $reservaCreada->id,
                        'orden_bingo' => $reservaCreada->orden_bingo
                    ]);
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
                'orden_bingo' => $reservaCreada ? $reservaCreada->orden_bingo : null,
                'series' => $series
            ]);
    
            // Comprobar si la petición es AJAX (para respuesta JSON)
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => '¡Participante añadido correctamente!',
                    'reserva_id' => $reservaCreada->id,
                    'series' => $series
                ]);
            }
    
            // 6. Redirigir según el origen de la solicitud
            if ($request->has('desde_admin') && $request->desde_admin == 1) {
                // Si viene del panel de administración, redirigir de vuelta al panel
                return redirect()->route('bingos.show', $bingo->id)
                    ->with('success', '¡Participante añadido correctamente!');
            } else {
                    // Si viene del flujo normal, redirigir a la vista "reservado"
    // Almacenamos el número de teléfono en la sesión para usarlo en buscarcartones
    session()->put('celular_comprador', $validated['celular']);
    
    return redirect()->route('cartones.indexDescargar')
        ->with('success', '¡Reserva realizada correctamente!')
        ->with('series', $series)
        ->with('bingo', $bingo->nombre)
        ->with('celular', $validated['celular']) // Pasamos también el celular
        ->with('orden', $reservaCreada->orden_bingo);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación', [
                'errors' => $e->errors(),
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $e->errors()
                ], 422);
            }
            
            throw $e; // Re-lanzar para que Laravel maneje la respuesta
        } catch (\Exception $e) {
            Log::error('Error general en el proceso de reserva', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ocurrió un error al procesar tu reserva. Por favor, intenta nuevamente.'
                ], 500);
            }
    
            // Puedes redirigir con un mensaje de error o relanzar la excepción
            return redirect()->back()
                ->withInput()
                ->with('error', 'Ocurrió un error al procesar tu reserva. Por favor, intenta nuevamente.');
        }
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
