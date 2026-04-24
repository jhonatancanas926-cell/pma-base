<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\DocumentoService;
use App\Services\InformeEvaluacionService;
use App\Services\PdfReporteService;
use App\Services\PmaService;
use App\Models\Entrevista;
use App\Models\EntrevistaSeccion;
use App\Models\EntrevistaRespuesta;
use App\Models\SesionPrueba;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class WebController extends Controller
{
    public function __construct(
        private readonly PmaService              $pmaService,
        private readonly DocumentoService        $documentoService,
        private readonly InformeEvaluacionService $informeService,
        private readonly PdfReporteService $pdfReporteService,
    ) {}

    // ── Auth ──────────────────────────────────────────────────────────────

    public function loginForm()
    {
        if (Auth::check()) return redirect()->route('dashboard');
        return view('auth.login');
    }

    public function loginPost(Request $request)
    {
        $datos = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($datos, true)) {
            return back()->with('error', 'Credenciales incorrectas.')->withInput();
        }

        $user = Auth::user();
        // Generar token Sanctum para fetch al API desde vistas Blade
        $user->tokens()->where('name', 'web-session')->delete();
        $apiToken = $user->createToken('web-session')->plainTextToken;

        session([
            'user_name'  => $user->name,
            'user_role'  => $user->role,
            'user_email' => $user->email,
            'api_token'  => $apiToken,
        ]);

        return redirect()->route('dashboard');
    }

    public function registerForm()
    {
        return view('auth.register');
    }

    public function registerPost(Request $request)
    {
        $datos = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users',
            'password'  => 'required|min:8|confirmed',
            'documento' => 'nullable|string|max:20',
            'programa'  => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name'      => $datos['name'],
            'email'     => $datos['email'],
            'password'  => Hash::make($datos['password']),
            'documento' => $datos['documento'] ?? null,
            'programa'  => $datos['programa'] ?? null,
            'role'      => 'evaluado',
        ]);

        Auth::login($user);
        // Generar token Sanctum para fetch al API desde vistas Blade
        $apiToken = $user->createToken('web-session')->plainTextToken;
        session([
            'user_name'  => $user->name,
            'user_role'  => $user->role,
            'api_token'  => $apiToken,
        ]);

        return redirect()->route('dashboard')->with('flash_success', 'Cuenta creada correctamente. ¡Bienvenido!');
    }

    public function logout()
    {
        Auth::logout();
        session()->flush();
        return redirect()->route('login');
    }

    // ── Dashboard ─────────────────────────────────────────────────────────

    public function dashboard()
    {
        $user = Auth::user();
        $tests = Test::where('activo', true)->withCount('preguntas')->get()->toArray();

        $sesiones = SesionPrueba::where('user_id', $user->id)
            ->with('test:id,nombre')
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->toArray();

        // Estado de entrevista del aspirante
        $entrevista           = Entrevista::where('user_id', $user->id)->first();
        $entrevistaCompletada = $entrevista?->estado === 'completada';
        $entrevistaEnProgreso = $entrevista?->estado === 'en_progreso';
        $pmaHabilitado        = $entrevista?->pma_habilitado ?? false;

        return view('dashboard.index', [
            'tests'                => $tests,
            'sesionesRecientes'    => $sesiones,
            'sesionesCompletadas'  => SesionPrueba::where('user_id', $user->id)->where('estado','completada')->count(),
            'sesionesActivas'      => SesionPrueba::where('user_id', $user->id)->where('estado','en_progreso')->count(),
            'entrevistaCompletada' => $entrevistaCompletada,
            'entrevistaEnProgreso' => $entrevistaEnProgreso,
            'pmaHabilitado'        => $pmaHabilitado,
        ]);
    }

    // ── Pruebas ───────────────────────────────────────────────────────────

    public function pruebasIndex()
    {
        $tests = Test::where('activo', true)->withCount('preguntas')->with('categorias')->get();
        return view('prueba.index', compact('tests'));
    }

    public function pruebasShow(int $testId)
    {
        if (Auth::user()->isEvaluado()) {
            $entrevista = Entrevista::where('user_id', Auth::id())->first();
            if (!$entrevista || $entrevista->estado !== 'completada' || !$entrevista->pma_habilitado) {
                return redirect()->route('dashboard')->with('error', 'Aún no tienes acceso a esta prueba.');
            }
        }

        $test = Test::with(['categorias' => fn($q) => $q->withCount('preguntas')])->withCount('preguntas')->findOrFail($testId)->toArray();
        $sesionActiva = SesionPrueba::where('user_id', Auth::id())
            ->where('test_id', $testId)
            ->where('estado', 'en_progreso')
            ->first()?->toArray();

        return view('prueba.show', compact('test', 'sesionActiva'));
    }

    // ── Sesiones ──────────────────────────────────────────────────────────

    public function sesionesIndex()
    {
        $user = Auth::user();

        if ($user->isEvaluador()) {
            // El evaluador ve sesiones de TODOS los evaluados
            $sesiones = SesionPrueba::with(['test:id,nombre,codigo', 'user:id,name,documento'])
                ->orderByDesc('created_at')
                ->paginate(20);
        } else {
            $sesiones = SesionPrueba::where('user_id', $user->id)
                ->with('test:id,nombre,codigo')
                ->orderByDesc('created_at')
                ->paginate(10);
        }

        return view('sesiones.index', ['sesiones' => $sesiones, 'esEvaluador' => $user->isEvaluador()]);
    }

    public function sesionesStore(Request $request)
    {
        $request->validate(['test_id' => 'required|exists:tests,id']);

        if (Auth::user()->isEvaluado()) {
            $entrevista = Entrevista::where('user_id', Auth::id())->first();
            if (!$entrevista || $entrevista->estado !== 'completada' || !$entrevista->pma_habilitado) {
                return redirect()->route('dashboard')->with('error', 'Aún no tienes acceso a esta prueba.');
            }
        }

        $activa = SesionPrueba::where('user_id', Auth::id())
            ->where('test_id', $request->test_id)
            ->where('estado', 'en_progreso')
            ->first();

        if ($activa) {
            return redirect()->route('prueba.responder', $activa->id);
        }

        $completada = SesionPrueba::where('user_id', Auth::id())
            ->where('test_id', $request->test_id)
            ->where('estado', 'completada')
            ->first();

        if ($completada) {
            return redirect()->route('sesiones.resultados', $completada->id)
                ->with('warning', 'Ya has completado esta prueba anteriormente.');
        }

        $sesion = SesionPrueba::create([
            'user_id'       => Auth::id(),
            'test_id'       => $request->test_id,
            'estado'        => 'en_progreso',
            'iniciada_en'   => now(),
            'ip_cliente'    => $request->ip(),
            'agente_usuario'=> $request->userAgent(),
        ]);

        return redirect()->route('prueba.responder', $sesion->id)
            ->with('flash_success', '¡Evaluación iniciada! Responde con calma.');
    }

    public function pruebaResponder(Request $request, int $sesionId)
    {
        $sesion = SesionPrueba::where('id', $sesionId)
            ->where('user_id', Auth::id())
            ->with('test.categorias.preguntas.opciones')
            ->firstOrFail();

        if (!$sesion->estaActiva()) {
            return redirect()->route('sesiones.resultados', $sesionId);
        }

        $categorias = $sesion->test->categorias->toArray();
        $categoriaActual = $request->integer('categoria', $categorias[0]['id'] ?? 0);

        $preguntas = $sesion->test->categorias
            ->firstWhere('id', $categoriaActual)
            ?->preguntas
            ->where('activo', true)
            ->values()
            ->toArray() ?? [];

        $respuestasGuardadas = $sesion->respuestas()
            ->pluck('respuesta_dada', 'pregunta_id')
            ->toArray();

        $totalPreguntas = $sesion->test->preguntas()->where('activo', true)->count();
        $respondidas    = $sesion->respuestas()->count();

        $respuestasPorCategoria = $sesion->respuestas()
            ->join('preguntas', 'respuestas_usuario.pregunta_id', '=', 'preguntas.id')
            ->selectRaw('preguntas.categoria_id, COUNT(*) as total')
            ->groupBy('preguntas.categoria_id')
            ->pluck('total', 'categoria_id')
            ->toArray();

        return view('prueba.responder', compact(
            'sesion', 'categorias', 'categoriaActual',
            'preguntas', 'respuestasGuardadas',
            'totalPreguntas', 'respondidas', 'respuestasPorCategoria'
        ));
    }

    public function responderAjax(Request $request, int $sesionId)
    {
        $sesion = SesionPrueba::where('id', $sesionId)->where('user_id', Auth::id())->firstOrFail();
        $request->validate(['pregunta_id' => 'required|exists:preguntas,id', 'respuesta' => 'required|string']);

        $pregunta = \App\Models\Pregunta::with('opciones')->findOrFail($request->pregunta_id);
        $esNueva  = !$sesion->respuestas()->where('pregunta_id', $pregunta->id)->exists();

        $this->pmaService->registrarRespuesta($sesion, $pregunta, $request->respuesta);

        return response()->json(['ok' => true, 'nueva' => $esNueva]);
    }

    public function responderMultipleAjax(Request $request, int $sesionId)
    {
        $sesion = SesionPrueba::where('id', $sesionId)->where('user_id', Auth::id())->firstOrFail();
        $request->validate([
            'pregunta_id' => 'required|exists:preguntas,id',
            'respuestas' => 'present|array',
            'respuestas.*' => 'string'
        ]);

        $pregunta = \App\Models\Pregunta::with('opciones')->findOrFail($request->pregunta_id);
        
        $esNueva = true;
        if ($sesion->respuestas()->where('pregunta_id', $pregunta->id)->exists()) {
            $esNueva = false;
        } else if (empty($request->respuestas)) {
            // Si está vacío y es nuevo, no marcamos como nueva si no se guardó antes. 
            // Esto evita que cuente como respondida al deseleccionar todo por primera vez.
            $esNueva = false;
        }

        $this->pmaService->registrarRespuestaMultiple($sesion, $pregunta, $request->respuestas);

        return response()->json(['ok' => true, 'nueva' => $esNueva]);
    }

    public function sesionesResultados(int $sesionId)
    {
        $user  = Auth::user();
        $query = SesionPrueba::with(['test', 'resultados.categoria', 'user']);

        // Evaluadores pueden ver resultados de cualquier sesión
        if ($user->isEvaluador()) {
            $sesion = $query->findOrFail($sesionId);
        } else {
            $sesion = $query->where('user_id', $user->id)->findOrFail($sesionId);
        }

        $resumen = $this->pmaService->resumenSesion($sesion);
        return view('resultados.show', [
            'resumen'     => $resumen,
            'sesionId'    => $sesionId,
            'esEvaluador' => $user->isEvaluador(),
        ]);
    }

    public function sesionesFinalizarWeb(Request $request, int $sesionId)
    {
        $sesion = SesionPrueba::where('id', $sesionId)->where('user_id', Auth::id())->firstOrFail();
        if ($sesion->estaActiva()) {
            $this->pmaService->calcularResultados($sesion);
        }
        return redirect()->route('sesiones.resultados', $sesionId)
            ->with('flash_success', '¡Evaluación completada! Aquí están tus resultados.');
    }

    public function descargarReporte(int $sesionId)
    {
        $user  = Auth::user();
        $query = SesionPrueba::with(['test', 'resultados.categoria', 'user']);

        $sesion = $user->isEvaluador()
            ? $query->findOrFail($sesionId)
            : $query->where('user_id', $user->id)->findOrFail($sesionId);

        $resumen = $this->pmaService->resumenSesion($sesion);
        $destino = storage_path('app/reportes');
        $rutaPdf = $this->pdfReporteService->generarPdf($sesion, $resumen, $destino);

        $nombre = 'Reporte_PMA_' . str_replace(' ', '_', $sesion->user->name) . '_' . now()->format('Ymd') . '.pdf';

        return response()->download($rutaPdf, $nombre)->deleteFileAfterSend(true);
    }

    public function descargarReporteWord(int $sesionId)
    {
        $user  = Auth::user();
        $query = SesionPrueba::with(['test', 'resultados.categoria', 'user']);

        // Evaluadores pueden descargar el informe de cualquier sesión
        $sesion = $user->isEvaluador()
            ? $query->findOrFail($sesionId)
            : $query->where('user_id', $user->id)->findOrFail($sesionId);

        $resumen  = $this->pmaService->resumenSesion($sesion);
        $destino  = storage_path('app/reportes');

        // Usar el InformeEvaluacionService (plantilla Ecotet Aviation Academy)
        $rutaDocx = $this->informeService->generar($sesion, $resumen, $destino);

        $nombre = 'Informe_Ecotet_' . str_replace(' ', '_', $sesion->user->name) . '_' . now()->format('Ymd') . '.docx';

        return response()->download($rutaDocx, $nombre)->deleteFileAfterSend(true);
    }

    // ── Entrevista: aspirante ─────────────────────────────────────────────

    public function entrevistaIndex()
    {
        $user       = Auth::user();
        $token      = session('api_token');

        // Crear o recuperar entrevista
        $entrevista = Entrevista::firstOrCreate(
            ['user_id' => $user->id],
            ['estado' => 'en_progreso']
        );
        if ($entrevista->estado === 'pendiente') {
            $entrevista->update(['estado' => 'en_progreso']);
        }

        $secciones = EntrevistaSeccion::where('activa', true)
            ->orderBy('orden')
            ->with(['preguntas' => fn($q) => $q->orderBy('orden')])
            ->get();

        $respuestasGuardadas = EntrevistaRespuesta::where('entrevista_id', $entrevista->id)
            ->pluck('respuesta', 'pregunta_id');

        $seccionesData = $secciones->map(fn($s) => [
            'id'          => $s->id,
            'nombre'      => $s->nombre,
            'slug'        => $s->slug,
            'tipo'        => $s->tipo,
            'descripcion' => $s->descripcion,
            'preguntas'   => $s->preguntas->map(fn($p) => [
                'id'             => $p->id,
                'enunciado'      => $p->enunciado,
                'tipo_respuesta' => $p->tipo_respuesta,
                'opciones'       => $p->opciones ?? [],
                'obligatoria'    => $p->obligatoria,
                'orden'          => $p->orden,
                'clave_word'     => $p->clave_word,
                'respuesta'      => $respuestasGuardadas[$p->id] ?? null,
            ])->toArray(),
        ])->toArray();

        $total       = \App\Models\EntrevistaPregunta::count();
        $respondidas = EntrevistaRespuesta::where('entrevista_id', $entrevista->id)
            ->whereNotNull('respuesta')->count();

        $progreso = [
            'total'       => $total,
            'respondidas' => $respondidas,
            'porcentaje'  => $total > 0 ? round(($respondidas / $total) * 100) : 0,
        ];

        $entrevistaData = [
            'id'            => $entrevista->id,
            'estado'        => $entrevista->estado,
            'completada_en' => $entrevista->completada_en,
            'pma_habilitado'=> $entrevista->pma_habilitado,
        ];

        return view('entrevista.index', [
            'entrevista' => $entrevistaData,
            'secciones'  => $seccionesData,
            'progreso'   => $progreso,
        ]);
    }

    public function entrevistaResponder(Request $request)
    {
        $user       = Auth::user();
        $entrevista = Entrevista::firstOrCreate(
            ['user_id' => $user->id],
            ['estado' => 'en_progreso']
        );

        if ($entrevista->estaCompleta()) {
            return response()->json(['message' => 'La entrevista ya fue completada.'], 403);
        }

        $request->validate(['pregunta_id' => 'required|integer', 'respuesta' => 'nullable|string|max:5000']);

        EntrevistaRespuesta::updateOrCreate(
            ['entrevista_id' => $entrevista->id, 'pregunta_id' => $request->pregunta_id],
            ['respuesta' => $request->respuesta, 'editado_por' => null, 'editada_en' => null]
        );

        // Calcular progreso actualizado para la barra en tiempo real
        $total       = \App\Models\EntrevistaPregunta::count();
        $respondidas = EntrevistaRespuesta::where('entrevista_id', $entrevista->id)->whereNotNull('respuesta')->count();

        return response()->json([
            'message'  => 'Guardado.',
            'progreso'  => [
                'total'       => $total,
                'respondidas' => $respondidas,
                'porcentaje'  => $total > 0 ? round(($respondidas / $total) * 100) : 0,
            ],
        ]);
    }

    public function entrevistaCompletar(Request $request)
    {
        $user       = Auth::user();
        $entrevista = Entrevista::where('user_id', $user->id)->firstOrFail();

        $entrevista->update([
            'estado'         => 'completada',
            'completado_por' => $user->id,
            'completada_en'  => now(),
        ]);

        return redirect()->route('dashboard')->with('flash_success', '¡Entrevista completada! Ya puedes acceder a la prueba PMA-R.');
    }

    // ── Evaluador ─────────────────────────────────────────────────────────

    public function evaluadorAspirantesIndex(Request $request)
    {
        $user = Auth::user();
        if (!$user->isEvaluador()) abort(403);

        $page = $request->get('page', 1);

        $entrevistas = Entrevista::with(['user'])
            ->latest()
            ->paginate(20, ['*'], 'page', $page);

        $data = [
            'data'         => $entrevistas->map(fn($e) => [
                'user_id'       => $e->user_id,
                'estado'        => $e->estado,
                'completada_en' => $e->completada_en,
                'pma_habilitado'=> $e->pma_habilitado,
                'user'          => [
                    'name'      => $e->user->name,
                    'email'     => $e->user->email,
                    'documento' => $e->user->documento,
                    'programa'  => $e->user->programa,
                ],
            ])->toArray(),
            'total'        => $entrevistas->total(),
            'current_page' => $entrevistas->currentPage(),
            'last_page'    => $entrevistas->lastPage(),
        ];

        return view('evaluador.aspirantes', compact('data'));
    }

    public function evaluadorEntrevistaShow(int $userId)
    {
        $user = Auth::user();
        if (!$user->isEvaluador()) abort(403);

        $aspirante  = User::findOrFail($userId);
        $entrevista = Entrevista::firstOrCreate(
            ['user_id' => $userId],
            ['estado' => 'pendiente']
        );

        $secciones = EntrevistaSeccion::where('activa', true)
            ->orderBy('orden')
            ->with(['preguntas' => fn($q) => $q->orderBy('orden')])
            ->get();

        $respuestasGuardadas = EntrevistaRespuesta::where('entrevista_id', $entrevista->id)
            ->pluck('respuesta', 'pregunta_id');

        $seccionesData = $secciones->map(fn($s) => [
            'id'        => $s->id,
            'nombre'    => $s->nombre,
            'slug'      => $s->slug,
            'tipo'      => $s->tipo,
            'preguntas' => $s->preguntas->map(fn($p) => [
                'id'             => $p->id,
                'enunciado'      => $p->enunciado,
                'tipo_respuesta' => $p->tipo_respuesta,
                'opciones'       => $p->opciones ?? [],
                'obligatoria'    => $p->obligatoria,
                'orden'          => $p->orden,
                'clave_word'     => $p->clave_word,
                'respuesta'      => $respuestasGuardadas[$p->id] ?? null,
            ])->toArray(),
        ])->toArray();

        return view('evaluador.entrevista_show', [
            'aspirante'  => $aspirante->toArray(),
            'entrevista' => ['estado' => $entrevista->estado, 'completada_en' => $entrevista->completada_en, 'pma_habilitado' => $entrevista->pma_habilitado],
            'secciones'  => $seccionesData,
            'userId'     => $userId,
        ]);
    }

    public function evaluadorEditarRespuesta(Request $request, int $userId)
    {
        $user = Auth::user();
        if (!$user->isEvaluador()) abort(403);

        $entrevista = Entrevista::where('user_id', $userId)->firstOrFail();

        EntrevistaRespuesta::updateOrCreate(
            ['entrevista_id' => $entrevista->id, 'pregunta_id' => $request->pregunta_id],
            ['respuesta' => $request->respuesta, 'editado_por' => $user->id, 'editada_en' => now()]
        );

        return response()->json(['message' => 'Actualizado por evaluador.']);
    }

    public function evaluadorCambiarEstado(Request $request, int $userId)
    {
        $user = Auth::user();
        if (!$user->isEvaluador()) abort(403);

        $entrevista = Entrevista::where('user_id', $userId)->firstOrFail();
        $entrevista->update([
            'estado'         => $request->estado,
            'completado_por' => $user->id,
            'completada_en'  => $request->estado === 'completada' ? now() : null,
        ]);

        return redirect()->route('evaluador.aspirantes')
            ->with('flash_success', 'Estado actualizado correctamente.');
    }

    public function evaluadorHabilitarPma(Request $request, int $userId)
    {
        $user = Auth::user();
        if (!$user->isEvaluador()) abort(403);

        $entrevista = Entrevista::where('user_id', $userId)->firstOrFail();
        $nuevoEstado = !$entrevista->pma_habilitado;
        $entrevista->update([
            'pma_habilitado' => $nuevoEstado,
        ]);

        $mensaje = $nuevoEstado 
            ? 'Acceso a la prueba PMA-R habilitado correctamente.' 
            : 'Acceso a la prueba PMA-R deshabilitado.';

        return redirect()->back()->with('flash_success', $mensaje);
    }


    public function estadisticas()
    {
        $user = Auth::user();
        if (!$user->isEvaluador()) abort(403);

        $totalSesiones    = SesionPrueba::count();
        $completadas      = SesionPrueba::where('estado', 'completada')->count();
        $totalUsuarios    = User::where('role', 'evaluado')->count();
        $promediosPorFactor = \App\Models\Resultado::with('categoria:id,nombre,codigo')
            ->select('categoria_id',
                \Illuminate\Support\Facades\DB::raw('AVG(correctas) as promedio_correctas'),
                \Illuminate\Support\Facades\DB::raw('AVG(puntaje_bruto) as promedio_puntaje'),
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as total')
            )
            ->groupBy('categoria_id')
            ->get();

        return view('estadisticas.index', compact(
            'totalSesiones', 'completadas', 'totalUsuarios', 'promediosPorFactor'
        ));
    }
}
