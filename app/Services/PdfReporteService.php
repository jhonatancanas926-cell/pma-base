<?php

namespace App\Services;

use App\Models\SesionPrueba;
use Illuminate\Support\Facades\Log;
use TCPDF;

/**
 * PdfReporteService — Reporte individual PMA-R en PDF
 * 100% PHP usando TCPDF. Sin dependencias de Python.
 *
 * INSTALACIÓN:
 *   composer require tecnickcom/tcpdf
 *
 * CAMPOS REQUERIDOS en users:
 *   edad (tinyint), sexo (enum)
 *   → Ejecutar migración: add_edad_sexo_to_users_table.php
 */
class PdfReporteService
{
    // ─── Constantes oficiales PMA-R ───────────────────────────────────────

    private const PENALIZACIONES = [
        'FACTOR_V' => 0.33,
        'FACTOR_E' => 1.00,
        'FACTOR_R' => 0.20,
        'FACTOR_N' => 1.00,
    ];

    // Baremos universitarios colombianos (puntaje bruto)
    private const BAREMOS = [
        'FACTOR_V' => ['media' => 30.0, 'dt' => 8.5],
        'FACTOR_E' => ['media' => 12.0, 'dt' => 4.0],
        'FACTOR_R' => ['media' => 17.0, 'dt' => 5.5],
        'FACTOR_N' => ['media' => 35.0, 'dt' => 12.0],
    ];

    private const DESCRIPCIONES = [
        'FACTOR_V' => 'Comprensión léxica y riqueza de vocabulario',
        'FACTOR_E' => 'Visualización y rotación de figuras en el espacio',
        'FACTOR_R' => 'Razonamiento lógico e inducción de reglas',
        'FACTOR_N' => 'Rapidez y exactitud en cálculo numérico',
    ];

    private const COLORES = [
        'navy' => [15, 31, 61],
        'navy2' => [26, 58, 107],
        'blue' => [46, 117, 182],
        'accent' => [232, 160, 32],
        'green' => [16, 124, 16],
        'red' => [197, 15, 31],
        'orange' => [202, 80, 16],
        'gray1' => [245, 247, 250],
        'gray2' => [238, 241, 245],
        'gray3' => [200, 208, 220],
        'gray5' => [107, 122, 141],
        'white' => [255, 255, 255],
        'black' => [28, 28, 28],
    ];

    private TCPDF $pdf;

    // ─── Entrada principal ────────────────────────────────────────────────

    public function generarPdf(SesionPrueba $sesion, array $resumen, string $destino): string
    {
        if (!is_dir($destino))
            mkdir($destino, 0755, true);

        $resumen = $this->enriquecerResumen($resumen, $sesion);
        $this->inicializarPdf($resumen);
        $this->construirContenido($resumen);

        $nombre = 'Reporte_PMA_' . str_replace(' ', '_', $resumen['usuario'])
            . '_' . now()->format('Ymd_His') . '.pdf';
        $ruta = rtrim($destino, '/') . '/' . $nombre;
        $this->pdf->Output($ruta, 'F');

        Log::info('[PdfReporteService] PDF generado: ' . $ruta);
        return $ruta;
    }

    // ─── Inicializar TCPDF ────────────────────────────────────────────────

    private function inicializarPdf(array $resumen): void
    {
        $this->pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8');
        $this->pdf->SetCreator('Sistema PMA-R — Uniempresarial');
        $this->pdf->SetAuthor('Uniempresarial');
        $this->pdf->SetTitle('Informe PMA-R — ' . $resumen['usuario']);
        $this->pdf->SetSubject('Reporte Individual de Aptitudes Mentales Primarias');
        $this->pdf->SetKeywords('PMA, psicometría, aptitudes, evaluación');

        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->pdf->SetMargins(15, 15, 15);
        $this->pdf->SetAutoPageBreak(true, 18);
        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->AddPage();
    }

    // ─── Construir todo el contenido ──────────────────────────────────────

    private function construirContenido(array $resumen): void
    {
        // PÁGINA 1
        $this->dibujarEncabezado($resumen);
        $this->dibujarDatosEvaluado($resumen);
        $this->dibujarTablaResultados($resumen);
        $this->dibujarIndiceGlobal($resumen);
        
        // PÁGINA 2
        $this->pdf->AddPage();
        $this->dibujarEncabezado($resumen);
        $this->pdf->SetY(35); // Bajar debajo del encabezado
        
        $this->dibujarGraficaPerfil($resumen);
        $this->dibujarTablaEscala();
        $this->dibujarInterpretacion($resumen);
        $this->dibujarPiePagina();
    }

    // ─── 1. Encabezado ────────────────────────────────────────────────────

    private function dibujarEncabezado(array $resumen): void
    {
        $pdf = $this->pdf;
        [$r, $g, $b] = self::COLORES['navy'];

        // Fondo banner
        $pdf->SetFillColor($r, $g, $b);
        $pdf->Rect(0, 0, 216, 28, 'F');

        // Franja accent
        [$r, $g, $b] = self::COLORES['accent'];
        $pdf->SetFillColor($r, $g, $b);
        $pdf->Rect(0, 28, 216, 2, 'F');

        // Círculo logo
        $pdf->SetFillColor($r, $g, $b);
        $pdf->Circle(22, 14, 9, 0, 360, 'F');
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetTextColor(26, 58, 107);
        $pdf->SetXY(16, 10);
        $pdf->Cell(12, 8, 'PMA', 0, 0, 'C');

        // Título
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetXY(36, 5);
        $pdf->Cell(160, 8, 'INFORME INDIVIDUAL DE RESULTADOS', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(200, 208, 220);
        $pdf->SetX(36);
        $pdf->Cell(160, 5, 'Batería PMA-R — Aptitudes Mentales Primarias', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->SetTextColor(150, 160, 180);
        $pdf->SetX(36);
        $pdf->Cell(160, 5, 'Uniempresarial · Sistemas de Evaluación Psicométrica', 0, 1, 'L');

        $pdf->SetY(35);
        $pdf->SetTextColor(28, 28, 28);
    }

    // ─── 2. Datos del evaluado ────────────────────────────────────────────

    private function dibujarDatosEvaluado(array $resumen): void
    {
        $pdf = $this->pdf;
        $this->seccionTitulo('1. DATOS DEL EVALUADO');

        $ig = $resumen['indice_global'];
        $nivIG = $resumen['nivel_global'];

        $datos = [
            ['ID Sesión', '#' . $resumen['sesion_id'], 'Fecha de aplicación', $resumen['fecha'] ?? '—'],
            ['Evaluado/a', $resumen['usuario'], 'Documento', $resumen['documento'] ?? '—'],
            ['Edad', ($resumen['edad'] ?? '—') . ' años', 'Sexo', $resumen['sexo'] ?? '—'],
            ['Programa', $resumen['programa'] ?? '—', 'Tiempo empleado', $resumen['tiempo_empleado'] ?? '—'],
            ['Índice Global (IG)', $ig . ' PT', 'Nivel global', $nivIG],
        ];

        $y = $pdf->GetY();
        $colWidths = [38, 58, 42, 48];
        $rowH = 7;

        foreach ($datos as $i => $fila) {
            $isLast = ($i === count($datos) - 1);
            // Fondo filas alternas
            if ($i % 2 === 0 && !$isLast) {
                $this->setFill('gray2');
                $pdf->Rect(15, $pdf->GetY(), 186, $rowH, 'F');
            }
            if ($isLast) {
                $this->setFill('blue');
                $pdf->SetFillColor(220, 234, 248);
                $pdf->Rect(15, $pdf->GetY(), 186, $rowH, 'F');
            }

            foreach ([$fila[0], $fila[1], $fila[2], $fila[3]] as $ci => $cell) {
                $isLabel = ($ci === 0 || $ci === 2);
                $pdf->SetFont('helvetica', $isLabel ? 'B' : '', 8.5);
                $pdf->SetTextColor(...($isLabel ? self::COLORES['navy2'] : self::COLORES['black']));
                $pdf->Cell($colWidths[$ci], $rowH, $cell, 'LRB', 0, 'L', false, '', 1);
            }
            $pdf->Ln();
        }

        $pdf->Ln(4);
        $pdf->SetTextColor(...self::COLORES['black']);
    }

    // ─── 3. Tabla de resultados ───────────────────────────────────────────

    private function dibujarTablaResultados(array $resumen): void
    {
        $pdf = $this->pdf;
        $this->seccionTitulo('2. APTITUDES EVALUADAS Y RESULTADOS');

        // Nota sobre PT
        $pdf->SetFont('helvetica', 'I', 7.5);
        $pdf->SetTextColor(...self::COLORES['gray5']);
        $pdf->MultiCell(
            186,
            4,
            'La Puntuación Típica (PT) transforma el puntaje bruto a escala estandarizada con media = 50 y desviación típica = 20.',
            0,
            'L'
        );
        $pdf->Ln(2);

        // Encabezado tabla
        $cols = ['Factor', 'Aptitud evaluada', 'Correc.', 'Errors', 'Omit.', 'Penal.', 'PB', 'PT', 'Percentil', 'Nivel'];
        $widths = [13, 47, 14, 14, 13, 14, 14, 14, 18, 25];

        $this->setFill('navy');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 7.5);
        foreach ($cols as $i => $col) {
            $pdf->Cell($widths[$i], 7, $col, 1, 0, 'C', true);
        }
        $pdf->Ln();

        // Filas de datos
        foreach ($resumen['resultados'] as $idx => $r) {
            $y = $pdf->GetY();
            if ($idx % 2 === 0) {
                $pdf->SetFillColor(245, 247, 250);
                $pdf->Rect(15, $y, 186, 9, 'F');
            }

            $pt = $r['pt'];
            $nivel = $r['nivel'];
            $colNivel = $this->colorNivel($nivel);

            // Factor letra
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetTextColor(...self::COLORES['navy2']);
            $pdf->Cell($widths[0], 9, $r['factor_letra'] ?? substr($r['codigo'], -1), 1, 0, 'C');

            // Descripción
            $pdf->SetFont('helvetica', '', 7.5);
            $pdf->SetTextColor(...self::COLORES['black']);
            $pdf->Cell($widths[1], 9, self::DESCRIPCIONES[$r['codigo']] ?? $r['factor'], 1, 0, 'L');

            // Correctas (verde)
            $pdf->SetFont('helvetica', 'B', 8.5);
            $pdf->SetTextColor(...self::COLORES['green']);
            $pdf->Cell($widths[2], 9, $r['correctas'], 1, 0, 'C');

            // Errores (rojo)
            $pdf->SetTextColor(...self::COLORES['red']);
            $pdf->Cell($widths[3], 9, $r['incorrectas'], 1, 0, 'C');

            // Omitidas (gris)
            $pdf->SetTextColor(...self::COLORES['gray5']);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell($widths[4], 9, $r['omitidas'], 1, 0, 'C');

            // Penalización
            $pdf->SetTextColor(...self::COLORES['orange']);
            $pdf->Cell($widths[5], 9, '-' . $r['penalizacion_total'], 1, 0, 'C');

            // Puntaje Bruto
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetTextColor(...self::COLORES['navy']);
            $pdf->Cell($widths[6], 9, $r['puntaje_bruto'], 1, 0, 'C');

            // PT — destacado
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetTextColor(...self::COLORES['navy2']);
            $pdf->Cell($widths[7], 9, $pt, 1, 0, 'C');

            // Percentil
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetTextColor(...self::COLORES['blue']);
            $pdf->Cell($widths[8], 9, $r['percentil'] ? 'P' . $r['percentil'] : '—', 1, 0, 'C');

            // Nivel con color
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetTextColor(...$colNivel);
            $pdf->Cell($widths[9], 9, $nivel, 1, 1, 'C');
        }

        // Nota penalizaciones
        $pdf->Ln(2);
        $pdf->SetFont('helvetica', 'I', 7);
        $pdf->SetTextColor(...self::COLORES['gray5']);
        $pdf->Cell(
            186,
            5,
            'Penalizaciones oficiales PMA-R: V = −0.33/error · E = −1.00/error · R = −0.20/error · N = −1.00/error',
            0,
            1,
            'L'
        );
        $pdf->Ln(3);
        $pdf->SetTextColor(...self::COLORES['black']);
    }

    // ─── 4. Índice Global ─────────────────────────────────────────────────

    private function dibujarIndiceGlobal(array $resumen): void
    {
        $pdf = $this->pdf;
        $ig = $resumen['indice_global'];
        $niv = $resumen['nivel_global'];
        $col = $this->colorNivel($niv);

        $this->seccionTitulo('3. ÍNDICE GLOBAL DE RENDIMIENTO (IG)');

        $y = $pdf->GetY();
        // Caja fondo
        $pdf->SetFillColor(235, 243, 252);
        $pdf->RoundedRect(15, $y, 186, 18, 3, '1111', 'F');

        // Etiqueta
        $pdf->SetXY(20, $y + 4);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetTextColor(...self::COLORES['navy']);
        $pdf->Cell(50, 6, 'Índice Global (IG):', 0, 0, 'L');

        // Valor PT grande
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->SetTextColor(...self::COLORES['navy2']);
        $pdf->SetXY(68, $y + 2);
        $pdf->Cell(28, 12, $ig . ' PT', 0, 0, 'C');

        // Nivel
        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->SetTextColor(...$col);
        $pdf->SetXY(100, $y + 4);
        $pdf->Cell(26, 6, $niv, 0, 0, 'C');

        // Descripción
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->SetTextColor(...self::COLORES['gray5']);
        $pdf->SetXY(130, $y + 3);
        $pdf->MultiCell(
            66,
            4,
            "Media de PT de todos los factores (V+E+R+N). Un IG de {$ig} PT indica rendimiento cognitivo general en nivel {$niv}.",
            0,
            'L'
        );

        $pdf->SetY($y + 22);
        $pdf->SetTextColor(...self::COLORES['black']);
    }

    // ─── 5. Gráfica de perfil ─────────────────────────────────────────────

    private function dibujarGraficaPerfil(array $resumen): void
    {
        $pdf = $this->pdf;
        $this->seccionTitulo('4. GRÁFICA DE PERFIL — PUNTUACIONES TÍPICAS');

        $pdf->SetFont('helvetica', 'I', 7.5);
        $pdf->SetTextColor(...self::COLORES['gray5']);
        $pdf->Cell(
            186,
            4,
            'Cada punto representa la PT del evaluado. La línea horizontal central indica la media (PT = 50).',
            0,
            1,
            'L'
        );
        $pdf->Ln(1);

        $resultados = $resumen['resultados'];
        $n = count($resultados);

        // Dimensiones del área gráfica
        $x0 = 28;
        $y0 = $pdf->GetY();
        $gw = 170;
        $gh = 55;
        $padB = 14;

        // ── Zonas de color por nivel ──────────────────────────────────────
        $zonas = [
            [10, 30, [253, 232, 232]],  // Bajo/Muy Bajo
            [30, 50, [254, 243, 226]],  // Medio bajo
            [50, 70, [232, 245, 232]],  // Medio
            [70, 90, [227, 240, 251]],  // Alto/Muy Alto
        ];
        foreach ($zonas as [$ptMin, $ptMax, $col]) {
            $pyMin = $y0 + $gh - ($ptMin - 10) / 80 * $gh;
            $pyMax = $y0 + $gh - ($ptMax - 10) / 80 * $gh;
            $pdf->SetFillColor(...$col);
            $pdf->Rect($x0, $pyMax, $gw, $pyMin - $pyMax, 'F');
        }

        // ── Líneas de referencia horizontales ────────────────────────────
        foreach ([30 => 'Bajo', 50 => 'Medio', 70 => 'Alto'] as $pt => $label) {
            $py = $y0 + $gh - ($pt - 10) / 80 * $gh;
            $pdf->SetDrawColor(...($pt === 50 ? self::COLORES['navy2'] : self::COLORES['gray3']));
            $pdf->SetLineWidth($pt === 50 ? 0.5 : 0.25);
            $pdf->Line($x0, $py, $x0 + $gw, $py);
            $pdf->SetFont('helvetica', '', 5.5);
            $pdf->SetTextColor(...self::COLORES['gray5']);
            $pdf->SetXY($x0 - 12, $py - 1.5);
            $pdf->Cell(11, 3, $label, 0, 0, 'R');
        }

        // ── Eje Y (valores) ───────────────────────────────────────────────
        $pdf->SetFont('helvetica', '', 5.5);
        $pdf->SetTextColor(...self::COLORES['gray5']);
        foreach (range(10, 90, 10) as $pt) {
            $py = $y0 + $gh - ($pt - 10) / 80 * $gh;
            $pdf->SetXY($x0 - 12, $py - 1.5);
            $pdf->Cell(11, 3, $pt, 0, 0, 'R');
            $pdf->SetDrawColor(...self::COLORES['gray3']);
            $pdf->SetLineWidth(0.15);
            $pdf->Line($x0 - 1, $py, $x0, $py);
        }

        // ── Eje Y label ───────────────────────────────────────────────────
        $pdf->SetFont('helvetica', 'B', 5.5);
        $pdf->SetTextColor(...self::COLORES['navy']);

        // ── Calcular posiciones X de cada factor ──────────────────────────
        $xs = [];
        $pts = [];
        for ($i = 0; $i < $n; $i++) {
            $xs[$i] = $x0 + ($i + 0.5) * $gw / $n;
            $pts[$i] = $resultados[$i]['pt'];
        }

        // ── Línea de conexión entre puntos ────────────────────────────────
        $pdf->SetDrawColor(...self::COLORES['navy2']);
        $pdf->SetLineWidth(0.7);
        for ($i = 0; $i < $n - 1; $i++) {
            $pyA = $y0 + $gh - ($pts[$i] - 10) / 80 * $gh;
            $pyB = $y0 + $gh - ($pts[$i + 1] - 10) / 80 * $gh;
            $pdf->Line($xs[$i], $pyA, $xs[$i + 1], $pyB);
        }

        // ── Puntos, valores y etiquetas ───────────────────────────────────
        foreach ($resultados as $i => $r) {
            $py = $y0 + $gh - ($pts[$i] - 10) / 80 * $gh;
            $col = $this->colorNivel($r['nivel']);

            // Círculo del punto
            $pdf->SetFillColor(...$col);
            $pdf->SetDrawColor(255, 255, 255);
            $pdf->SetLineWidth(0.8);
            $pdf->Circle($xs[$i], $py, 3.5, 0, 360, 'FD');

            // Letra del factor dentro del círculo
            $pdf->SetFont('helvetica', 'B', 5);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetXY($xs[$i] - 3.5, $py - 2);
            $pdf->Cell(7, 4, $r['factor_letra'] ?? substr($r['codigo'], -1), 0, 0, 'C');

            // Valor PT sobre el punto
            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->SetTextColor(...self::COLORES['navy']);
            $pdf->SetXY($xs[$i] - 8, $py - 8);
            $pdf->Cell(16, 4, $pts[$i], 0, 0, 'C');

            // Nombre del factor debajo
            $pdf->SetFont('helvetica', '', 6);
            $pdf->SetTextColor(...self::COLORES['gray5']);
            $pdf->SetXY($xs[$i] - 12, $y0 + $gh + 2);
            $pdf->Cell(24, 3.5, $r['nombre'] ?? $r['factor'], 0, 0, 'C');

            // Nivel debajo del nombre
            $pdf->SetFont('helvetica', 'B', 5.5);
            $pdf->SetTextColor(...$col);
            $pdf->SetXY($xs[$i] - 12, $y0 + $gh + 5.5);
            $pdf->Cell(24, 3, $r['nivel'], 0, 0, 'C');
        }

        // ── Borde del área gráfica ────────────────────────────────────────
        $pdf->SetDrawColor(...self::COLORES['gray3']);
        $pdf->SetLineWidth(0.3);
        $pdf->Rect($x0, $y0, $gw, $gh);

        $pdf->SetY($y0 + $gh + $padB);
        $pdf->SetTextColor(...self::COLORES['black']);
        $pdf->Ln(2);
    }

    // ─── 6. Tabla de escala típica ────────────────────────────────────────

    private function dibujarTablaEscala(): void
    {
        $pdf = $this->pdf;
        $this->seccionTitulo('5. ESCALA DE PUNTUACIONES TÍPICAS — REFERENCIA');

        $filas = [
            ['≥ 70', 'Muy Alto', self::COLORES['green'], 'Capacidad cognitiva significativamente superior al grupo de referencia', 'P90 – P99'],
            ['60 – 69', 'Alto', [34, 134, 58], 'Rendimiento superior al promedio, maneja con facilidad tareas del factor', 'P70 – P89'],
            ['40 – 59', 'Medio', self::COLORES['blue'], 'Dentro del rango esperado para la población universitaria de referencia', 'P30 – P69'],
            ['30 – 39', 'Bajo', self::COLORES['orange'], 'Por debajo del promedio, se recomienda refuerzo en esta área cognitiva', 'P10 – P29'],
            ['< 30', 'Muy Bajo', self::COLORES['red'], 'Significativamente inferior, requiere intervención especializada', 'P1 – P9'],
        ];

        $widths = [22, 24, 100, 40];

        // Encabezado
        $this->setFill('navy2');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 8);
        foreach (['PT', 'Nivel', 'Descripción', 'Percentil aprox.'] as $i => $h) {
            $pdf->Cell($widths[$i], 6, $h, 1, 0, 'C', true);
        }
        $pdf->Ln();

        foreach ($filas as $i => [$pt, $nivel, $col, $desc, $percentil]) {
            $bg = $i % 2 === 0 ? [245, 247, 250] : [255, 255, 255];
            $pdf->SetFillColor(...$bg);
            $pdf->Rect(15, $pdf->GetY(), 186, 6.5, 'F');

            $pdf->SetFont('helvetica', 'B', 8.5);
            $pdf->SetTextColor(...self::COLORES['navy']);
            $pdf->Cell($widths[0], 6.5, $pt, 1, 0, 'C');

            $pdf->SetTextColor(...$col);
            $pdf->Cell($widths[1], 6.5, $nivel, 1, 0, 'C');

            $pdf->SetFont('helvetica', '', 7.5);
            $pdf->SetTextColor(...self::COLORES['black']);
            $pdf->Cell($widths[2], 6.5, $desc, 1, 0, 'L');

            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetTextColor(...self::COLORES['blue']);
            $pdf->Cell($widths[3], 6.5, $percentil, 1, 1, 'C');
        }

        $pdf->Ln(4);
        $pdf->SetTextColor(...self::COLORES['black']);
    }

    // ─── 7. Interpretación ────────────────────────────────────────────────

    private function dibujarInterpretacion(array $resumen): void
    {
        $pdf = $this->pdf;
        $this->seccionTitulo('6. INTERPRETACIÓN Y RECOMENDACIONES');

        // Texto general
        $pdf->SetFont('helvetica', '', 8.5);
        $pdf->SetTextColor(...self::COLORES['black']);
        $pdf->MultiCell(186, 5, $resumen['interpretacion'] ?? '', 0, 'J');
        $pdf->Ln(3);

        // Interpretación por factor
        $interp = [
            'FACTOR_V' => 'evalúa la comprensión léxica y la riqueza de vocabulario. '
                . 'Puntuaciones altas indican facilidad para comprender y usar conceptos expresados verbalmente.',
            'FACTOR_E' => 'evalúa la capacidad de visualizar y rotar mentalmente figuras en el espacio. '
                . 'Puntuaciones altas se asocian con habilidades de diseño, ingeniería y orientación espacial.',
            'FACTOR_R' => 'evalúa la capacidad para deducir reglas lógicas en series de estímulos. '
                . 'Puntuaciones altas indican facilidad para el razonamiento abstracto, la planificación y la anticipación.',
            'FACTOR_N' => 'evalúa la rapidez y exactitud en operaciones aritméticas. '
                . 'Puntuaciones altas se asocian con eficiencia en tareas que requieren cálculo y verificación numérica.',
        ];

        foreach ($resumen['resultados'] as $r) {
            $pt = $r['pt'];
            $col = $this->colorNivel($r['nivel']);
            $letra = $r['factor_letra'] ?? substr($r['codigo'], -1);

            // Etiqueta coloreada del factor
            $pdf->SetFillColor(...$col);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->Cell(22, 5.5, "Factor {$letra}", 0, 0, 'C', true, '', 3);

            $pdf->SetTextColor(...self::COLORES['navy']);
            $pdf->SetFont('helvetica', 'B', 8.5);
            $pdf->Cell(6, 5.5, '', 0, 0);

            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetTextColor(...self::COLORES['black']);
            $texto = ucfirst($r['nombre'] ?? $r['factor']) . ': Este factor '
                . ($interp[$r['codigo']] ?? '')
                . " PT obtenida: {$pt} — Nivel: {$r['nivel']}.";
            $pdf->MultiCell(158, 5, $texto, 0, 'J');
            $pdf->Ln(1);
        }

        $pdf->Ln(2);
        // Nota confidencialidad
        $pdf->SetFillColor(238, 241, 245);
        $y = $pdf->GetY();
        $pdf->Rect(15, $y, 186, 10, 'F');
        $pdf->SetXY(18, $y + 2);
        $pdf->SetFont('helvetica', 'I', 7.5);
        $pdf->SetTextColor(...self::COLORES['gray5']);
        $pdf->MultiCell(
            180,
            4,
            'NOTA CONFIDENCIAL: Este informe debe ser interpretado únicamente por un profesional en psicología o gestión humana. '
            . 'Los resultados son válidos exclusivamente para el proceso de evaluación para el cual fueron generados.',
            0,
            'J'
        );
    }

    // ─── 8. Pie de página ─────────────────────────────────────────────────

    private function dibujarPiePagina(): void
    {
        $pdf = $this->pdf;
        $pdf->SetAutoPageBreak(false); // Prevenir saltos de página infinitos
        $pdf->SetY(-15);
        $pdf->SetFillColor(...self::COLORES['navy']);
        $pdf->Rect(0, $pdf->GetY(), 216, 15, 'F');
        $pdf->SetTextColor(...self::COLORES['gray3']);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(
            186,
            8,
            'Informe generado automáticamente · Sistema PMA-R · Uniempresarial · Página ' . $pdf->getPage(),
            0,
            0,
            'C'
        );
        $pdf->SetAutoPageBreak(true, 18);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    private function seccionTitulo(string $titulo): void
    {
        $pdf = $this->pdf;
        $y = $pdf->GetY();

        $pdf->SetFillColor(...self::COLORES['navy2']);
        $pdf->Rect(15, $y, 186, 7, 'F');

        $pdf->SetFillColor(...self::COLORES['accent']);
        $pdf->Rect(15, $y + 7, 186, 1.5, 'F');

        $pdf->SetXY(18, $y + 1);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(183, 5, $titulo, 0, 1, 'L');

        $pdf->SetY($y + 11);
        $pdf->SetTextColor(...self::COLORES['black']);
    }

    private function setFill(string $colorKey): void
    {
        $this->pdf->SetFillColor(...(self::COLORES[$colorKey] ?? [255, 255, 255]));
    }

    private function colorNivel(string $nivel): array
    {
        return match ($nivel) {
            'Muy Alto' => self::COLORES['green'],
            'Alto' => [34, 134, 58],
            'Medio' => self::COLORES['blue'],
            'Bajo' => self::COLORES['orange'],
            'Muy Bajo' => self::COLORES['red'],
            default => self::COLORES['gray5'],
        };
    }

    private function enriquecerResumen(array $resumen, SesionPrueba $sesion): array
    {
        $resultadosData = $resumen['resultados'] ?? [];
        if ($resultadosData instanceof \Illuminate\Support\Collection) {
            $resultadosData = $resultadosData->toArray();
        }

        $resumen['resultados'] = array_map(function ($r) {
            $r['pt'] = $this->puntajeTipico($r['codigo'], $r['puntaje_bruto']);
            $r['penalizacion'] = self::PENALIZACIONES[$r['codigo']] ?? 0.33;
            $r['penalizacion_total'] = round($r['incorrectas'] * $r['penalizacion'], 2);
            $r['factor_letra'] = match ($r['codigo']) {
                'FACTOR_V' => 'V', 'FACTOR_E' => 'E',
                'FACTOR_R' => 'R', 'FACTOR_N' => 'N', default => '?',
            };
            return $r;
        }, $resultadosData);

        $ig = $this->indiceGlobal($resumen['resultados']);
        $resumen['indice_global'] = $ig;
        $resumen['nivel_global'] = match (true) {
            $ig >= 70 => 'Muy Alto', $ig >= 60 => 'Alto',
            $ig >= 40 => 'Medio', $ig >= 30 => 'Bajo',
            default => 'Muy Bajo',
        };

        $user = $sesion->user;
        $resumen['edad'] = $user->edad ?? '—';
        $resumen['sexo'] = $user->sexo ?? '—';
        $resumen['programa'] = $user->programa ?? '—';

        return $resumen;
    }

    public function puntajeTipico(string $codigo, float $pb): float
    {
        $b = self::BAREMOS[$codigo] ?? ['media' => 20, 'dt' => 8];
        $pt = 50 + 20 * (($pb - $b['media']) / $b['dt']);
        return round(max(10, min(90, $pt)), 1);
    }

    public function indiceGlobal(array $resultados): float
    {
        $pts = array_map(fn($r) => $this->puntajeTipico($r['codigo'], $r['puntaje_bruto']), $resultados);
        return round(array_sum($pts) / count($pts), 1);
    }
}