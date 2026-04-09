<?php

namespace App\Console\Commands;

use App\Imports\PmaImport;
use App\Models\Test;
use App\Models\User;
use Illuminate\Console\Command;

class ImportarPma extends Command
{
    protected $signature   = 'pma:importar {archivo : Ruta al archivo Excel PMA_R_Preguntas.xlsx} {--test_id= : ID del test (opcional)} {--user_id=1 : ID del usuario que realiza la importación}';
    protected $description = 'Importar preguntas PMA-R desde un archivo Excel';

    public function handle(): int
    {
        $rutaArchivo = $this->argument('archivo');

        if (!file_exists($rutaArchivo)) {
            $this->error("❌ Archivo no encontrado: {$rutaArchivo}");
            return Command::FAILURE;
        }

        $userId = (int) $this->option('user_id');
        $testId = $this->option('test_id');

        if (!$testId) {
            $test = Test::firstOrCreate(
                ['codigo' => 'PMA-R'],
                [
                    'nombre'       => 'PMA - Aptitudes Mentales Primarias (Revisada)',
                    'descripcion'  => 'Batería de Thurstone que evalúa V, E, R, N.',
                    'version'      => '1.0',
                    'tiempo_limite'=> 25,
                    'activo'       => true,
                ]
            );
            $testId = $test->id;
        }

        $this->info("🚀 Iniciando importación del archivo: {$rutaArchivo}");
        $this->info("📝 Test ID: {$testId}");

        $importador = new PmaImport($userId, (int)$testId);

        $this->withProgressBar(['V', 'E', 'R', 'N'], fn($factor) => usleep(100000));
        $this->newLine();

        $resultado = $importador->importar($rutaArchivo);

        $this->newLine();
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Estado',         $resultado['estado']],
                ['Total filas',    $resultado['filas_total']],
                ['Exitosas',       $resultado['filas_exitosas']],
                ['Con error',      $resultado['filas_con_error']],
                ['Importación ID', $resultado['importacion_id']],
            ]
        );

        if (!empty($resultado['errores'])) {
            $this->warn('⚠ Errores encontrados:');
            foreach ($resultado['errores'] as $error) {
                $this->warn("   Fila {$error['fila']} [{$error['factor']}]: {$error['mensaje']}");
            }
        }

        if ($resultado['estado'] === 'completado') {
            $this->info('✅ Importación completada sin errores críticos.');
            return Command::SUCCESS;
        }

        $this->warn('⚠ Importación completada con errores. Revisa los logs.');
        return Command::SUCCESS;
    }
}
