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
        <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600">Pruebas disponibles</div>
        <div style="font-family:'DM Serif Display',serif;font-size:2.2rem;color:#0f1f3d;line-height:1.1;margin-top:4px">{{ count($tests) }}</div>
    </div>
    <div class="card card-sm" style="border-left:4px solid #107c10">
        <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600">Sesiones completadas</div>
        <div style="font-family:'DM Serif Display',serif;font-size:2.2rem;color:#0f1f3d;line-height:1.1;margin-top:4px">{{ $sesionesCompletadas }}</div>
    </div>
    <div class="card card-sm" style="border-left:4px solid #e8a020">
        <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600">En progreso</div>
        <div style="font-family:'DM Serif Display',serif;font-size:2.2rem;color:#0f1f3d;line-height:1.1;margin-top:4px">{{ $sesionesActivas }}</div>
    </div>
    <div class="card card-sm" style="border-left:4px solid #1a3a6b">
        <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600">Mi rol</div>
        <div style="font-size:1.1rem;color:#0f1f3d;font-weight:700;margin-top:8px;text-transform:capitalize">{{ session('user_role') }}</div>
    </div>
</div>

<div class="grid-2">
    <!-- Pruebas disponibles -->
    <div class="card">
        <div class="d-flex justify-between align-center mb-2">
            <h2 class="serif" style="font-size:1.3rem;color:#0f1f3d">Pruebas Disponibles</h2>
            <a href="{{ route('pruebas.index') }}" class="btn btn-outline btn-sm">Ver todas</a>
        </div>

        @forelse($tests as $test)
        <div style="padding:1rem;border:1px solid #eef1f5;border-radius:12px;margin-bottom:0.75rem;display:flex;align-items:center;justify-content:space-between;transition:box-shadow 0.2s" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)'" onmouseout="this.style.boxShadow='none'">
            <div style="display:flex;align-items:center;gap:12px">
                <div style="width:44px;height:44px;background:#1a3a6b;border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:'DM Serif Display',serif;color:#e8a020;font-size:1rem;flex-shrink:0">
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
            <a href="{{ route('pruebas.show', $test['id']) }}" class="btn btn-primary btn-sm">Iniciar</a>
        </div>
        @empty
        <div class="text-center text-muted" style="padding:2rem">
            No hay pruebas disponibles aún.
        </div>
        @endforelse
    </div>

    <!-- Sesiones recientes -->
    <div class="card">
        <div class="d-flex justify-between align-center mb-2">
            <h2 class="serif" style="font-size:1.3rem;color:#0f1f3d">Sesiones Recientes</h2>
            <a href="{{ route('sesiones.index') }}" class="btn btn-outline btn-sm">Ver todas</a>
        </div>

        @forelse($sesionesRecientes as $sesion)
        @php
            $estadoClase = match($sesion['estado']) {
                'completada'  => 'badge-green',
                'en_progreso' => 'badge-orange',
                'abandonada'  => 'badge-red',
                default       => 'badge-gray',
            };
            $estadoTexto = match($sesion['estado']) {
                'completada'  => 'Completada',
                'en_progreso' => 'En progreso',
                'abandonada'  => 'Abandonada',
                default       => $sesion['estado'],
            };
        @endphp
        <div style="padding:0.85rem 1rem;border:1px solid #eef1f5;border-radius:12px;margin-bottom:0.75rem;display:flex;align-items:center;justify-content:space-between">
            <div>
                <div style="font-weight:600;color:#0f1f3d;font-size:0.875rem">{{ $sesion['test']['nombre'] ?? 'Prueba' }}</div>
                <div style="font-size:0.78rem;color:#6b7a8d">
                    {{ \Carbon\Carbon::parse($sesion['created_at'])->format('d/m/Y H:i') }}
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px">
                <span class="badge {{ $estadoClase }}">{{ $estadoTexto }}</span>
                @if($sesion['estado'] === 'completada')
                <a href="{{ route('sesiones.resultados', $sesion['id']) }}" class="btn btn-sm btn-primary">Resultados</a>
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
</div>

@endsection
