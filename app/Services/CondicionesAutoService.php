<?php

namespace App\Services;

/**
 * Genera automáticamente el texto de Descripción y Desarrollo
 * para las 6 condiciones vinculadas a factores PMA-R.
 *
 * Los textos están calibrados para el perfil de Tripulante de Cabina de Pasajeros.
 * El evaluador puede editar cualquier campo después de auto-generado.
 */
class CondicionesAutoService
{
    // ── Tablas de análisis por nivel ──────────────────────────────────────

    private array $textos = [

        'seguridad_personal' => [
            'desc' => 'Capacidad del aspirante para actuar con confianza, claridad y autonomía en situaciones cotidianas y desafiantes propias del rol de TCP.',
            'dev'  => [
                'Muy Alto' => 'El aspirante evidencia una sólida seguridad personal sustentada en un alto dominio verbal. Su capacidad para comprender, procesar y comunicar información le permite afrontar situaciones con aplomo y criterio propio, característica fundamental para el rol de TCP.',
                'Alto'     => 'El aspirante demuestra adecuada seguridad personal, con recursos cognitivos verbales bien desarrollados que le permiten actuar con confianza en la mayoría de situaciones. Se espera buen desempeño en el manejo de pasajeros e instrucciones de seguridad.',
                'Medio'    => 'El aspirante presenta una seguridad personal en desarrollo. Si bien cuenta con habilidades verbales funcionales, puede requerir acompañamiento inicial para consolidar su autonomía en situaciones de alta exigencia propias del servicio aéreo.',
                'Bajo'     => 'El aspirante muestra indicadores de baja seguridad personal vinculados a limitaciones en el procesamiento verbal. Se recomienda fortalecer habilidades comunicativas y de autoevaluación antes de asumir responsabilidades de cara al pasajero.',
                'Muy Bajo' => 'El aspirante presenta dificultades significativas en seguridad personal. El bajo rendimiento en el factor verbal sugiere que puede enfrentar retos considerables en situaciones que demanden claridad, comunicación asertiva y autonomía decisional.',
            ],
        ],

        'relaciones_interpersonales' => [
            'desc' => 'Habilidad del aspirante para establecer, mantener y enriquecer vínculos positivos con pasajeros, tripulación y personal de tierra en el contexto aeronáutico.',
            'dev'  => [
                'Muy Alto' => 'El aspirante evidencia una fluida capacidad de comunicación verbal y producción de ideas, lo que favorece relaciones interpersonales ricas, empáticas y efectivas. Esta competencia es un activo diferencial en la atención al pasajero y el trabajo en equipo de cabina.',
                'Alto'     => 'El aspirante muestra buenas habilidades relacionales apoyadas en una fluidez verbal adecuada. Se espera que establezca relaciones positivas con pasajeros y tripulación, adaptándose con facilidad a distintos perfiles de interlocutores.',
                'Medio'    => 'El aspirante cuenta con habilidades relacionales básicas. Su fluidez verbal moderada puede limitar en algunas ocasiones la calidad de sus interacciones, especialmente en situaciones de alta demanda comunicativa o con pasajeros en situación de estrés.',
                'Bajo'     => 'El aspirante presenta dificultades en relaciones interpersonales asociadas a baja fluidez verbal. Se sugiere trabajar en habilidades de comunicación asertiva y escucha activa para optimizar su desempeño en contextos de servicio.',
                'Muy Bajo' => 'El aspirante muestra limitaciones importantes en relaciones interpersonales. El bajo nivel de fluidez verbal puede afectar significativamente la calidad del servicio al pasajero y la coordinación con la tripulación.',
            ],
        ],

        'procesos_cognitivos' => [
            'desc' => 'Capacidad del aspirante para razonar, analizar situaciones, tomar decisiones lógicas y realizar operaciones numéricas bajo las condiciones propias del entorno aeronáutico.',
            'dev'  => [
                'Muy Alto' => 'El aspirante evidencia procesos cognitivos superiores, con alto rendimiento en razonamiento lógico y capacidad numérica. Esto le permite analizar situaciones complejas con rapidez y precisión, una ventaja significativa en procedimientos de seguridad, emergencias y cálculo de datos de vuelo.',
                'Alto'     => 'El aspirante presenta buenos procesos cognitivos, con adecuada capacidad de razonamiento y manejo numérico. Se espera un desempeño eficiente en la comprensión de protocolos, seguimiento de instrucciones complejas y resolución de situaciones no rutinarias.',
                'Medio'    => 'El aspirante muestra procesos cognitivos funcionales. Puede desempeñarse adecuadamente en tareas estructuradas, aunque situaciones de alta complejidad o presión temporal podrían representar un reto mayor que requiera entrenamiento específico.',
                'Bajo'     => 'El aspirante presenta procesos cognitivos por debajo del promedio esperado para el perfil TCP. Se recomienda fortalecer habilidades de razonamiento y cálculo básico antes de asumir funciones que demanden alta precisión y rapidez de análisis.',
                'Muy Bajo' => 'El aspirante evidencia limitaciones significativas en procesos cognitivos. El bajo rendimiento en razonamiento y numeración sugiere dificultades para seguir procedimientos complejos y tomar decisiones acertadas bajo presión.',
            ],
        ],

        'tolerancia_estres' => [
            'desc' => 'Capacidad del aspirante para mantener el rendimiento, la calma y la eficiencia en situaciones de alta presión, turbulencia, emergencias o demanda sostenida propias del entorno aéreo.',
            'dev'  => [
                'Muy Alto' => 'El aspirante demuestra alta tolerancia a situaciones de estrés y fatiga, respaldada por un sólido razonamiento lógico que le permite mantener la claridad y la sistematicidad en condiciones adversas. Este perfil es especialmente valioso en protocolos de emergencia.',
                'Alto'     => 'El aspirante presenta buena tolerancia al estrés, con capacidad para mantener la concentración y el orden lógico ante dificultades. Se espera un desempeño estable en situaciones de presión moderada a alta dentro de la cabina.',
                'Medio'    => 'El aspirante muestra una tolerancia al estrés aceptable pero susceptible de mejora. En situaciones de alta presión podría ver afectado su rendimiento, por lo que se recomienda entrenamiento en gestión emocional y toma de decisiones bajo tensión.',
                'Bajo'     => 'El aspirante presenta baja tolerancia al estrés y la fatiga. Su rendimiento en razonamiento sugiere dificultad para mantener la claridad analítica en situaciones de alta demanda, lo que representa un riesgo en contextos de emergencia aeronáutica.',
                'Muy Bajo' => 'El aspirante evidencia limitaciones importantes en tolerancia al estrés. Se recomienda una evaluación adicional de su capacidad de respuesta ante emergencias antes de avanzar en el proceso de selección.',
            ],
        ],

        'evaluacion_riesgos' => [
            'desc' => 'Habilidad del aspirante para identificar, cuantificar y tomar decisiones informadas frente a situaciones de riesgo en el entorno aeronáutico.',
            'dev'  => [
                'Muy Alto' => 'El aspirante posee una destacada capacidad para evaluar y asumir riesgos, sustentada en un alto rendimiento numérico. Su precisión en el manejo de datos y cálculo le permite dimensionar correctamente las situaciones y actuar con criterio técnico fundamentado.',
                'Alto'     => 'El aspirante demuestra buena capacidad para evaluar riesgos, con habilidades numéricas que le permiten procesar información cuantitativa con precisión. Se espera un desempeño adecuado en situaciones que requieran estimación de variables y toma de decisiones informadas.',
                'Medio'    => 'El aspirante presenta una capacidad media para la evaluación de riesgos. Puede manejar situaciones estándar, pero podría requerir apoyo ante escenarios que demanden procesamiento numérico rápido o evaluación de múltiples variables simultáneas.',
                'Bajo'     => 'El aspirante muestra dificultades en la evaluación y asunción de riesgos, asociadas a un rendimiento numérico bajo. Se recomienda fortalecer habilidades de análisis cuantitativo y entrenamiento en protocolos de evaluación de riesgo.',
                'Muy Bajo' => 'El aspirante presenta limitaciones importantes en la evaluación de riesgos. Su bajo rendimiento numérico puede afectar significativamente su capacidad de tomar decisiones técnicas acertadas en situaciones críticas.',
            ],
        ],

        'proyeccion_tcp' => [
            'desc' => 'Potencial del aspirante para desarrollarse, adaptarse y proyectarse profesionalmente en el exigente rol de Tripulante de Cabina de Pasajeros.',
            'dev'  => [
                'Muy Alto' => 'El aspirante presenta un perfil cognitivo global sobresaliente que augura una sólida proyección como TCP. Sus capacidades intelectuales le permiten anticipar las exigencias del rol, adaptarse a entornos cambiantes y crecer profesionalmente con autonomía y criterio.',
                'Alto'     => 'El aspirante muestra un buen potencial de proyección en el rol de TCP. Su perfil cognitivo general es favorable para asumir las responsabilidades del cargo, con posibilidades de desarrollo progresivo dentro de la aviación comercial.',
                'Medio'    => 'El aspirante presenta una proyección moderada hacia el rol de TCP. Con acompañamiento formativo adecuado y disposición personal, puede alcanzar el nivel de desempeño requerido, aunque su desarrollo podría ser más gradual.',
                'Bajo'     => 'El aspirante muestra una proyección limitada de cara a las exigencias del rol TCP en este momento. Se recomienda un plan de refuerzo en competencias cognitivas específicas antes de avanzar en el proceso.',
                'Muy Bajo' => 'El aspirante presenta una proyección baja para el rol de TCP basada en su rendimiento cognitivo global. Se sugiere una evaluación integral adicional y considerar alternativas de formación complementaria.',
            ],
        ],
    ];

    // ── Método principal ──────────────────────────────────────────────────

    /**
     * Genera los textos de las 6 condiciones PMA-R para un conjunto de resultados.
     *
     * @param array $resultados Array de resultados indexados por código (FACTOR_V, FACTOR_E, etc.)
     * @return array  Array con claves: cond_{nombre}_desc y cond_{nombre}_dev
     */
    public function generarDesdeResultados(array $resultados): array
    {
        $por = collect($resultados)->keyBy('codigo');

        $nivel = fn(string $codigo) => $por[$codigo]['nivel'] ?? 'Medio';

        $v = $nivel('FACTOR_V');
        $r = $nivel('FACTOR_R');
        $n = $nivel('FACTOR_N');
        $f = $nivel('FACTOR_F');

        // Índice global: nivel del promedio (usamos el peor de R y N para procesos cognitivos)
        $global = $this->nivelGlobal($resultados);

        return [
            // Seguridad personal ← V
            'cond_seguridad_personal_desc'              => $this->desc('seguridad_personal'),
            'cond_seguridad_personal_dev'               => $this->dev('seguridad_personal', $v),
            // Relaciones interpersonales ← F
            'cond_relaciones_interpersonales_desc'      => $this->desc('relaciones_interpersonales'),
            'cond_relaciones_interpersonales_dev'       => $this->dev('relaciones_interpersonales', $f),
            // Procesos cognitivos ← R + N (peor nivel de los dos)
            'cond_procesos_cognitivos_desc'             => $this->desc('procesos_cognitivos'),
            'cond_procesos_cognitivos_dev'              => $this->dev('procesos_cognitivos', $this->peorNivel($r, $n)),
            // Tolerancia al estrés ← R
            'cond_tolerancia_estres_desc'               => $this->desc('tolerancia_estres'),
            'cond_tolerancia_estres_dev'                => $this->dev('tolerancia_estres', $r),
            // Evaluación de riesgos ← N
            'cond_evaluacion_riesgos_desc'              => $this->desc('evaluacion_riesgos'),
            'cond_evaluacion_riesgos_dev'               => $this->dev('evaluacion_riesgos', $n),
            // Proyección TCP ← global
            'cond_proyeccion_tcp_desc'                  => $this->desc('proyeccion_tcp'),
            'cond_proyeccion_tcp_dev'                   => $this->dev('proyeccion_tcp', $global),
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function desc(string $key): string
    {
        return $this->textos[$key]['desc'] ?? '';
    }

    private function dev(string $key, string $nivel): string
    {
        $niveles = $this->textos[$key]['dev'] ?? [];
        // Fallback: si el nivel exacto no existe, usar Medio
        return $niveles[$nivel] ?? $niveles['Medio'] ?? '';
    }

    /** Devuelve el nivel más bajo entre dos */
    private function peorNivel(string $a, string $b): string
    {
        $orden = ['Muy Bajo' => 0, 'Bajo' => 1, 'Medio' => 2, 'Alto' => 3, 'Muy Alto' => 4];
        return ($orden[$a] ?? 2) <= ($orden[$b] ?? 2) ? $a : $b;
    }

    /** Calcula el nivel global como promedio ponderado */
    private function nivelGlobal(array $resultados): string
    {
        if (empty($resultados)) return 'Medio';

        $orden  = ['Muy Bajo' => 0, 'Bajo' => 1, 'Medio' => 2, 'Alto' => 3, 'Muy Alto' => 4];
        $inv    = array_flip($orden);
        $niveles = array_map(fn($r) => $orden[$r['nivel'] ?? 'Medio'] ?? 2, $resultados);
        $prom   = round(array_sum($niveles) / count($niveles));

        return $inv[$prom] ?? 'Medio';
    }
}
