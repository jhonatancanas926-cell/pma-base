<?php

namespace Database\Seeders;

use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Usuarios de prueba ─────────────────────────────────────────────
        User::firstOrCreate(['email' => 'admin@pma.test'], [
            'name' => 'Administrador',
            'password' => Hash::make('Admin1234!'),
            'role' => 'admin',
        ]);

        User::firstOrCreate(['email' => 'evaluador@pma.test'], [
            'name' => 'Evaluador Demo',
            'password' => Hash::make('Evaluador1234!'),
            'role' => 'evaluador',
        ]);

        User::firstOrCreate(['email' => 'evaluado@pma.test'], [
            'name' => 'Evaluado Demo',
            'password' => Hash::make('Evaluado1234!'),
            'role' => 'evaluado',
            'documento' => '1234567890',
            'programa' => 'Administración de Empresas',
        ]);

        // ── Crear estructura base del test PMA-R ──────────────────────────
        $test = Test::firstOrCreate(
            ['codigo' => 'PMA-R'],
            [
                'nombre' => 'PMA - Aptitudes Mentales Primarias (Revisada)',
                'descripcion' => 'Batería de Thurstone que evalúa las cinco aptitudes mentales primarias: Verbal, Espacial, Razonamiento, Numérico y Fluidez Verbal.',
                'version' => '1.0',
                'tiempo_limite' => 25,
                'activo' => true,
            ]
        );

        $this->command->info("✅ Test PMA-R creado/verificado: ID {$test->id}");

        // ── Cargar preguntas de entrevista psicosocial ────────────────────
        $this->call(EntrevistaSeeder::class);
        $this->command->info("✅ Secciones y preguntas de entrevista cargadas.");

        $this->command->info("");
        $this->command->info("📋 Usuarios de prueba creados:");
        $this->command->info("   admin@pma.test / Admin1234! (rol: admin)");
        $this->command->info("   evaluador@pma.test / Evaluador1234! (rol: evaluador)");
        $this->command->info("   evaluado@pma.test / Evaluado1234! (rol: evaluado)");
        $this->command->info("");
        $this->command->info("📊 Para cargar las preguntas del Excel, ejecuta:");
        $this->command->info("   POST /api/v1/importar  (con autenticación de admin)");
        $this->command->info("   O usa: php artisan pma:importar ruta/al/PMA_R_Preguntas.xlsx");
    }
}
