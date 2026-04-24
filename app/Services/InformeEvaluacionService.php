<?php

namespace App\Services;

use App\Models\Entrevista;
use App\Models\SesionPrueba;
use ZipArchive;

/**
 * Genera el Informe de Evaluación Psicológica de Ecotet Aviation Academy
 * usando el archivo .docx original como plantilla con marcadores {{CAMPO}}.
 *
 * Estrategia: copiar plantilla → abrir su ZIP → reemplazar marcadores en
 * word/document.xml con los datos reales. Conserva 100% el formato,
 * tablas, imágenes y estilos del original.
 */
class InformeEvaluacionService
{
    private function rutaPlantilla(): string
    {
        return storage_path('app/plantillas/informe_ecotet_template.docx');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Punto de entrada
    // ──────────────────────────────────────────────────────────────────────────

    public function generar(SesionPrueba $sesion, array $resumenPma, string $destino): string
    {
        $datos = $this->compilarDatos($sesion, $resumenPma);

        if (!is_dir($destino)) {
            mkdir($destino, 0755, true);
        }

        $nombre     = 'informe_' . $sesion->user_id . '_' . now()->format('Ymd_His') . '.docx';
        $rutaSalida = rtrim($destino, '/') . '/' . $nombre;
        copy($this->rutaPlantilla(), $rutaSalida);

        $zip = new ZipArchive();
        if ($zip->open($rutaSalida) !== true) {
            throw new \RuntimeException("No se pudo abrir la plantilla: {$rutaSalida}");
        }

        $xmlIndex = $zip->locateName('word/document.xml');
        if ($xmlIndex === false) {
            $zip->close();
            throw new \RuntimeException("word/document.xml no encontrado en la plantilla.");
        }

        $xml = $zip->getFromIndex($xmlIndex);
        $xml = $this->reemplazarMarcadores($xml, $datos);
        $zip->deleteIndex($xmlIndex);
        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        return $rutaSalida;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Compilar datos
    // ──────────────────────────────────────────────────────────────────────────

    private function compilarDatos(SesionPrueba $sesion, array $resumen): array
    {
        $user       = $sesion->user;
        $entrevista = Entrevista::where('user_id', $user->id)
            ->with('respuestas.pregunta')
            ->first();
        $resp = $entrevista ? $entrevista->respuestasIndexadas() : [];

        $datos = [
            // Identificación
            'NOMBRE_COMPLETO'  => $resp['nombre_completo']  ?? $user->name       ?? '',
            'EDAD'             => $resp['edad']             ?? ($user->edad ?? ''),
            'ESTADO_CIVIL'     => $resp['estado_civil']     ?? '',
            'DOCUMENTO'        => $resp['documento']        ?? ($user->documento ?? ''),
            'TELEFONO'         => $resp['telefono']         ?? '',
            'DIRECCION'        => $resp['direccion']        ?? '',
            'FECHA_EVALUACION' => $resp['fecha_evaluacion'] ?? now()->format('d/m/Y'),
            // Datos familiares (campo único de texto libre)
            'DATOS_FAMILIARES' => $this->unirNoVacios([
                $resp['familia_padre']        ?? null,
                $resp['familia_madre']        ?? null,
                $resp['familia_hermanos']     ?? null,
                $resp['familia_convivientes'] ?? null,
            ]),
        ];

        // Antecedentes médicos familiares
        foreach ([
            'AMF_CANCER'       => 'amf_cáncer',
            'AMF_AUTOINMUNES'  => 'amf_enfermedades_autoinmunes',
            'AMF_DIABETES'     => 'amf_diabetes',
            'AMF_ARTRITIS'     => 'amf_artritis',
            'AMF_HIPERTENSION' => 'amf_hipertension_arterial',
            'AMF_RENALES'      => 'amf_enfermedades_renales',
            'AMF_CARDIOPATIAS' => 'amf_cardiopatias',
            'AMF_MENTALES'     => 'amf_enfermedades_mentales',
            'AMF_ALERGIAS'     => 'amf_alergias',
            'AMF_NEUROLOGICAS' => 'amf_enfermedades_neurologicas',
            'AMF_ASMA'         => 'amf_asma',
        ] as $key => $clave) {
            [$si, $no] = $this->siNo($resp[$clave] ?? '');
            $datos["{$key}_SI"] = $si;
            $datos["{$key}_NO"] = $no;
        }

        // Antecedentes médicos del aspirante
        foreach ([
            'AMA_HIPERTENSION'  => 'ama_hipertension',
            'AMA_TUMOR'         => 'ama_tumor_cerebral',
            'AMA_CANCER'        => 'ama_cancer',
            'AMA_CONVULSIVOS'   => 'ama_convulsivos',
            'AMA_DIABETES'      => 'ama_diabetes',
            'AMA_ETS'           => 'ama_ets_vih',
            'AMA_RENALES'       => 'ama_renal',
            'AMA_ALCOHOLISMO'   => 'ama_alcoholismo',
            'AMA_HEPATICAS'     => 'ama_hepatica',
            'AMA_ANSIEDAD'      => 'ama_ansiedad',
            'AMA_CARDIACAS'     => 'ama_cardiaca',
            'AMA_DEPRESIVOS'    => 'ama_depresion',
            'AMA_RESPIRATORIAS' => 'ama_respiratoria',
            'AMA_TDAH'          => 'ama_tdah',
            'AMA_TCE'           => 'ama_tce',
            'AMA_APRENDIZAJE'   => 'ama_aprendizaje',
        ] as $key => $clave) {
            [$si, $no] = $this->siNo($resp[$clave] ?? '');
            $datos["{$key}_SI"] = $si;
            $datos["{$key}_NO"] = $no;
        }

        // Patológicos con SI/NO y descripción
        foreach ([
            'AMA_PATOLOGICAS'       => 'ama_patologicas_desc',
            'AMA_QUIRURGICOS'       => 'ama_quirurgicos_desc',
            'AMA_HOSPITALIZACIONES' => 'ama_hospitalizaciones_desc',
            'AMA_TRAUMAS'           => 'ama_traumas_desc',
            'AMA_ALERGIAS'          => 'ama_alergias_desc',
            'AMA_PSIQUIATRICO'      => 'ama_psiquiatrico_desc',
            'AMA_FARMACOLOGICOS'    => 'ama_farmacologicos_desc',
        ] as $key => $clave) {
            $desc = $resp[$clave] ?? '';
            $datos["{$key}_DESC"] = $desc;
            $datos["{$key}_SI"]   = !empty($desc) ? '✓' : '';
            $datos["{$key}_NO"]   = empty($desc)  ? '✓' : '';
        }

        // Toxicológicos
        [$fs, $fn] = $this->siNo($resp['tox_fuma']    ?? '');
        [$as, $an] = $this->siNo($resp['tox_alcohol'] ?? '');
        $datos['TOX_FUMA_SI']    = $fs;
        $datos['TOX_FUMA_NO']    = $fn;
        $datos['TOX_CIGARRILLOS']= $resp['tox_cigarrillos_dia']    ?? '';
        $datos['TOX_ANIOS']      = $resp['tox_anios_fumando']      ?? '';
        $datos['TOX_ALCOHOL_SI'] = $as;
        $datos['TOX_ALCOHOL_NO'] = $an;
        $datos['TOX_FRECUENCIA'] = $resp['tox_alcohol_frecuencia'] ?? '';
        $datos['TOX_SUSTANCIAS'] = $resp['tox_sustancias_desc']    ?? '';

        // Historia, experiencia, motivación
        $datos['HISTORIA_EDUCATIVA'] = $this->unirNoVacios([
            $resp['edu_bachillerato']   ?? null,
            $resp['edu_tecnico']        ?? null,
            $resp['edu_universitario']  ?? null,
            $resp['edu_otros']          ?? null,
        ]);
        $datos['EXPERIENCIA_LABORAL'] = $this->unirNoVacios([
            $resp['lab_empresa_reciente']   ?? null,
            $resp['lab_tiempo_reciente']    ?? null,
            $resp['lab_funciones']          ?? null,
            $resp['lab_experiencia_previa'] ?? null,
        ]);
        $datos['MOTIVACION'] = $this->unirNoVacios([
            $resp['mot_razon']                 ?? null,
            $resp['mot_conocimiento_rol']      ?? null,
            $resp['mot_atencion_cliente_desc'] ?? null,
            $resp['mot_proyeccion']            ?? null,
        ]);

        // NEO PI-R (placeholders vacíos hasta integración)
        foreach (['NEO_NEUROTICISMO','NEO_EXTRAVERSION','NEO_AMABILIDAD','NEO_RESPONSABILIDAD'] as $neo) {
            $datos["{$neo}_PC"]  = '';
            $datos["{$neo}_INT"] = '';
        }

        // PMA-R
        $porFactor = collect($resumen['resultados'] ?? [])->keyBy('codigo');
        foreach ([
            'PMA_V' => 'FACTOR_V',
            'PMA_E' => 'FACTOR_E',
            'PMA_R' => 'FACTOR_R',
            'PMA_N' => 'FACTOR_N',
            'PMA_F' => 'FACTOR_F',
        ] as $key => $codigo) {
            $r = $porFactor[$codigo] ?? null;
            $datos["{$key}_SCORE"] = $r ? ($r['puntaje_bruto'] ?? '') : '';
            $datos["{$key}_INT"]   = $r ? ($r['nivel'] ?? '')          : '';
        }

        $global = $resumen['indice_global'] ?? null;
        $datos['PMA_GLOBAL_SCORE'] = $global['puntaje'] ?? '';
        $datos['PMA_GLOBAL_INT']   = $global['nivel']   ?? '';

        // Campos libres del evaluador
        $datos['CONCLUSIONES']    = '';
        $datos['CONCEPTO']        = '';
        $datos['RECOMENDACIONES'] = '';

        return $datos;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Reemplazar {{MARCADOR}} en el XML
    // ──────────────────────────────────────────────────────────────────────────

    private function reemplazarMarcadores(string $xml, array $datos): string
    {
        foreach ($datos as $marcador => $valor) {
            $xml = str_replace(
                '{{' . $marcador . '}}',
                htmlspecialchars((string) $valor, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                $xml
            );
        }
        return $xml;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /** Devuelve ['✓', ''] o ['', '✓'] según si el valor es SÍ o NO */
    private function siNo(string $valor): array
    {
        $v = mb_strtoupper(trim($valor));
        if ($v === 'SÍ' || $v === 'SI' || $v === 'S') return ['✓', ''];
        if ($v === 'NO' || $v === 'N')                  return ['', '✓'];
        return ['', ''];
    }

    /** Une valores no vacíos con separador */
    private function unirNoVacios(array $partes, string $sep = ' | '): string
    {
        return implode($sep, array_filter(array_map('trim', $partes)));
    }
}
