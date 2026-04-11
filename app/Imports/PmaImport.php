<?php

namespace App\Imports;

use App\Models\Categoria;
use App\Models\Importacion;
use App\Models\Opcion;
use App\Models\Pregunta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Importador PMA-R — Factores V, E, R, N
 * Factor R actualizado con 6 opciones reales del archivo datos.xlsx
 */
class PmaImport
{
    private array $errores = [];
    private int $filasTotal = 0;
    private int $filasOk = 0;
    private int $filasError = 0;
    private ?Importacion $registro = null;

    private const RESPUESTAS_V = [
        1 => 'C',
        2 => 'D',
        3 => 'B',
        4 => 'D',
        5 => 'D',
        6 => 'C',
        7 => 'A',
        8 => 'D',
        9 => 'C',
        10 => 'D',
        11 => 'C',
        12 => 'C',
        13 => 'B',
        14 => 'A',
        15 => 'C',
        16 => 'C',
        17 => 'B',
        18 => 'A',
        19 => 'A',
        20 => 'C',
        21 => 'C',
        22 => 'B',
        23 => 'A',
        24 => 'B',
        25 => 'C',
        26 => 'C',
        27 => 'C',
        28 => 'D',
        29 => 'C',
        30 => 'C',
        31 => 'D',
        32 => 'C',
        33 => 'A',
        34 => 'D',
        35 => 'A',
        36 => 'C',
        37 => 'D',
        38 => 'B',
        39 => 'D',
        40 => 'D',
        41 => 'C',
        42 => 'B',
        43 => 'B',
        44 => 'A',
        45 => 'B',
        46 => 'B',
        47 => 'A',
        48 => 'B',
        49 => 'B',
        50 => 'C',
    ];

    private const RESPUESTAS_R = [
        1 => 'h',
        2 => 'e',
        3 => 'j',
        4 => 'j',
        5 => 'g',
        6 => 'd',
        7 => 'o',
        8 => 'a',
        9 => 'm',
        10 => 'k',
        11 => 'i',
        12 => 'e',
        13 => 'd',
        14 => 'l',
        15 => 'i',
        16 => 'j',
        17 => 'h',
        18 => 'a',
        19 => 'ñ',
        20 => 'y',
        21 => 'g',
        22 => 'v',
        23 => 'j',
        24 => 'y',
        25 => 'h',
        26 => 'g',
        27 => 's',
        28 => 'y',
        29 => 'i',
        30 => 'i',
    ];

    private const OPCIONES_R = [
        1 => ['a', 'b', 'c', 'f', 'g', 'h'],
        2 => ['d', 'e', 'f', 'x', 'y', 'z'],
        3 => ['g', 'h', 'i', 'j', 'k', 'l'],
        4 => ['j', 'k', 'l', 'x', 'y', 'z'],
        5 => ['a', 'b', 'c', 'f', 'g', 'h'],
        6 => ['x', 'b', 'c', 'd', 'e', 'y'],
        7 => ['c', 'd', 'm', 'n', 'ñ', 'o'],
        8 => ['a', 'b', 'c', 'd', 'e', 'f'],
        9 => ['h', 'i', 'j', 'k', 'l', 'm'],
        10 => ['h', 'i', 'j', 'k', 'l', 'm'],
        11 => ['c', 'd', 'i', 'j', 'k', 'l'],
        12 => ['d', 'e', 'f', 'g', 'h', 'i'],
        13 => ['a', 'b', 'c', 'd', 'e', 'f'],
        14 => ['j', 'k', 'l', 'm', 'n', 'ñ'],
        15 => ['g', 'h', 'i', 'j', 'k', 'l'],
        16 => ['i', 'j', 'k', 'ñ', 'o', 'p'],
        17 => ['g', 'h', 'i', 'j', 'k', 'l'],
        18 => ['a', 'b', 'c', 'g', 'h', 'i'],
        19 => ['j', 'k', 'l', 'm', 'n', 'ñ'],
        20 => ['a', 'b', 'c', 'x', 'y', 'z'],
        21 => ['e', 'f', 'g', 'h', 'i', 'j'],
        22 => ['s', 't', 'u', 'v', 'w', 'x'],
        23 => ['i', 'j', 'k', 'l', 'm', 'n'],
        24 => ['f', 'g', 'h', 'x', 'y', 'z'],
        25 => ['h', 'i', 'j', 'k', 'l', 'm'],
        26 => ['e', 'f', 'g', 'h', 'i', 'j'],
        27 => ['p', 'q', 'r', 's', 't', 'u'],
        28 => ['u', 'v', 'w', 'x', 'y', 'z'],
        29 => ['h', 'i', 'j', 'k', 'l', 'm'],
        30 => ['f', 'g', 'h', 'i', 'j', 'k'],
    ];

    private const LETRAS_6 = ['A', 'B', 'C', 'D', 'E', 'F'];
    private const LETRAS_4 = ['A', 'B', 'C', 'D'];

    public function __construct(
        private readonly int $userId,
        private readonly int $testId,
    ) {
    }

    public function importar(string $rutaArchivo): array
    {
        $this->registro = Importacion::create([
            'user_id' => $this->userId,
            'nombre_archivo' => basename($rutaArchivo),
            'tipo' => 'excel',
            'estado' => 'procesando',
        ]);

        try {
            $spreadsheet = IOFactory::load($rutaArchivo);
            DB::transaction(function () use ($spreadsheet) {
                $this->procesarFactorV($spreadsheet);
                $this->procesarFactorE($spreadsheet);
                $this->procesarFactorR($spreadsheet);
                $this->procesarFactorN($spreadsheet);
            });
            $estado = $this->filasError > 0 ? 'con_errores' : 'completado';
        } catch (\Throwable $e) {
            $estado = 'fallido';
            $this->errores[] = ['fila' => 0, 'factor' => 'GENERAL', 'mensaje' => 'Error crítico: ' . $e->getMessage()];
            Log::error('[PmaImport] ' . $e->getMessage());
        }

        $this->registro->update([
            'estado' => $estado,
            'filas_total' => $this->filasTotal,
            'filas_exitosas' => $this->filasOk,
            'filas_con_error' => $this->filasError,
            'errores' => $this->errores,
            'estadisticas' => ['factores' => ['V', 'E', 'R', 'N'], 'timestamp' => now()->toIso8601String()],
        ]);

        return [
            'importacion_id' => $this->registro->id,
            'estado' => $estado,
            'filas_total' => $this->filasTotal,
            'filas_exitosas' => $this->filasOk,
            'filas_con_error' => $this->filasError,
            'errores' => $this->errores
        ];
    }

    private function procesarFactorV(Spreadsheet $s): void
    {
        $cat = $this->obtenerOCrearCategoria('FACTOR V', 'FACTOR_V', 'Aptitud Verbal — Sinónimos', 5, 1);
        $sheet = $s->getSheetByName('FACTOR V');
        if (!$sheet) {
            $this->registrarError(0, 'FACTOR V', 'Hoja no encontrada');
            return;
        }

        $filas = $sheet->toArray(null, true, true, false);
        array_shift($filas);
        foreach ($filas as $idx => $fila) {
            $this->filasTotal++;
            $n = $fila[0] ?? null;
            if (!$n || !is_numeric($n)) {
                $this->filasTotal--;
                continue;
            }
            try {
                $palabra = trim((string) ($fila[1] ?? ''));
                $opciones = ['A' => trim((string) ($fila[2] ?? '')), 'B' => trim((string) ($fila[3] ?? '')), 'C' => trim((string) ($fila[4] ?? '')), 'D' => trim((string) ($fila[5] ?? ''))];
                $resp = self::RESPUESTAS_V[(int) $n] ?? 'C';
                $p = Pregunta::updateOrCreate(
                    ['categoria_id' => $cat->id, 'numero' => (int) $n],
                    [
                        'enunciado' => "¿Cuál es el sinónimo de: {$palabra}?",
                        'tipo' => 'opcion_multiple',
                        'respuesta_correcta' => $resp,
                        'metadatos' => ['palabra_origen' => $palabra, 'num_opciones' => 4],
                        'puntaje' => 1,
                        'orden' => (int) $n,
                        'activo' => true
                    ]
                );
                $p->opciones()->delete();
                foreach ($opciones as $l => $t) {
                    if (empty($t))
                        continue;
                    Opcion::create(['pregunta_id' => $p->id, 'letra' => $l, 'texto' => $t, 'es_correcta' => ($l === $resp), 'orden' => array_search($l, self::LETRAS_4)]);
                }
                $this->filasOk++;
            } catch (\Throwable $e) {
                $this->filasError++;
                $this->registrarError($idx + 2, 'FACTOR V', $e->getMessage());
            }
        }
    }

    private function procesarFactorE(Spreadsheet $s): void
    {
        $cat = $this->obtenerOCrearCategoria('FACTOR E', 'FACTOR_E', 'Aptitud Espacial — Rotación de figuras', 5, 2);
        $sheet = $s->getSheetByName('FACTOR E');
        if (!$sheet) {
            $this->registrarError(0, 'FACTOR E', 'Hoja no encontrada');
            return;
        }
        $filas = $sheet->toArray(null, true, true, false);
        foreach ($filas as $idx => $fila) {
            $numRaw = $fila[0] ?? null;
            if (!$numRaw)
                continue;
            $n = (int) filter_var($numRaw, FILTER_SANITIZE_NUMBER_INT);
            if ($n < 1 || $n > 20)
                continue;
            $this->filasTotal++;
            try {
                Pregunta::updateOrCreate(
                    ['categoria_id' => $cat->id, 'numero' => $n],
                    [
                        'enunciado' => "Pregunta espacial N° {$n}: Seleccione la figura correcta.",
                        'tipo' => 'opcion_multiple',
                        'respuesta_correcta' => null,
                        'metadatos' => ['requiere_imagen' => true, 'num_opciones' => 5],
                        'puntaje' => 1,
                        'orden' => $n,
                        'activo' => false
                    ]
                );
                $this->filasOk++;
            } catch (\Throwable $e) {
                $this->filasError++;
                $this->registrarError($idx + 1, 'FACTOR E', $e->getMessage());
            }
        }
    }

    private function procesarFactorR(Spreadsheet $s): void
    {
        $cat = $this->obtenerOCrearCategoria('FACTOR R', 'FACTOR_R', 'Razonamiento — Series de letras (6 opciones)', 5, 3);
        $sheet = $s->getSheetByName('FACTOR R');
        if (!$sheet) {
            $this->registrarError(0, 'FACTOR R', 'Hoja no encontrada');
            return;
        }
        $filas = $sheet->toArray(null, true, true, false);
        array_shift($filas);

        foreach ($filas as $idx => $fila) {
            $this->filasTotal++;
            $n = $fila[0] ?? null;
            if (!$n || !is_numeric($n)) {
                $this->filasTotal--;
                continue;
            }
            $n = (int) $n;
            $serie = trim((string) ($fila[1] ?? ''));
            if (empty($serie)) {
                $this->filasError++;
                $this->registrarError($idx + 2, 'FACTOR R', "Pregunta {$n}: serie vacía");
                continue;
            }

            try {
                $optsTexto = self::OPCIONES_R[$n] ?? null;
                $respLetra = self::RESPUESTAS_R[$n] ?? null;
                $respOpcion = null;
                if ($optsTexto && $respLetra) {
                    foreach ($optsTexto as $i => $opt) {
                        if (strtolower($opt) === strtolower($respLetra)) {
                            $respOpcion = self::LETRAS_6[$i];
                            break;
                        }
                    }
                }

                $p = Pregunta::updateOrCreate(
                    ['categoria_id' => $cat->id, 'numero' => $n],
                    [
                        'enunciado' => "Complete la serie: " . rtrim($serie, '. ') . " ___",
                        'tipo' => 'opcion_multiple',
                        'respuesta_correcta' => $respOpcion ?? strtoupper($respLetra ?? '?'),
                        'metadatos' => ['serie_completa' => $serie, 'letra_esperada' => $respLetra, 'num_opciones' => 6],
                        'puntaje' => 1,
                        'orden' => $n,
                        'activo' => true
                    ]
                );

                $p->opciones()->delete();
                if ($optsTexto) {
                    foreach ($optsTexto as $i => $textoOpt) {
                        if (empty($textoOpt))
                            continue;
                        $l = self::LETRAS_6[$i];
                        Opcion::create(['pregunta_id' => $p->id, 'letra' => $l, 'texto' => strtoupper($textoOpt), 'es_correcta' => ($l === $respOpcion), 'orden' => $i]);
                    }
                }
                if (!$respOpcion)
                    Log::warning("[PmaImport] FACTOR R pregunta {$n}: respuesta '{$respLetra}' no encontrada en opciones.");
                $this->filasOk++;
            } catch (\Throwable $e) {
                $this->filasError++;
                $this->registrarError($idx + 2, 'FACTOR R', $e->getMessage());
            }
        }
    }

    private function procesarFactorN(Spreadsheet $s): void
    {
        $cat = $this->obtenerOCrearCategoria('FACTOR N', 'FACTOR_N', 'Aptitud Numérica — Verificación de sumas', 10, 4);
        $sheet = $s->getSheetByName('FACTOR N');
        if (!$sheet) {
            $this->registrarError(0, 'FACTOR N', 'Hoja no encontrada');
            return;
        }
        $filas = $sheet->toArray(null, true, true, false);
        array_shift($filas);

        foreach ($filas as $idx => $fila) {
            $this->filasTotal++;
            $n = $fila[0] ?? null;
            if (!$n || !is_numeric($n)) {
                $this->filasTotal--;
                continue;
            }
            try {
                $s1 = (float) ($fila[1] ?? 0);
                $s2 = (float) ($fila[2] ?? 0);
                $s3 = (float) ($fila[3] ?? 0);
                $s4 = (float) ($fila[4] ?? 0);
                $td = (float) ($fila[5] ?? 0);
                $sr = $s1 + $s2 + $s3 + $s4;
                $ok = abs($sr - $td) < 0.001;
                $resp = $ok ? 'V' : 'F';

                $p = Pregunta::updateOrCreate(
                    ['categoria_id' => $cat->id, 'numero' => (int) $n],
                    [
                        'enunciado' => "{$s1} + {$s2} + {$s3} + {$s4} = {$td}  ¿Es correcto el resultado?",
                        'tipo' => 'verdadero_falso',
                        'respuesta_correcta' => $resp,
                        'metadatos' => ['sumando_1' => $s1, 'sumando_2' => $s2, 'sumando_3' => $s3, 'sumando_4' => $s4, 'total_dado' => $td, 'suma_real' => $sr, 'num_opciones' => 2],
                        'puntaje' => 1,
                        'orden' => (int) $n,
                        'activo' => true
                    ]
                );
                $p->opciones()->delete();
                Opcion::create(['pregunta_id' => $p->id, 'letra' => 'V', 'texto' => 'Verdadero (el resultado es correcto)', 'es_correcta' => $ok, 'orden' => 0]);
                Opcion::create(['pregunta_id' => $p->id, 'letra' => 'F', 'texto' => 'Falso (el resultado es incorrecto)', 'es_correcta' => !$ok, 'orden' => 1]);
                $this->filasOk++;
            } catch (\Throwable $e) {
                $this->filasError++;
                $this->registrarError($idx + 2, 'FACTOR N', $e->getMessage());
            }
        }
    }

    private function obtenerOCrearCategoria(string $nombre, string $codigo, string $descripcion, int $tiempo, int $orden): Categoria
    {
        return Categoria::firstOrCreate(['test_id' => $this->testId, 'codigo' => $codigo], compact('nombre', 'descripcion', 'tiempo', 'orden'));
    }

    private function registrarError(int $fila, string $factor, string $mensaje): void
    {
        $e = ['fila' => $fila, 'factor' => $factor, 'mensaje' => $mensaje];
        $this->errores[] = $e;
        Log::warning('[PmaImport] ', $e);
    }
}
