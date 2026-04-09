<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\PmaImport;
use App\Models\Importacion;
use App\Models\Test;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportarController extends Controller
{
    /**
     * POST /api/v1/importar
     * Carga un Excel y procesa todos los factores PMA-R.
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Solo administradores
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $request->validate([
            'archivo'  => 'required|file|mimes:xlsx,xls|max:10240',
            'test_id'  => 'nullable|exists:tests,id',
        ]);

        // Obtener o crear la prueba PMA-R
        $test = $request->filled('test_id')
            ? Test::findOrFail($request->test_id)
            : Test::firstOrCreate(
                ['codigo' => 'PMA-R'],
                [
                    'nombre'       => 'PMA - Aptitudes Mentales Primarias (Revisada)',
                    'descripcion'  => 'Batería de aptitudes cognitivas de Thurstone. Evalúa factores V, E, R y N.',
                    'version'      => '1.0',
                    'tiempo_limite'=> 25,
                    'activo'       => true,
                ]
            );

        // Guardar archivo temporalmente
        $ruta = $request->file('archivo')->storeAs(
            'importaciones',
            'pma_' . now()->format('Ymd_His') . '.' . $request->file('archivo')->getClientOriginalExtension()
        );

        $rutaAbsoluta = Storage::path($ruta);

        try {
            $importador = new PmaImport($request->user()->id, $test->id);
            $resultado  = $importador->importar($rutaAbsoluta);

            Log::info('[ImportarController] Importación completada.', $resultado);

            return response()->json([
                'message'   => 'Importación completada.',
                'test_id'   => $test->id,
                'resultado' => $resultado,
            ], 201);

        } catch (\Throwable $e) {
            Log::error('[ImportarController] Error fatal.', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Error al procesar el archivo.',
                'detalle' => $e->getMessage(),
            ], 500);
        } finally {
            // Limpiar archivo temporal
            Storage::delete($ruta);
        }
    }
}
