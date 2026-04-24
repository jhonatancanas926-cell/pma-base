<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EntrevistaController;
use App\Http\Controllers\Api\EstadisticasController;
use App\Http\Controllers\Api\ImportarController;
use App\Http\Controllers\Api\ReporteController;
use App\Http\Controllers\Api\SesionController;
use App\Http\Controllers\Api\TestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Sistema PMA-R
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ── Autenticación (pública) ──────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    // ── Rutas protegidas con Sanctum ─────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // Pruebas
        Route::get('tests', [TestController::class, 'index']);
        Route::get('tests/{test}', [TestController::class, 'show']);
        Route::get('tests/{test}/preguntas', [TestController::class, 'preguntas']);
        Route::post('tests', [TestController::class, 'store']);

        // ── Entrevista psicosocial (aspirante) ──────────────────────────
        Route::get('entrevista', [EntrevistaController::class, 'show']);
        Route::post('entrevista/responder', [EntrevistaController::class, 'guardarRespuesta']);
        Route::post('entrevista/completar', [EntrevistaController::class, 'completar']);

        // ── Evaluador: gestión de entrevistas de aspirantes ──────────────
        Route::prefix('evaluador')->group(function () {
            Route::get('entrevistas', [EntrevistaController::class, 'listarParaEvaluador']);
            Route::get('entrevistas/{userId}', [EntrevistaController::class, 'showParaEvaluador']);
            Route::patch('entrevistas/{userId}/responder', [EntrevistaController::class, 'editarRespuesta']);
            Route::patch('entrevistas/{userId}/estado', [EntrevistaController::class, 'cambiarEstado']);
        });

        // ── Sesiones de evaluación (PMA-R — requiere entrevista completa) ─
        Route::middleware('entrevista.completada')->group(function () {
            Route::post('sesiones', [SesionController::class, 'store']);
            Route::post('sesiones/{id}/responder', [SesionController::class, 'responder']);
            Route::post('sesiones/{id}/finalizar', [SesionController::class, 'finalizar']);
        });

        // Consulta de sesiones (sin gate — para ver historial)
        Route::get('sesiones', [SesionController::class, 'index']);
        Route::get('sesiones/{id}/resultados', [SesionController::class, 'resultados']);
        Route::get('sesiones/{id}/reporte', [ReporteController::class, 'descargarReporte']);

        // Importación Excel
        Route::post('importar', ImportarController::class);

        // Documentación
        Route::get('documentacion', [ReporteController::class, 'documentacion']);

        // Informe de evaluación Ecotet Aviation Academy (reporte final completo)
        Route::get('sesiones/{id}/informe-ecotet', [ReporteController::class, 'descargarInformeEcotet']);

        // Estadísticas
        Route::get('estadisticas', EstadisticasController::class);
    });
});

