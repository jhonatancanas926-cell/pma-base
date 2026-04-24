<?php

namespace App\Http\Middleware;

use App\Models\Entrevista;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EntrevistaCompletada
{
    /**
     * Bloquea el acceso a rutas de PMA-R si el aspirante
     * no ha completado la entrevista psicosocial.
     * Los evaluadores y admins pasan siempre.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Evaluadores y admins no necesitan completar la entrevista
        if ($user && $user->isEvaluador()) {
            return $next($request);
        }

        $entrevista = Entrevista::where('user_id', $user->id)->first();

        if (!$entrevista || !$entrevista->estaCompleta()) {
            // Respuesta para peticiones AJAX/API
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Debes completar la entrevista psicosocial antes de acceder a la prueba PMA-R.',
                    'redirect' => route('entrevista.index'),
                ], 403);
            }

            // Respuesta para navegación web normal
            return redirect()->route('entrevista.index')
                ->with('warning', 'Primero debes completar el formulario de entrevista y antecedentes.');
        }

        return $next($request);
    }
}
