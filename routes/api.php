<?php

use App\Http\Controllers\Api\AuthController;
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
        Route::post('login',    [AuthController::class, 'login']);
    });

    // ── Rutas protegidas con Sanctum ─────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me',      [AuthController::class, 'me']);

        // Pruebas
        Route::get('tests',                   [TestController::class, 'index']);
        Route::get('tests/{test}',            [TestController::class, 'show']);
        Route::get('tests/{test}/preguntas',  [TestController::class, 'preguntas']);
        Route::post('tests',                  [TestController::class, 'store']);

        // Sesiones de evaluación
        Route::get('sesiones',                [SesionController::class, 'index']);
        Route::post('sesiones',               [SesionController::class, 'store']);
        Route::post('sesiones/{id}/responder',[SesionController::class, 'responder']);
        Route::post('sesiones/{id}/finalizar',[SesionController::class, 'finalizar']);
        Route::get('sesiones/{id}/resultados',[SesionController::class, 'resultados']);
        Route::get('sesiones/{id}/reporte',   [ReporteController::class, 'descargarReporte']);

        // Importación Excel
        Route::post('importar', ImportarController::class);

        // Documentación
        Route::get('documentacion', [ReporteController::class, 'documentacion']);

        // Estadísticas
        Route::get('estadisticas', EstadisticasController::class);
    });
});
