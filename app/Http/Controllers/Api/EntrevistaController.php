<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entrevista;
use App\Models\EntrevistaPregunta;
use App\Models\EntrevistaRespuesta;
use App\Models\EntrevistaSeccion;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntrevistaController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────
    // ASPIRANTE: obtener formulario completo con progreso
    // GET /api/v1/entrevista
    // ──────────────────────────────────────────────────────────────────────
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        // Crear entrevista si no existe
        $entrevista = Entrevista::firstOrCreate(
            ['user_id' => $user->id],
            ['estado' => 'en_progreso']
        );

        // Actualizar estado si estaba pendiente
        if ($entrevista->estado === 'pendiente') {
            $entrevista->update(['estado' => 'en_progreso']);
        }

        $secciones = EntrevistaSeccion::where('activa', true)
            ->orderBy('orden')
            ->with(['preguntas' => fn($q) => $q->orderBy('orden')])
            ->get();

        // Respuestas ya guardadas
        $respuestasGuardadas = EntrevistaRespuesta::where('entrevista_id', $entrevista->id)
            ->pluck('respuesta', 'pregunta_id');

        $data = $secciones->map(function ($seccion) use ($respuestasGuardadas) {
            return [
                'id'          => $seccion->id,
                'nombre'      => $seccion->nombre,
                'slug'        => $seccion->slug,
                'tipo'        => $seccion->tipo,
                'orden'       => $seccion->orden,
                'descripcion' => $seccion->descripcion,
                'preguntas'   => $seccion->preguntas->map(fn($p) => [
                    'id'             => $p->id,
                    'enunciado'      => $p->enunciado,
                    'tipo_respuesta' => $p->tipo_respuesta,
                    'opciones'       => $p->opciones,
                    'obligatoria'    => $p->obligatoria,
                    'orden'          => $p->orden,
                    'respuesta'      => $respuestasGuardadas[$p->id] ?? null,
                ]),
            ];
        });

        return response()->json([
            'entrevista' => [
                'id'             => $entrevista->id,
                'estado'         => $entrevista->estado,
                'completada_en'  => $entrevista->completada_en,
            ],
            'secciones' => $data,
            'progreso'  => $this->calcularProgreso($entrevista),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // ASPIRANTE: guardar respuesta individual (auto-save)
    // POST /api/v1/entrevista/responder
    // ──────────────────────────────────────────────────────────────────────
    public function guardarRespuesta(Request $request): JsonResponse
    {
        $request->validate([
            'pregunta_id' => 'required|exists:entrevista_preguntas,id',
            'respuesta'   => 'nullable|string|max:5000',
        ]);

        $user       = $request->user();
        $entrevista = Entrevista::where('user_id', $user->id)->firstOrFail();

        if ($entrevista->estaCompleta() && !$user->isEvaluador()) {
            return response()->json(['message' => 'La entrevista ya fue completada y no puede modificarse.'], 403);
        }

        EntrevistaRespuesta::updateOrCreate(
            [
                'entrevista_id' => $entrevista->id,
                'pregunta_id'   => $request->pregunta_id,
            ],
            [
                'respuesta'  => $request->respuesta,
                'editado_por' => null,
                'editada_en'  => null,
            ]
        );

        return response()->json([
            'message'  => 'Respuesta guardada.',
            'progreso' => $this->calcularProgreso($entrevista->fresh()),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // ASPIRANTE: marcar entrevista como completada
    // POST /api/v1/entrevista/completar
    // ──────────────────────────────────────────────────────────────────────
    public function completar(Request $request): JsonResponse
    {
        $user       = $request->user();
        $entrevista = Entrevista::where('user_id', $user->id)->firstOrFail();

        if ($entrevista->estaCompleta()) {
            return response()->json(['message' => 'La entrevista ya estaba completada.'], 422);
        }

        // Verificar obligatorias respondidas
        $obligatorias = EntrevistaPregunta::where('obligatoria', true)->pluck('id');
        $respondidas  = EntrevistaRespuesta::where('entrevista_id', $entrevista->id)
            ->whereIn('pregunta_id', $obligatorias)
            ->whereNotNull('respuesta')
            ->count();

        if ($respondidas < $obligatorias->count()) {
            $faltantes = $obligatorias->count() - $respondidas;
            return response()->json([
                'message'  => "Faltan {$faltantes} pregunta(s) obligatoria(s) por responder.",
                'faltantes' => $faltantes,
            ], 422);
        }

        $entrevista->update([
            'estado'         => 'completada',
            'completado_por' => $user->id,
            'completada_en'  => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Entrevista completada exitosamente. Ya puedes acceder a la prueba PMA-R.',
            'estado'  => 'completada',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // EVALUADOR: listar aspirantes con estado de entrevista
    // GET /api/v1/evaluador/entrevistas
    // ──────────────────────────────────────────────────────────────────────
    public function listarParaEvaluador(Request $request): JsonResponse
    {
        if (!$request->user()->isEvaluador()) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $entrevistas = Entrevista::with(['user', 'completadoPor'])
            ->latest()
            ->paginate(20);

        return response()->json($entrevistas);
    }

    // ──────────────────────────────────────────────────────────────────────
    // EVALUADOR: ver entrevista de un aspirante específico
    // GET /api/v1/evaluador/entrevistas/{userId}
    // ──────────────────────────────────────────────────────────────────────
    public function showParaEvaluador(Request $request, int $userId): JsonResponse
    {
        if (!$request->user()->isEvaluador()) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $entrevista = Entrevista::where('user_id', $userId)
            ->with(['user', 'respuestas.pregunta.seccion'])
            ->firstOrFail();

        $secciones = EntrevistaSeccion::where('activa', true)
            ->orderBy('orden')
            ->with(['preguntas' => fn($q) => $q->orderBy('orden')])
            ->get();

        $respuestasGuardadas = EntrevistaRespuesta::where('entrevista_id', $entrevista->id)
            ->pluck('respuesta', 'pregunta_id');

        $data = $secciones->map(fn($seccion) => [
            'id'        => $seccion->id,
            'nombre'    => $seccion->nombre,
            'slug'      => $seccion->slug,
            'tipo'      => $seccion->tipo,
            'preguntas' => $seccion->preguntas->map(fn($p) => [
                'id'             => $p->id,
                'enunciado'      => $p->enunciado,
                'tipo_respuesta' => $p->tipo_respuesta,
                'opciones'       => $p->opciones,
                'obligatoria'    => $p->obligatoria,
                'respuesta'      => $respuestasGuardadas[$p->id] ?? null,
            ]),
        ]);

        return response()->json([
            'entrevista' => $entrevista,
            'secciones'  => $data,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // EVALUADOR: editar una respuesta del aspirante
    // PATCH /api/v1/evaluador/entrevistas/{userId}/responder
    // ──────────────────────────────────────────────────────────────────────
    public function editarRespuesta(Request $request, int $userId): JsonResponse
    {
        if (!$request->user()->isEvaluador()) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $request->validate([
            'pregunta_id' => 'required|exists:entrevista_preguntas,id',
            'respuesta'   => 'nullable|string|max:5000',
        ]);

        $entrevista = Entrevista::where('user_id', $userId)->firstOrFail();

        EntrevistaRespuesta::updateOrCreate(
            [
                'entrevista_id' => $entrevista->id,
                'pregunta_id'   => $request->pregunta_id,
            ],
            [
                'respuesta'  => $request->respuesta,
                'editado_por' => $request->user()->id,
                'editada_en'  => Carbon::now(),
            ]
        );

        return response()->json(['message' => 'Respuesta actualizada por el evaluador.']);
    }

    // ──────────────────────────────────────────────────────────────────────
    // EVALUADOR: marcar/desmarcar como completada la entrevista de un aspirante
    // PATCH /api/v1/evaluador/entrevistas/{userId}/estado
    // ──────────────────────────────────────────────────────────────────────
    public function cambiarEstado(Request $request, int $userId): JsonResponse
    {
        if (!$request->user()->isEvaluador()) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $request->validate([
            'estado' => 'required|in:en_progreso,completada',
        ]);

        $entrevista = Entrevista::where('user_id', $userId)->firstOrFail();

        $entrevista->update([
            'estado'         => $request->estado,
            'completado_por' => $request->user()->id,
            'completada_en'  => $request->estado === 'completada' ? Carbon::now() : null,
        ]);

        return response()->json([
            'message' => 'Estado actualizado a: ' . $request->estado,
            'estado'  => $request->estado,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function calcularProgreso(Entrevista $entrevista): array
    {
        $total      = EntrevistaPregunta::count();
        $respondidas = EntrevistaRespuesta::where('entrevista_id', $entrevista->id)
            ->whereNotNull('respuesta')
            ->count();

        return [
            'total'       => $total,
            'respondidas' => $respondidas,
            'porcentaje'  => $total > 0 ? round(($respondidas / $total) * 100) : 0,
        ];
    }
}
