@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')

    <div class="page-header">
        <h1>Bienvenido, <span class="text-accent">{{ session('user_name') }}</span> 👋</h1>
        <p class="text-muted">Panel de control — Sistema PMA-R · Uniempresarial</p>
    </div>

    <!-- Stats -->
    <div class="grid-4 mb-3">
        <div class="card card-sm" style="border-left:4px solid #2e75b6">
            <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600">
                Pruebas disponibles</div>
            <div style="font-family:'DM Serif Display',serif;font-size:2.2rem;color:#0f1f3d;line-height:1.1;margin-top:4px">
                {{ count($tests) }}</div>
        </div>
        <div class="card card-sm" style="border-left:4px solid #107c10">
            <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600">
                Sesiones completadas</div>
            <div style="font-family:'DM Serif Display',serif;font-size:2.2rem;color:#0f1f3d;line-height:1.1;margin-top:4px">
                {{ $sesionesCompletadas }}</div>
        </div>
        <div class="card card-sm" style="border-left:4px solid #e8a020">
            <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600">
                En progreso</div>
            <div style="font-family:'DM Serif Display',serif;font-size:2.2rem;color:#0f1f3d;line-height:1.1;margin-top:4px">
                {{ $sesionesActivas }}</div>
        </div>
        <div class="card card-sm" style="border-left:4px solid #1a3a6b">
            <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600">
                Mi rol</div>
            <div style="font-size:1.1rem;color:#0f1f3d;font-weight:700;margin-top:8px;text-transform:capitalize">
                {{ session('user_role') }}</div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Módulo: Entrevista psicosocial — solo visible para evaluados -->
        @if(session('user_role') === 'evaluado')
            @if(!($entrevistaCompletada ?? false))
                <div
                    style="background:#fffbeb;border:1.5px solid #fcd34d;border-radius:16px;padding:1.25rem 1.5rem;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
                    <div style="display:flex;align-items:center;gap:12px">
                        <div
                            style="width:44px;height:44px;background:#e8a020;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0">
                            📋</div>
                        <div>
                            <div style="font-weight:700;color:#0f1f3d;font-size:.95rem">Entrevista y Antecedentes</div>
                            <div style="font-size:.78rem;color:#92400e;margin-top:2px">⚠ Requisito obligatorio antes de realizar la
                                prueba PMA-R</div>
                        </div>
                    </div>
                    <a href="{{ route('entrevista.index') }}"
                        style="padding:.6rem 1.25rem;background:#e8a020;color:#fff;border-radius:10px;font-weight:700;font-size:.875rem;text-decoration:none;white-space:nowrap">
                        {{ $entrevistaEnProgreso ?? false ? '▶ Continuar' : '➕ Iniciar' }}
                    </a>
                </div>
            @else
                <div
                    style="background:#d1fae5;border:1.5px solid #6ee7b7;border-radius:16px;padding:1rem 1.5rem;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
                    <div style="display:flex;align-items:center;gap:12px">
                        <div style="font-size:1.3rem">✅</div>
                        <div>
                            <div style="font-weight:700;color:#065f46;font-size:.95rem">Entrevista completada</div>
                            <div style="font-size:.78rem;color:#065f46;margin-top:2px">
                                {{ $pmaHabilitado ? 'La prueba PMA-R ya está disponible' : 'Esperando a que el evaluador habilite la prueba PMA-R' }}
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('entrevista.index') }}"
                        style="font-size:.8rem;color:#065f46;text-decoration:none;font-weight:600">Ver formulario →</a>
                </div>
            @endif
        @endif

        <!-- Pruebas disponibles -->
        <div class="card">
            <div class="d-flex justify-between align-center mb-2">
                <h2 class="serif" style="font-size:1.3rem;color:#0f1f3d">Pruebas Disponibles</h2>
                <a href="{{ route('pruebas.index') }}" class="btn btn-outline btn-sm">Ver todas</a>
            </div>

            @forelse($tests as $test)
                @php
                    // Evaluadores y admins siempre tienen acceso; evaluados necesitan entrevista completada Y habilitación del evaluador
                    $puedeIniciar = session('user_role') !== 'evaluado' || (($entrevistaCompletada ?? false) && ($pmaHabilitado ?? false));
                @endphp
                <div style="padding:1rem;border:1px solid #eef1f5;border-radius:12px;margin-bottom:0.75rem;display:flex;align-items:center;justify-content:space-between;transition:box-shadow 0.2s;{{ !$puedeIniciar ? 'opacity:.6' : '' }}"
                    onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)'" onmouseout="this.style.boxShadow='none'">
                    <div style="display:flex;align-items:center;gap:12px">
                        <div
                            style="width:44px;height:44px;background:#1a3a6b;border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:'DM Serif Display',serif;color:#e8a020;font-size:1rem;flex-shrink:0">
                            {{ substr($test['codigo'], 0, 1) }}
                        </div>
                        <div>
                            <div style="font-weight:600;color:#0f1f3d;font-size:0.9rem">{{ $test['nombre'] }}</div>
                            <div style="font-size:0.78rem;color:#6b7a8d">
                                {{ $test['preguntas_count'] ?? '—' }} preguntas ·
                                {{ $test['tiempo_limite'] }} min
                            </div>
                        </div>
                    </div>
                    @if($puedeIniciar)
                        <form action="{{ route('sesiones.store') }}" method="POST" style="margin:0;">
                            @csrf
                            <input type="hidden" name="test_id" value="{{ $test['id'] }}">
                            <button type="submit" class="btn btn-primary btn-sm">Iniciar</button>
                        </form>
                    @else
                        <span title="{{ ($entrevistaCompletada ?? false) ? 'Esperando habilitación del evaluador' : 'Completa la entrevista primero' }}"
                            style="padding:.35rem .9rem;background:#eef1f5;color:#6b7a8d;border-radius:8px;font-size:.78rem;font-weight:600;cursor:not-allowed">
                            🔒 Bloqueado
                        </span>
                    @endif
                </div>
            @empty
                <div class="text-center text-muted" style="padding:2rem">
                    No hay pruebas disponibles aún.
                </div>
            @endforelse
        </div>

        @if(session('user_role') !== 'evaluado')
        <!-- Sesiones recientes -->
        <div class="card">
            <div class="d-flex justify-between align-center mb-2">
                <h2 class="serif" style="font-size:1.3rem;color:#0f1f3d">Sesiones Recientes</h2>
                <a href="{{ route('sesiones.index') }}" class="btn btn-outline btn-sm">Ver todas</a>
            </div>

            @forelse($sesionesRecientes as $sesion)
                @php
                    $estadoClase = match ($sesion['estado']) {
                        'completada' => 'badge-green',
                        'en_progreso' => 'badge-orange',
                        'abandonada' => 'badge-red',
                        default => 'badge-gray',
                    };
                    $estadoTexto = match ($sesion['estado']) {
                        'completada' => 'Completada',
                        'en_progreso' => 'En progreso',
                        'abandonada' => 'Abandonada',
                        default => $sesion['estado'],
                    };
                @endphp
                <div
                    style="padding:0.85rem 1rem;border:1px solid #eef1f5;border-radius:12px;margin-bottom:0.75rem;display:flex;align-items:center;justify-content:space-between">
                    <div>
                        <div style="font-weight:600;color:#0f1f3d;font-size:0.875rem">
                            {{ $sesion['test']['nombre'] ?? 'Prueba' }}</div>
                        <div style="font-size:0.78rem;color:#6b7a8d">
                            {{ \Carbon\Carbon::parse($sesion['created_at'])->format('d/m/Y H:i') }}
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px">
                        <span class="badge {{ $estadoClase }}">{{ $estadoTexto }}</span>
                        @if($sesion['estado'] === 'completada')
                            <a href="{{ route('sesiones.resultados', $sesion['id']) }}"
                                class="btn btn-sm btn-primary">Resultados</a>
                        @elseif($sesion['estado'] === 'en_progreso')
                            <a href="{{ route('prueba.responder', $sesion['id']) }}" class="btn btn-sm btn-accent">Continuar</a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center text-muted" style="padding:2rem">
                    <div style="font-size:2.5rem;margin-bottom:0.5rem">📋</div>
                    Aún no has realizado ninguna evaluación.
                </div>
            @endforelse
        </div>
        @endif
    </div>

@endsection