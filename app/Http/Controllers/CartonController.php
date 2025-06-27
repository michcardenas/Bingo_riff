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
        
        // Definir rutas de archivos con directorio absoluto
        $directorioBingo = '/home/u861598707/domains/https://mediumspringgreen-chamois-657776.hostingersite.com/public_html/TablasbingoRIFFY';
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
                    $urlDirecta = 'https://https://mediumspringgreen-chamois-657776.hostingersite.com/TablasbingoRIFFY/Carton-RIFFY-' . $numeroParaArchivo . '.jpg';
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
            $urlDirecta = 'https://https://mediumspringgreen-chamois-657776.hostingersite.com/TablasbingoRIFFY/Carton-RIFFY-' . $numeroParaArchivo . '.' . $extension;
            Log::info("Redirigiendo a URL directa: " . $urlDirecta);
            return redirect($urlDirecta);
        }
        
        // Plan B: usar URL directa si la descarga falla
        if (!file_exists($rutaCompleta) || filesize($rutaCompleta) == 0) {
            Log::warning("Archivo no disponible o vacío, redirigiendo a URL directa");
            $urlDirecta = 'https://https://mediumspringgreen-chamois-657776.hostingersite.com/TablasbingoRIFFY/Carton-RIFFY-' . $numeroParaArchivo . '.' . $extension;
            return redirect($urlDirecta);
        }
        
        if ($extension === 'jpg' && isset($reservaEncontrada)) {
            try {
                Log::info("🖼 Aplicando marca de agua personalizada en cartón JPG");
        
                // Intentamos obtener el nombre del propietario
                $nombrePropietario = "";
                
                // PRIMERO: Verificar si la reserva específica tiene nombre
                if (!empty($reservaEncontrada->nombre) && trim($reservaEncontrada->nombre) !== '') {
                    $nombrePropietario = trim($reservaEncontrada->nombre);
                    Log::info("Nombre encontrado en la reserva específica (ID: {$reservaEncontrada->id}): '" . $nombrePropietario . "'");
                } else {
                    Log::info("La reserva específica (ID: {$reservaEncontrada->id}) no tiene nombre, buscando por celular como fallback");
                    
                    // FALLBACK: Si la reserva específica no tiene nombre, buscar por número de celular
                    if (!empty($reservaEncontrada->celular)) {
                        try {
                            // Buscar otras reservas con el mismo número de celular que tengan nombre
                            $reservasPorCelular = Reserva::where('celular', $reservaEncontrada->celular)
                                                        ->where('eliminado', 0)
                                                        ->whereNotNull('nombre')
                                                        ->where('nombre', '!=', '')
                                                        ->orderBy('id', 'desc') // Ordenar por ID descendente para tomar la más reciente
                                                        ->get();
                            
                            Log::info("Buscando nombre por celular como fallback: " . $reservaEncontrada->celular);
                            Log::info("Reservas encontradas con mismo celular que tienen nombre: " . count($reservasPorCelular));
                            
                            if ($reservasPorCelular->isNotEmpty()) {
                                // Loggear las reservas encontradas para diagnóstico
                                foreach ($reservasPorCelular as $index => $reserva) {
                                    Log::info("Reserva fallback #" . ($index + 1) . " - ID: " . $reserva->id . 
                                             ", Nombre: '" . $reserva->nombre . "'" .
                                             ", Fecha creación: " . $reserva->created_at);
                                }
                                
                                // Buscar nombres completos (que contengan al menos un espacio)
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
                                    Log::info("Nombre completo encontrado por fallback: '" . $nombrePropietario . "'");
                                }
                            }
                        } catch (\Exception $e) {
                            Log::warning("Error al buscar nombre por celular como fallback: " . $e->getMessage());
                        }
                    }
                }
                
                // Si no encontramos un nombre válido, usamos el número de celular o ID de reserva
                if (empty($nombrePropietario)) {
                    $nombrePropietario = !empty($reservaEncontrada->celular) ? 
                        $reservaEncontrada->celular : 
                        "Reserva #" . $reservaEncontrada->id;
                    Log::info("No se encontró nombre, usando identificador por defecto: '" . $nombrePropietario . "'");
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
            $numeroParaArchivo = $numeroCarton ? intval($numeroCarton) : 1; // valor por defecto si no hay número
            $urlDirecta = 'https://https://mediumspringgreen-chamois-657776.hostingersite.com/TablasbingoRIFFY/Carton-RIFFY-' . $numeroParaArchivo . '.jpg';
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