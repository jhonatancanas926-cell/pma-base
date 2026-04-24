<?php

use App\Http\Controllers\Web\WebController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReporteController;

// ── Públicas ──────────────────────────────────────────────────────────────────
Route::get('/', [WebController::class, 'loginForm'])->name('login');
Route::post('/login', [WebController::class, 'loginPost'])->name('login.post');
Route::get('/register', [WebController::class, 'registerForm'])->name('register');
Route::post('/register', [WebController::class, 'registerPost'])->name('register.post');

// ── Protegidas ────────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/logout', [WebController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [WebController::class, 'dashboard'])->name('dashboard');

    // ── Entrevista (aspirante) ────────────────────────────────────────────
    Route::get('/entrevista', [WebController::class, 'entrevistaIndex'])->name('entrevista.index');
    Route::post('/entrevista/responder', [WebController::class, 'entrevistaResponder'])->name('entrevista.responder');
    Route::post('/entrevista/completar', [WebController::class, 'entrevistaCompletar'])->name('entrevista.completar');

    // ── Evaluador: gestión de aspirantes ─────────────────────────────────
    Route::get('/evaluador/aspirantes', [WebController::class, 'evaluadorAspirantesIndex'])->name('evaluador.aspirantes');
    Route::get('/evaluador/aspirantes/{userId}', [WebController::class, 'evaluadorEntrevistaShow'])->name('evaluador.entrevista.show');
    Route::post('/evaluador/aspirantes/{userId}/responder', [WebController::class, 'evaluadorEditarRespuesta'])->name('evaluador.entrevista.responder');
    Route::post('/evaluador/aspirantes/{userId}/estado', [WebController::class, 'evaluadorCambiarEstado'])->name('evaluador.entrevista.estado');
    Route::post('/evaluador/aspirantes/{userId}/habilitar-pma', [WebController::class, 'evaluadorHabilitarPma'])->name('evaluador.entrevista.habilitar_pma');


    // Pruebas
    Route::get('/pruebas', [WebController::class, 'pruebasIndex'])->name('pruebas.index');
    Route::get('/pruebas/{id}', [WebController::class, 'pruebasShow'])->name('pruebas.show');

    // Sesiones
    Route::get('/sesiones', [WebController::class, 'sesionesIndex'])->name('sesiones.index');
    Route::post('/sesiones', [WebController::class, 'sesionesStore'])->name('sesiones.store');
    Route::get('/sesiones/{id}/resultados', [WebController::class, 'sesionesResultados'])->name('sesiones.resultados');
    Route::post('/sesiones/{id}/finalizar', [WebController::class, 'sesionesFinalizarWeb'])->name('sesiones.finalizar');
    Route::get('/sesiones/{id}/reporte', [WebController::class, 'descargarReporte'])->name('sesiones.reporte');
    Route::get('/sesiones/{id}/reporte/word', [WebController::class, 'descargarReporteWord'])->name('sesiones.reporte.word');

    // Responder preguntas
    Route::get('/prueba/{id}/responder', [WebController::class, 'pruebaResponder'])->name('prueba.responder');
    Route::post('/web/sesiones/{id}/responder', [WebController::class, 'responderAjax'])->name('prueba.responder.ajax');
    Route::post('/web/sesiones/{id}/responder-multiple', [WebController::class, 'responderMultipleAjax'])->name('prueba.responder.multiple.ajax');

    // Estadísticas
    Route::get('/estadisticas', [WebController::class, 'estadisticas'])->name('estadisticas');

});
