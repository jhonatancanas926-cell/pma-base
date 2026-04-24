<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EntrevistaSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // ── 1. SECCIONES ──────────────────────────────────────────────────
        $secciones = [
            [
                'nombre' => 'Datos de Identificación',
                'slug' => 'datos_identificacion',
                'tipo' => 'formulario',
                'orden' => 1,
                'descripcion' => 'Información personal básica del aspirante.',
            ],
            [
                'nombre' => 'Datos Familiares',
                'slug' => 'datos_familiares',
                'tipo' => 'formulario',
                'orden' => 2,
                'descripcion' => 'Composición del núcleo familiar.',
            ],
            [
                'nombre' => 'Antecedentes Médicos Familiares',
                'slug' => 'antecedentes_medicos_familiares',
                'tipo' => 'formulario',
                'orden' => 3,
                'descripcion' => 'Historial de enfermedades en la familia del aspirante.',
            ],
            [
                'nombre' => 'Antecedentes Médicos del Aspirante',
                'slug' => 'antecedentes_medicos_aspirante',
                'tipo' => 'formulario',
                'orden' => 4,
                'descripcion' => 'Historial médico personal del aspirante.',
            ],
            [
                'nombre' => 'Antecedentes Toxicológicos',
                'slug' => 'antecedentes_toxicologicos',
                'tipo' => 'formulario',
                'orden' => 5,
                'descripcion' => 'Consumo de sustancias.',
            ],
            [
                'nombre' => 'Historia Educativa',
                'slug' => 'historia_educativa',
                'tipo' => 'formulario',
                'orden' => 6,
                'descripcion' => 'Formación académica del aspirante.',
            ],
            [
                'nombre' => 'Experiencia Laboral',
                'slug' => 'experiencia_laboral',
                'tipo' => 'formulario',
                'orden' => 7,
                'descripcion' => 'Trayectoria profesional.',
            ],
            [
                'nombre' => 'Motivación hacia el Ámbito Aeronáutico',
                'slug' => 'motivacion_aeronautica',
                'tipo' => 'formulario',
                'orden' => 8,
                'descripcion' => 'Razones e interés por el sector aeronáutico.',
            ],
            // Secciones del Excel (escala Likert — almacenamiento, sin efecto en Word aún)
            [
                'nombre' => 'Área Psicosocial',
                'slug' => 'area_psicosocial',
                'tipo' => 'escala',
                'orden' => 9,
                'descripcion' => 'Cuestionario de autopercepción psicosocial.',
            ],
            [
                'nombre' => 'Entorno Familiar',
                'slug' => 'entorno_familiar',
                'tipo' => 'escala',
                'orden' => 10,
                'descripcion' => 'Cuestionario sobre el entorno familiar.',
            ],
            [
                'nombre' => 'Motivación hacia el Sector Aeronáutico',
                'slug' => 'motivacion_escala',
                'tipo' => 'escala',
                'orden' => 11,
                'descripcion' => 'Cuestionario de motivación vocacional.',
            ],
        ];

        DB::table('entrevista_secciones')->insert(
            array_map(fn($s) => array_merge($s, ['activa' => true, 'created_at' => $now, 'updated_at' => $now]), $secciones)
        );

        // Obtener IDs por slug
        $ids = DB::table('entrevista_secciones')->pluck('id', 'slug');

        // ── 2. PREGUNTAS ──────────────────────────────────────────────────
        $opcionesEscala = json_encode([
            'De acuerdo',
            'Parcialmente de acuerdo',
            'En desacuerdo',
        ]);

        $opcionesSiNo = json_encode(['Sí', 'No']);

        $preguntas = [];

        // ── Sección 1: Datos de Identificación ────────────────────────────
        $sec = $ids['datos_identificacion'];
        $campos = [
            ['Apellidos y nombres completos', 'texto', 'nombre_completo'],
            ['Edad', 'numero', 'edad'],
            ['Fecha de nacimiento', 'fecha', 'fecha_nacimiento'],
            ['Estado civil', 'seleccion', 'estado_civil'],
            ['Número de documento de identidad', 'texto', 'documento'],
            ['Número de teléfono', 'texto', 'telefono'],
            ['Dirección de residencia', 'texto', 'direccion'],
            ['Fecha de evaluación', 'fecha', 'fecha_evaluacion'],
        ];
        foreach ($campos as $i => [$enunciado, $tipo, $clave]) {
            $opciones = null;
            if ($tipo === 'seleccion' && $enunciado === 'Estado civil') {
                $opciones = json_encode(['Soltero(a)', 'Casado(a)', 'Unión libre', 'Divorciado(a)', 'Viudo(a)']);
            }
            $preguntas[] = [
                'seccion_id' => $sec,
                'enunciado' => $enunciado,
                'tipo_respuesta' => $tipo,
                'opciones' => $opciones,
                'obligatoria' => true,
                'orden' => $i + 1,
                'clave_word' => $clave,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // ── Sección 2: Datos Familiares ────────────────────────────────────
        $sec = $ids['datos_familiares'];
        $camposFam = [
            ['Nombre y edad del padre (o tutor)', 'texto', 'familia_padre'],
            ['Nombre y edad de la madre (o tutora)', 'texto', 'familia_madre'],
            ['Número de hermanos y edades', 'texto', 'familia_hermanos'],
            ['Personas con quienes vive actualmente', 'textarea', 'familia_convivientes'],
        ];
        foreach ($camposFam as $i => [$enunciado, $tipo, $clave]) {
            $preguntas[] = [
                'seccion_id' => $sec,
                'enunciado' => $enunciado,
                'tipo_respuesta' => $tipo,
                'opciones' => null,
                'obligatoria' => false,
                'orden' => $i + 1,
                'clave_word' => $clave,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // ── Sección 3: Antecedentes Médicos Familiares ─────────────────────
        $sec = $ids['antecedentes_medicos_familiares'];
        $enfermedadesFam = [
            'Cáncer',
            'Enfermedades autoinmunes',
            'Diabetes',
            'Artritis',
            'Hipertensión arterial',
            'Enfermedades renales',
            'Cardiopatías',
            'Enfermedades mentales',
            'Alergias',
            'Enfermedades neurológicas',
            'Asma',
        ];
        foreach ($enfermedadesFam as $i => $enfermedad) {
            $preguntas[] = [
                'seccion_id' => $sec,
                'enunciado' => '¿Tiene antecedente familiar de ' . $enfermedad . '?',
                'tipo_respuesta' => 'si_no',
                'opciones' => $opcionesSiNo,
                'obligatoria' => false,
                'orden' => $i + 1,
                'clave_word' => 'amf_' . strtolower(str_replace([' ', 'á', 'é', 'í', 'ó', 'ú'], ['_', 'a', 'e', 'i', 'o', 'u'], $enfermedad)),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // ── Sección 4: Antecedentes Médicos del Aspirante ─────────────────
        $sec = $ids['antecedentes_medicos_aspirante'];
        $antecedentes = [
            ['Hipertensión arterial', 'ama_hipertension'],
            ['Tumor cerebral', 'ama_tumor_cerebral'],
            ['Cáncer', 'ama_cancer'],
            ['Trastornos convulsivos', 'ama_convulsivos'],
            ['Diabetes', 'ama_diabetes'],
            ['ETS o VIH', 'ama_ets_vih'],
            ['Enfermedades renales', 'ama_renal'],
            ['Alcoholismo', 'ama_alcoholismo'],
            ['Enfermedades hepáticas', 'ama_hepatica'],
            ['Trastornos de ansiedad', 'ama_ansiedad'],
            ['Enfermedades cardíacas / Infarto', 'ama_cardiaca'],
            ['Trastornos depresivos', 'ama_depresion'],
            ['Enfermedades respiratorias', 'ama_respiratoria'],
            ['Trastornos por déficit de atención', 'ama_tdah'],
            ['Trauma craneoencefálico', 'ama_tce'],
            ['Trastornos de aprendizaje', 'ama_aprendizaje'],
        ];
        $orden = 1;
        foreach ($antecedentes as [$enunciado, $clave]) {
            $preguntas[] = [
                'seccion_id' => $sec,
                'enunciado' => '¿Ha presentado ' . $enunciado . '?',
                'tipo_respuesta' => 'si_no',
                'opciones' => $opcionesSiNo,
                'obligatoria' => false,
                'orden' => $orden++,
                'clave_word' => $clave,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        // Campos con descripción libre
        $conDescripcion = [
            ['Enfermedades patológicas', 'ama_patologicas_desc'],
            ['Antecedentes quirúrgicos', 'ama_quirurgicos_desc'],
            ['Hospitalizaciones', 'ama_hospitalizaciones_desc'],
            ['Traumas o accidentes', 'ama_traumas_desc'],
            ['Alergias', 'ama_alergias_desc'],
            ['Tratamiento psiquiátrico', 'ama_psiquiatrico_desc'],
            ['Farmacológicos / Medicación actual', 'ama_farmacologicos_desc'],
        ];
        foreach ($conDescripcion as [$enunciado, $clave]) {
            $preguntas[] = [
                'seccion_id' => $sec,
                'enunciado' => $enunciado,
                'tipo_respuesta' => 'textarea',
                'opciones' => null,
                'obligatoria' => false,
                'orden' => $orden++,
                'clave_word' => $clave,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // ── Sección 5: Antecedentes Toxicológicos ─────────────────────────
        $sec = $ids['antecedentes_toxicologicos'];
        $tox = [
            ['¿Fuma?', 'si_no', 'tox_fuma', $opcionesSiNo],
            ['Cantidad de cigarrillos que consume al día', 'numero', 'tox_cigarrillos_dia', null],
            ['Número de años fumando', 'numero', 'tox_anios_fumando', null],
            ['¿Consume alcohol?', 'si_no', 'tox_alcohol', $opcionesSiNo],
            ['Frecuencia de consumo de alcohol', 'seleccion', 'tox_alcohol_frecuencia', json_encode(['Anual', 'Mensual', 'Semanal', 'Diario'])],
            ['¿Consume sustancias psicoactivas?', 'si_no', 'tox_sustancias', $opcionesSiNo],
            ['Especifique el tipo de sustancia', 'textarea', 'tox_sustancias_desc', null],
            ['Concepto toxicológico (observaciones)', 'textarea', 'tox_concepto', null],
        ];
        foreach ($tox as $i => [$enunciado, $tipo, $clave, $opciones]) {
            $preguntas[] = [
                'seccion_id' => $sec,
                'enunciado' => $enunciado,
                'tipo_respuesta' => $tipo,
                'opciones' => $opciones,
                'obligatoria' => false,
                'orden' => $i + 1,
                'clave_word' => $clave,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // ── Sección 6: Historia Educativa ──────────────────────────────────
        $sec = $ids['historia_educativa'];
        $edu = [
            ['Institución de bachillerato y año de graduación', 'texto', 'edu_bachillerato'],
            ['Estudios técnicos o tecnológicos', 'textarea', 'edu_tecnico'],
            ['Estudios universitarios', 'textarea', 'edu_universitario'],
            ['Otros estudios o cursos relevantes', 'textarea', 'edu_otros'],
        ];
        foreach ($edu as $i => [$enunciado, $tipo, $clave]) {
            $preguntas[] = [
                'seccion_id' => $sec,
                'enunciado' => $enunciado,
                'tipo_respuesta' => $tipo,
                'opciones' => null,
                'obligatoria' => false,
                'orden' => $i + 1,
                'clave_word' => $clave,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // ── Sección 7: Experiencia Laboral ────────────────────────────────
        $sec = $ids['experiencia_laboral'];
        $lab = [
            ['Empresa y cargo más reciente', 'texto', 'lab_empresa_reciente'],
            ['Tiempo en ese cargo', 'texto', 'lab_tiempo_reciente'],
            ['Principales funciones desempeñadas', 'textarea', 'lab_funciones'],
            ['Experiencia previa relevante (si aplica)', 'textarea', 'lab_experiencia_previa'],
        ];
        foreach ($lab as $i => [$enunciado, $tipo, $clave]) {
            $preguntas[] = [
                'seccion_id' => $sec,
                'enunciado' => $enunciado,
                'tipo_respuesta' => $tipo,
                'opciones' => null,
                'obligatoria' => false,
                'orden' => $i + 1,
                'clave_word' => $clave,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // ── Sección 8: Motivación Aeronáutica ─────────────────────────────
        $sec = $ids['motivacion_aeronautica'];
        $mot = [
            ['¿Por qué desea formarse en el sector aeronáutico?', 'textarea', 'mot_razon'],
            ['¿Qué conoce sobre el rol de Tripulante de Cabina de Pasajeros?', 'textarea', 'mot_conocimiento_rol'],
            ['¿Tiene experiencia previa en atención al cliente?', 'si_no', 'mot_atencion_cliente'],
            ['Cuéntenos sobre esa experiencia en atención al cliente', 'textarea', 'mot_atencion_cliente_desc'],
            ['¿Cómo se proyecta en esta profesión a 5 años?', 'textarea', 'mot_proyeccion'],
        ];
        foreach ($mot as $i => [$enunciado, $tipo, $clave]) {
            $opciones = ($tipo === 'si_no') ? $opcionesSiNo : null;
            $preguntas[] = [
                'seccion_id' => $sec,
                'enunciado' => $enunciado,
                'tipo_respuesta' => $tipo,
                'opciones' => $opciones,
                'obligatoria' => false,
                'orden' => $i + 1,
                'clave_word' => $clave,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // ── Sección 9: Área Psicosocial (escala del Excel) ────────────────
        $sec = $ids['area_psicosocial'];
        $psicosocial = [
            'Considero que manejo la tranquilidad ante situaciones difíciles o estresantes.',
            'Considero que tengo habilidades adecuadas de comunicación con otras personas.',
            'Se me facilita mantener grupos de amigos estables.',
            'Identifico claramente las características de mi personalidad que facilitan mis relaciones interpersonales.',
            'Reconozco aspectos personales que debo mejorar en mis relaciones con otros.',
        ];
        foreach ($psicosocial as $i => $enunciado) {
            $preguntas[] = [
                'seccion_id' => $sec,
                'enunciado' => $enunciado,
                'tipo_respuesta' => 'escala_3',
                'opciones' => $opcionesEscala,
                'obligatoria' => true,
                'orden' => $i + 1,
                'clave_word' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // ── Sección 10: Entorno Familiar (escala del Excel) ───────────────
        $sec = $ids['entorno_familiar'];
        $entornoFam = [
            'Considero que mi familia es un apoyo importante en mi vida.',
            'Considero que en mi familia existe buena comunicación.',
            'Tengo relaciones cercanas y positivas con los miembros de mi familia.',
            'En mi familia se promueven valores como el respeto, la unión y el apoyo.',
            'Percibo mi entorno familiar como emocionalmente estable.',
        ];
        foreach ($entornoFam as $i => $enunciado) {
            $preguntas[] = [
                'seccion_id' => $sec,
                'enunciado' => $enunciado,
                'tipo_respuesta' => 'escala_3',
                'opciones' => $opcionesEscala,
                'obligatoria' => true,
                'orden' => $i + 1,
                'clave_word' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // ── Sección 11: Motivación Escala (escala del Excel) ──────────────
        $sec = $ids['motivacion_escala'];
        $motivacionEscala = [
            'Tengo claridad sobre por qué quiero formarme en esta profesión.',
            'Considero que tengo habilidades que me hacen apto(a) para esta profesión.',
            'Tengo expectativas realistas sobre mi futuro en esta carrera.',
            'Comprendo las responsabilidades y exigencias del rol de tripulante de cabina.',
            'Me siento motivado(a) por el servicio al cliente y la atención a las personas.',
        ];
        foreach ($motivacionEscala as $i => $enunciado) {
            $preguntas[] = [
                'seccion_id' => $sec,
                'enunciado' => $enunciado,
                'tipo_respuesta' => 'escala_3',
                'opciones' => $opcionesEscala,
                'obligatoria' => true,
                'orden' => $i + 1,
                'clave_word' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('entrevista_preguntas')->insert($preguntas);
    }
}
