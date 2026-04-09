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
    // ─── Tablas de percentiles PMA-R (adultos universitarios) ─────────────
    // Fuente: Thurstone, L. L. & Thurstone, T. G. (1996). PMA, Aptitudes Mentales Primarias.
    private const PERCENTILES = [
        'FACTOR_V' => [
            ['min' =>  0, 'max' => 10, 'percentil' =>  5, 'nivel' => 'Bajo'],
            ['min' => 11, 'max' => 20, 'percentil' => 15, 'nivel' => 'Bajo'],
            ['min' => 21, 'max' => 30, 'percentil' => 30, 'nivel' => 'Medio'],
            ['min' => 31, 'max' => 38, 'percentil' => 50, 'nivel' => 'Medio'],
            ['min' => 39, 'max' => 44, 'percentil' => 70, 'nivel' => 'Alto'],
            ['min' => 45, 'max' => 48, 'percentil' => 85, 'nivel' => 'Alto'],
            ['min' => 49, 'max' => 50, 'percentil' => 95, 'nivel' => 'Muy Alto'],
        ],
        'FACTOR_E' => [
            ['min' =>  0, 'max' =>  4, 'percentil' =>  5, 'nivel' => 'Bajo'],
            ['min' =>  5, 'max' =>  8, 'percentil' => 20, 'nivel' => 'Bajo'],
            ['min' =>  9, 'max' => 13, 'percentil' => 40, 'nivel' => 'Medio'],
            ['min' => 14, 'max' => 16, 'percentil' => 60, 'nivel' => 'Medio'],
            ['min' => 17, 'max' => 18, 'percentil' => 80, 'nivel' => 'Alto'],
            ['min' => 19, 'max' => 20, 'percentil' => 95, 'nivel' => 'Muy Alto'],
        ],
        'FACTOR_R' => [
            ['min' =>  0, 'max' =>  6, 'percentil' =>  5, 'nivel' => 'Bajo'],
            ['min' =>  7, 'max' => 12, 'percentil' => 20, 'nivel' => 'Bajo'],
            ['min' => 13, 'max' => 18, 'percentil' => 40, 'nivel' => 'Medio'],
            ['min' => 19, 'max' => 22, 'percentil' => 60, 'nivel' => 'Medio'],
            ['min' => 23, 'max' => 26, 'percentil' => 80, 'nivel' => 'Alto'],
            ['min' => 27, 'max' => 30, 'percentil' => 95, 'nivel' => 'Muy Alto'],
        ],
        'FACTOR_N' => [
            ['min' =>  0, 'max' => 14, 'percentil' =>  5, 'nivel' => 'Bajo'],
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
        Pregunta     $pregunta,
        string       $respuestaDada,
        ?int         $tiempoRespuesta = null
    ): RespuestaUsuario {
        if (!$sesion->estaActiva()) {
            throw new \DomainException('La sesión no está activa.');
        }

        $esCorrecta = $pregunta->verificarRespuesta($respuestaDada);

        $opcion = null;
        if ($pregunta->tipo === 'opcion_multiple') {
            $opcion = $pregunta->opciones()->where('letra', strtoupper($respuestaDada))->first();
        } elseif ($pregunta->tipo === 'verdadero_falso') {
            $opcion = $pregunta->opciones()->where('letra', strtoupper($respuestaDada))->first();
        }

        return RespuestaUsuario::updateOrCreate(
            ['sesion_id' => $sesion->id, 'pregunta_id' => $pregunta->id],
            [
                'respuesta_dada'  => strtoupper($respuestaDada),
                'opcion_id'       => $opcion?->id,
                'es_correcta'     => $esCorrecta,
                'tiempo_respuesta'=> $tiempoRespuesta,
                'respondida_en'   => now(),
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
                $resultado = $this->calcularResultadoCategoria($sesion, $categoria);
                $resultados[] = $resultado;
            }

            $sesion->update([
                'estado'       => 'completada',
                'finalizada_en'=> now(),
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
        $preguntas    = $categoria->preguntas()->where('activo', true)->get();
        $totalPreg    = $preguntas->count();
        $idPreguntas  = $preguntas->pluck('id');

        $respuestas   = RespuestaUsuario::where('sesion_id', $sesion->id)
                            ->whereIn('pregunta_id', $idPreguntas)
                            ->get();

        $correctas    = $respuestas->where('es_correcta', true)->count();
        $incorrectas  = $respuestas->where('es_correcta', false)->count();
        $respondidas  = $respuestas->count();
        $omitidas     = $totalPreg - $respondidas;

        // Puntaje bruto: aciertos - (incorrectas / (num_opciones - 1)) — corrección PMA
        $numOpciones  = 4; // Standard PMA-R
        $puntajeBruto = max(0, $correctas - ($incorrectas / ($numOpciones - 1)));

        // Percentil y nivel
        [$percentil, $nivel] = $this->calcularPercentilYNivel($categoria->codigo, (int)round($correctas));

        // Análisis de errores por opción elegida
        $analisisErrores = $this->analizarErrores($respuestas, $preguntas);

        return Resultado::updateOrCreate(
            ['sesion_id' => $sesion->id, 'categoria_id' => $categoria->id],
            [
                'total_preguntas'  => $totalPreg,
                'respondidas'      => $respondidas,
                'correctas'        => $correctas,
                'incorrectas'      => $incorrectas,
                'omitidas'         => $omitidas,
                'puntaje_bruto'    => round($puntajeBruto, 2),
                'puntaje_percentil'=> $percentil,
                'nivel'            => $nivel,
                'analisis_errores' => $analisisErrores,
            ]
        );
    }

    // ─── Percentil y nivel de rendimiento ────────────────────────────────

    private function calcularPercentilYNivel(string $codigoCategoria, int $correctas): array
    {
        $tabla = self::PERCENTILES[$codigoCategoria] ?? null;

        if (!$tabla) return [null, 'Sin datos'];

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
        if ($incorrectas->isEmpty()) return [];

        $preguntasMap = $preguntas->keyBy('id');
        $patronesOpcion = [];
        $preguntasMasFalladas = [];

        foreach ($incorrectas as $resp) {
            // Contar opciones elegidas incorrectamente
            if ($resp->respuesta_dada) {
                $patronesOpcion[$resp->respuesta_dada] = ($patronesOpcion[$resp->respuesta_dada] ?? 0) + 1;
            }
            // Preguntas más falladas
            $preguntasMasFalladas[] = [
                'pregunta_id' => $resp->pregunta_id,
                'numero'      => $preguntasMap[$resp->pregunta_id]?->numero ?? '?',
                'respuesta_dada' => $resp->respuesta_dada,
                'respuesta_correcta' => $preguntasMap[$resp->pregunta_id]?->respuesta_correcta,
            ];
        }

        arsort($patronesOpcion);

        return [
            'total_incorrectas'     => $incorrectas->count(),
            'opciones_frecuentes'   => $patronesOpcion,
            'preguntas_falladas'    => array_slice($preguntasMasFalladas, 0, 10),
            'sesgo_distractor'      => $this->detectarSesgoDistractor($patronesOpcion),
        ];
    }

    private function detectarSesgoDistractor(array $patronesOpcion): ?string
    {
        if (empty($patronesOpcion)) return null;
        $maxOpcion = array_key_first($patronesOpcion);
        $maxFreq   = $patronesOpcion[$maxOpcion];
        $totalErr  = array_sum($patronesOpcion);

        if ($totalErr > 0 && ($maxFreq / $totalErr) > 0.5) {
            return "Tendencia marcada hacia la opción '{$maxOpcion}' ({$maxFreq} veces, " . round(($maxFreq/$totalErr)*100) . '% de errores)';
        }

        return null;
    }

    // ─── Resumen completo de resultados ──────────────────────────────────

    public function resumenSesion(SesionPrueba $sesion): array
    {
        $sesion->load(['test', 'resultados.categoria', 'user']);

        $resultadosPorCategoria = $sesion->resultados->map(fn($r) => [
            'factor'           => $r->categoria->nombre,
            'codigo'           => $r->categoria->codigo,
            'correctas'        => $r->correctas,
            'incorrectas'      => $r->incorrectas,
            'omitidas'         => $r->omitidas,
            'puntaje_bruto'    => $r->puntaje_bruto,
            'percentil'        => $r->puntaje_percentil,
            'nivel'            => $r->nivel,
            'porcentaje'       => $r->porcentajeAcierto(),
            'analisis_errores' => $r->analisis_errores,
        ]);

        $totalCorrectas   = $sesion->resultados->sum('correctas');
        $totalPreguntas   = $sesion->resultados->sum('total_preguntas');
        $puntajeTotal     = $sesion->resultados->sum('puntaje_bruto');
        $tiempoMinutos    = $sesion->tiempo_total ? round($sesion->tiempo_total / 60, 1) : null;

        return [
            'sesion_id'          => $sesion->id,
            'usuario'            => $sesion->user->name,
            'documento'          => $sesion->user->documento,
            'prueba'             => $sesion->test->nombre,
            'fecha'              => $sesion->finalizada_en?->format('d/m/Y H:i'),
            'tiempo_empleado'    => $tiempoMinutos ? "{$tiempoMinutos} min" : 'N/A',
            'estado'             => $sesion->estado,
            'puntaje_total'      => round($puntajeTotal, 2),
            'porcentaje_global'  => $totalPreguntas > 0 ? round(($totalCorrectas / $totalPreguntas) * 100, 1) : 0,
            'resultados'         => $resultadosPorCategoria,
            'interpretacion'     => $this->generarInterpretacion($resultadosPorCategoria->toArray()),
        ];
    }

    private function generarInterpretacion(array $resultados): string
    {
        $fortalezas = [];
        $debilidades = [];

        foreach ($resultados as $r) {
            if (in_array($r['nivel'], ['Alto', 'Muy Alto'])) $fortalezas[] = $r['factor'];
            if ($r['nivel'] === 'Bajo') $debilidades[] = $r['factor'];
        }

        $texto = '';
        if (!empty($fortalezas)) {
            $texto .= 'Fortalezas cognitivas en: ' . implode(', ', $fortalezas) . '. ';
        }
        if (!empty($debilidades)) {
            $texto .= 'Áreas de mejora identificadas en: ' . implode(', ', $debilidades) . '. ';
        }
        if (empty($fortalezas) && empty($debilidades)) {
            $texto = 'Perfil cognitivo en rango medio en todos los factores evaluados.';
        }

        return trim($texto);
    }
}
