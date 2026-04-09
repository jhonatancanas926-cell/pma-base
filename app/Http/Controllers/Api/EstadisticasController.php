<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Resultado;
use App\Models\SesionPrueba;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstadisticasController extends Controller
{
    /**
     * GET /api/v1/estadisticas
     * Panel estadístico global (solo admin/evaluador).
     */
    public function __invoke(Request $request): JsonResponse
    {
        if (!$request->user()->isEvaluador()) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $request->validate([
            'test_id'      => 'nullable|exists:tests,id',
            'desde'        => 'nullable|date',
            'hasta'        => 'nullable|date|after_or_equal:desde',
        ]);

        $testId = $request->integer('test_id');
        $desde  = $request->input('desde', now()->subMonths(3)->toDateString());
        $hasta  = $request->input('hasta', now()->toDateString());

        // Totales generales
        $totalSesiones     = SesionPrueba::when($testId, fn($q) => $q->where('test_id', $testId))
                                ->whereBetween('created_at', [$desde, $hasta])->count();
        $sesionesCompletadas = SesionPrueba::where('estado', 'completada')
                                ->when($testId, fn($q) => $q->where('test_id', $testId))
                                ->whereBetween('created_at', [$desde, $hasta])->count();

        // Promedio por factor
        $promediosPorFactor = Resultado::with('categoria:id,nombre,codigo')
            ->whereHas('sesion', function ($q) use ($testId, $desde, $hasta) {
                $q->where('estado', 'completada')
                  ->when($testId, fn($q2) => $q2->where('test_id', $testId))
                  ->whereBetween('created_at', [$desde, $hasta]);
            })
            ->select('categoria_id',
                DB::raw('AVG(correctas) as promedio_correctas'),
                DB::raw('AVG(puntaje_bruto) as promedio_puntaje'),
                DB::raw('COUNT(*) as total_evaluaciones')
            )
            ->groupBy('categoria_id')
            ->get()
            ->map(fn($r) => [
                'factor'              => $r->categoria->nombre ?? $r->categoria_id,
                'codigo'              => $r->categoria->codigo ?? null,
                'promedio_correctas'  => round($r->promedio_correctas, 2),
                'promedio_puntaje'    => round($r->promedio_puntaje, 2),
                'total_evaluaciones'  => $r->total_evaluaciones,
            ]);

        // Distribución de niveles
        $distribucionNiveles = Resultado::whereHas('sesion', fn($q) => $q->where('estado', 'completada'))
            ->select('nivel', DB::raw('COUNT(*) as total'))
            ->groupBy('nivel')
            ->orderByDesc('total')
            ->pluck('total', 'nivel');

        // Tiempo promedio de sesión
        $tiempoPromedio = SesionPrueba::where('estado', 'completada')
            ->when($testId, fn($q) => $q->where('test_id', $testId))
            ->avg('tiempo_total');

        return response()->json([
            'data' => [
                'periodo'               => compact('desde', 'hasta'),
                'total_sesiones'        => $totalSesiones,
                'sesiones_completadas'  => $sesionesCompletadas,
                'tasa_completitud'      => $totalSesiones > 0 ? round(($sesionesCompletadas / $totalSesiones) * 100, 1) : 0,
                'tiempo_promedio_min'   => $tiempoPromedio ? round($tiempoPromedio / 60, 1) : null,
                'promedios_por_factor'  => $promediosPorFactor,
                'distribucion_niveles'  => $distribucionNiveles,
            ],
        ]);
    }
}
