<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Services\ReservaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReservaApiController extends Controller
{
    protected $reservaService;

    public function __construct(ReservaService $reservaService)
    {
        $this->reservaService = $reservaService;
    }

    /**
     * Crear reserva vía API
     */
    public function store(Request $request)
    {
        Log::info('Iniciando proceso de reserva API', ['request' => $request->all()]);

        try {
            // Validar datos para API
            $validated = $request->validate([
                'bingo_id'     => 'required|exists:bingos,id',
                'cartones'     => 'required|integer|min:1',
                'nombre'       => 'required|string|max:255',
                'celular'      => 'required|string|max:20',
                'auto_approve' => 'sometimes|boolean'
            ]);

            // Usar directamente el service
            $resultado = $this->reservaService->crearReserva($validated);

            if (!$resultado['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $resultado['message']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Reserva creada correctamente',
                'data' => [
                    'reserva_id'    => $resultado['reserva']->id,
                    'orden_bingo'   => $resultado['reserva']->orden_bingo,
                    'series'        => $resultado['series'],
                    'total'         => $resultado['total'],
                    'bingo'         => $resultado['bingo']->nombre,
                    'estado'        => $resultado['reserva']->estado
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error en API de reserva', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Mostrar detalles de una reserva
     */    
    public function show($id)
    {
        try {
            $reserva = $this->reservaService->obtenerReservaPorId($id);
            if (!$reserva) {
                return response()->json(['success' => false, 'message' => 'Reserva no encontrada'], 404);
            }
            return response()->json(['success' => true, 'data' => $reserva], 200);
        } catch (\Exception $e) {
            Log::error('Error en API de reserva', ['error' => $e->getMessage()]);
            dd($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    // listar todas las reservas paginados, filtrar por bingo, numero de telefono o nombre, usar service
    public function index(Request $request)
    {
        try {
            $query = Reserva::query();

            $perPagin = $request->input('per_page', 10);

            if ($request->has('bingo_id')) {
                $query->where('bingo_id', $request->input('bingo_id'));
            }

            if ($request->has('nombre')) {
                $query->where('nombre', 'like', '%' . $request->input('nombre') . '%');
            }

            if ($request->has('celular')) {
                $query->where('celular', 'like', '%' . $request->input('celular') . '%');
            }

            $reservas = $query->with('bingo', 'reservaSeries')->orderBy('created_at', 'desc')->paginate($perPagin);

            return response()->json(['success' => true, 'data' => $reservas], 200);
        } catch (\Exception $e) {
            dd($e->getMessage());
            Log::error('Error en API de reservas', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }
}
