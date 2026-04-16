<?php
// INSTRUCCIONES:
// 1. Copia este archivo a: database/seeders/FactorESeeder.php
// 2. Ejecuta: php artisan db:seed --class=FactorESeeder
// 3. Las imágenes deben estar en: public/imagenes/factor_e/
//    Formato: {n}-0.png (principal), {n}-1.png ... {n}-6.png (opciones)

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Opcion;
use App\Models\Pregunta;
use Illuminate\Database\Seeder;

class FactorESeeder extends Seeder
{
    // Respuestas correctas del Factor E
    // Clave: número de pregunta → opciones correctas (1-6)
    private const RESPUESTAS = [
        1 => [3, 5],
        2 => [1, 3, 5],
        3 => [2, 4, 6],
        4 => [3, 6],
        5 => [1, 3, 5],
        6 => [2, 3, 6],
        7 => [1, 4, 6],
        8 => [3, 5],
        9 => [2, 4, 6],
        10 => [1, 4, 6],
        11 => [2, 4],
        12 => [1, 2, 4],
        13 => [1, 3, 5],
        14 => [2, 6],
        15 => [3, 5],
        16 => [1, 3, 4, 6],
        17 => [2, 4, 6],
        18 => [1, 2, 3, 4],
        19 => [1, 3, 4, 6],
        20 => [1, 3, 5],
    ];

    private const LETRAS = [1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E', 6 => 'F'];

    public function run(): void
    {
        $categoria = Categoria::where('codigo', 'FACTOR_E')->firstOrFail();

        foreach (self::RESPUESTAS as $numero => $correctas) {
            // Respuesta correcta guardada como JSON de letras: "C,E"
            $respuestaStr = implode(',', array_map(fn($n) => self::LETRAS[$n], $correctas));

            // Actualizar pregunta
            $pregunta = Pregunta::updateOrCreate(
                ['categoria_id' => $categoria->id, 'numero' => $numero],
                [
                    'enunciado' => "Pregunta espacial N° {$numero}: Selecciona todas las figuras que son iguales a la figura de referencia.",
                    'tipo' => 'seleccion_multiple', // nuevo tipo
                    'respuesta_correcta' => $respuestaStr,
                    'metadatos' => [
                        'imagen_principal' => "imagenes/factor_e/{$numero}-0.jpg",
                        'total_opciones' => 6,
                        'correctas_nums' => $correctas,
                        'correctas_letras' => array_map(fn($n) => self::LETRAS[$n], $correctas),
                        'requiere_imagen' => true,
                        'seleccion_multiple' => true,
                        'instruccion' => 'Marca TODAS las figuras que son iguales a la figura de referencia',
                    ],
                    'puntaje' => 1,
                    'orden' => $numero,
                    'activo' => true, // ← activar ahora que tenemos respuestas
                ]
            );

            // Eliminar opciones anteriores
            $pregunta->opciones()->delete();

            // Crear 6 opciones con imagen
            for ($i = 1; $i <= 6; $i++) {
                $letra = self::LETRAS[$i];
                $esCorrecta = in_array($i, $correctas);

                Opcion::create([
                    'pregunta_id' => $pregunta->id,
                    'letra' => $letra,
                    'texto' => "Opción {$letra}",
                    'es_correcta' => $esCorrecta,
                    'orden' => $i - 1,
                    // La imagen se referencia por convención: {numero}-{i}.png
                    // El frontend la carga desde: /imagenes/factor_e/{numero}-{i}.png
                ]);
            }

            $this->command->info("✅ Pregunta E-{$numero} cargada — Correctas: {$respuestaStr}");
        }

        $this->command->info("\n✅ Factor E completo: 20 preguntas activas con 6 opciones cada una.");
    }
}
