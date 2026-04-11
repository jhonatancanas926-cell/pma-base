<?php

namespace App\Services;

use App\Models\SesionPrueba;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\Style\Font;

class DocumentoService
{
    private PhpWord $word;

    private const COLORES = [
        'primario'  => '1A3A6B',
        'acento'    => '2E75B6',
        'verde'     => '107C10',
        'rojo'      => 'C50F1F',
        'gris'      => '737373',
        'fondo'     => 'EBF3FA',
        'texto'     => '1C1C1C',
    ];

    // ─── Generar documentación general del sistema ────────────────────────

    public function generarDocumentacionSistema(string $destino): string
    {
        $this->word = new PhpWord();
        $this->configurarEstilos();

        $section = $this->word->addSection($this->propiedadesPagina());

        // Portada
        $this->agregarPortada($section);

        // Índice
        $section->addTOC(['name' => 'Heading1'], ['tabLeader' => \PhpOffice\PhpWord\Style\TOC::TABLEADER_DOT]);
        $section->addPageBreak();

        // Secciones de documentación
        $this->agregarSeccionSistema($section);
        $this->agregarSeccionPMA($section);
        $this->agregarSeccionEstructura($section);
        $this->agregarSeccionInterpretacion($section);
        $this->agregarSeccionEjemplo($section);
        $this->agregarSeccionAPI($section);

        return $this->guardar($destino, 'documentacion_sistema_pma');
    }

    // ─── Generar reporte individual de resultados ─────────────────────────

    public function generarReporteUsuario(SesionPrueba $sesion, array $resumen, string $destino): string
    {
        $this->word = new PhpWord();
        $this->configurarEstilos();

        $section = $this->word->addSection($this->propiedadesPagina());

        $this->agregarEncabezadoReporte($section, $sesion, $resumen);
        $this->agregarTablaResultados($section, $resumen);
        $this->agregarGraficoTextual($section, $resumen);
        $this->agregarAnalisisErrores($section, $resumen);
        $this->agregarInterpretacion($section, $resumen);
        $this->agregarPieReporte($section);

        $nombreArchivo = 'reporte_' . str_replace(' ', '_', strtolower($sesion->user->name)) . '_' . now()->format('Ymd_His');
        return $this->guardar($destino, $nombreArchivo);
    }

    public function generarReportePdfUsuario(SesionPrueba $sesion, array $resumen, string $destino): string
    {
        $pdf = new \TCPDF();
        $pdf->SetCreator('Sistema');
        $pdf->SetAuthor('Uniempresarial');
        $pdf->SetTitle('Informe PMA-R');
        $pdf->SetMargins(15, 30, 15);
        $pdf->AddPage();

        $navy = [15, 31, 61];
        $blue = [46, 117, 182];
        $green = [16, 124, 16];
        $red = [197, 15, 31];
        $gray = [107, 122, 141];

        $pdf->SetFillColor(...$navy);
        $pdf->Rect(0, 0, 210, 25, 'F');

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetXY(15, 10);
        $pdf->Cell(0, 0, 'INFORME INDIVIDUAL PMA-R');

        $pdf->Ln(25);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Datos del Evaluado', 0, 1);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, "Nombre: " . $resumen['usuario'], 0, 1);
        $pdf->Cell(0, 6, "Documento: " . ($resumen['documento'] ?? '—'), 0, 1);
        $pdf->Cell(0, 6, "Prueba: " . $resumen['prueba'], 0, 1);
        $pdf->Cell(0, 6, "Fecha: " . ($resumen['fecha'] ?? '—'), 0, 1);

        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, 'Resultados por Factor', 0, 1);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(40, 7, 'Factor', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Correctas', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Pt. Bruto', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Percentil', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Nivel', 1, 1, 'C', true);

        $pdf->SetFont('helvetica', '', 10);

        foreach ($resumen['resultados'] as $r) {
            $nivel = $r['nivel'] ?? '—';

            if (in_array($nivel, ['Muy Alto', 'Alto'])) {
                $pdf->SetTextColor(...$green);
            } elseif (in_array($nivel, ['Muy Bajo', 'Bajo'])) {
                $pdf->SetTextColor(...$red);
            } else {
                $pdf->SetTextColor(...$blue);
            }

            $pdf->Cell(40, 7, $r['factor'], 1, 0, 'C');
            $pdf->Cell(30, 7, $r['correctas'], 1, 0, 'C');
            $pdf->Cell(30, 7, number_format($r['puntaje_bruto'], 2), 1, 0, 'C');
            $pdf->Cell(40, 7, $r['percentil'] ? 'P'.$r['percentil'] : '—', 1, 0, 'C');
            $pdf->Cell(40, 7, $nivel, 1, 1, 'C');
        }

        $pdf->Ln(10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, 'Interpretación', 0, 1);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 6, $resumen['interpretacion'] ?? '—');

        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, 'Perfil de Rendimiento (%)', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        foreach ($resumen['resultados'] as $r) {
            $pct = (int) round($r['porcentaje'] ?? 0);
            $barWidth = $pct; // max 100
            $pdf->Cell(40, 6, $r['factor'], 0, 0);

            // Draw bar
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetFillColor(220, 220, 220);
            $pdf->Rect($x, $y + 1, 100, 4, 'F');
            $pdf->SetFillColor(...$navy);
            if ($barWidth > 0) {
                $pdf->Rect($x, $y + 1, $barWidth, 4, 'F');
            }
            $pdf->SetX($x + 105);
            $pdf->Cell(20, 6, $pct . '%', 0, 1);
        }

        $pdf->SetY(-20);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(...$gray);
        $pdf->Cell(0, 10, 'Informe confidencial - Página ' . $pdf->getAliasNumPage(), 0, 0, 'C');

        if (!is_dir($destino)) mkdir($destino, 0755, true);
        $nombreArchivo = 'reporte_' . str_replace(' ', '_', strtolower($sesion->user->name)) . '_' . now()->format('Ymd_His') . '.pdf';
        $rutaPdf = rtrim($destino, '/') . '/' . $nombreArchivo;

        $pdf->Output($rutaPdf, 'F');

        return $rutaPdf;
    }

    // ─── Portada ──────────────────────────────────────────────────────────

    private function agregarPortada($section): void
    {
        for ($i = 0; $i < 5; $i++) $section->addTextBreak();

        $section->addText(
            'SISTEMA DE AUTOMATIZACIÓN DE EVALUACIONES PSICOMÉTRICAS',
            ['name' => 'Arial', 'size' => 22, 'bold' => true, 'color' => self::COLORES['primario']],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 200]
        );
        $section->addText(
            'Batería PMA-R — Aptitudes Mentales Primarias',
            ['name' => 'Arial', 'size' => 16, 'color' => self::COLORES['acento']],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 100]
        );
        $section->addText(
            'Documentación Técnica y Manual de Usuario',
            ['name' => 'Arial', 'size' => 12, 'color' => self::COLORES['gris']],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 600]
        );

        // Línea decorativa
        $section->addText(
            str_repeat('─', 60),
            ['name' => 'Arial', 'size' => 12, 'color' => self::COLORES['acento']],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 200]
        );

        $section->addText(
            'Uniempresarial — Sistemas de Información',
            ['name' => 'Arial', 'size' => 11, 'bold' => true, 'color' => self::COLORES['texto']],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );
        $section->addText(
            now()->format('F Y'),
            ['name' => 'Arial', 'size' => 11, 'color' => self::COLORES['gris']],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        $section->addPageBreak();
    }

    // ─── Sección 1: Descripción del Sistema ──────────────────────────────

    private function agregarSeccionSistema($section): void
    {
        $section->addTitle('1. Descripción General del Sistema', 1);

        $section->addText(
            'El Sistema de Automatización de Evaluaciones Psicométricas (SAEP) es una plataforma API RESTful desarrollada en Laravel 12 que permite digitalizar, administrar y analizar pruebas psicológicas estandarizadas. El sistema reemplaza los flujos de trabajo basados en papel, automatizando la carga de preguntas desde Excel, la aplicación de pruebas en línea, el cálculo de puntajes y la generación de reportes.',
            'Normal', ['spaceAfter' => 200]
        );

        $section->addTitle('1.1 Objetivos', 2);
        $bullets = [
            'Digitalizar y gestionar baterías psicométricas (PMA-R, NEO PI-R y otras).',
            'Administrar pruebas a múltiples usuarios de forma simultánea mediante API.',
            'Calcular puntajes, percentiles y niveles de rendimiento automáticamente.',
            'Identificar patrones de error por usuario y categoría cognitiva.',
            'Generar reportes individuales en formato Word (.docx).',
            'Registrar trazabilidad completa de cada sesión de evaluación.',
        ];
        foreach ($bullets as $b) {
            $section->addListItem($b, 0, 'Normal');
        }
        $section->addTextBreak(1);

        $section->addTitle('1.2 Arquitectura Técnica', 2);
        $section->addText(
            'El sistema sigue una arquitectura de microservicio REST aislado de la plataforma Laravel principal (justificación: protección del entorno de producción, desacoplamiento de lógica psicométrica especializada y facilidad de escalado independiente).',
            'Normal', ['spaceAfter' => 200]
        );

        // Tabla de stack tecnológico
        $table = $section->addTable(['borderColor' => 'CCCCCC', 'borderSize' => 6, 'cellMargin' => 80]);
        $encabezados = ['Componente', 'Tecnología', 'Versión'];
        $filas = [
            ['Backend Framework', 'Laravel', '12.x'],
            ['Lenguaje',         'PHP',     '8.2+'],
            ['Base de datos',    'MySQL',   '8.0+'],
            ['Autenticación',    'Laravel Sanctum', '4.x'],
            ['Importación Excel','Maatwebsite Excel / PhpSpreadsheet', '3.1 / 1.29'],
            ['Documentos Word',  'PHPWord', '1.2+'],
            ['Servidor',         'Nginx / Apache', 'LTS'],
        ];

        $this->agregarFilaTabla($table, $encabezados, true);
        foreach ($filas as $fila) {
            $this->agregarFilaTabla($table, $fila, false);
        }
        $section->addTextBreak(1);
    }

    // ─── Sección 2: Prueba PMA-R ──────────────────────────────────────────

    private function agregarSeccionPMA($section): void
    {
        $section->addTitle('2. La Prueba PMA-R', 1);

        $section->addText(
            'Las Aptitudes Mentales Primarias (PMA) fueron desarrolladas por Louis L. Thurstone y Thelma G. Thurstone. La versión PMA-R evalúa las cinco capacidades cognitivas fundamentales que componen la inteligencia general según el modelo factorial de Thurstone.',
            'Normal', ['spaceAfter' => 200]
        );

        $section->addTitle('2.1 Factores Evaluados', 2);

        $factores = [
            ['FACTOR V', 'Verbal', '50 ítems', '4 opciones', '5 min', 'Comprensión léxica y riqueza de vocabulario. El evaluado selecciona el sinónimo correcto de una palabra estímulo.'],
            ['FACTOR E', 'Espacial', '20 ítems', '5 figuras', '5 min', 'Visualización y rotación mental de figuras geométricas en el plano y el espacio.'],
            ['FACTOR R', 'Razonamiento', '30 ítems', 'Letra libre', '5 min', 'Capacidad de inducir reglas en series de letras y completar el siguiente elemento.'],
            ['FACTOR N', 'Numérico', '70 ítems', 'V o F', '10 min', 'Rapidez y exactitud en el manejo de operaciones aritméticas (verificación de sumas).'],
        ];

        $table = $section->addTable(['borderColor' => 'CCCCCC', 'borderSize' => 6, 'cellMargin' => 80]);
        $this->agregarFilaTabla($table, ['Factor', 'Nombre', 'Ítems', 'Formato respuesta', 'Tiempo', 'Descripción'], true);
        foreach ($factores as $f) {
            $this->agregarFilaTabla($table, $f, false);
        }
        $section->addTextBreak(1);

        $section->addTitle('2.2 Aplicación y Corrección', 2);
        $section->addText(
            'La corrección aplica la fórmula de corrección por azar estándar del PMA-R: Puntaje Bruto = Aciertos − (Errores / (N_opciones − 1)). Los puntajes negativos se reemplazan por cero. Los puntajes brutos se transforman a percentiles usando baremos estandarizados para población adulta universitaria colombiana.',
            'Normal', ['spaceAfter' => 200]
        );
    }

    // ─── Sección 3: Estructura de Preguntas ──────────────────────────────

    private function agregarSeccionEstructura($section): void
    {
        $section->addTitle('3. Estructura de las Preguntas', 1);

        $section->addTitle('3.1 Modelo de Datos', 2);
        $section->addText(
            'Cada pregunta pertenece a una categoría (factor) dentro de una prueba. La estructura jerárquica es: Test → Categoría → Pregunta → Opciones.',
            'Normal', ['spaceAfter' => 200]
        );

        $campos = [
            ['Campo', 'Tipo', 'Descripción'],
            ['id',                'bigint',   'Identificador único auto-incremental'],
            ['categoria_id',      'bigint FK','Relación con el factor (V, E, R, N)'],
            ['numero',            'integer',  'Número de ítem dentro del factor'],
            ['enunciado',         'text',     'Texto de la pregunta presentada al evaluado'],
            ['tipo',              'enum',     'opcion_multiple | verdadero_falso | texto'],
            ['respuesta_correcta','varchar',  'Letra o valor de la respuesta esperada'],
            ['metadatos',         'JSON',     'Datos extra: palabra_origen, sumandos, serie'],
            ['puntaje',           'integer',  'Valor asignado a la respuesta correcta (defecto 1)'],
            ['activo',            'boolean',  'Controla si la pregunta se presenta en la prueba'],
        ];

        $table = $section->addTable(['borderColor' => 'CCCCCC', 'borderSize' => 6, 'cellMargin' => 80]);
        foreach ($campos as $i => $fila) {
            $this->agregarFilaTabla($table, $fila, $i === 0);
        }
        $section->addTextBreak(1);

        $section->addTitle('3.2 Formatos por Factor', 2);
        $formatos = [
            'FACTOR V — Sinónimos: La pregunta presenta una palabra en mayúsculas. El evaluado elige entre 4 opciones el sinónimo correcto. Ejemplo: HÚMEDO → (A) Corto (B) Humano (C) Mojado (D) Moderado. Respuesta: C.',
            'FACTOR E — Espacial: La pregunta presenta una figura geométrica de referencia. El evaluado identifica cuál de 5 figuras es idéntica en forma pero diferente en orientación (requiere material gráfico).',
            'FACTOR R — Razonamiento: Se presenta una serie de letras incompleta. El evaluado debe inducir el patrón y escribir la siguiente letra. Ejemplo: a a b c c d e e f g g ... → Respuesta: h.',
            'FACTOR N — Numérico: Se presenta una suma con 4 sumandos y un total. El evaluado indica V (verdadero) si el total es correcto o F (falso) si no lo es. Ejemplo: 61+34+78+53=226 → V.',
        ];
        foreach ($formatos as $f) {
            $section->addListItem($f, 0, 'Normal');
        }
        $section->addTextBreak(1);
    }

    // ─── Sección 4: Interpretación de Resultados ─────────────────────────

    private function agregarSeccionInterpretacion($section): void
    {
        $section->addTitle('4. Interpretación de Resultados', 1);

        $section->addTitle('4.1 Niveles de Rendimiento', 2);

        $niveles = [
            ['Nivel', 'Percentil', 'Interpretación Clínica'],
            ['Muy Alto', 'P90 – P99', 'Capacidad cognitiva significativamente superior al promedio. Potencial alto para tareas analíticas.'],
            ['Alto',     'P70 – P89', 'Rendimiento superior. Domina con facilidad tareas propias del factor evaluado.'],
            ['Medio',    'P30 – P69', 'Rendimiento dentro del rango esperado para la población de referencia.'],
            ['Bajo',     'P10 – P29', 'Por debajo del promedio. Se recomienda exploración adicional y apoyo focalizado.'],
            ['Muy Bajo', 'P1  – P9',  'Rendimiento significativamente inferior. Requiere intervención especializada.'],
        ];

        $table = $section->addTable(['borderColor' => 'CCCCCC', 'borderSize' => 6, 'cellMargin' => 80]);
        foreach ($niveles as $i => $fila) {
            $this->agregarFilaTabla($table, $fila, $i === 0);
        }
        $section->addTextBreak(1);

        $section->addTitle('4.2 Análisis de Errores', 2);
        $section->addText(
            'El sistema registra la opción elegida en cada respuesta incorrecta. Esto permite identificar: (1) opciones distractoras recurrentes que sugieren confusión conceptual; (2) preguntas con alta tasa de error en la muestra; (3) patrones de impulsividad o sesgo sistemático en la selección de respuestas.',
            'Normal', ['spaceAfter' => 200]
        );
    }

    // ─── Sección 5: Ejemplo de Uso (API) ─────────────────────────────────

    private function agregarSeccionEjemplo($section): void
    {
        $section->addTitle('5. Ejemplo de Uso', 1);

        $section->addTitle('5.1 Flujo Típico de Evaluación', 2);
        $pasos = [
            '1. El administrador sube el archivo Excel PMA_R_Preguntas.xlsx mediante POST /api/v1/importar.',
            '2. El sistema procesa los 4 factores y carga las preguntas en la base de datos automáticamente.',
            '3. El evaluado se autentica con POST /api/v1/auth/login y obtiene su token Bearer.',
            '4. El evaluado inicia una sesión de prueba con POST /api/v1/sesiones.',
            '5. El evaluado responde pregunta por pregunta con POST /api/v1/sesiones/{id}/responder.',
            '6. Al terminar, POST /api/v1/sesiones/{id}/finalizar calcula resultados y percentiles.',
            '7. El evaluador descarga el reporte en Word con GET /api/v1/sesiones/{id}/reporte.',
        ];
        foreach ($pasos as $paso) {
            $section->addListItem($paso, 0, 'Normal');
        }
        $section->addTextBreak(1);

        $section->addTitle('5.2 Ejemplo de Respuesta JSON (Resultados)', 2);
        $json = <<<'JSON'
{
  "sesion_id": 42,
  "usuario": "Ana García",
  "prueba": "PMA - Aptitudes Mentales Primarias",
  "fecha": "15/01/2025 10:35",
  "tiempo_empleado": "23.5 min",
  "puntaje_total": 87.3,
  "porcentaje_global": 71.0,
  "resultados": [
    {
      "factor": "FACTOR V",
      "correctas": 38, "incorrectas": 7, "omitidas": 5,
      "puntaje_bruto": 35.67,
      "percentil": 70, "nivel": "Alto"
    },
    {
      "factor": "FACTOR N",
      "correctas": 42, "incorrectas": 18, "omitidas": 10,
      "puntaje_bruto": 36.0,
      "percentil": 50, "nivel": "Medio"
    }
  ],
  "interpretacion": "Fortalezas cognitivas en: FACTOR V, FACTOR R."
}
JSON;

        $section->addText('Ejemplo de respuesta JSON:', ['bold' => true, 'name' => 'Courier New', 'size' => 9]);
        foreach (explode("\n", $json) as $linea) {
            $section->addText(
                htmlspecialchars($linea),
                ['name' => 'Courier New', 'size' => 8, 'color' => '1C3A6B'],
                ['spaceAfter' => 0, 'spaceBefore' => 0]
            );
        }
        $section->addTextBreak(1);
    }

    // ─── Sección 6: Documentación API ────────────────────────────────────

    private function agregarSeccionAPI($section): void
    {
        $section->addTitle('6. Referencia de la API', 1);

        $endpoints = [
            ['Método', 'Endpoint', 'Descripción', 'Auth'],
            ['POST', '/api/v1/auth/register',     'Registrar nuevo usuario',       'No'],
            ['POST', '/api/v1/auth/login',         'Iniciar sesión → token',        'No'],
            ['POST', '/api/v1/auth/logout',        'Cerrar sesión',                 'Sí'],
            ['GET',  '/api/v1/tests',              'Listar pruebas disponibles',    'Sí'],
            ['GET',  '/api/v1/tests/{id}',         'Detalle de prueba y factores',  'Sí'],
            ['GET',  '/api/v1/tests/{id}/preguntas','Preguntas de un factor',       'Sí'],
            ['POST', '/api/v1/importar',           'Importar Excel PMA-R',          'Admin'],
            ['POST', '/api/v1/sesiones',           'Iniciar sesión de prueba',      'Sí'],
            ['POST', '/api/v1/sesiones/{id}/responder','Registrar respuesta',       'Sí'],
            ['POST', '/api/v1/sesiones/{id}/finalizar','Calcular resultados',       'Sí'],
            ['GET',  '/api/v1/sesiones/{id}/resultados','Ver resultados detallados','Sí'],
            ['GET',  '/api/v1/sesiones/{id}/reporte','Descargar reporte Word',      'Evaluador'],
            ['GET',  '/api/v1/estadisticas',       'Panel estadístico global',      'Admin'],
        ];

        $table = $section->addTable(['borderColor' => 'CCCCCC', 'borderSize' => 6, 'cellMargin' => 80]);
        foreach ($endpoints as $i => $fila) {
            $this->agregarFilaTabla($table, $fila, $i === 0);
        }
    }

    // ─── Encabezado del reporte de usuario ───────────────────────────────

    private function agregarEncabezadoReporte($section, SesionPrueba $sesion, array $resumen): void
    {
        $section->addText(
            'REPORTE DE EVALUACIÓN — PMA-R',
            ['name' => 'Arial', 'size' => 18, 'bold' => true, 'color' => self::COLORES['primario']],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 300]
        );

        $datos = [
            ['Evaluado',  $resumen['usuario']],
            ['Documento', $resumen['documento'] ?? '—'],
            ['Prueba',    $resumen['prueba']],
            ['Fecha',     $resumen['fecha'] ?? '—'],
            ['Tiempo',    $resumen['tiempo_empleado'] ?? '—'],
            ['Estado',    ucfirst($resumen['estado'])],
        ];

        $table = $section->addTable(['borderColor' => 'CCCCCC', 'borderSize' => 6, 'cellMargin' => 80]);
        foreach ($datos as [$campo, $valor]) {
            $row = $table->addRow();
            $cell1 = $row->addCell(3000, ['bgColor' => self::COLORES['fondo']]);
            $cell1->addText($campo, ['bold' => true, 'name' => 'Arial', 'size' => 10]);
            $cell2 = $row->addCell(6360);
            $cell2->addText($valor, ['name' => 'Arial', 'size' => 10]);
        }
        $section->addTextBreak(1);
    }

    // ─── Tabla de resultados por factor ──────────────────────────────────

    private function agregarTablaResultados($section, array $resumen): void
    {
        $section->addTitle('Resultados por Factor', 1);

        $table = $section->addTable(['borderColor' => 'CCCCCC', 'borderSize' => 6, 'cellMargin' => 80]);
        $this->agregarFilaTabla($table, ['Factor','Correctas','Incorrectas','Omitidas','Puntaje','Percentil','Nivel'], true);

        foreach ($resumen['resultados'] as $r) {
            $colorNivel = match($r['nivel'] ?? '') {
                'Muy Alto', 'Alto' => self::COLORES['verde'],
                'Bajo', 'Muy Bajo' => self::COLORES['rojo'],
                default            => self::COLORES['texto'],
            };
            $row   = $table->addRow();
            $celdas = [
                $r['factor'],
                $r['correctas'],
                $r['incorrectas'],
                $r['omitidas'],
                number_format($r['puntaje_bruto'], 2),
                $r['percentil'] ? 'P'.$r['percentil'] : '—',
                $r['nivel'] ?? '—',
            ];
            foreach ($celdas as $i => $v) {
                $cell = $row->addCell(null);
                $fontStyle = ['name' => 'Arial', 'size' => 9];
                if ($i === 6) $fontStyle['color'] = $colorNivel;
                $cell->addText((string)$v, $fontStyle);
            }
        }
        $section->addTextBreak(1);
    }

    // ─── Barra de progreso textual ────────────────────────────────────────

    private function agregarGraficoTextual($section, array $resumen): void
    {
        $section->addTitle('Perfil de Rendimiento', 1);

        foreach ($resumen['resultados'] as $r) {
            $pct  = (int) round($r['porcentaje'] ?? 0);
            $bar  = str_repeat('█', (int)($pct / 5)) . str_repeat('░', 20 - (int)($pct / 5));
            $section->addText(
                "{$r['factor']}: [{$bar}] {$pct}% — {$r['nivel']}",
                ['name' => 'Courier New', 'size' => 9, 'color' => self::COLORES['primario']],
                ['spaceAfter' => 60]
            );
        }
        $section->addTextBreak(1);
    }

    // ─── Análisis de errores ──────────────────────────────────────────────

    private function agregarAnalisisErrores($section, array $resumen): void
    {
        $section->addTitle('Análisis de Errores por Factor', 1);

        foreach ($resumen['resultados'] as $r) {
            $analisis = $r['analisis_errores'] ?? [];
            if (empty($analisis)) continue;

            $section->addText($r['factor'], ['bold' => true, 'name' => 'Arial', 'size' => 11]);

            if (!empty($analisis['sesgo_distractor'])) {
                $section->addText('⚠ ' . $analisis['sesgo_distractor'], ['name' => 'Arial', 'size' => 10, 'color' => self::COLORES['rojo']]);
            }

            if (!empty($analisis['opciones_frecuentes'])) {
                $linea = 'Distribución de errores: ';
                foreach ($analisis['opciones_frecuentes'] as $opcion => $frecuencia) {
                    $linea .= "Opción {$opcion}: {$frecuencia}x  ";
                }
                $section->addText($linea, ['name' => 'Arial', 'size' => 9, 'color' => self::COLORES['gris']]);
            }
        }
        $section->addTextBreak(1);
    }

    // ─── Interpretación final ─────────────────────────────────────────────

    private function agregarInterpretacion($section, array $resumen): void
    {
        $section->addTitle('Interpretación', 1);
        $section->addText($resumen['interpretacion'] ?? '', 'Normal', ['spaceAfter' => 200]);

        $section->addText(
            'NOTA: Este reporte es confidencial y debe ser interpretado por un profesional en psicología o recursos humanos. Los resultados son válidos únicamente para el proceso de evaluación para el que fueron generados.',
            ['name' => 'Arial', 'size' => 9, 'italic' => true, 'color' => self::COLORES['gris']],
            ['spaceAfter' => 200]
        );
    }

    private function agregarPieReporte($section): void
    {
        $section->addText(
            'Generado automáticamente por el Sistema PMA-R · Uniempresarial · ' . now()->format('d/m/Y H:i'),
            ['name' => 'Arial', 'size' => 8, 'color' => self::COLORES['gris']],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );
    }

    // ─── Utilidades ───────────────────────────────────────────────────────

    private function agregarFilaTabla($table, array $celdas, bool $esEncabezado): void
    {
        $row = $table->addRow();
        foreach ($celdas as $texto) {
            $cell = $row->addCell(null, $esEncabezado ? ['bgColor' => self::COLORES['primario']] : []);
            $cell->addText(
                (string)$texto,
                [
                    'name'  => 'Arial',
                    'size'  => 9,
                    'bold'  => $esEncabezado,
                    'color' => $esEncabezado ? 'FFFFFF' : self::COLORES['texto'],
                ]
            );
        }
    }

    private function configurarEstilos(): void
    {
        $this->word->addTitleStyle(1, [
            'name' => 'Arial', 'size' => 14, 'bold' => true, 'color' => self::COLORES['primario'],
        ], ['spaceBefore' => 240, 'spaceAfter' => 120]);

        $this->word->addTitleStyle(2, [
            'name' => 'Arial', 'size' => 12, 'bold' => true, 'color' => self::COLORES['acento'],
        ], ['spaceBefore' => 160, 'spaceAfter' => 80]);

        $this->word->addParagraphStyle('Normal', ['spaceAfter' => 160, 'spaceBefore' => 0]);

        $this->word->setDefaultFontName('Arial');
        $this->word->setDefaultFontSize(10);
    }

    private function propiedadesPagina(): array
    {
        return [
            'marginLeft'   => 1440,
            'marginRight'  => 1440,
            'marginTop'    => 1440,
            'marginBottom' => 1440,
            'paperSize'    => 'Letter',
        ];
    }

    private function guardar(string $destino, string $nombreBase): string
    {
        if (!is_dir($destino)) mkdir($destino, 0755, true);
        $ruta = rtrim($destino, '/') . "/{$nombreBase}.docx";
        $writer = IOFactory::createWriter($this->word, 'Word2007');
        $writer->save($ruta);
        return $ruta;
    }
}
