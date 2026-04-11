<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\DocumentoService;
use App\Services\PdfReporteService;
use App\Services\PmaService;
use App\Models\SesionPrueba;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class WebController extends Controller
{
    public function __construct(
        private readonly PmaService $pmaService,
        private readonly DocumentoService $documentoService,
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
        session([
            'user_name'  => $user->name,
            'user_role'  => $user->role,
            'user_email' => $user->email,
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
        session(['user_name' => $user->name, 'user_role' => $user->role]);

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

        return view('dashboard.index', [
            'tests'              => $tests,
            'sesionesRecientes'  => $sesiones,
            'sesionesCompletadas'=> SesionPrueba::where('user_id', $user->id)->where('estado','completada')->count(),
            'sesionesActivas'    => SesionPrueba::where('user_id', $user->id)->where('estado','en_progreso')->count(),
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
        $sesiones = SesionPrueba::where('user_id', Auth::id())
            ->with('test:id,nombre,codigo')
            ->orderByDesc('created_at')
            ->paginate(10);
        return view('sesiones.index', compact('sesiones'));
    }

    public function sesionesStore(Request $request)
    {
        $request->validate(['test_id' => 'required|exists:tests,id']);

        $activa = SesionPrueba::where('user_id', Auth::id())
            ->where('test_id', $request->test_id)
            ->where('estado', 'en_progreso')
            ->first();

        if ($activa) {
            return redirect()->route('prueba.responder', $activa->id);
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

    public function sesionesResultados(int $sesionId)
    {
        $sesion = SesionPrueba::where('id', $sesionId)
            ->where('user_id', Auth::id())
            ->with(['test', 'resultados.categoria', 'user'])
            ->firstOrFail();

        $resumen = $this->pmaService->resumenSesion($sesion);
        return view('resultados.show', ['resumen' => $resumen, 'sesionId' => $sesionId]);
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
        $sesion = SesionPrueba::where('id', $sesionId)
            ->where('user_id', Auth::id())
            ->with(['test', 'resultados.categoria', 'user'])
            ->firstOrFail();

        $resumen  = $this->pmaService->resumenSesion($sesion);
        $destino  = storage_path('app/reportes');
        $rutaPdf = $this->pdfReporteService->generarPdf($sesion, $resumen, $destino);

        $nombre = 'Reporte_PMA_' . str_replace(' ', '_', $sesion->user->name) . '_' . now()->format('Ymd') . '.pdf';

        return response()->download($rutaPdf, $nombre)->deleteFileAfterSend(true);
    }

    public function descargarReporteWord(int $sesionId)
    {
        $sesion = SesionPrueba::where('id', $sesionId)
            ->where('user_id', Auth::id())
            ->with(['test', 'resultados.categoria', 'user'])
            ->firstOrFail();

        $resumen  = $this->pmaService->resumenSesion($sesion);
        $destino  = storage_path('app/reportes');
        $rutaDocx = $this->documentoService->generarReporteUsuario($sesion, $resumen, $destino);

        $nombre = 'Reporte_PMA_' . str_replace(' ', '_', $sesion->user->name) . '_' . now()->format('Ymd') . '.docx';

        return response()->download($rutaDocx, $nombre)->deleteFileAfterSend(true);
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
