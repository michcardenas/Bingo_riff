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
                'comprobante.*' => 'image|max:5120', // Cada archivo debe ser una imagen de máx 2 MB
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
    
            // 4. Asignar series automáticamente en orden POR BINGO
            $series = [];
            $reservaCreada = null;
            
            try {
                DB::transaction(function () use ($validated, &$series, $totalPagar, $comprobanteStr, $bingo, &$reservaCreada) {
                    Log::info('Iniciando transacción DB');
                    
                    // Se obtiene el total de cartones vendidos PARA ESTE BINGO ESPECÍFICO
                    $lastNumber = Reserva::where('bingo_id', $bingo->id)
                                       ->selectRaw('COALESCE(SUM(cantidad), 0) as total')
                                       ->value('total');
                    
                    Log::info('Último número de cartón para este bingo', ['lastNumber' => $lastNumber]);
    
                    $cantidad = $validated['cartones'];
                    // Generar la serie para cada cartón de la reserva actual
                    for ($i = 1; $i <= $cantidad; $i++) {
                        $nextNumber = $lastNumber + $i;
                        // Formatea el número con 5 dígitos (por ejemplo, "00001")
                        $series[] = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
                    }
                    
                    Log::info('Series generadas para los cartones', ['series' => $series]);
    
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
}
