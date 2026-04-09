<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SesionPrueba;
use App\Services\DocumentoService;
use App\Services\PmaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReporteController extends Controller
{
    public function __construct(
        private readonly PmaService       $pmaService,
        private readonly DocumentoService $documentoService,
    ) {}

    /**
     * GET /api/v1/sesiones/{id}/reporte
     * Genera y descarga el reporte Word del evaluado.
     */
    public function descargarReporte(Request $request, int $sesionId): BinaryFileResponse|JsonResponse
    {
        // Evaluadores y admins pueden ver cualquier sesión; evaluados solo la suya
        $query = SesionPrueba::with(['test', 'resultados.categoria', 'user']);

        if ($request->user()->isEvaluador()) {
            $sesion = $query->findOrFail($sesionId);
        } else {
            $sesion = $query->where('user_id', $request->user()->id)->findOrFail($sesionId);
        }

        if ($sesion->estaActiva()) {
            return response()->json(['message' => 'La sesión aún no ha sido finalizada.'], 422);
        }

        $resumen   = $this->pmaService->resumenSesion($sesion);
        $destino   = storage_path('app/reportes');
        $rutaDocx  = $this->documentoService->generarReporteUsuario($sesion, $resumen, $destino);

        $nombre = 'Reporte_PMA_' . str_replace(' ', '_', $sesion->user->name) . '_' . now()->format('Ymd') . '.docx';

        return response()->download($rutaDocx, $nombre, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    /**
     * GET /api/v1/documentacion
     * Genera y descarga la documentación técnica completa del sistema.
     */
    public function documentacion(Request $request): BinaryFileResponse|JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $destino  = storage_path('app/reportes');
        $rutaDocx = $this->documentoService->generarDocumentacionSistema($destino);

        return response()->download($rutaDocx, 'Documentacion_Sistema_PMA_R.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }
}
