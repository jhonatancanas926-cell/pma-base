<?php
require_once('vendor/autoload.php');

use TCPDF;

// Crear PDF
$pdf = new TCPDF();
$pdf->SetCreator('Sistema');
$pdf->SetAuthor('Uniempresarial');
$pdf->SetTitle('Informe PMA-R');
$pdf->SetMargins(15, 30, 15);
$pdf->AddPage();

// Colores (RGB)
$navy = [15, 31, 61];
$blue = [46, 117, 182];
$green = [16, 124, 16];
$red = [197, 15, 31];
$gray = [107, 122, 141];

// Datos simulados
$datos = [
    "usuario" => "María Camila Rodríguez",
    "edad" => 22,
    "programa" => "Administración de Empresas",
    "fecha" => "09/04/2026"
];

// Función PT
function puntajeTipico($pb, $media, $dt)
{
    $z = ($pb - $media) / $dt;
    $pt = 50 + 20 * $z;
    return round(max(10, min(90, $pt)), 1);
}

// Ejemplo resultados
$resultados = [
    ["factor" => "V", "pb" => 35.67, "media" => 30, "dt" => 8.5],
    ["factor" => "E", "pb" => 10, "media" => 12, "dt" => 4],
    ["factor" => "R", "pb" => 21.2, "media" => 17, "dt" => 5.5],
    ["factor" => "N", "pb" => 24, "media" => 35, "dt" => 12]
];

// ---------- HEADER ----------
$pdf->SetFillColor(...$navy);
$pdf->Rect(0, 0, 210, 25, 'F');

$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetXY(15, 10);
$pdf->Cell(0, 0, 'INFORME INDIVIDUAL PMA-R');

// ---------- DATOS ----------
$pdf->Ln(25);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Datos del Evaluado', 0, 1);

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, "Nombre: " . $datos['usuario'], 0, 1);
$pdf->Cell(0, 6, "Edad: " . $datos['edad'], 0, 1);
$pdf->Cell(0, 6, "Programa: " . $datos['programa'], 0, 1);
$pdf->Cell(0, 6, "Fecha: " . $datos['fecha'], 0, 1);

// ---------- TABLA RESULTADOS ----------
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 8, 'Resultados', 0, 1);

$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(200, 200, 200);
$pdf->Cell(30, 7, 'Factor', 1, 0, 'C', true);
$pdf->Cell(40, 7, 'PB', 1, 0, 'C', true);
$pdf->Cell(40, 7, 'PT', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 10);

foreach ($resultados as $r) {
    $pt = puntajeTipico($r['pb'], $r['media'], $r['dt']);

    // Color por nivel
    if ($pt >= 70)
        $pdf->SetTextColor(...$green);
    elseif ($pt < 40)
        $pdf->SetTextColor(...$red);
    else
        $pdf->SetTextColor(...$blue);

    $pdf->Cell(30, 7, $r['factor'], 1, 0, 'C');
    $pdf->Cell(40, 7, $r['pb'], 1, 0, 'C');
    $pdf->Cell(40, 7, $pt, 1, 1, 'C');
}

// ---------- INTERPRETACIÓN ----------
$pdf->Ln(10);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 8, 'Interpretación', 0, 1);

$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(
    0,
    6,
    "El evaluado presenta fortalezas en habilidades verbales. " .
    "El rendimiento global se ubica dentro de parámetros medios, " .
    "con potencial de mejora en áreas numéricas y espaciales."
);

// ---------- FOOTER ----------
$pdf->SetY(-20);
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(...$gray);
$pdf->Cell(0, 10, 'Informe confidencial - Página ' . $pdf->getAliasNumPage(), 0, 0, 'C');

// ---------- OUTPUT ----------
$pdf->Output('Reporte_PMA_R.pdf', 'I');