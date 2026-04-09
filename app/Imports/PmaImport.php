<?php

namespace App\Imports;

use App\Models\Categoria;
use App\Models\Importacion;
use App\Models\Opcion;
use App\Models\Pregunta;
use App\Models\Test;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Importador principal del Excel PMA-R.
 * Procesa los 4 factores: V (Verbal), E (Espacial), R (Razonamiento), N (Numérico).
 */
class PmaImport
{
    private array $errores      = [];
    private int   $filasTotal   = 0;
    private int   $filasOk      = 0;
    private int   $filasError   = 0;
    private ?Importacion $registro = null;

    // Respuestas correctas conocidas del PMA-R para cada factor
    private const RESPUESTAS_V = [
        1=>'C',2=>'D',3=>'B',4=>'D',5=>'D',6=>'C',7=>'A',8=>'D',9=>'C',10=>'D',
        11=>'C',12=>'C',13=>'B',14=>'A',15=>'C',16=>'C',17=>'B',18=>'A',19=>'A',20=>'C',
        21=>'C',22=>'B',23=>'A',24=>'B',25=>'C',26=>'C',27=>'C',28=>'D',29=>'C',30=>'C',
        31=>'D',32=>'C',33=>'A',34=>'D',35=>'A',36=>'C',37=>'D',38=>'B',39=>'D',40=>'D',
        41=>'C',42=>'B',43=>'B',44=>'A',45=>'B',46=>'B',47=>'A',48=>'B',49=>'B',50=>'C',
    ];

    // Letras de opciones estándar
    private const LETRAS = ['A','B','C','D'];

    public function __construct(
        private readonly int $userId,
        private readonly int $testId,
    ) {}

    public function importar(string $rutaArchivo): array
    {
        $this->registro = Importacion::create([
            'user_id'       => $this->userId,
            'nombre_archivo'=> basename($rutaArchivo),
            'tipo'          => 'excel',
            'estado'        => 'procesando',
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
            $this->errores[] = ['fila' => 0, 'mensaje' => 'Error crítico: '.$e->getMessage()];
            Log::error('[PmaImport] Error crítico', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }

        $this->registro->update([
            'estado'          => $estado,
            'filas_total'     => $this->filasTotal,
            'filas_exitosas'  => $this->filasOk,
            'filas_con_error' => $this->filasError,
            'errores'         => $this->errores,
            'estadisticas'    => [
                'factores_procesados' => ['V', 'E', 'R', 'N'],
                'timestamp'           => now()->toIso8601String(),
            ],
        ]);

        return [
            'importacion_id'  => $this->registro->id,
            'estado'          => $estado,
            'filas_total'     => $this->filasTotal,
            'filas_exitosas'  => $this->filasOk,
            'filas_con_error' => $this->filasError,
            'errores'         => $this->errores,
        ];
    }

    // ─── FACTOR V: Vocabulario / Sinónimos ────────────────────────────────

    private function procesarFactorV(Spreadsheet $spreadsheet): void
    {
        $categoria = $this->obtenerOCrearCategoria('FACTOR V', 'FACTOR_V', 'Aptitud Verbal - Vocabulario y sinónimos', 5, 1);
        $sheet     = $spreadsheet->getSheetByName('FACTOR V');

        if (!$sheet) {
            $this->registrarError(0, 'FACTOR V', 'Hoja FACTOR V no encontrada en el Excel');
            return;
        }

        $filas = $sheet->toArray(null, true, true, false);
        array_shift($filas); // quitar encabezado

        foreach ($filas as $idx => $fila) {
            $this->filasTotal++;
            $numero = $fila[0] ?? null;

            if (!$numero || !is_numeric($numero)) continue;

            try {
                $this->validarFilaV($fila, $idx + 2);
                $palabra   = trim((string)($fila[1] ?? ''));
                $opciones  = [
                    'A' => trim((string)($fila[2] ?? '')),
                    'B' => trim((string)($fila[3] ?? '')),
                    'C' => trim((string)($fila[4] ?? '')),
                    'D' => trim((string)($fila[5] ?? '')),
                ];
                $respuesta = self::RESPUESTAS_V[(int)$numero] ?? 'C';

                $pregunta = Pregunta::updateOrCreate(
                    ['categoria_id' => $categoria->id, 'numero' => (int)$numero],
                    [
                        'enunciado'        => "¿Cuál es el sinónimo de: {$palabra}?",
                        'tipo'             => 'opcion_multiple',
                        'respuesta_correcta'=> $respuesta,
                        'metadatos'        => ['palabra_origen' => $palabra],
                        'puntaje'          => 1,
                        'orden'            => (int)$numero,
                        'activo'           => true,
                    ]
                );

                // Eliminar opciones previas para evitar duplicados
                $pregunta->opciones()->delete();

                foreach ($opciones as $letra => $texto) {
                    if (empty($texto)) continue;
                    Opcion::create([
                        'pregunta_id' => $pregunta->id,
                        'letra'       => $letra,
                        'texto'       => $texto,
                        'es_correcta' => ($letra === $respuesta),
                        'orden'       => array_search($letra, self::LETRAS),
                    ]);
                }

                $this->filasOk++;
            } catch (\InvalidArgumentException $e) {
                $this->filasError++;
                $this->registrarError($idx + 2, 'FACTOR V', $e->getMessage());
            }
        }
    }

    // ─── FACTOR E: Espacial (sin imágenes, marcador de posición) ─────────

    private function procesarFactorE(Spreadsheet $spreadsheet): void
    {
        $categoria = $this->obtenerOCrearCategoria('FACTOR E', 'FACTOR_E', 'Aptitud Espacial - Visualización y rotación mental', 5, 2);
        $sheet     = $spreadsheet->getSheetByName('FACTOR E');

        if (!$sheet) {
            $this->registrarError(0, 'FACTOR E', 'Hoja FACTOR E no encontrada en el Excel');
            return;
        }

        $filas = $sheet->toArray(null, true, true, false);

        foreach ($filas as $idx => $fila) {
            $numRaw = $fila[0] ?? null;
            if (!$numRaw) continue;

            $numero = (int) filter_var($numRaw, FILTER_SANITIZE_NUMBER_INT);
            if ($numero < 1 || $numero > 20) continue;

            $this->filasTotal++;

            try {
                // FACTOR E contiene imágenes; guardamos metadatos para carga posterior
                Pregunta::updateOrCreate(
                    ['categoria_id' => $categoria->id, 'numero' => $numero],
                    [
                        'enunciado'         => "Pregunta espacial N° {$numero}: Seleccione la figura que completa correctamente el patrón.",
                        'tipo'              => 'opcion_multiple',
                        'respuesta_correcta'=> null, // Se configura manualmente con las imágenes
                        'metadatos'         => [
                            'requiere_imagen' => true,
                            'nota'            => 'Factor espacial: cargar imagen desde el material físico del PMA-R',
                        ],
                        'puntaje' => 1,
                        'orden'   => $numero,
                        'activo'  => false, // Inactivo hasta configurar imagen
                    ]
                );
                $this->filasOk++;
            } catch (\Throwable $e) {
                $this->filasError++;
                $this->registrarError($idx + 1, 'FACTOR E', $e->getMessage());
            }
        }
    }

    // ─── FACTOR R: Razonamiento (series de letras) ────────────────────────

    private function procesarFactorR(Spreadsheet $spreadsheet): void
    {
        $categoria = $this->obtenerOCrearCategoria('FACTOR R', 'FACTOR_R', 'Razonamiento - Completar series de letras', 5, 3);
        $sheet     = $spreadsheet->getSheetByName('FACTOR R');

        if (!$sheet) {
            $this->registrarError(0, 'FACTOR R', 'Hoja FACTOR R no encontrada en el Excel');
            return;
        }

        // Respuestas conocidas del PMA-R para series de letras
        $respuestasR = [
            1=>'h', 2=>'e', 3=>'j', 4=>'j', 5=>'g',  6=>'d', 7=>'o', 8=>'a', 9=>'m', 10=>'k',
            11=>'i',12=>'e',13=>'d',14=>'l',15=>'i', 16=>'j',17=>'h',18=>'a',19=>'o',20=>'y',
            21=>'g',22=>'v',23=>'j',24=>'y',25=>'h', 26=>'g',27=>'s',28=>'y',29=>'i',30=>'i',
        ];

        $filas = $sheet->toArray(null, true, true, false);
        array_shift($filas); // encabezado

        foreach ($filas as $idx => $fila) {
            $this->filasTotal++;
            $numero = $fila[0] ?? null;
            if (!$numero || !is_numeric($numero)) { $this->filasTotal--; continue; }

            $serie = trim((string)($fila[1] ?? ''));
            if (empty($serie)) {
                $this->filasError++;
                $this->registrarError($idx + 2, 'FACTOR R', "Fila {$numero}: serie vacía");
                continue;
            }

            try {
                $respuesta    = $respuestasR[(int)$numero] ?? '?';
                $parteVisible = rtrim($serie, '. ');

                Pregunta::updateOrCreate(
                    ['categoria_id' => $categoria->id, 'numero' => (int)$numero],
                    [
                        'enunciado'         => "Complete la serie: {$parteVisible} ___",
                        'tipo'              => 'opcion_multiple',
                        'respuesta_correcta'=> strtoupper($respuesta),
                        'metadatos'         => [
                            'serie_completa' => $serie,
                            'letra_esperada' => $respuesta,
                        ],
                        'puntaje' => 1,
                        'orden'   => (int)$numero,
                        'activo'  => true,
                    ]
                );
                $this->filasOk++;
            } catch (\Throwable $e) {
                $this->filasError++;
                $this->registrarError($idx + 2, 'FACTOR R', $e->getMessage());
            }
        }
    }

    // ─── FACTOR N: Numérico (verificación de sumas) ───────────────────────

    private function procesarFactorN(Spreadsheet $spreadsheet): void
    {
        $categoria = $this->obtenerOCrearCategoria('FACTOR N', 'FACTOR_N', 'Aptitud Numérica - Verificación de operaciones aritméticas', 10, 4);
        $sheet     = $spreadsheet->getSheetByName('FACTOR N');

        if (!$sheet) {
            $this->registrarError(0, 'FACTOR N', 'Hoja FACTOR N no encontrada en el Excel');
            return;
        }

        $filas = $sheet->toArray(null, true, true, false);
        array_shift($filas); // encabezado

        foreach ($filas as $idx => $fila) {
            $this->filasTotal++;
            $numero = $fila[0] ?? null;
            if (!$numero || !is_numeric($numero)) { $this->filasTotal--; continue; }

            try {
                [$n, $s1, $s2, $s3, $s4, $totalDado] = [
                    (int)$numero,
                    (float)($fila[1] ?? 0),
                    (float)($fila[2] ?? 0),
                    (float)($fila[3] ?? 0),
                    (float)($fila[4] ?? 0),
                    (float)($fila[5] ?? 0),
                ];

                $sumaReal      = $s1 + $s2 + $s3 + $s4;
                $esCorrecta    = abs($sumaReal - $totalDado) < 0.001;
                $respuesta     = $esCorrecta ? 'V' : 'F';

                $enunciado = "{$s1} + {$s2} + {$s3} + {$s4} = {$totalDado}  ¿Es correcto el resultado?";

                $pregunta = Pregunta::updateOrCreate(
                    ['categoria_id' => $categoria->id, 'numero' => $n],
                    [
                        'enunciado'         => $enunciado,
                        'tipo'              => 'verdadero_falso',
                        'respuesta_correcta'=> $respuesta,
                        'metadatos'         => [
                            'sumando_1'  => $s1,
                            'sumando_2'  => $s2,
                            'sumando_3'  => $s3,
                            'sumando_4'  => $s4,
                            'total_dado' => $totalDado,
                            'suma_real'  => $sumaReal,
                        ],
                        'puntaje' => 1,
                        'orden'   => $n,
                        'activo'  => true,
                    ]
                );

                $pregunta->opciones()->delete();
                foreach ([['V','Verdadero',$esCorrecta],['F','Falso',!$esCorrecta]] as [$letra,$texto,$correct]) {
                    Opcion::create([
                        'pregunta_id' => $pregunta->id,
                        'letra'       => $letra,
                        'texto'       => $texto,
                        'es_correcta' => $correct,
                        'orden'       => $letra === 'V' ? 0 : 1,
                    ]);
                }

                $this->filasOk++;
            } catch (\Throwable $e) {
                $this->filasError++;
                $this->registrarError($idx + 2, 'FACTOR N', $e->getMessage());
            }
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    private function obtenerOCrearCategoria(
        string $nombre, string $codigo, string $descripcion, int $tiempo, int $orden
    ): Categoria {
        return Categoria::firstOrCreate(
            ['test_id' => $this->testId, 'codigo' => $codigo],
            compact('nombre', 'descripcion', 'tiempo', 'orden')
        );
    }

    private function validarFilaV(array $fila, int $numFila): void
    {
        if (empty($fila[1])) throw new \InvalidArgumentException("Fila {$numFila}: Columna 'Palabra' vacía");
        if (empty($fila[2])) throw new \InvalidArgumentException("Fila {$numFila}: Opción 1 vacía");
        if (empty($fila[3])) throw new \InvalidArgumentException("Fila {$numFila}: Opción 2 vacía");
        if (empty($fila[4])) throw new \InvalidArgumentException("Fila {$numFila}: Opción 3 vacía");
        if (empty($fila[5])) throw new \InvalidArgumentException("Fila {$numFila}: Opción 4 vacía");
    }

    private function registrarError(int $fila, string $factor, string $mensaje): void
    {
        $entry = ['fila' => $fila, 'factor' => $factor, 'mensaje' => $mensaje];
        $this->errores[] = $entry;
        Log::warning('[PmaImport] Error en fila', $entry);
    }
}
