<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\Pregunta;
use App\Models\Resultado;
use App\Models\RespuestaUsuario;
use App\Models\SesionPrueba;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PmaService
{
    // ─── Penalizaciones oficiales PMA-R (Manual Thurstone) ───────────────
    // V: cada error resta 0.33 (1/3 → 4 opciones)
    // E: cada error resta 1.00 (1/1 → penalización total)
    // R: cada error resta 0.20 (1/5 → 6 opciones)
    // N: cada error resta 1.00 (1/1 → verdadero/falso)
    private const PENALIZACIONES = [
        'FACTOR_V' => 0.33,
        'FACTOR_E' => 1.00,
        'FACTOR_R' => 0.20,
        'FACTOR_N' => 1.00,
    ];

    // ─── Tablas de percentiles PMA-R (adultos universitarios) ─────────────
    private const PERCENTILES = [
        'FACTOR_V' => [
            ['min' => 0, 'max' => 10, 'percentil' => 5, 'nivel' => 'Muy Bajo'],
            ['min' => 11, 'max' => 20, 'percentil' => 15, 'nivel' => 'Bajo'],
            ['min' => 21, 'max' => 30, 'percentil' => 30, 'nivel' => 'Medio'],
            ['min' => 31, 'max' => 38, 'percentil' => 50, 'nivel' => 'Medio'],
            ['min' => 39, 'max' => 44, 'percentil' => 70, 'nivel' => 'Alto'],
            ['min' => 45, 'max' => 48, 'percentil' => 85, 'nivel' => 'Alto'],
            ['min' => 49, 'max' => 50, 'percentil' => 95, 'nivel' => 'Muy Alto'],
        ],
        'FACTOR_E' => [
            ['min' => 0, 'max' => 4, 'percentil' => 5, 'nivel' => 'Muy Bajo'],
            ['min' => 5, 'max' => 8, 'percentil' => 20, 'nivel' => 'Bajo'],
            ['min' => 9, 'max' => 13, 'percentil' => 40, 'nivel' => 'Medio'],
            ['min' => 14, 'max' => 16, 'percentil' => 60, 'nivel' => 'Medio'],
            ['min' => 17, 'max' => 18, 'percentil' => 80, 'nivel' => 'Alto'],
            ['min' => 19, 'max' => 20, 'percentil' => 95, 'nivel' => 'Muy Alto'],
        ],
        'FACTOR_R' => [
            ['min' => 0, 'max' => 6, 'percentil' => 5, 'nivel' => 'Muy Bajo'],
            ['min' => 7, 'max' => 12, 'percentil' => 20, 'nivel' => 'Bajo'],
            ['min' => 13, 'max' => 18, 'percentil' => 40, 'nivel' => 'Medio'],
            ['min' => 19, 'max' => 22, 'percentil' => 60, 'nivel' => 'Medio'],
            ['min' => 23, 'max' => 26, 'percentil' => 80, 'nivel' => 'Alto'],
            ['min' => 27, 'max' => 30, 'percentil' => 95, 'nivel' => 'Muy Alto'],
        ],
        'FACTOR_N' => [
            ['min' => 0, 'max' => 14, 'percentil' => 5, 'nivel' => 'Muy Bajo'],
            ['min' => 15, 'max' => 28, 'percentil' => 20, 'nivel' => 'Bajo'],
            ['min' => 29, 'max' => 42, 'percentil' => 40, 'nivel' => 'Medio'],
            ['min' => 43, 'max' => 55, 'percentil' => 60, 'nivel' => 'Medio'],
            ['min' => 56, 'max' => 63, 'percentil' => 80, 'nivel' => 'Alto'],
            ['min' => 64, 'max' => 70, 'percentil' => 95, 'nivel' => 'Muy Alto'],
        ],
    ];

    // ─── Registrar respuesta ──────────────────────────────────────────────

    public function registrarRespuesta(
        SesionPrueba $sesion,
        Pregunta $pregunta,
        string $respuestaDada,
        ?int $tiempoRespuesta = null
    ): RespuestaUsuario {
        if (!$sesion->estaActiva()) {
            throw new \DomainException('La sesión no está activa.');
        }

        $esCorrecta = $pregunta->verificarRespuesta($respuestaDada);

        $opcion = $pregunta->opciones()
            ->where('letra', strtoupper($respuestaDada))
            ->first();

        return RespuestaUsuario::updateOrCreate(
            ['sesion_id' => $sesion->id, 'pregunta_id' => $pregunta->id],
            [
                'respuesta_dada' => strtoupper($respuestaDada),
                'opcion_id' => $opcion?->id,
                'es_correcta' => $esCorrecta,
                'tiempo_respuesta' => $tiempoRespuesta,
                'respondida_en' => now(),
            ]
        );
    }

    public function registrarRespuestaMultiple(
        SesionPrueba $sesion,
        Pregunta $pregunta,
        array $respuestasDadas,
        ?int $tiempoRespuesta = null
    ): RespuestaUsuario {
        if (!$sesion->estaActiva()) {
            throw new \DomainException('La sesión no está activa.');
        }

        $respuestasDadas = array_map('strtoupper', $respuestasDadas);
        sort($respuestasDadas);
        $respuestaDadaStr = implode(',', $respuestasDadas);

        $esCorrecta = $pregunta->verificarRespuesta($respuestaDadaStr);

        return RespuestaUsuario::updateOrCreate(
            ['sesion_id' => $sesion->id, 'pregunta_id' => $pregunta->id],
            [
                'respuesta_dada' => $respuestaDadaStr,
                'opcion_id' => null, // Omitimos ID individual
                'es_correcta' => $esCorrecta,
                'tiempo_respuesta' => $tiempoRespuesta,
                'respondida_en' => now(),
            ]
        );
    }

    // ─── Calcular resultados al finalizar sesión ──────────────────────────

    public function calcularResultados(SesionPrueba $sesion): array
    {
        $categorias = $sesion->test->categorias()->with('preguntas')->get();
        $resultados = [];

        DB::transaction(function () use ($sesion, $categorias, &$resultados) {
            foreach ($categorias as $categoria) {
                $resultados[] = $this->calcularResultadoCategoria($sesion, $categoria);
            }

            $sesion->update([
                'estado' => 'completada',
                'finalizada_en' => now(),
                'tiempo_total' => now()->diffInSeconds($sesion->iniciada_en),
            ]);
        });

        Log::info("[PmaService] Sesión {$sesion->id} completada.", [
            'user_id' => $sesion->user_id,
            'test_id' => $sesion->test_id,
        ]);

        return $resultados;
    }

    // ─── Calcular resultado por categoría ────────────────────────────────

    private function calcularResultadoCategoria(SesionPrueba $sesion, Categoria $categoria): Resultado
    {
        $preguntas = $categoria->preguntas()->where('activo', true)->get();
        $totalPreg = $preguntas->count();
        $idPreguntas = $preguntas->pluck('id');

        $respuestas = RespuestaUsuario::where('sesion_id', $sesion->id)
            ->whereIn('pregunta_id', $idPreguntas)
            ->get();

        $correctas = $respuestas->where('es_correcta', true)->count();
        $incorrectas = $respuestas->where('es_correcta', false)->count();
        $respondidas = $respuestas->count();
        $omitidas = $totalPreg - $respondidas;

        // ── Puntaje bruto con penalización oficial del manual PMA-R ──────
        $penalizacion = self::PENALIZACIONES[$categoria->codigo] ?? 0.33;
        $puntajeBruto = max(0, $correctas - ($incorrectas * $penalizacion));

        // ── Percentil y nivel ─────────────────────────────────────────────
        [$percentil, $nivel] = $this->calcularPercentilYNivel($categoria->codigo, $correctas);

        // ── Análisis de errores ───────────────────────────────────────────
        $analisisErrores = $this->analizarErrores($respuestas, $preguntas);

        return Resultado::updateOrCreate(
            ['sesion_id' => $sesion->id, 'categoria_id' => $categoria->id],
            [
                'total_preguntas' => $totalPreg,
                'respondidas' => $respondidas,
                'correctas' => $correctas,
                'incorrectas' => $incorrectas,
                'omitidas' => $omitidas,
                'puntaje_bruto' => round($puntajeBruto, 2),
                'puntaje_percentil' => $percentil,
                'nivel' => $nivel,
                'analisis_errores' => $analisisErrores,
            ]
        );
    }

    // ─── Percentil y nivel ────────────────────────────────────────────────

    private function calcularPercentilYNivel(string $codigo, int $correctas): array
    {
        $tabla = self::PERCENTILES[$codigo] ?? null;
        if (!$tabla)
            return [null, 'Sin datos'];

        foreach ($tabla as $rango) {
            if ($correctas >= $rango['min'] && $correctas <= $rango['max']) {
                return [$rango['percentil'], $rango['nivel']];
            }
        }

        return [null, 'Sin datos'];
    }

    // ─── Análisis de patrones de error ───────────────────────────────────

    private function analizarErrores($respuestas, $preguntas): array
    {
        $incorrectas = $respuestas->where('es_correcta', false);
        if ($incorrectas->isEmpty())
            return [];

        $preguntasMap = $preguntas->keyBy('id');
        $patronesOpcion = [];
        $preguntasFalladas = [];

        foreach ($incorrectas as $resp) {
            if ($resp->respuesta_dada) {
                $patronesOpcion[$resp->respuesta_dada] = ($patronesOpcion[$resp->respuesta_dada] ?? 0) + 1;
            }
            $preguntasFalladas[] = [
                'pregunta_id' => $resp->pregunta_id,
                'numero' => $preguntasMap[$resp->pregunta_id]?->numero ?? '?',
                'respuesta_dada' => $resp->respuesta_dada,
                'respuesta_correcta' => $preguntasMap[$resp->pregunta_id]?->respuesta_correcta,
            ];
        }

        arsort($patronesOpcion);

        return [
            'total_incorrectas' => $incorrectas->count(),
            'opciones_frecuentes' => $patronesOpcion,
            'preguntas_falladas' => array_slice($preguntasFalladas, 0, 10),
            'sesgo_distractor' => $this->detectarSesgoDistractor($patronesOpcion),
        ];
    }

    private function detectarSesgoDistractor(array $patronesOpcion): ?string
    {
        if (empty($patronesOpcion))
            return null;
        $maxOpcion = array_key_first($patronesOpcion);
        $maxFreq = $patronesOpcion[$maxOpcion];
        $totalErr = array_sum($patronesOpcion);

        if ($totalErr > 0 && ($maxFreq / $totalErr) > 0.5) {
            return "Tendencia marcada hacia la opción '{$maxOpcion}' ({$maxFreq} veces, "
                . round(($maxFreq / $totalErr) * 100) . '% de errores)';
        }

        return null;
    }

    // ─── Resumen completo ────────────────────────────────────────────────

    public function resumenSesion(SesionPrueba $sesion): array
    {
        $sesion->load(['test', 'resultados.categoria', 'user']);

        $resultadosPorCategoria = $sesion->resultados->map(fn($r) => [
            'factor' => $r->categoria->nombre,
            'codigo' => $r->categoria->codigo,
            'correctas' => $r->correctas,
            'incorrectas' => $r->incorrectas,
            'omitidas' => $r->omitidas,
            'puntaje_bruto' => $r->puntaje_bruto,
            'penalizacion' => self::PENALIZACIONES[$r->categoria->codigo] ?? null,
            'penalizacion_total' => round($r->incorrectas * (self::PENALIZACIONES[$r->categoria->codigo] ?? 0), 2),
            'percentil' => $r->puntaje_percentil,
            'nivel' => $r->nivel,
            'porcentaje' => $r->porcentajeAcierto(),
            'analisis_errores' => $r->analisis_errores,
        ]);

        $totalCorrectas = $sesion->resultados->sum('correctas');
        $totalPreguntas = $sesion->resultados->sum('total_preguntas');
        $puntajeTotal = $sesion->resultados->sum('puntaje_bruto');
        $tiempoMinutos = $sesion->tiempo_total ? round($sesion->tiempo_total / 60, 1) : null;

        return [
            'sesion_id' => $sesion->id,
            'usuario' => $sesion->user->name,
            'documento' => $sesion->user->documento,
            'prueba' => $sesion->test->nombre,
            'fecha' => $sesion->finalizada_en?->format('d/m/Y H:i'),
            'tiempo_empleado' => $tiempoMinutos ? "{$tiempoMinutos} min" : 'N/A',
            'estado' => $sesion->estado,
            'puntaje_total' => round($puntajeTotal, 2),
            'porcentaje_global' => $totalPreguntas > 0
                ? round(($totalCorrectas / $totalPreguntas) * 100, 1)
                : 0,
            'resultados' => $resultadosPorCategoria,
            'interpretacion' => $this->generarInterpretacion($resultadosPorCategoria->toArray()),
            'nota_calificacion' => 'Penalizaciones oficiales PMA-R: V=−0.33/error · E=−1/error · R=−0.20/error · N=−1/error',
        ];
    }

    // ─── Interpretación narrativa ─────────────────────────────────────────

    private function generarInterpretacion(array $resultados): string
    {
        $fortalezas = [];
        $medios = [];
        $debilidades = [];

        foreach ($resultados as $r) {
            $nivel = $r['nivel'] ?? '';
            if (in_array($nivel, ['Alto', 'Muy Alto']))
                $fortalezas[] = $r['factor'];
            elseif ($nivel === 'Medio')
                $medios[] = $r['factor'];
            elseif (in_array($nivel, ['Bajo', 'Muy Bajo']))
                $debilidades[] = $r['factor'];
        }

        $texto = '';
        if (!empty($fortalezas)) {
            $texto .= 'Fortalezas cognitivas en: ' . implode(', ', $fortalezas) . '. ';
        }
        if (!empty($medios)) {
            $texto .= 'Rendimiento medio en: ' . implode(', ', $medios) . '. ';
        }
        if (!empty($debilidades)) {
            $texto .= 'Áreas de mejora identificadas en: ' . implode(', ', $debilidades) . '.';
        }
        if (empty($fortalezas) && empty($debilidades)) {
            $texto = 'Perfil cognitivo equilibrado en todos los factores evaluados.';
        }

        return trim($texto);
    }
}