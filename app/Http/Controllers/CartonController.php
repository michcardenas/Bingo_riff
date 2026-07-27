<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reserva;
use App\Models\Bingo;
use App\Models\Enlace;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use App\Models\Serie;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\DB;


class CartonController extends Controller
{
/**
     * Muestra la vista para buscar cartones.
     */
    public function index()
    {
        // Obtener número de contacto para WhatsApp
        $enlaces = Enlace::first();
        // Usar el nuevo campo telefono_atencion con respaldo al número de contacto antiguo
        $numeroContacto = $enlaces ? ($enlaces->telefono_atencion ?: $enlaces->numero_contacto) : '3235903774';
        
        return view('buscarcartones', compact('numeroContacto'));
    }

        /**
     * Muestra la vista específica para descargar cartones.
     */
    public function indexDescargar()
    {
        // Obtener número de contacto para WhatsApp
        $enlaces = Enlace::first();
        // Usar el nuevo campo telefono_atencion con respaldo al número de contacto antiguo
        $numeroContacto = $enlaces ? ($enlaces->telefono_atencion ?: $enlaces->numero_contacto) : '3235903774';
        
        return view('descargarcartones', compact('numeroContacto'));
    }


/**
 * Busca cartones por número de teléfono y filtra los bingos archivados y no visibles.
 */
public function buscar(Request $request) {
    $request->validate([
        'celular' => 'required|numeric',
    ]);
    
    $telefono = $request->input('celular');
    Log::info('Búsqueda iniciada para teléfono: ' . $telefono);
    
    // Determinar qué vista usar basado en el parámetro explícito o la URL referente
    $vista = $request->input('vista', null);
        
    if (!$vista) {
        $referer = $request->headers->get('referer', '');
        if (str_contains($referer, 'descargarcartones')) {
            $vista = 'buscarcartones';
        } else {
            $vista = 'buscarcartones';
        }
    }
    
    Log::info('Vista seleccionada para resultados: ' . $vista);
    
    // Buscar reservas asociadas al número de teléfono y que pertenezcan a bingos abiertos o cerrados
    // Utilizamos un join con la tabla bingos para filtrar directamente
    $reservas = Reserva::join('bingos', 'reservas.bingo_id', '=', 'bingos.id')
        ->where('reservas.celular', $telefono)
        ->whereIn('bingos.estado', ['abierto', 'cerrado']) // Solo bingos abiertos o cerrados
        ->where('bingos.visible', 1) // Solo bingos visibles
        ->select('reservas.*') // Seleccionamos solo los campos de la tabla reservas
        ->get();
    
    Log::info('Reservas encontradas (solo de bingos abiertos o cerrados): ' . $reservas->count());
    
    // Preparar datos de cartones
    $cartones = collect();
    
    foreach ($reservas as $reserva) {
        // Manejo seguro de series - verificar si es array o string
        $seriesInfo = '';
        $seriesArray = [];
        
        if (!empty($reserva->series)) {
            if (is_string($reserva->series)) {
                // Si es un string, intentar decodificar JSON
                try {
                    $seriesArray = json_decode($reserva->series, true);
                    // Si no es un JSON válido o no devuelve un array, tratarlo como un valor único
                    if (!is_array($seriesArray) || json_last_error() !== JSON_ERROR_NONE) {
                        $seriesArray = [$reserva->series];
                    }
                } catch (\Exception $e) {
                    $seriesArray = [$reserva->series];
                }
            } elseif (is_array($reserva->series)) {
                $seriesArray = $reserva->series;
            } else {
                // Convertir a string para manejar cualquier otro tipo de dato
                $seriesArray = [(string)$reserva->series];
            }
            
            // Crear string para log
            $seriesInfo = implode(', ', $seriesArray);
        }
        
        Log::info('Procesando reserva ID: ' . $reserva->id . ', Series: ' . $seriesInfo . ', Estado: ' . $reserva->estado);
        
        // Cargar la información del bingo explícitamente para asegurarnos de tener datos actualizados
        $bingo = DB::table('bingos')->find($reserva->bingo_id);
        
        // Información del bingo
        $bingoNombre = 'No asignado';
        $bingoId = null;
        $bingoEstado = null;
        $bingoVisible = null;
        
        if ($bingo) {
            $bingoNombre = $bingo->nombre;
            $bingoId = $bingo->id;
            $bingoEstado = $bingo->estado;
            $bingoVisible = $bingo->visible;
            
            // Verificación adicional por si acaso (aunque ya filtramos en la consulta)
            if (strtolower($bingoEstado) !== 'abierto' && strtolower($bingoEstado) !== 'cerrado') {
                Log::info('Saltando reserva porque el bingo no está abierto ni cerrado: ' . $bingoNombre . ', Estado: ' . $bingoEstado);
                continue;
            }
            
            if ($bingoVisible != 1) {
                Log::info('Saltando reserva porque el bingo no es visible: ' . $bingoNombre);
                continue;
            }
        } else {
            Log::info('No se encontró información del bingo ID: ' . $reserva->bingo_id);
            continue; // Saltamos esta reserva si no existe el bingo
        }
        
        Log::info('Bingo asociado: ' . $bingoNombre . ', Estado: ' . $bingoEstado . ', Visible: ' . $bingoVisible);
        
        // Procesar cada serie
        if (!empty($seriesArray)) {
            foreach ($seriesArray as $serie) {
                // Asegurarse de que $serie sea un string
                $serie = (string)$serie;
                
                // Preparar número sin ceros iniciales para descarga
                $numeroDescarga = intval($serie);
                
                $cartones->push([
                    'numero' => $serie,
                    'numero_descarga' => $numeroDescarga, // Este se usará para la descarga directa
                    'estado' => $reserva->estado,
                    'nombre' => $reserva->nombre,
                    'fecha_creacion' => $reserva->created_at->format('d/m/Y'),
                    'tipo_sorteo' => 'Principal',
                    'id_reserva' => $reserva->id,
                    'bingo_nombre' => $bingoNombre,
                    'bingo_id' => $bingoId,
                    'bingo_estado' => $bingoEstado,
                    'bingo_visible' => $bingoVisible,
                    'eliminado' => $reserva->eliminado
                ]);
                
                Log::info('Cartón agregado: ' . $serie . ' para bingo: ' . $bingoNombre . ', Estado: ' . $reserva->estado);
            }
        } else {
            Log::info('No hay series para la reserva ID: ' . $reserva->id);
        }
    }
    
    Log::info('Total de cartones encontrados (solo de bingos abiertos o cerrados): ' . $cartones->count());
    
    // Obtener número de contacto para WhatsApp
    $enlaces = Enlace::first();
    $numeroContacto = $enlaces ? ($enlaces->telefono_atencion ?: $enlaces->numero_contacto) : '3235903774';
    
    // Guardar en sesión
    session(['celular_comprador' => $telefono]);
    
    // Retornar la vista
    return view($vista, [
        'cartones' => $cartones,
        'numeroContacto' => $numeroContacto
    ]);
}


public function descargar($reservaId, $numeroCarton = null) {
    // Debug inicial
    Log::info("=== INICIO PROCESO DESCARGA ===");
    Log::info("Parámetros recibidos - Reserva ID: $reservaId, Número cartón: " . ($numeroCarton ?: 'no proporcionado'));
    
    try {
        // Buscar la reserva directamente por ID
        Log::info("Buscando reserva por ID: $reservaId");
        $reservaEncontrada = Reserva::where('id', $reservaId)
                                   ->where('eliminado', 0)
                                   ->first();
        
        if (!$reservaEncontrada) {
            Log::warning("Reserva no encontrada o eliminada con ID: $reservaId");
            return redirect()->back()->with('error', 'La reserva no existe o no está disponible para descarga.');
        }
        
        Log::info("Reserva encontrada - ID: {$reservaEncontrada->id}, Bingo ID: {$reservaEncontrada->bingo_id}");
        
        // Verificar el estado del bingo
        if ($reservaEncontrada->bingo_id && $reservaEncontrada->bingo) {
            $bingo = $reservaEncontrada->bingo;
            $bingoEstado = strtolower($bingo->estado);
            Log::info("Bingo encontrado ID: " . $bingo->id . ", Estado: " . $bingoEstado);
            
            // Verificar si el bingo está archivado
            if ($bingoEstado === 'archivado') {
                Log::warning("Intento de descarga de reserva {$reservaId} para bingo archivado");
                return redirect()->back()->with('error', 'Esta reserva pertenece a un bingo archivado y no puede ser descargada.');
            }
        }
        
        // Determinar el número del cartón a descargar
        $numeroParaArchivo = null;
        
        if ($numeroCarton) {
            // Si se proporciona un número específico, usarlo
            $numeroParaArchivo = intval($numeroCarton);
            Log::info("Usando número de cartón específico: $numeroParaArchivo");
        } else {
            // Si no se proporciona número, intentar obtener el primero de las series
            if (!empty($reservaEncontrada->series)) {
                $seriesArray = $reservaEncontrada->series;
                
                // Decodificar series si es JSON
                if (is_string($seriesArray)) {
                    try {
                        $decodedArray = json_decode($seriesArray, true);
                        if (is_string($decodedArray)) {
                            $decodedArray = json_decode($decodedArray, true);
                        }
                        if (is_array($decodedArray)) {
                            $seriesArray = $decodedArray;
                        }
                    } catch (\Exception $e) {
                        Log::warning("Error decodificando series: " . $e->getMessage());
                    }
                }
                
                // Tomar el primer número de la serie
                if (is_array($seriesArray) && !empty($seriesArray)) {
                    $numeroParaArchivo = intval($seriesArray[0]);
                    Log::info("Usando primer número de la serie: $numeroParaArchivo");
                }
            }
        }
        
        if (!$numeroParaArchivo) {
            Log::warning("No se pudo determinar el número del cartón para la reserva: $reservaId");
            return redirect()->back()->with('error', 'No se pudo determinar el cartón a descargar.');
        }
        
        // ✅ DETECCIÓN AUTOMÁTICA DE DIRECTORIO (CORREGIDO)
        $directorioBingo = public_path('TablasbingoRIFFY');
        
        // Si no existe, probar rutas alternativas comunes en hosting compartido
        if (!is_dir($directorioBingo)) {
            $alternativas = [
                base_path('public/TablasbingoRIFFY'),
                base_path('../public_html/TablasbingoRIFFY'),
                $_SERVER['DOCUMENT_ROOT'] . '/TablasbingoRIFFY',
                dirname($_SERVER['SCRIPT_FILENAME']) . '/TablasbingoRIFFY',
                '/home/u690165375/domains/mediumspringgreen-chamois-657776.hostingersite.com/public_html/TablasbingoRIFFY'
            ];
            
            foreach ($alternativas as $ruta) {
                if (is_dir($ruta)) {
                    $directorioBingo = $ruta;
                    Log::info("✅ Directorio encontrado en ruta alternativa: " . $ruta);
                    break;
                }
            }
        } else {
            Log::info("✅ Directorio encontrado en ruta principal: " . $directorioBingo);
        }
        
        Log::info("Directorio de bingo configurado: " . $directorioBingo);
        
        $rutaJpg = $directorioBingo . '/Carton-RIFFY-' . $numeroParaArchivo . '.jpg';
        $rutaPdf = $directorioBingo . '/Carton-RIFFY-' . $numeroParaArchivo . '.pdf';
        
        // Debug de rutas
        Log::info("Verificando existencia de archivos:");
        Log::info("Ruta JPG: " . $rutaJpg . " - Existe: " . (file_exists($rutaJpg) ? 'SÍ' : 'NO'));
        Log::info("Ruta PDF: " . $rutaPdf . " - Existe: " . (file_exists($rutaPdf) ? 'SÍ' : 'NO'));
        
        // Comprobar permisos
        if (file_exists($rutaJpg)) {
            $permisos = substr(sprintf('%o', fileperms($rutaJpg)), -4);
            Log::info("Permisos del archivo JPG: " . $permisos);
        }
        
        if (file_exists($rutaPdf)) {
            $permisos = substr(sprintf('%o', fileperms($rutaPdf)), -4);
            Log::info("Permisos del archivo PDF: " . $permisos);
        }
        
        Log::info("Verificando archivos en entorno de servidor compartido");
        
        // Determinar qué archivo existe y su extensión
        if (file_exists($rutaJpg)) {
            $rutaCompleta = $rutaJpg;
            $extension = 'jpg';
            Log::info("Usando archivo JPG para la descarga");
        } elseif (file_exists($rutaPdf)) {
            $rutaCompleta = $rutaPdf;
            $extension = 'pdf';
            Log::info("Usando archivo PDF para la descarga");
        } else {
            // Intentar con método alternativo para localizar el archivo
            Log::warning("No se encontró el archivo con rutas directas. Intentando alternativa...");
            
            // Listar archivos en el directorio para depuración
            if (is_dir($directorioBingo)) {
                $archivosEnDir = scandir($directorioBingo);
                Log::info("Archivos en el directorio: " . implode(", ", $archivosEnDir));
                
                // Buscar archivos que coincidan parcialmente
                $patronBusqueda = "Carton-RIFFY-" . $numeroParaArchivo;
                $archivoCoincidente = null;
                
                foreach ($archivosEnDir as $archivo) {
                    if (strpos($archivo, $patronBusqueda) !== false) {
                        $archivoCoincidente = $archivo;
                        $rutaCompleta = $directorioBingo . '/' . $archivo;
                        $extension = pathinfo($archivo, PATHINFO_EXTENSION);
                        Log::info("Archivo coincidente encontrado: " . $archivo);
                        break;
                    }
                }
                
                if (!$archivoCoincidente) {
                    Log::warning("No se encontró ningún archivo que coincida con el patrón: " . $patronBusqueda);
                    
                    // Plan B: Usar URL directa en caso de que no se encuentre
                    $urlDirecta = 'mediumspringgreen-chamois-657776.hostingersite.com/TablasbingoRIFFY/Carton-RIFFY-' . $numeroParaArchivo . '.jpg';
                    Log::info("Redirigiendo a URL directa: " . $urlDirecta);
                    return redirect($urlDirecta);
                }
            } else {
                Log::error("El directorio no existe o no es accesible: " . $directorioBingo);
                return redirect()->back()->with('error', 'Error en la configuración del sistema de archivos.');
            }
        }
        
        // Preparar el nombre del archivo para descarga
        $nombreArchivo = "Carton-RIFFY-{$numeroParaArchivo}";
        
        // Verificar si el archivo es legible
        if (!is_readable($rutaCompleta)) {
            Log::error("El archivo existe pero no es legible: " . $rutaCompleta);
            
            // Plan B: Usar URL directa en caso de que el archivo no sea legible
            $urlDirecta = 'mediumspringgreen-chamois-657776.hostingersite.com/TablasbingoRIFFY/Carton-RIFFY-' . $numeroParaArchivo . '.' . $extension;
            Log::info("Redirigiendo a URL directa: " . $urlDirecta);
            return redirect($urlDirecta);
        }
        
        // Plan B: usar URL directa si la descarga falla
        if (!file_exists($rutaCompleta) || filesize($rutaCompleta) == 0) {
            Log::warning("Archivo no disponible o vacío, redirigiendo a URL directa");
            $urlDirecta = 'mediumspringgreen-chamois-657776.hostingersite.com/TablasbingoRIFFY/Carton-RIFFY-' . $numeroParaArchivo . '.' . $extension;
            return redirect($urlDirecta);
        }
        
        // ✅ APLICAR MARCA DE AGUA CON LOGS DETALLADOS Y SIN FUENTES TTF
        if ($extension === 'jpg' && $reservaEncontrada) {
            try {
                Log::info("🖼 INICIANDO PROCESO DE MARCA DE AGUA");
                Log::info("Extension: " . $extension);
                Log::info("Reserva encontrada ID: " . $reservaEncontrada->id);
                
                // Verificar extensión GD
                if (!extension_loaded('gd')) {
                    throw new \Exception("La extensión GD no está disponible");
                }
                Log::info("✅ Extensión GD disponible");
                
                // Verificar que el archivo existe y es legible
                if (!file_exists($rutaCompleta)) {
                    throw new \Exception("El archivo no existe: " . $rutaCompleta);
                }
                if (!is_readable($rutaCompleta)) {
                    throw new \Exception("El archivo no es legible: " . $rutaCompleta);
                }
                Log::info("✅ Archivo existe y es legible");
                
                // Obtener nombre del propietario con debug
                $nombrePropietario = "";
                
                Log::info("DEBUG - Datos de la reserva:");
                Log::info("- ID: " . $reservaEncontrada->id);
                Log::info("- Nombre: '" . ($reservaEncontrada->nombre ?: 'VACÍO') . "'");
                Log::info("- Celular: '" . ($reservaEncontrada->celular ?: 'VACÍO') . "'");
                Log::info("- Bingo ID: " . ($reservaEncontrada->bingo_id ?: 'VACÍO'));
                
                if (!empty($reservaEncontrada->nombre) && trim($reservaEncontrada->nombre) !== '') {
                    $nombrePropietario = trim($reservaEncontrada->nombre);
                    Log::info("✅ Nombre encontrado en reserva: '" . $nombrePropietario . "'");
                } else {
                    Log::info("⚠️ La reserva no tiene nombre, buscando por celular...");
                    
                    // FALLBACK: Si la reserva específica no tiene nombre, buscar por número de celular
                    if (!empty($reservaEncontrada->celular)) {
                        try {
                            $reservasPorCelular = Reserva::where('celular', $reservaEncontrada->celular)
                                                        ->where('eliminado', 0)
                                                        ->whereNotNull('nombre')
                                                        ->where('nombre', '!=', '')
                                                        ->orderBy('id', 'desc')
                                                        ->get();
                            
                            Log::info("Buscando por celular: " . $reservaEncontrada->celular);
                            Log::info("Reservas encontradas: " . count($reservasPorCelular));
                            
                            if ($reservasPorCelular->isNotEmpty()) {
                                $nombrePropietario = trim($reservasPorCelular[0]->nombre);
                                Log::info("✅ Nombre encontrado por celular: '" . $nombrePropietario . "'");
                            }
                        } catch (\Exception $e) {
                            Log::warning("Error al buscar por celular: " . $e->getMessage());
                        }
                    }
                    
                    // Si aún no tenemos nombre, usar valor por defecto
                    if (empty($nombrePropietario)) {
                        $nombrePropietario = !empty($reservaEncontrada->celular) ? 
                            $reservaEncontrada->celular : 
                            "Reserva #" . $reservaEncontrada->id;
                        Log::info("⚠️ Usando nombre por defecto: '" . $nombrePropietario . "'");
                    }
                }
                
                // Obtener nombre del bingo de forma simple
                $nombreBingo = "";
                if ($reservaEncontrada->bingo_id && $reservaEncontrada->bingo) {
                    $nombreBingo = $reservaEncontrada->bingo->nombre;
                    Log::info("✅ Nombre del bingo obtenido: '" . $nombreBingo . "'");
                } else {
                    $nombreBingo = "Bingo RIFFY";
                    Log::info("⚠️ Usando nombre de bingo por defecto");
                }
                
                // Preparar textos
                $textoBingo = "Bingo: " . $nombreBingo;
                $textoNombre = "Nombre: " . $nombrePropietario;
                
                Log::info("Textos preparados:");
                Log::info("- Línea 1: " . $textoBingo);
                Log::info("- Línea 2: " . $textoNombre);
                
                // Intentar cargar la imagen
                Log::info("Intentando cargar imagen con imagecreatefromjpeg...");
                $sourceImage = @imagecreatefromjpeg($rutaCompleta);
                
                if (!$sourceImage) {
                    // Obtener el último error de GD
                    $error = error_get_last();
                    throw new \Exception("No se pudo cargar la imagen. Error: " . ($error['message'] ?? 'Desconocido'));
                }
                
                Log::info("✅ Imagen cargada correctamente");
                
                // Obtener dimensiones
                $width = imagesx($sourceImage);
                $height = imagesy($sourceImage);
                Log::info("Dimensiones: " . $width . "x" . $height);
                
                if ($width === false || $height === false) {
                    throw new \Exception("No se pudieron obtener las dimensiones de la imagen");
                }
                
                // Asignar colores
                $textColor = imagecolorallocate($sourceImage, 0, 0, 0);
                $shadowColor = imagecolorallocate($sourceImage, 255, 255, 255);
                
                if ($textColor === false || $shadowColor === false) {
                    throw new \Exception("No se pudieron asignar los colores");
                }
                
                Log::info("✅ Colores asignados correctamente");

                // ============= PARCHE DINÁMICO DEL PRECIO =============
                // Tapa el "$6.000 Pesos" quemado en el JPG y escribe el precio real del bingo.
                // Muestrea el color del fondo del header para que el rectángulo combine.
                try {
                    $bingoPrecio = (float) ($reservaEncontrada->bingo->precio ?? 0);
                    if ($bingoPrecio > 0) {
                        // Muestrear el color del fondo del header solo si la imagen es lo suficientemente grande.
                        // Si no, usar blanco casi puro (RGB 248,248,248) que es el color estándar del header RIFFY.
                        if ($width >= 1000 && $height >= 250) {
                            $rgb = imagecolorat($sourceImage, 950, 195);
                            $fondoR = ($rgb >> 16) & 0xFF;
                            $fondoG = ($rgb >>  8) & 0xFF;
                            $fondoB = ($rgb      ) & 0xFF;
                        } else {
                            $fondoR = 248; $fondoG = 248; $fondoB = 248;
                        }
                        $fondoHeader = imagecolorallocate($sourceImage, $fondoR, $fondoG, $fondoB);

                        // Rectángulo tapador sobre la línea "VALOR: $ $6.000 Pesos"
                        imagefilledrectangle($sourceImage, 375, 190, 720, 235, $fondoHeader);

                        // Escribir el precio real usando Arial Regular TTF
                        $fuentePrecio = base_path('public/fonts/arial.ttf');
                        if (file_exists($fuentePrecio)) {
                            $precioTexto = '$ ' . number_format(floor($bingoPrecio), 0, ',', '.') . ' Pesos';
                            $precioFontSize = 12;
                            $bbox = imagettfbbox($precioFontSize, 0, $fuentePrecio, $precioTexto);
                            $precioTextH = $bbox[1] - $bbox[7];
                            $precioY = 190 + ((235 - 190) + $precioTextH) / 2 - 2;
                            imagettftext($sourceImage, $precioFontSize, 0, 390, $precioY, $textColor, $fuentePrecio, $precioTexto);
                            Log::info("✅ Precio parchado a {$precioTexto} (fondo RGB {$fondoR},{$fondoG},{$fondoB})");
                        } else {
                            Log::warning("Fuente arial.ttf no encontrada en {$fuentePrecio}, se omite el parche del precio");
                        }
                    }
                } catch (\Throwable $ePrecio) {
                    Log::error("Error aplicando parche del precio: " . $ePrecio->getMessage());
                }
                // ============= FIN PARCHE PRECIO =============

                // Marca de agua "Bingo:" y "Nombre:" en la esquina superior derecha del header,
                // ARRIBA del rectángulo del precio (Y=190). Usar Arial TTF 11pt para look consistente.
                $fuenteMarca = base_path('public/fonts/arial.ttf');
                if (file_exists($fuenteMarca)) {
                    $marcaFontSize = 11;
                    $marcaX  = 750;
                    $marcaY1 = 155;   // alineado con "FECHA Y HORA"
                    $marcaY2 = 210;   // alineado con "VALOR / $ Pesos" (fuera del rect X=375-720)

                    // Truncar textos largos para que no se salgan del cartón (max ~44 chars → ~470px)
                    // Usar mb_* para respetar UTF-8 (acentos, ñ) — no cortar en medio de un byte
                    $truncar = function ($t, $max = 44) {
                        return mb_strlen($t) > $max ? mb_substr($t, 0, $max - 1) . '…' : $t;
                    };
                    $textoBingoTrunc  = $truncar($textoBingo);
                    $textoNombreTrunc = $truncar($textoNombre);

                    // Halo blanco (offset +1) + texto negro
                    imagettftext($sourceImage, $marcaFontSize, 0, $marcaX+1, $marcaY1+1, $shadowColor, $fuenteMarca, $textoBingoTrunc);
                    imagettftext($sourceImage, $marcaFontSize, 0, $marcaX+1, $marcaY2+1, $shadowColor, $fuenteMarca, $textoNombreTrunc);
                    imagettftext($sourceImage, $marcaFontSize, 0, $marcaX,   $marcaY1,   $textColor,   $fuenteMarca, $textoBingoTrunc);
                    imagettftext($sourceImage, $marcaFontSize, 0, $marcaX,   $marcaY2,   $textColor,   $fuenteMarca, $textoNombreTrunc);
                    Log::info("✅ Marca de agua aplicada en ({$marcaX},{$marcaY1}) y ({$marcaX},{$marcaY2})");
                } else {
                    // Fallback: bitmap si no está la fuente
                    $fontSize = 5;
                    $textX = max(10, $width - 500);
                    imagestring($sourceImage, $fontSize, $textX+1, 156, $textoBingo,  $shadowColor);
                    imagestring($sourceImage, $fontSize, $textX+1, 211, $textoNombre, $shadowColor);
                    imagestring($sourceImage, $fontSize, $textX,   155, $textoBingo,  $textColor);
                    imagestring($sourceImage, $fontSize, $textX,   210, $textoNombre, $textColor);
                }
                
                Log::info("✅ Texto aplicado correctamente");
                
                // Crear directorio temporal si no existe
                $dirTemporal = storage_path('app/public/tmp');
                if (!file_exists($dirTemporal)) {
                    if (!mkdir($dirTemporal, 0775, true)) {
                        throw new \Exception("No se pudo crear el directorio temporal: " . $dirTemporal);
                    }
                    Log::info("✅ Directorio temporal creado");
                }
                
                // Guardar imagen
                $rutaTemporal = $dirTemporal . '/Carton-RIFFY-' . $numeroParaArchivo . '-marca.jpg';
                Log::info("Intentando guardar en: " . $rutaTemporal);
                
                $resultado = imagejpeg($sourceImage, $rutaTemporal, 95);
                
                if (!$resultado) {
                    throw new \Exception("Error al guardar la imagen con marca de agua");
                }
                
                // Verificar que se guardó correctamente
                if (!file_exists($rutaTemporal) || filesize($rutaTemporal) == 0) {
                    throw new \Exception("El archivo se guardó pero está vacío o no existe");
                }
                
                Log::info("✅ Imagen guardada exitosamente");
                Log::info("Tamaño del archivo: " . filesize($rutaTemporal) . " bytes");
                
                $rutaCompleta = $rutaTemporal;
                
                // Liberar memoria
                imagedestroy($sourceImage);
                Log::info("✅ MARCA DE AGUA APLICADA EXITOSAMENTE");
                    
            } catch (\Exception $e) {
                Log::error("❌ ERROR EN MARCA DE AGUA: " . $e->getMessage());
                Log::error("Archivo: " . $e->getFile());
                Log::error("Línea: " . $e->getLine());
                Log::error("Trace completo: " . $e->getTraceAsString());
                
                // Liberar memoria si existe
                if (isset($sourceImage) && $sourceImage) {
                    imagedestroy($sourceImage);
                }
                
                Log::warning("⚠️ Usando imagen original sin marca de agua debido al error");
            }
        }
        
        // Intentar descarga directa
        Log::info("Iniciando descarga del archivo: " . $rutaCompleta);
        Log::info("=== FIN PROCESO DESCARGA ===");
        
        // Añadir headers adicionales para evitar problemas de caché
        return response()->download($rutaCompleta, "{$nombreArchivo}.{$extension}", [
            'Content-Type' => $extension == 'pdf' ? 'application/pdf' : 'image/jpeg',
            'Content-Disposition' => 'attachment; filename="' . $nombreArchivo . '.' . $extension . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
        
    } catch (\Exception $e) {
        // Capturar cualquier excepción para evitar errores fatales
        Log::error("Error en la descarga: " . $e->getMessage());
        Log::error("Archivo: " . $e->getFile() . ", Línea: " . $e->getLine());
        Log::error("Trace: " . $e->getTraceAsString());
        Log::info("=== FIN PROCESO DESCARGA CON ERROR ===");
        
        // Plan B final: intentar redireccionar directamente como último recurso
        try {
            $numeroParaArchivo = $numeroCarton ? intval($numeroCarton) : 1; // valor por defecto si no hay número
            $urlDirecta = 'mediumspringgreen-chamois-657776.hostingersite.com/TablasbingoRIFFY/Carton-RIFFY-' . $numeroParaArchivo . '.jpg';
            Log::info("Error en descarga normal. Último intento: redirección a " . $urlDirecta);
            return redirect($urlDirecta);
        } catch (\Exception $e2) {
            return redirect()->back()->with('error', 'Ocurrió un error al procesar la descarga. Por favor contacte al administrador.');
        }
    }
}
    public function getBingoByName(Request $request)
    {
        $nombre = $request->nombre;
        $bingo = Bingo::where('nombre', $nombre)->first();
        if (!$bingo) {
            return response()->json(['error' => 'Bingo no encontrado'], 404);
        }
        return response()->json($bingo);
    }
    
    /**
     * Obtener información del bingo por ID para la API
     */
    public function getBingoById($id)
    {
        $bingo = Bingo::find($id);
        if (!$bingo) {
            return response()->json(['error' => 'Bingo no encontrado'], 404);
        }
        return response()->json($bingo);
    }

    public function buscarPorSerieCarton()
{
    return view('buscar_cartones_series');
}

public function buscarSeriesPorCelular(Request $request) 
{
    $request->validate([
        'celular' => 'required|string',
    ]);

    $celular = $request->input('celular');
    $bingoAbierto = Bingo::orderBy('created_at', 'desc')->first();
    
    if (!$bingoAbierto) {
        return view('buscar_cartones_series', [
            'reservas' => [],
            'celular' => $celular,
            'mensaje' => 'No hay un bingo abierto actualmente.',
        ]);
    }

    // Filtrar reservas excluyendo las rechazadas y eliminadas
    $reservas = Reserva::where('celular', $celular)
        ->where('bingo_id', $bingoAbierto->id)
        ->where('eliminado', 0) // Excluir reservas eliminadas
        ->where(function($query) {
            $query->where('estado', '!=', 'rechazado')
                  ->orWhereNull('estado'); // Incluir reservas sin estado definido
        })
        ->get();

    // Log para debug
    Log::info("Búsqueda de reservas para celular: $celular");
    Log::info("Bingo abierto ID: " . $bingoAbierto->id);
    Log::info("Reservas encontradas (sin rechazadas): " . count($reservas));

    // Buscar un nombre válido para el usuario
    $nombreUsuario = '';
    foreach ($reservas as $reserva) {
        if (!empty($reserva->nombre) && trim($reserva->nombre) !== '') {
            $nombreUsuario = trim($reserva->nombre);
            Log::info("Nombre encontrado en reserva ID {$reserva->id}: '{$nombreUsuario}'");
            break;
        }
    }

    // Si no encontramos nombre en las reservas actuales, buscar en reservas anteriores del mismo celular
    if (empty($nombreUsuario)) {
        $reservaAnterior = Reserva::where('celular', $celular)
            ->where('eliminado', 0)
            ->whereNotNull('nombre')
            ->where('nombre', '!=', '')
            ->orderBy('id', 'desc')
            ->first();
            
        if ($reservaAnterior) {
            $nombreUsuario = trim($reservaAnterior->nombre);
            Log::info("Nombre encontrado en reserva anterior ID {$reservaAnterior->id}: '{$nombreUsuario}'");
        }
    }

    // Recolectar todos los números de cartón de las reservas válidas
    $cartonesComprados = [];
    foreach ($reservas as $reserva) {
        // Verificar que la reserva tenga series
        if (!empty($reserva->series)) {
            $seriesCartones = is_array($reserva->series) ? $reserva->series : json_decode($reserva->series, true);
                        
            // Verificar que la decodificación fue exitosa
            if (is_array($seriesCartones)) {
                $cartonesComprados = array_merge($cartonesComprados, $seriesCartones);
                Log::info("Reserva ID {$reserva->id} - Estado: {$reserva->estado} - Nombre: '{$reserva->nombre}' - Cartones: " . json_encode($seriesCartones));
            } else {
                Log::warning("No se pudo decodificar las series de la reserva ID: {$reserva->id}");
            }
        }
    }

    // Eliminar duplicados y limpiar
    $cartonesComprados = array_unique($cartonesComprados);
    Log::info("Total de cartones únicos comprados: " . count($cartonesComprados));
    Log::info("Nombre final del usuario: '{$nombreUsuario}'");

    // Buscar series asociadas a esos cartones
    $seriesDetalladas = [];
    if (!empty($cartonesComprados)) {
        $seriesDetalladas = Serie::whereIn('carton', $cartonesComprados)->get();
        Log::info("Series detalladas encontradas: " . count($seriesDetalladas));
    }

    return view('buscar_cartones_series', compact(
        'reservas', 
        'celular', 
        'bingoAbierto', 
        'seriesDetalladas',
        'nombreUsuario'  // Agregar el nombre del usuario
    ));
}

}