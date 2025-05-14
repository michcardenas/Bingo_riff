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
public function buscar(Request $request)
{
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

    // Buscar reservas asociadas al número de teléfono
    $reservas = Reserva::where('celular', $telefono)->get();

    Log::info('Reservas encontradas: ' . $reservas->count());

    // Preparar datos de cartones
    $cartones = collect();

    foreach ($reservas as $reserva) {
        Log::info('Procesando reserva ID: ' . $reserva->id . ', Series: ' . implode(', ', $reserva->series) . ', Estado: ' . $reserva->estado);

        // Información del bingo
        $bingoNombre = 'No asignado';
        $bingoId = null;
        $bingoEstado = null;
        $bingoVisible = null;

        if ($reserva->bingo_id && $reserva->bingo) {
            $bingoNombre = $reserva->bingo->nombre;
            $bingoId = $reserva->bingo_id;
            $bingoEstado = $reserva->bingo->estado;
            $bingoVisible = $reserva->bingo->visible;

            if (strtolower($bingoEstado) === 'archivado' || $bingoVisible == 0) {
                Log::info('Saltando reserva para bingo archivado o no visible: ' . $bingoNombre);
                continue;
            }
        }

        Log::info('Bingo asociado: ' . $bingoNombre . ', Estado: ' . ($bingoEstado ?? 'N/A') . ', Visible: ' . ($bingoVisible ?? 'N/A'));

        // Series → YA es array
        if (!empty($reserva->series)) {
            $seriesArray = $reserva->series;

            foreach ($seriesArray as $serie) {

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

    Log::info('Total de cartones encontrados (después de filtrar archivados y no visibles): ' . $cartones->count());

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
        // Eliminar ceros a la izquierda para la búsqueda (en la base de datos)
        $numeroSinCeros = ltrim($numero, '0');
        
        // Para buscar el archivo, convertir a entero para asegurar que no haya ceros iniciales
        $numeroParaArchivo = intval($numero);
        
        Log::info("Número formateado para búsqueda en DB: $numeroSinCeros");
        Log::info("Número formateado para archivo: $numeroParaArchivo");
        
        // Preparar la consulta base para las reservas
        Log::info("Iniciando búsqueda de reserva en la base de datos");
        $query = Reserva::where(function($q) {
            $q->where('reservas.estado', 'aprobado')
              ->orWhere('reservas.estado', 'revision');
        })->where('reservas.eliminado', 0);
        
        // Si se proporciona un bingoId específico, priorizar ese bingo
        if ($bingoId) {
            Log::info("Filtrando por Bingo ID específico: $bingoId");
            $query->where('reservas.bingo_id', $bingoId);
        } else {
            // Si no se proporciona un bingoId, unir con la tabla de bingos para ordenar
            Log::info("No se proporcionó Bingo ID, priorizando bingos más recientes");
            $query->join('bingos', 'reservas.bingo_id', '=', 'bingos.id')
                  ->where('bingos.estado', '!=', 'archivado')
                  ->orderBy('bingos.created_at', 'desc'); // Ordenar por fecha de creación descendente
        }
        
        $reservas = $query->get();
        Log::info("Cantidad de reservas encontradas: " . count($reservas));
        
        $reservaEncontrada = null;
        
        // Buscar manualmente en las series
        Log::info("Buscando cartón $numero en las series de reservas");
        foreach ($reservas as $reserva) {
            if (!empty($reserva->series)) {
                $seriesArray = $reserva->series;
                Log::debug("Verificando reserva ID: " . $reserva->id . ", Series: " . json_encode($seriesArray));
                
                if (is_array($seriesArray) && in_array($numero, $seriesArray)) {
                    $reservaEncontrada = $reserva;
                    Log::info("Cartón encontrado en reserva ID: " . $reserva->id);
                    break; // Romper el ciclo al encontrar la primera reserva (la más reciente debido al orderBy)
                }
            }
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
                                                    ->where('nombre', '!=', $reservaEncontrada->bingo->nombre) // Evitar que sea igual al nombre del bingo
                                                    ->get();
                        
                        Log::info("Buscando nombre por celular: " . $reservaEncontrada->celular);
                        Log::info("Reservas encontradas con mismo celular: " . count($reservasPorCelular));
                        
                        // Si encontramos reservas con ese celular, usamos el nombre de la primera que tenga un nombre válido
                        foreach ($reservasPorCelular as $reservaPorCelular) {
                            if (!empty($reservaPorCelular->nombre) && 
                                $reservaPorCelular->nombre != $reservaEncontrada->bingo->nombre) {
                                $nombrePropietario = $reservaPorCelular->nombre;
                                Log::info("Nombre encontrado por celular: " . $nombrePropietario);
                                break;
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
                
                // Nombre del evento/bingo
                $nombreBingo = $reservaEncontrada->bingo->nombre ?? "Bingo RIFFY";
                
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
                
                // Altura del rectángulo de fondo
                $rectHeight = 80;
                
                // Crear un rectángulo para el fondo del texto
                $backgroundColor = imagecolorallocatealpha($sourceImage, 255, 255, 255, 30);
                imagefilledrectangle($sourceImage, 0, 0, $width, $rectHeight, $backgroundColor);
                
                // Agregar un borde inferior
                $borderColor = imagecolorallocate($sourceImage, 0, 0, 0);
                imageline($sourceImage, 0, $rectHeight, $width, $rectHeight, $borderColor);
                
                // Colores para el texto
                $textColor1 = imagecolorallocate($sourceImage, 0, 0, 128); // Azul oscuro
                $textColor2 = imagecolorallocate($sourceImage, 128, 0, 0); // Rojo oscuro
                
                // Verificar si la fuente existe
                $fuente = base_path('public/fonts/arial.ttf');
                if (!file_exists($fuente)) {
                    throw new \Exception("No se encontró la fuente en $fuente");
                }
                
                // Calcular el tamaño de la fuente y margen para alinear a la derecha
                $fontSize1 = 20;
                $fontSize2 = 20;
                
                // Calcular posiciones para alinear a la derecha (con un margen de 20px)
                $margenDerecho = 20;
                
                // Posición X para alinear a la derecha
                $bbox1 = imagettfbbox($fontSize1, 0, $fuente, $textoBingo);
                $textWidth1 = $bbox1[2] - $bbox1[0];
                $textX1 = $width - $textWidth1 - $margenDerecho;
                
                $bbox2 = imagettfbbox($fontSize2, 0, $fuente, $textoNombre);
                $textWidth2 = $bbox2[2] - $bbox2[0];
                $textX2 = $width - $textWidth2 - $margenDerecho;
                
                // Posición Y para cada línea
                $textY1 = 70; // Primera línea
                $textY2 = 105; // Segunda línea
                
                // Añadir las dos líneas de texto (alineadas a la derecha)
        
                
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