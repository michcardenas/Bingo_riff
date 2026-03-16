<?php

namespace App\Console\Commands;

use App\Models\Bingo;
use App\Services\ComprobanteScanner;
use App\Services\ReservaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class TestOcrMasivo extends Command
{
    protected $signature = 'test:ocr-masivo
        {bingo_id : ID del bingo donde crear las reservas}
        {--carpeta=public/comprobantes-test : Carpeta con las imagenes}
        {--nombre=Test OCR : Nombre base para las reservas}
        {--celular=3001234567 : Celular base}
        {--cartones=1 : Cantidad de cartones por reserva}
        {--limpiar : Eliminar reservas de prueba anteriores antes de ejecutar}';

    protected $description = 'Sube imagenes de una carpeta como comprobantes, las procesa con OCR y crea reservas';

    public function handle()
    {
        $bingoId = $this->argument('bingo_id');
        $carpeta = $this->option('carpeta');
        $nombreBase = $this->option('nombre');
        $celularBase = $this->option('celular');
        $cartones = (int) $this->option('cartones');

        $bingo = Bingo::find($bingoId);
        if (!$bingo) {
            $this->error("Bingo ID {$bingoId} no encontrado.");
            return 1;
        }

        $this->info("Bingo: {$bingo->nombre} (ID: {$bingo->id}) - Precio carton: \${$bingo->precio}");

        // Ruta completa de la carpeta
        $rutaCarpeta = base_path($carpeta);
        if (!File::isDirectory($rutaCarpeta)) {
            $this->error("La carpeta no existe: {$rutaCarpeta}");
            $this->info("Crea la carpeta y coloca las imagenes ahi.");
            return 1;
        }

        // Buscar imagenes
        $extensiones = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'];
        $archivos = collect(File::files($rutaCarpeta))->filter(function ($file) use ($extensiones) {
            return in_array(strtolower($file->getExtension()), $extensiones);
        })->values();

        if ($archivos->isEmpty()) {
            $this->error("No se encontraron imagenes en: {$rutaCarpeta}");
            $this->info("Extensiones soportadas: " . implode(', ', $extensiones));
            return 1;
        }

        $this->info("Se encontraron {$archivos->count()} imagenes.");

        // Limpiar anteriores si se pide
        if ($this->option('limpiar')) {
            $eliminadas = \App\Models\Reserva::where('bingo_id', $bingoId)
                ->where('nombre', 'like', $nombreBase . '%')
                ->delete();
            $this->warn("Eliminadas {$eliminadas} reservas de prueba anteriores.");
        }

        $scanner = app(ComprobanteScanner::class);
        $reservaService = app(ReservaService::class);
        $precio = (float) $bingo->precio;
        $totalPagar = $cartones * $precio;

        $resultados = [
            'total' => $archivos->count(),
            'aprobado' => 0,
            'revision' => 0,
            'duplicado' => 0,
            'ocr_fallido' => 0,
            'error' => 0,
        ];

        $bar = $this->output->createProgressBar($archivos->count());
        $bar->start();

        foreach ($archivos as $index => $archivo) {
            $nombreArchivo = $archivo->getFilename();
            $numero = $index + 1;

            try {
                // Copiar imagen a public/comprobantes/
                $destino = 'comprobantes/test_' . time() . '_' . $numero . '.' . $archivo->getExtension();
                $rutaDestino = public_path($destino);
                File::copy($archivo->getPathname(), $rutaDestino);

                // Escanear con OCR
                $ocrResult = $scanner->scan($rutaDestino, $totalPagar);
                $ocrStatus = ($ocrResult['metodo'] ?? '') === 'fallido' ? 'fallido' : 'procesado';

                if ($ocrStatus === 'fallido') {
                    $resultados['ocr_fallido']++;
                }

                // Crear reserva via service (incluye auto-aprobacion)
                $data = [
                    'bingo_id' => $bingoId,
                    'cartones' => $cartones,
                    'nombre' => $nombreBase . ' #' . $numero,
                    'celular' => substr($celularBase, 0, -4) . str_pad($numero, 4, '0', STR_PAD_LEFT),
                    'comprobante' => json_encode([$destino]),
                    'comprobante_metadata' => json_encode([]),
                    'ocr_data' => $ocrResult,
                    'ocr_status' => $ocrStatus,
                    'auto_approve' => false,
                ];

                $resultado = $reservaService->crearReserva($data);

                if ($resultado['success']) {
                    $reserva = $resultado['reserva'];
                    $estado = $reserva->estado;
                    $numComp = $reserva->numero_comprobante;

                    if ($numComp === 'Duplicado') {
                        $resultados['duplicado']++;
                    } elseif ($estado === 'aprobado') {
                        $resultados['aprobado']++;
                    } else {
                        $resultados['revision']++;
                    }
                } else {
                    $resultados['error']++;
                    $this->newLine();
                    $this->error("  Error en imagen {$numero} ({$nombreArchivo}): " . ($resultado['message'] ?? 'desconocido'));
                }

            } catch (\Exception $e) {
                $resultados['error']++;
                $this->newLine();
                $this->error("  Excepcion en imagen {$numero} ({$nombreArchivo}): " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Resumen
        $this->info('========== RESUMEN ==========');
        $this->table(
            ['Metrica', 'Cantidad'],
            [
                ['Total imagenes', $resultados['total']],
                ['Aprobadas (auto)', $resultados['aprobado']],
                ['En revision', $resultados['revision']],
                ['Duplicados detectados', $resultados['duplicado']],
                ['OCR fallido', $resultados['ocr_fallido']],
                ['Errores', $resultados['error']],
            ]
        );

        // Mostrar detalle de las ultimas reservas creadas
        $this->newLine();
        $this->info('Ultimas reservas creadas:');
        $ultimas = \App\Models\Reserva::where('bingo_id', $bingoId)
            ->where('nombre', 'like', $nombreBase . '%')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'nombre', 'estado', 'numero_comprobante', 'ocr_status']);

        $this->table(
            ['ID', 'Nombre', 'Estado', 'Num. Comprobante', 'OCR Status'],
            $ultimas->map(fn($r) => [
                $r->id,
                $r->nombre,
                $r->estado,
                $r->numero_comprobante ?? '-',
                $r->ocr_status,
            ])->toArray()
        );

        return 0;
    }
}
