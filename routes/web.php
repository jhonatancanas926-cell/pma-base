<?php

use App\Http\Controllers\Web\WebController;
use Illuminate\Support\Facades\Route;

// ── Públicas ──────────────────────────────────────────────────────────────────
Route::get('/',        [WebController::class, 'loginForm'])->name('login');
Route::post('/login',  [WebController::class, 'loginPost'])->name('login.post');
Route::get('/register',  [WebController::class, 'registerForm'])->name('register');
Route::post('/register', [WebController::class, 'registerPost'])->name('register.post');

// ── Protegidas ────────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/logout', [WebController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [WebController::class, 'dashboard'])->name('dashboard');

    // Pruebas
    Route::get('/pruebas',      [WebController::class, 'pruebasIndex'])->name('pruebas.index');
    Route::get('/pruebas/{id}', [WebController::class, 'pruebasShow'])->name('pruebas.show');

    // Sesiones
    Route::get('/sesiones',              [WebController::class, 'sesionesIndex'])->name('sesiones.index');
    Route::post('/sesiones',             [WebController::class, 'sesionesStore'])->name('sesiones.store');
    Route::get('/sesiones/{id}/resultados', [WebController::class, 'sesionesResultados'])->name('sesiones.resultados');
    Route::post('/sesiones/{id}/finalizar', [WebController::class, 'sesionesFinalizarWeb'])->name('sesiones.finalizar');
    Route::get('/sesiones/{id}/reporte',    [WebController::class, 'descargarReporte'])->name('sesiones.reporte');

    // Responder preguntas
    Route::get('/prueba/{id}/responder',   [WebController::class, 'pruebaResponder'])->name('prueba.responder');
    Route::post('/web/sesiones/{id}/responder', [WebController::class, 'responderAjax'])->name('prueba.responder.ajax');

    // Estadísticas
    Route::get('/estadisticas', [WebController::class, 'estadisticas'])->name('estadisticas');
});
