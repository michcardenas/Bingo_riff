<?php

namespace App\Console\Commands;

use App\Services\ComprobanteScanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TestOcrSolo extends Command
{
    protected $signature = 'test:ocr-solo
        {--carpeta=public/comprobantes-test : Carpeta con las imagenes}
        {--limite=0 : Limitar cantidad de imagenes (0 = todas)}';

    protected $description = 'Escanea imagenes con OCR sin crear reservas. Genera un CSV con los resultados.';

    public function handle()
    {
        $carpeta = $this->option('carpeta');
        $limite = (int) $this->option('limite');

        $rutaCarpeta = base_path($carpeta);
        if (!File::isDirectory($rutaCarpeta)) {
            $this->error("La carpeta no existe: {$rutaCarpeta}");
            return 1;
        }

        $extensiones = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'];
        $archivos = collect(File::files($rutaCarpeta))->filter(function ($file) use ($extensiones) {
            return in_array(strtolower($file->getExtension()), $extensiones);
        })->values();

        if ($archivos->isEmpty()) {
            $this->error("No se encontraron imagenes en: {$rutaCarpeta}");
            return 1;
        }

        if ($limite > 0) {
            $archivos = $archivos->take($limite);
        }

        $this->info("Escaneando {$archivos->count()} imagenes con Claude Haiku...");
        $this->newLine();

        $scanner = app(ComprobanteScanner::class);

        $resultados = [];
        $exitosos = 0;
        $fallidos = 0;

        $bar = $this->output->createProgressBar($archivos->count());
        $bar->start();

        foreach ($archivos as $index => $archivo) {
            $nombreArchivo = $archivo->getFilename();

            try {
                $ocrResult = $scanner->scan($archivo->getPathname());
                $metodo = $ocrResult['metodo'] ?? 'desconocido';

                if ($metodo === 'fallido') {
                    $fallidos++;
                } else {
                    $exitosos++;
                }

                $resultados[] = [
                    'archivo' => $nombreArchivo,
                    'banco' => $ocrResult['banco'] ?? '',
                    'monto' => $ocrResult['monto'] ?? '',
                    'referencia' => $ocrResult['referencia'] ?? '',
                    'fecha' => $ocrResult['fecha'] ?? '',
                    'telefono_emisor' => $ocrResult['telefono_emisor'] ?? '',
                    'estado_transaccion' => $ocrResult['estado_transaccion'] ?? '',
                    'confianza' => $ocrResult['confianza'] ?? 0,
                    'metodo' => $metodo,
                ];

            } catch (\Exception $e) {
                $fallidos++;
                $resultados[] = [
                    'archivo' => $nombreArchivo,
                    'banco' => 'ERROR',
                    'monto' => '',
                    'referencia' => '',
                    'fecha' => '',
                    'telefono_emisor' => '',
                    'estado_transaccion' => '',
                    'confianza' => 0,
                    'metodo' => 'error: ' . $e->getMessage(),
                ];
            }

            $bar->advance();

            // Pausa de 500ms para no saturar la API
            usleep(500000);
        }

        $bar->finish();
        $this->newLine(2);

        // Mostrar tabla en consola
        $this->table(
            ['#', 'Archivo', 'Banco', 'Monto', 'Referencia', 'Fecha', 'Tel. Emisor', 'Estado Tx'],
            collect($resultados)->map(function ($r, $i) {
                return [
                    $i + 1,
                    substr($r['archivo'], 0, 30),
                    $r['banco'],
                    $r['monto'] ? '$' . number_format($r['monto'], 0, ',', '.') : '-',
                    $r['referencia'] ?: '-',
                    $r['fecha'] ?: '-',
                    $r['telefono_emisor'] ?: '-',
                    $r['estado_transaccion'] ?: '-',
                ];
            })->toArray()
        );

        // Guardar CSV
        $csvPath = storage_path('logs/ocr_resultados_' . date('Y-m-d_His') . '.csv');
        $csv = fopen($csvPath, 'w');
        fputcsv($csv, ['archivo', 'banco', 'monto', 'referencia', 'fecha', 'telefono_emisor', 'estado_transaccion', 'confianza', 'metodo']);
        foreach ($resultados as $r) {
            fputcsv($csv, $r);
        }
        fclose($csv);

        // Resumen
        $this->newLine();
        $this->info('========== RESUMEN ==========');
        $this->info("Total: {$archivos->count()} | Exitosos: {$exitosos} | Fallidos: {$fallidos}");
        $this->info("CSV guardado en: {$csvPath}");

        // Estadisticas de bancos
        $bancos = collect($resultados)->where('banco', '!=', '')->where('banco', '!=', 'ERROR')
            ->groupBy('banco')->map->count()->sortDesc();
        if ($bancos->isNotEmpty()) {
            $this->newLine();
            $this->info('Bancos detectados:');
            $this->table(['Banco', 'Cantidad'], $bancos->map(fn($c, $b) => [$b, $c])->values()->toArray());
        }

        return 0;
    }
}
