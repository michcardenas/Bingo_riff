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
            $vista = 'descargarcartones';
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


public function descargar($numero, $bingoId = null) {
    // Debug inicial
    Log::info("=== INICIO PROCESO DESCARGA ===");
    Log::info("Parámetros recibidos - Número: $numero, Bingo ID: " . ($bingoId ?: 'no proporcionado'));
    
    try {
            $reservaEncontrada = Reserva::where('id', $numero) // o $id si cambiaste el parámetro
            ->where('eliminado', 0)
            ->first();

        if (!$reservaEncontrada) {
            Log::warning("Reserva no encontrada con ID: $numero");
            return redirect()->back()->with('error', 'La reserva no existe o fue eliminada.');
        }

        
        if (!$reservaEncontrada) {
            Log::warning("Cartón no encontrado o no disponible: $numero");
            return redirect()->back()->with('error', 'El cartón no existe o no está disponible para descarga.');
        }
        
        // Verificar el estado del bingo
        if ($reservaEncontrada->bingo_id && $reservaEncontrada->bingo) {
            $bingo = $reservaEncontrada->bingo;
            $bingoEstado = strtolower($bingo->estado);
            Log::info("Bingo encontrado ID: " . $bingo->id . ", Estado: " . $bingoEstado);
            
            // Verificar si el bingo está archivado
            if ($bingoEstado === 'archivado') {
                Log::warning("Intento de descarga de cartón {$numero} para bingo archivado");
                return redirect()->back()->with('error', 'Este cartón pertenece a un bingo archivado y no puede ser descargado.');
            }
        }
        
        // Definir rutas de archivos con directorio absoluto
        $directorioBingo = '/home/u861598707/domains/white-dragonfly-473649.hostingersite.com/public_html/TablasbingoRIFFY';
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
        
        // ELIMINADA LA LÍNEA QUE USA exec()
        // En su lugar, simplemente registramos información del servidor
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
                    $urlDirecta = 'https://white-dragonfly-473649.hostingersite.com/TablasbingoRIFFY/Carton-RIFFY-' . $numeroParaArchivo . '.jpg';
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
            $urlDirecta = 'https://white-dragonfly-473649.hostingersite.com/TablasbingoRIFFY/Carton-RIFFY-' . $numeroParaArchivo . '.' . $extension;
            Log::info("Redirigiendo a URL directa: " . $urlDirecta);
            return redirect($urlDirecta);
        }
        
        // Planb: usar URL directa si la descarga falla
        if (!file_exists($rutaCompleta) || filesize($rutaCompleta) == 0) {
            Log::warning("Archivo no disponible o vacío, redirigiendo a URL directa");
            $urlDirecta = 'https://white-dragonfly-473649.hostingersite.com/TablasbingoRIFFY/Carton-RIFFY-' . $numeroParaArchivo . '.' . $extension;
            return redirect($urlDirecta);
        }
        
        if ($extension === 'jpg' && isset($reservaEncontrada)) {
            try {
                Log::info("🖼 Aplicando marca de agua personalizada en cartón JPG");
        
                // Intentamos obtener el nombre del propietario
                $nombrePropietario = "";
                
                // Si la reserva tiene un número de celular, intentamos buscar en la BD
                if (!empty($reservaEncontrada->celular)) {
                    try {
                        // Buscar todas las reservas con el mismo número de celular
                        $reservasPorCelular = Reserva::where('celular', $reservaEncontrada->celular)
                                                    ->where('eliminado', 0)
                                                    ->whereNotNull('nombre')
                                                    ->where('nombre', '!=', '')
                                                    ->orderBy('id', 'desc') // Ordenar por ID descendente para tomar la más reciente
                                                    ->get();
                        
                        Log::info("Buscando nombre por celular: " . $reservaEncontrada->celular);
                        Log::info("Reservas encontradas con mismo celular: " . count($reservasPorCelular));
                        
                        // Loggear todas las reservas encontradas para diagnóstico
                        foreach ($reservasPorCelular as $index => $reserva) {
                            Log::info("Reserva #" . ($index + 1) . " - ID: " . $reserva->id . 
                                     ", Nombre: '" . $reserva->nombre . "'" .
                                     ", Fecha creación: " . $reserva->created_at);
                        }
                        
                        // Si encontramos reservas, usamos la primera que tenga un nombre válido completo
                        if ($reservasPorCelular->isNotEmpty()) {
                            // Intentemos buscar nombres completos (que contengan al menos un espacio)
                            $nombreCompleto = null;
                            foreach ($reservasPorCelular as $reserva) {
                                if (strpos($reserva->nombre, ' ') !== false) {
                                    $nombreCompleto = $reserva->nombre;
                                    break;
                                }
                            }
                            
                            // Si no encontramos nombre con espacio, usamos el primero disponible
                            if ($nombreCompleto === null && !empty($reservasPorCelular[0]->nombre)) {
                                $nombreCompleto = $reservasPorCelular[0]->nombre;
                            }
                            
                            if ($nombreCompleto !== null) {
                                $nombrePropietario = trim($nombreCompleto);
                                Log::info("Nombre completo encontrado: '" . $nombrePropietario . "'");
                            }
                        }
                        
                        // Última opción: consultar directamente con DB::select para verificar
                        if (empty($nombrePropietario)) {
                            $celular = $reservaEncontrada->celular;
                            $results = DB::select("
                                SELECT id, nombre 
                                FROM reservas 
                                WHERE celular = ? 
                                  AND eliminado = 0 
                                  AND nombre IS NOT NULL 
                                  AND nombre != '' 
                                ORDER BY id DESC
                            ", [$celular]);
                            
                            Log::info("Consulta directa a la BD - Resultados: " . count($results));
                            
                            foreach ($results as $index => $result) {
                                Log::info("Resultado directo #" . ($index + 1) . 
                                         " - ID: " . $result->id . 
                                         ", Nombre: '" . $result->nombre . "'");
                                
                                if (empty($nombrePropietario) && !empty($result->nombre)) {
                                    $nombrePropietario = trim($result->nombre);
                                    Log::info("Nombre encontrado por consulta directa: '" . $nombrePropietario . "'");
                                    break;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning("Error al buscar nombre por celular: " . $e->getMessage());
                    }
                }
                
                // Si no encontramos un nombre válido, usamos el número de celular
                if (empty($nombrePropietario)) {
                    $nombrePropietario = !empty($reservaEncontrada->celular) ? 
                        $reservaEncontrada->celular : 
                        "Reserva #" . $reservaEncontrada->id;
                }
                
                // Truncar el nombre si es demasiado largo para evitar que se salga
                $maxLongitudNombre = 500; // Ajustar según sea necesario
                
                // En la parte donde se determina el nombre del bingo
                Log::info("Intentando obtener el nombre del bingo correcto para la ID: " . $reservaEncontrada->bingo_id);

                // Primero, veamos si la reserva tiene un bingo_id
                if (!empty($reservaEncontrada->bingo_id)) {
                    // Consulta detallada que busca bingos activos o cerrados, ordenados por fecha
                    $bingoQuery = "
                        SELECT id, nombre, estado, created_at 
                        FROM bingos 
                        WHERE id = ? 
                        AND (estado = 'abierto' OR estado = 'cerrado')
                        ORDER BY created_at DESC
                        LIMIT 1
                    ";
                    
                    // Registramos la consulta
                    Log::info("Consulta SQL para bingo: " . $bingoQuery);
                    Log::info("Buscando bingo con ID: " . $reservaEncontrada->bingo_id);
                    
                    $bingoResultado = DB::select($bingoQuery, [$reservaEncontrada->bingo_id]);
                    
                    if (!empty($bingoResultado)) {
                        $bingoEncontrado = $bingoResultado[0];
                        $nombreBingo = $bingoEncontrado->nombre;
                        
                        Log::info("Bingo encontrado - ID: " . $bingoEncontrado->id . 
                                ", Nombre: '" . $bingoEncontrado->nombre . "'" . 
                                ", Estado: " . $bingoEncontrado->estado . 
                                ", Fecha: " . $bingoEncontrado->created_at);
                    } else {
                        // Si no hay resultados, buscamos cualquier bingo con esa ID
                        $bingoQuery2 = "SELECT id, nombre, estado, created_at FROM bingos WHERE id = ? LIMIT 1";
                        $bingoResultado2 = DB::select($bingoQuery2, [$reservaEncontrada->bingo_id]);
                        
                        if (!empty($bingoResultado2)) {
                            $bingoEncontrado = $bingoResultado2[0];
                            $nombreBingo = $bingoEncontrado->nombre;
                            
                            Log::info("Bingo encontrado (segunda búsqueda) - ID: " . $bingoEncontrado->id . 
                                    ", Nombre: '" . $bingoEncontrado->nombre . "'" . 
                                    ", Estado: " . $bingoEncontrado->estado . 
                                    ", Fecha: " . $bingoEncontrado->created_at);
                        } else {
                            $nombreBingo = "Bingo RIFFY";
                            Log::info("No se encontró ningún bingo con ID: " . $reservaEncontrada->bingo_id . ". Usando nombre por defecto.");
                        }
                    }
                } else {
                    // Si no hay bingo_id, intentamos buscar el bingo más reciente
                    $bingoQuery = "
                        SELECT id, nombre, estado, created_at 
                        FROM bingos 
                        WHERE estado = 'abierto' OR estado = 'cerrado'
                        ORDER BY created_at DESC
                        LIMIT 1
                    ";
                    
                    $bingoResultado = DB::select($bingoQuery);
                    
                    if (!empty($bingoResultado)) {
                        $bingoEncontrado = $bingoResultado[0];
                        $nombreBingo = $bingoEncontrado->nombre;
                        
                        Log::info("Usando bingo más reciente - ID: " . $bingoEncontrado->id . 
                                ", Nombre: '" . $bingoEncontrado->nombre . "'" . 
                                ", Estado: " . $bingoEncontrado->estado . 
                                ", Fecha: " . $bingoEncontrado->created_at);
                    } else {
                        $nombreBingo = "Bingo RIFFY";
                        Log::info("No se encontraron bingos activos o cerrados. Usando nombre por defecto.");
                    }
                }

                // Verificar el valor final
                Log::info("Nombre del bingo final: " . $nombreBingo);
                
                // Truncar el nombre del bingo si es demasiado largo
                if (mb_strlen($nombreBingo) > $maxLongitudNombre) {
                    $nombreBingo = mb_substr($nombreBingo, 0, $maxLongitudNombre) . '...';
                    Log::info("Nombre del bingo truncado por longitud excesiva: " . $nombreBingo);
                }
                
                // Formatear textos para la marca de agua
                $textoBingo = "Bingo: " . $nombreBingo;
                $textoNombre = "Nombre: " . $nombrePropietario;
                
                Log::info("Línea 1 (Bingo): " . $textoBingo);
                Log::info("Línea 2 (Nombre): " . $textoNombre);
                
                // Cargar la imagen con GD
                $sourceImage = @imagecreatefromjpeg($rutaCompleta);
                if (!$sourceImage) {
                    throw new \Exception("No se pudo cargar la imagen con GD");
                }
                
                // Obtener dimensiones
                $width = imagesx($sourceImage);
                $height = imagesy($sourceImage);
                
                // Color negro para el texto, con leve sombreado para mejor visibilidad
                $textColor = imagecolorallocate($sourceImage, 0, 0, 0); // Negro
                $shadowColor = imagecolorallocate($sourceImage, 255, 255, 255); // Blanco para sombreado
                
                // Verificar si la fuente existe
                $fuente = base_path('public/fonts/arial.ttf');
                if (!file_exists($fuente)) {
                    throw new \Exception("No se encontró la fuente en $fuente");
                }
                
                // Tamaño de la fuente
                $fontSize = 16;
                
                // Márgenes y posiciones
                $margenDerecho = 200;
                $margenIzquierdo = 20; // Para asegurar que el texto no se salga por la izquierda
                
                // Calcular el ancho máximo disponible para el texto
                $maxTextWidth = $width - $margenDerecho - $margenIzquierdo;
                
                // Asegurarnos de que el texto no se salga por la izquierda
                $bbox1 = imagettfbbox($fontSize, 0, $fuente, $textoBingo);
                $textWidth1 = $bbox1[2] - $bbox1[0];
                
                // Si el texto es más ancho que el espacio disponible, lo reducimos
                if ($textWidth1 > $maxTextWidth) {
                    // Reducir el texto gradualmente hasta que quepa
                    $tempTextoBingo = $textoBingo;
                    while ($textWidth1 > $maxTextWidth && mb_strlen($tempTextoBingo) > 10) {
                        $tempTextoBingo = mb_substr($tempTextoBingo, 0, mb_strlen($tempTextoBingo) - 1);
                        $bbox1 = imagettfbbox($fontSize, 0, $fuente, $tempTextoBingo . "...");
                        $textWidth1 = $bbox1[2] - $bbox1[0];
                    }
                    $textoBingo = $tempTextoBingo . "...";
                    Log::info("Texto bingo ajustado para caber: " . $textoBingo);
                }
                
                // Lo mismo para el nombre
                $bbox2 = imagettfbbox($fontSize, 0, $fuente, $textoNombre);
                $textWidth2 = $bbox2[2] - $bbox2[0];
                
                if ($textWidth2 > $maxTextWidth) {
                    $tempTextoNombre = $textoNombre;
                    while ($textWidth2 > $maxTextWidth && mb_strlen($tempTextoNombre) > 10) {
                        $tempTextoNombre = mb_substr($tempTextoNombre, 0, mb_strlen($tempTextoNombre) - 1);
                        $bbox2 = imagettfbbox($fontSize, 0, $fuente, $tempTextoNombre . "...");
                        $textWidth2 = $bbox2[2] - $bbox2[0];
                    }
                    $textoNombre = $tempTextoNombre . "...";
                    Log::info("Texto nombre ajustado para caber: " . $textoNombre);
                }
                
                // Calcular posiciones finales
                $textX1 = $width - $textWidth1 - $margenDerecho;
                $textX2 = $width - $textWidth2 - $margenDerecho;
                
                // Asegurarse de que el texto no se salga de la imagen
                $textX1 = max($margenIzquierdo, $textX1);
                $textX2 = max($margenIzquierdo, $textX2);
                
                // Posición Y para cada línea
                $textY1 = 170;
                $textY2 = 200;
                
                // Añadir sombreado para mejor visibilidad (1px offset)
                imagettftext($sourceImage, $fontSize, 0, $textX1+1, $textY1+1, $shadowColor, $fuente, $textoBingo);
                imagettftext($sourceImage, $fontSize, 0, $textX2+1, $textY2+1, $shadowColor, $fuente, $textoNombre);
                
                // Añadir las dos líneas de texto
                imagettftext($sourceImage, $fontSize, 0, $textX1, $textY1, $textColor, $fuente, $textoBingo);
                imagettftext($sourceImage, $fontSize, 0, $textX2, $textY2, $textColor, $fuente, $textoNombre);
                
                // Guardar la imagen con marca de agua
                $rutaTemporal = storage_path('app/public/tmp/Carton-RIFFY-' . $numeroParaArchivo . '-marca.jpg');
                if (!file_exists(dirname($rutaTemporal))) {
                    mkdir(dirname($rutaTemporal), 0775, true);
                }
                
                // Guardar la imagen con alta calidad
                imagejpeg($sourceImage, $rutaTemporal, 95);
                Log::info("✅ Imagen con marca de agua guardada exitosamente: $rutaTemporal");
                $rutaCompleta = $rutaTemporal;
                
                // Liberar recursos
                imagedestroy($sourceImage);
                    
            } catch (\Exception $e) {
                Log::error("❌ Error al aplicar marca de agua: " . $e->getMessage());
                Log::error("Traza: " . $e->getTraceAsString());
                
                // Fallback: usar la imagen original sin marca de agua
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
            $urlDirecta = 'https://white-dragonfly-473649.hostingersite.com/TablasbingoRIFFY/Carton-RIFFY-' . $numeroParaArchivo . '.jpg';
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

    $bingoAbierto = Bingo::where('estado', 'abierto')->first();

    if (!$bingoAbierto) {
        return view('buscar_cartones_series', [
            'reservas' => [],
            'celular' => $celular,
            'mensaje' => 'No hay un bingo abierto actualmente.',
        ]);
    }

    $reservas = Reserva::where('celular', $celular)
        ->where('bingo_id', $bingoAbierto->id)
        ->get();

    // Recolectar todos los números de cartón de las reservas
    $cartonesComprados = [];
    foreach ($reservas as $reserva) {
        $seriesCartones = is_array($reserva->series) ? $reserva->series : json_decode($reserva->series, true);
        $cartonesComprados = array_merge($cartonesComprados, $seriesCartones);
    }

    // Buscar series asociadas a esos cartones
    $seriesDetalladas = Serie::whereIn('carton', $cartonesComprados)->get();

    return view('buscar_cartones_series', compact('reservas', 'celular', 'bingoAbierto', 'seriesDetalladas'));
}


}