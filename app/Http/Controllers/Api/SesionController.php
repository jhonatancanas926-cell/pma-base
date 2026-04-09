<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pregunta;
use App\Models\SesionPrueba;
use App\Models\Test;
use App\Services\PmaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SesionController extends Controller
{
    public function __construct(private readonly PmaService $pmaService) {}

    /** Iniciar una nueva sesión de prueba. */
    public function store(Request $request): JsonResponse
    {
        $request->validate(['test_id' => 'required|exists:tests,id']);

        $test = Test::findOrFail($request->test_id);

        if (!$test->activo) {
            return response()->json(['message' => 'Esta prueba no está disponible.'], 422);
        }

        // Una sola sesión activa por usuario y prueba
        $activa = SesionPrueba::where('user_id', $request->user()->id)
            ->where('test_id', $test->id)
            ->where('estado', 'en_progreso')
            ->first();

        if ($activa) {
            return response()->json([
                'message' => 'Ya tienes una sesión activa para esta prueba.',
                'data'    => ['sesion_id' => $activa->id],
            ], 409);
        }

        $sesion = SesionPrueba::create([
            'user_id'       => $request->user()->id,
            'test_id'       => $test->id,
            'estado'        => 'en_progreso',
            'iniciada_en'   => now(),
            'ip_cliente'    => $request->ip(),
            'agente_usuario'=> $request->userAgent(),
        ]);

        return response()->json([
            'message'   => 'Sesión iniciada.',
            'data'      => [
                'sesion_id'   => $sesion->id,
                'prueba'      => $test->nombre,
                'iniciada_en' => $sesion->iniciada_en->toIso8601String(),
            ],
        ], 201);
    }

    /** Registrar respuesta de una pregunta. */
    public function responder(Request $request, int $sesionId): JsonResponse
    {
        $sesion = SesionPrueba::where('id', $sesionId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (!$sesion->estaActiva()) {
            return response()->json(['message' => 'La sesión no está activa.'], 422);
        }

        $request->validate([
            'pregunta_id'     => 'required|exists:preguntas,id',
            'respuesta'       => 'required|string|max:50',
            'tiempo_respuesta'=> 'nullable|integer|min:0|max:600',
        ]);

        $pregunta = Pregunta::with('opciones')->findOrFail($request->pregunta_id);

        // Verificar que la pregunta pertenece a este test
        if ($pregunta->categoria->test_id !== $sesion->test_id) {
            return response()->json(['message' => 'Pregunta no pertenece a esta prueba.'], 422);
        }

        try {
            $respuesta = $this->pmaService->registrarRespuesta(
                $sesion,
                $pregunta,
                $request->respuesta,
                $request->tiempo_respuesta,
            );
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message'    => 'Respuesta registrada.',
            'es_correcta'=> $respuesta->es_correcta,
        ]);
    }

    /** Finalizar sesión y calcular resultados. */
    public function finalizar(Request $request, int $sesionId): JsonResponse
    {
        $sesion = SesionPrueba::where('id', $sesionId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (!$sesion->estaActiva()) {
            return response()->json(['message' => 'La sesión ya fue finalizada.'], 422);
        }

        $resultados = $this->pmaService->calcularResultados($sesion);
        $resumen    = $this->pmaService->resumenSesion($sesion->fresh(['test', 'resultados.categoria', 'user']));

        return response()->json([
            'message' => 'Prueba finalizada. Resultados calculados.',
            'data'    => $resumen,
        ]);
    }

    /** Ver resultados de una sesión finalizada. */
    public function resultados(Request $request, int $sesionId): JsonResponse
    {
        $sesion = SesionPrueba::where('id', $sesionId)
            ->where('user_id', $request->user()->id)
            ->with(['test', 'resultados.categoria', 'user'])
            ->firstOrFail();

        if ($sesion->estaActiva()) {
            return response()->json(['message' => 'La sesión aún está en progreso.'], 422);
        }

        $resumen = $this->pmaService->resumenSesion($sesion);
        return response()->json(['data' => $resumen]);
    }

    /** Listar sesiones del usuario autenticado. */
    public function index(Request $request): JsonResponse
    {
        $sesiones = SesionPrueba::where('user_id', $request->user()->id)
            ->with('test:id,nombre,codigo')
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json(['data' => $sesiones]);
    }
}
