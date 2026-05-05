<?php

namespace App\Services;

use App\Models\EvaluadorPerfil;
use App\Models\InformeEvaluador;
use App\Models\SesionPrueba;
use TCPDF;

/**
 * Genera el PDF del Informe de Evaluación Psicológica de Ecotet Aviation Academy
 * usando TCPDF con diseño fiel al documento Word original.
 *
 * Colores del documento original:
 *   Header secciones: #b7b7b7 (gris medio)
 *   Celdas label:     #d9d9d9 (gris claro)
 *   Checkbox cells:   #bfbfbf (gris checkbox)
 *   Bordes:           #000000
 *   Texto:            #222222
 */
class InformeEvaluacionService
{
    // ── Constantes de diseño ──────────────────────────────────────────────
    private const C_HEADER   = [183, 183, 183]; // #b7b7b7
    private const C_LABEL    = [217, 217, 217]; // #d9d9d9
    private const C_CHECK    = [191, 191, 191]; // #bfbfbf
    private const C_WHITE    = [255, 255, 255];
    private const C_BLACK    = [0,   0,   0  ];
    private const C_TEXT     = [34,  34,  34 ];
    private const C_ADMITIDO = [198, 239, 206]; // verde claro
    private const C_SEGUIM   = [255, 235, 156]; // amarillo
    private const C_NOADMIT  = [255, 199, 206]; // rojo claro

    private TCPDF $pdf;
    private float $pageW;   // ancho útil

    // ──────────────────────────────────────────────────────────────────────
    // Punto de entrada
    // ──────────────────────────────────────────────────────────────────────

    public function generar(
        SesionPrueba    $sesion,
        array           $resumen,
        InformeEvaluador $informe,
        ?EvaluadorPerfil $perfil,
        array           $datos,
        string          $destino
    ): string {
        if (!is_dir($destino)) mkdir($destino, 0755, true);

        $this->iniciarPdf();

        // ── Páginas ───────────────────────────────────────────────────────
        $this->pdf->AddPage();
        $this->encabezado($datos, $sesion);
        $this->seccionIdentificacion($datos);
        $this->seccionFamiliares($datos);
        $this->seccionAMF($datos);
        $this->seccionAMA($datos);
        $this->seccionToxicologicos($datos, $informe);
        $this->seccionHistoriaEducativa($datos);
        $this->seccionExperienciaLaboral($datos);
        $this->seccionMotivacion($datos);
        $this->seccionNeopiR();
        $this->seccionPmaR($datos, $resumen);
        $this->seccionCondiciones($informe);
        $this->seccionConclusiones($informe);
        $this->seccionConcepto($informe);
        $this->seccionRecomendaciones($informe);
        $this->seccionSeguimiento();
        $this->firma($perfil);

        // ── Guardar ───────────────────────────────────────────────────────
        $nombre = 'informe_' . $sesion->user_id . '_' . now()->format('Ymd_His') . '.pdf';
        $ruta   = rtrim($destino, '/') . '/' . $nombre;
        $this->pdf->Output($ruta, 'F');

        return $ruta;
    }

    // ──────────────────────────────────────────────────────────────────────
    // Inicializar TCPDF
    // ──────────────────────────────────────────────────────────────────────

    private function iniciarPdf(): void
    {
        $this->pdf = new TCPDF('P', 'mm', 'Letter', true, 'UTF-8', false);
        $this->pdf->SetCreator('Sistema PMA-R');
        $this->pdf->SetAuthor('Ecotet Aviation Academy');
        $this->pdf->SetTitle('Informe de Evaluación Psicológica');

        // Márgenes: izq=15, der=15, arr=10, pie=15
        $this->pdf->SetMargins(15, 10, 15);
        $this->pdf->SetHeaderMargin(0);
        $this->pdf->SetFooterMargin(10);
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(true);
        $this->pdf->SetAutoPageBreak(true, 18);
        $this->pdf->SetFont('helvetica', '', 8);

        // Pie de página
        $this->pdf->setFooterData(self::C_TEXT, self::C_LABEL);
        $this->pdf->setFooterFont(['helvetica', '', 7]);

        $this->pageW = $this->pdf->getPageWidth() - 30; // 215.9 - 30 = 185.9
    }

    // ──────────────────────────────────────────────────────────────────────
    // Encabezado institucional con imagen
    // ──────────────────────────────────────────────────────────────────────

    private function encabezado(array $d, SesionPrueba $sesion): void
    {
        $imgPath = storage_path('app/plantillas/ecotet_header.png');
        $logoPath = storage_path('app/plantillas/ecotet_logo.jpg');

        if (file_exists($imgPath)) {
            // Ajuste de altura basado en TCPDF (0 auto-calcula para mantener proporción)
            $this->pdf->Image($imgPath, 15, 10, $this->pageW, 0, 'PNG', '', 'T', true, 300);
            $this->pdf->Ln(28);
        } else {
            // Fallback: encabezado en texto
            $this->pdf->SetFont('helvetica', 'B', 13);
            $this->pdf->SetTextColor(...self::C_BLACK);
            $this->pdf->Cell($this->pageW, 8, 'ECOTET AVIATION ACADEMY', 0, 1, 'C');
            $this->pdf->SetFont('helvetica', 'B', 10);
            $this->pdf->Cell($this->pageW, 6, 'INFORME DE EVALUACIÓN PSICOLÓGICA', 0, 1, 'C');
            $this->pdf->SetFont('helvetica', '', 8);
            $this->pdf->Cell($this->pageW, 5,
                'PROCESO DE ADMISIÓN — PROGRAMA TRIPULANTE CABINA DE PASAJEROS', 0, 1, 'C');
            $this->pdf->Ln(3);
        }

        if (file_exists($logoPath)) {
            // Dibujar el logo centrado debajo del header (o si no hay header)
            $y = $this->pdf->GetY();
            $logoW = 40; // Ancho estimado
            $cx = ($this->pdf->getPageWidth() - $logoW) / 2;
            $this->pdf->Image($logoPath, $cx, $y, $logoW, 0, 'JPG', '', 'T', true, 300);
            $this->pdf->Ln(20);
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // 1. Datos de identificación
    // ──────────────────────────────────────────────────────────────────────

    private function seccionIdentificacion(array $d): void
    {
        $this->titSeccion('1. DATOS DE IDENTIFICACIÓN');

        $w1 = $this->pageW * 0.28;
        $w2 = $this->pageW * 0.22;
        $w3 = $this->pageW * 0.28;
        $w4 = $this->pageW * 0.22;
        $h  = 6;

        $filas = [
            [['Apellidos y Nombres', $d['NOMBRE_COMPLETO']], ['Edad', $d['EDAD']]],
            [['Estado civil', $d['ESTADO_CIVIL']], ['Doc. Identidad', $d['DOCUMENTO']]],
            [['Número de Teléfono', $d['TELEFONO']], ['Fecha de Evaluación', $d['FECHA_EVALUACION']]],
        ];

        foreach ($filas as $fila) {
            $this->pdf->SetFont('helvetica', 'B', 7.5);
            $this->celda($w1, $h, $fila[0][0], self::C_LABEL, 1);
            $this->pdf->SetFont('helvetica', '', 7.5);
            $this->celda($w2, $h, $fila[0][1], self::C_WHITE, 0);
            $this->pdf->SetFont('helvetica', 'B', 7.5);
            $this->celda($w3, $h, $fila[1][0], self::C_LABEL, 0);
            $this->pdf->SetFont('helvetica', '', 7.5);
            $this->celda($w4, $h, $fila[1][1], self::C_WHITE, 0, 'R');
            $this->pdf->Ln();
        }
        // Dirección — fila completa
        $this->pdf->SetFont('helvetica', 'B', 7.5);
        $this->celda($w1, $h, 'Dirección de Residencia', self::C_LABEL, 1);
        $this->pdf->SetFont('helvetica', '', 7.5);
        $this->celda($this->pageW - $w1, $h, $d['DIRECCION'], self::C_WHITE, 0, 'R');
        $this->pdf->Ln(8);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 2. Datos familiares
    // ──────────────────────────────────────────────────────────────────────

    private function seccionFamiliares(array $d): void
    {
        $this->titSeccion('2. DATOS FAMILIARES');
        $this->bloqueTexto($d['DATOS_FAMILIARES'] ?: '—', 22);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 3. Antecedentes médicos familiares
    // ──────────────────────────────────────────────────────────────────────

    private function seccionAMF(array $d): void
    {
        $this->titSeccion('3. ANTECEDENTES MÉDICOS FAMILIARES');

        $enfermedades = [
            ['CÁNCER',                'AMF_CANCER'],
            ['ENF. AUTOINMUNES',      'AMF_AUTOINMUNES'],
            ['DIABETES',              'AMF_DIABETES'],
            ['ARTRITIS',              'AMF_ARTRITIS'],
            ['HIPERTENSIÓN ARTERIAL', 'AMF_HIPERTENSION'],
            ['ENF. RENALES',          'AMF_RENALES'],
            ['CARDIOPATÍAS',          'AMF_CARDIOPATIAS'],
            ['ENF. MENTALES',         'AMF_MENTALES'],
            ['ALERGIAS',              'AMF_ALERGIAS'],
            ['ENF. NEUROLÓGICAS',     'AMF_NEUROLOGICAS'],
            ['ASMA',                  'AMF_ASMA'],
        ];

        $this->tablaCheckDoble($d, $enfermedades);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 4. Antecedentes médicos del aspirante
    // ──────────────────────────────────────────────────────────────────────

    private function seccionAMA(array $d): void
    {
        $this->titSeccion('4. ANTECEDENTES MÉDICOS DEL ASPIRANTE');

        $antecedentes = [
            ['HIPERTENSIÓN ARTERIAL',    'AMA_HIPERTENSION'],
            ['TUMOR CEREBRAL',           'AMA_TUMOR'],
            ['CÁNCER',                   'AMA_CANCER'],
            ['TRAST. CONVULSIVOS',       'AMA_CONVULSIVOS'],
            ['DIABETES',                 'AMA_DIABETES'],
            ['ETS o VIH',                'AMA_ETS'],
            ['ENF. RENALES',             'AMA_RENALES'],
            ['ALCOHOLISMO',              'AMA_ALCOHOLISMO'],
            ['ENF. HEPÁTICAS',           'AMA_HEPATICAS'],
            ['TRAST. ANSIEDAD',          'AMA_ANSIEDAD'],
            ['ENF. CARDÍACAS / INFARTO', 'AMA_CARDIACAS'],
            ['TRAST. DEPRESIVOS',        'AMA_DEPRESIVOS'],
            ['ENF. RESPIRATORIAS',       'AMA_RESPIRATORIAS'],
            ['TDAH',                     'AMA_TDAH'],
            ['TRAUMA CRANEOENCEFÁLICO',  'AMA_TCE'],
            ['TRAST. APRENDIZAJE',       'AMA_APRENDIZAJE'],
        ];

        $this->tablaCheckDoble($d, $antecedentes);

        // Tabla de patológicos con SI/NO/Describa
        $pato = [
            ['Enfermedades patológicas',          'AMA_PATOLOGICAS'],
            ['Antecedentes quirúrgicos',          'AMA_QUIRURGICOS'],
            ['Hospitalizaciones',                 'AMA_HOSPITALIZACIONES'],
            ['Traumas o accidentes',              'AMA_TRAUMAS'],
            ['Alergias',                          'AMA_ALERGIAS'],
            ['Tratamiento psiquiátrico',          'AMA_PSIQUIATRICO'],
            ['Farmacológicos / Medicación actual','AMA_FARMACOLOGICOS'],
        ];

        $wL = $this->pageW * 0.30;
        $wC = 10;
        $wD = $this->pageW - $wL - $wC * 2;
        $h  = 6;

        // Encabezado
        $this->pdf->SetFont('helvetica', 'B', 7);
        $this->celda($wL, $h, 'Antecedente', self::C_HEADER, 1, 'C');
        $this->celda($wC, $h, 'SÍ',    self::C_HEADER, 0, 'C');
        $this->celda($wC, $h, 'NO',    self::C_HEADER, 0, 'C');
        $this->celda($wD, $h, 'DESCRIBA', self::C_HEADER, 0, 'C');
        $this->pdf->Ln();

        foreach ($pato as [$nombre, $key]) {
            $this->pdf->SetFont('helvetica', 'B', 7);
            $this->celda($wL, $h, $nombre, self::C_LABEL, 1);
            $this->pdf->SetFont('helvetica', '', 8);
            $this->celda($wC, $h, $d["{$key}_SI"] ?? '', self::C_CHECK, 0, 'C');
            $this->celda($wC, $h, $d["{$key}_NO"] ?? '', self::C_CHECK, 0, 'C');
            $this->celda($wD, $h, $d["{$key}_DESC"] ?? '', self::C_WHITE, 0);
            $this->pdf->Ln();
        }
        $this->pdf->Ln(4);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 5. Antecedentes toxicológicos
    // ──────────────────────────────────────────────────────────────────────

    private function seccionToxicologicos(array $d, InformeEvaluador $inf): void
    {
        $this->titSeccion('5. ANTECEDENTES TOXICOLÓGICOS');

        $w1 = $this->pageW * 0.28; $w2 = 10; $w3 = 10; $w4 = $this->pageW - $w1 - $w2 - $w3;
        $h  = 6;

        $filas = [
            ['FUMA:',              'TOX_FUMA_SI',   'TOX_FUMA_NO'],
            ['ALCOHOL:',           'TOX_ALCOHOL_SI','TOX_ALCOHOL_NO'],
            ['SUSTANCIAS PSICOA.', 'TOX_SUST_SI',   'TOX_SUST_NO'],
        ];

        // Fila encabezado
        $this->pdf->SetFont('helvetica', 'B', 7);
        $this->celda($w1, $h, 'Ítem', self::C_HEADER, 1, 'C');
        $this->celda($w2, $h, 'SÍ',   self::C_HEADER, 0, 'C');
        $this->celda($w3, $h, 'NO',   self::C_HEADER, 0, 'C');
        $this->celda($w4, $h, 'Especificación', self::C_HEADER, 0, 'C');
        $this->pdf->Ln();

        $specs = [
            $d['TOX_CIGARRILLOS'] ? 'Cigarrillos/día: ' . $d['TOX_CIGARRILLOS'] . ' | Años: ' . $d['TOX_ANIOS'] : '',
            $d['TOX_FRECUENCIA'] ? 'Frecuencia: ' . $d['TOX_FRECUENCIA'] : '',
            $d['TOX_SUSTANCIAS'] ?: '',
        ];

        foreach ($filas as $i => [$lbl, $ksi, $kno]) {
            $this->pdf->SetFont('helvetica', 'B', 7);
            $this->celda($w1, $h, $lbl, self::C_LABEL, 1);
            $this->pdf->SetFont('helvetica', '', 8);
            $this->celda($w2, $h, $d[$ksi] ?? '', self::C_CHECK, 0, 'C');
            $this->celda($w3, $h, $d[$kno] ?? '', self::C_CHECK, 0, 'C');
            $this->celda($w4, $h, $specs[$i] ?? '', self::C_WHITE, 0);
            $this->pdf->Ln();
        }

        // Concepto toxicológico
        $this->pdf->SetFont('helvetica', 'B', 7);
        $this->celda($w1, $h, 'CONCEPTO TOXICOLÓGICO:', self::C_LABEL, 1);
        $this->pdf->SetFont('helvetica', '', 7.5);
        $this->celda($w2 + $w3 + $w4, $h, $inf->concepto_toxicologico ?? '', self::C_WHITE, 0);
        $this->pdf->Ln(6);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 6. Historia educativa
    // ──────────────────────────────────────────────────────────────────────

    private function seccionHistoriaEducativa(array $d): void
    {
        $this->titSeccion('6. HISTORIA EDUCATIVA');
        $this->bloqueTexto($d['HISTORIA_EDUCATIVA'] ?: '—', 22);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 7. Experiencia laboral
    // ──────────────────────────────────────────────────────────────────────

    private function seccionExperienciaLaboral(array $d): void
    {
        $this->titSeccion('7. EXPERIENCIA LABORAL');
        $this->bloqueTexto($d['EXPERIENCIA_LABORAL'] ?: '—', 22);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 8. Motivación
    // ──────────────────────────────────────────────────────────────────────

    private function seccionMotivacion(array $d): void
    {
        $this->titSeccion('8. MOTIVACIÓN HACIA EL ÁMBITO AERONÁUTICO');
        $this->bloqueTexto($d['MOTIVACION'] ?: '—', 22);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 9. NEO PI-R (placeholder)
    // ──────────────────────────────────────────────────────────────────────

    private function seccionNeopiR(): void
    {
        $this->titSeccion('9. PRUEBA DE PERSONALIDAD: NEO PI-R');

        $wN = $this->pageW * 0.55; $wP = 20; $wI = $this->pageW - $wN - $wP;
        $h  = 6;

        $dims = [
            'Neuroticismo (Ansiedad, depresión, tolerancia al estrés, seguridad)',
            'Extraversión y Apertura mental (Tolerancia, flexibilidad, cordialidad)',
            'Amabilidad (Habilidad social, trabajo en equipo)',
            'Responsabilidad (Auto-exigencia, dinamismo, tesón)',
        ];

        $this->pdf->SetFont('helvetica', 'B', 7);
        $this->celda($wN, $h, 'Dimensiones', self::C_HEADER, 1, 'C');
        $this->celda($wP, $h, 'PC',          self::C_HEADER, 0, 'C');
        $this->celda($wI, $h, 'Interpretación', self::C_HEADER, 0, 'C');
        $this->pdf->Ln();

        $this->pdf->SetFont('helvetica', '', 7.5);
        foreach ($dims as $dim) {
            $this->celda($wN, $h, $dim, self::C_WHITE, 1);
            $this->celda($wP, $h, '—', self::C_WHITE, 0, 'C');
            $this->celda($wI, $h, 'Pendiente NEO PI-R', self::C_WHITE, 0);
            $this->pdf->Ln();
        }
        $this->pdf->Ln(4);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 9.1 PMA-R
    // ──────────────────────────────────────────────────────────────────────

    private function seccionPmaR(array $d, array $resumen): void
    {
        $this->titSeccion('9.1 PRUEBA DE APTITUDES GENERALES: PMA-R');

        $wF = $this->pageW * 0.50; $wS = 25; $wI = $this->pageW - $wF - $wS;
        $h  = 6;

        $this->pdf->SetFont('helvetica', 'B', 7);
        $this->celda($wF, $h, 'Factor', self::C_HEADER, 1, 'C');
        $this->celda($wS, $h, 'S:',     self::C_HEADER, 0, 'C');
        $this->celda($wI, $h, 'Interpretación', self::C_HEADER, 0, 'C');
        $this->pdf->Ln();

        $factores = [
            ['Verbal (V)',              'PMA_V'],
            ['Espacial (E)',            'PMA_E'],
            ['Razonamiento Lógico (R)', 'PMA_R'],
            ['Numérico (N)',            'PMA_N'],
            ['Fluidez verbal (F)',      'PMA_F'],
        ];

        $this->pdf->SetFont('helvetica', '', 7.5);
        foreach ($factores as [$nom, $key]) {
            $nivel = $d["{$key}_INT"] ?? '';
            $bg    = $this->colorNivel($nivel);
            $this->celda($wF, $h, $nom, self::C_WHITE, 1);
            $this->celda($wS, $h, (string)($d["{$key}_SCORE"] ?? '—'), self::C_WHITE, 0, 'C');
            $this->celda($wI, $h, $nivel, $bg, 0);
            $this->pdf->Ln();
        }

        // Índice global
        $this->pdf->SetFont('helvetica', 'B', 7.5);
        $gnivel = $d['PMA_GLOBAL_INT'] ?? '';
        $gbg    = $this->colorNivel($gnivel);
        $this->celda($wF, $h, 'Índice Global', self::C_LABEL, 1);
        $this->celda($wS, $h, (string)($d['PMA_GLOBAL_SCORE'] ?? '—'), self::C_LABEL, 0, 'C');
        $this->celda($wI, $h, $gnivel, $gbg, 0);
        $this->pdf->Ln(4);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 10. Condiciones
    // ──────────────────────────────────────────────────────────────────────

    private function seccionCondiciones(InformeEvaluador $inf): void
    {
        $this->titSeccion('10. CONDICIONES');

        $wI = $this->pageW * 0.26;
        $wD = $this->pageW * 0.37;
        $wR = $this->pageW - $wI - $wD;
        $h  = 5.5;

        $this->pdf->SetFont('helvetica', 'B', 7);
        $this->celda($wI, $h, 'Indicador',    self::C_HEADER, 1, 'C');
        $this->celda($wD, $h, 'Descripción',  self::C_HEADER, 0, 'C');
        $this->celda($wR, $h, 'Desarrollo',   self::C_HEADER, 0, 'C');
        $this->pdf->Ln();

        $condiciones = [
            ['Seguridad personal',                   'cond_seguridad_personal'],
            ['Relaciones interpersonales',            'cond_relaciones_interpersonales'],
            ['Procesos cognitivos',                   'cond_procesos_cognitivos'],
            ['Tolerancia al estrés y fatiga',         'cond_tolerancia_estres'],
            ['Evaluación y asunción de riesgos',      'cond_evaluacion_riesgos'],
            ['Proyección ante exigencias TCP',        'cond_proyeccion_tcp'],
            ['Manejo de conflictos',                  'cond_manejo_conflictos'],
            ['Seguimiento de normas',                 'cond_seguimiento_normas'],
            ['Manejo de presiones externas',          'cond_manejo_presiones'],
            ['Reacción ante situaciones emergencia',  'cond_reaccion_emergencias'],
            ['Afiliación social',                     'cond_afiliacion_social'],
        ];

        foreach ($condiciones as [$titulo, $key]) {
            $desc = $inf->{"{$key}_desc"} ?? '';
            $dev  = $inf->{"{$key}_dev"}  ?? '';
            $hRow = max($h, $this->estimarAltura($desc, $wD), $this->estimarAltura($dev, $wR));

            $x = $this->pdf->GetX(); $y = $this->pdf->GetY();
            $this->pdf->SetFont('helvetica', 'B', 7);
            $this->celdaMulti($wI, $hRow, $titulo, self::C_LABEL, $x, $y);
            $this->pdf->SetFont('helvetica', '', 7);
            $this->celdaMulti($wD, $hRow, $desc, self::C_WHITE, $x + $wI, $y);
            $this->celdaMulti($wR, $hRow, $dev,  self::C_WHITE, $x + $wI + $wD, $y);
            $this->pdf->SetXY($x, $y + $hRow);
        }
        $this->pdf->Ln(4);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 11. Conclusiones
    // ──────────────────────────────────────────────────────────────────────

    private function seccionConclusiones(InformeEvaluador $inf): void
    {
        $this->titSeccion('11. CONCLUSIONES');
        $this->bloqueTexto($inf->conclusiones ?? '', 28);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 12. Concepto
    // ──────────────────────────────────────────────────────────────────────

    private function seccionConcepto(InformeEvaluador $inf): void
    {
        $this->titSeccion('12. CONCEPTO');

        $cg = $inf->concepto_global ?? '';
        $bg = match($cg) {
            'Admitido'                => self::C_ADMITIDO,
            'Admitido con seguimiento'=> self::C_SEGUIM,
            'No admitido'             => self::C_NOADMIT,
            default                   => self::C_WHITE,
        };

        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->celda($this->pageW, 8, $cg ?: '— Sin definir —', $bg, 1, 'C');
        $this->pdf->Ln(4);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 13. Recomendaciones
    // ──────────────────────────────────────────────────────────────────────

    private function seccionRecomendaciones(InformeEvaluador $inf): void
    {
        $this->titSeccion('13. RECOMENDACIONES Y/O OBSERVACIONES');
        $this->bloqueTexto($inf->recomendaciones ?? '', 28);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 14. Seguimiento (placeholder)
    // ──────────────────────────────────────────────────────────────────────

    private function seccionSeguimiento(): void
    {
        $this->titSeccion('14. SEGUIMIENTO DEL ALUMNO (Si aplica)');

        $campos = [
            'Concepto inicial de evaluación',
            'Observaciones relevantes del profesional',
            'Evolución y seguimiento durante el programa',
            'Reporte de tutor de curso',
            'Acompañamiento académico',
            'Acompañamiento psicológico',
            'Resultados del proceso de seguimiento',
            'Recomendaciones y observaciones finales',
        ];

        $w1 = $this->pageW * 0.38; $w2 = $this->pageW - $w1; $h = 7;
        $this->pdf->SetFont('helvetica', '', 7.5);
        foreach ($campos as $campo) {
            $this->pdf->SetFont('helvetica', 'B', 7);
            $this->celda($w1, $h, $campo, self::C_LABEL, 1);
            $this->pdf->SetFont('helvetica', '', 7.5);
            $this->celda($w2, $h, '', self::C_WHITE, 0);
            $this->pdf->Ln();
        }
        $this->pdf->Ln(6);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Firma del evaluador
    // ──────────────────────────────────────────────────────────────────────

    private function firma(?EvaluadorPerfil $perfil): void
    {
        $this->pdf->Ln(8);

        // Centrar bloque de firma
        $cw = 70; $cx = ($this->pdf->getPageWidth() - $cw) / 2;

        // Imagen de firma
        if ($perfil && $perfil->firma_path) {
            $firmaAbs = storage_path('app/public/' . $perfil->firma_path);
            if (file_exists($firmaAbs)) {
                $this->pdf->Image($firmaAbs, $cx + 5, $this->pdf->GetY(), 60, 18, '', '', 'T', true);
                $this->pdf->Ln(20);
            }
        }

        // Línea de firma
        $y = $this->pdf->GetY();
        $this->pdf->Line($cx, $y, $cx + $cw, $y);
        $this->pdf->Ln(2);

        $this->pdf->SetFont('helvetica', 'B', 8);
        $this->pdf->SetX($cx);
        $this->pdf->Cell($cw, 5, $perfil->nombre_completo ?? 'Psicólogo(a) Evaluador(a)', 0, 1, 'C');

        $this->pdf->SetFont('helvetica', '', 7.5);
        $this->pdf->SetX($cx);
        $this->pdf->Cell($cw, 5, 'T.P. No. ' . ($perfil->tarjeta_profesional ?? '___________'), 0, 1, 'C');

        $this->pdf->Ln(4);
        $this->pdf->SetFont('helvetica', 'I', 6.5);
        $this->pdf->SetTextColor(120, 120, 120);
        $this->pdf->Cell($this->pageW, 4,
            'Generado automáticamente — Sistema PMA-R · Ecotet Aviation Academy · ' . now()->format('d/m/Y H:i'),
            0, 1, 'C');
        $this->pdf->SetTextColor(...self::C_TEXT);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Helpers de renderizado
    // ──────────────────────────────────────────────────────────────────────

    /** Título de sección con fondo gris */
    private function titSeccion(string $texto): void
    {
        $this->pdf->SetFont('helvetica', 'B', 8);
        $this->pdf->SetFillColor(...self::C_HEADER);
        $this->pdf->SetTextColor(...self::C_BLACK);
        $this->pdf->Cell($this->pageW, 6, $texto, 1, 1, 'L', true);
        $this->pdf->SetTextColor(...self::C_TEXT);
        $this->pdf->SetFont('helvetica', '', 7.5);
    }

    /** Celda simple con color de fondo */
    private function celda(float $w, float $h, string $txt, array $bg, int $border = 0, string $align = 'L'): void
    {
        $this->pdf->SetFillColor(...$bg);
        $this->pdf->Cell($w, $h, $txt, 1, 0, $align, true);
    }

    /** Celda multilínea posicionada en X,Y */
    private function celdaMulti(float $w, float $h, string $txt, array $bg, float $x, float $y): void
    {
        $this->pdf->SetFillColor(...$bg);
        $this->pdf->MultiCell($w, $h, $txt, 1, 'L', true, 0, $x, $y);
    }

    /** Bloque de texto con altura mínima */
    private function bloqueTexto(string $texto, float $hMin = 20): void
    {
        $this->pdf->SetFont('helvetica', '', 7.5);
        $this->pdf->SetFillColor(...self::C_WHITE);
        $this->pdf->MultiCell($this->pageW, max($hMin, 6), $texto, 1, 'L', true);
        $this->pdf->Ln(3);
    }

    /** Tabla de checkboxes en dos columnas */
    private function tablaCheckDoble(array $d, array $items): void
    {
        $mid  = (int) ceil(count($items) / 2);
        $wN   = $this->pageW * 0.36; $wC = 10;
        $wRow = $wN + $wC + $wC;
        $h    = 5.5;

        // Encabezado
        $this->pdf->SetFont('helvetica', 'B', 7);
        $this->celda($wN, $h, 'Antecedente / Enfermedad', self::C_HEADER, 1, 'C');
        $this->celda($wC, $h, 'SÍ', self::C_HEADER, 0, 'C');
        $this->celda($wC, $h, 'NO', self::C_HEADER, 0, 'C');
        $this->celda($wN, $h, 'Antecedente / Enfermedad', self::C_HEADER, 0, 'C');
        $this->celda($wC, $h, 'SÍ', self::C_HEADER, 0, 'C');
        $this->celda($wC, $h, 'NO', self::C_HEADER, 0, 'C');
        $this->pdf->Ln();

        for ($i = 0; $i < $mid; $i++) {
            [$nomI, $keyI] = $items[$i];
            $der            = $items[$i + $mid] ?? null;

            $this->pdf->SetFont('helvetica', 'B', 7);
            $this->celda($wN, $h, $nomI, self::C_LABEL, 1);
            $this->pdf->SetFont('helvetica', '', 8);
            $this->celda($wC, $h, $d["{$keyI}_SI"] ?? '', self::C_CHECK, 0, 'C');
            $this->celda($wC, $h, $d["{$keyI}_NO"] ?? '', self::C_CHECK, 0, 'C');

            if ($der) {
                [$nomD, $keyD] = $der;
                $this->pdf->SetFont('helvetica', 'B', 7);
                $this->celda($wN, $h, $nomD, self::C_LABEL, 0);
                $this->pdf->SetFont('helvetica', '', 8);
                $this->celda($wC, $h, $d["{$keyD}_SI"] ?? '', self::C_CHECK, 0, 'C');
                $this->celda($wC, $h, $d["{$keyD}_NO"] ?? '', self::C_CHECK, 0, 'C');
            } else {
                $this->celda($wRow, $h, '', self::C_WHITE, 0);
            }
            $this->pdf->Ln();
        }
        $this->pdf->Ln(4);
    }

    /** Color de fondo según nivel PMA-R */
    private function colorNivel(string $nivel): array
    {
        return match(true) {
            in_array($nivel, ['Alto', 'Muy Alto']) => [198, 239, 206],
            in_array($nivel, ['Bajo', 'Muy Bajo']) => [255, 199, 206],
            default                                 => self::C_WHITE,
        };
    }

    /** Estima altura necesaria para un texto en una celda de ancho dado */
    private function estimarAltura(string $texto, float $ancho): float
    {
        if (!$texto) return 5.5;
        $chars   = mb_strlen($texto);
        $porLinea = max(1, (int) ($ancho / 2.2));
        $lineas  = max(1, (int) ceil($chars / $porLinea));
        return max(5.5, $lineas * 5.0);
    }
}
