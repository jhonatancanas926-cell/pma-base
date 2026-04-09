@extends('layouts.app')
@section('title', $test['nombre'])
@section('content')

<div class="page-header">
    <div class="d-flex align-center gap-2 mb-1">
        <a href="{{ route('dashboard') }}" class="text-muted" style="text-decoration:none">← Dashboard</a>
    </div>
    <h1>{{ $test['nombre'] }}</h1>
    <p class="text-muted">{{ $test['descripcion'] }}</p>
</div>

<div class="grid-2">
    <div class="card">
        <h2 class="serif mb-2" style="font-size:1.3rem;color:#0f1f3d">Información de la prueba</h2>
        <div style="display:grid;gap:0.75rem">
            <div style="display:flex;justify-content:space-between;padding:0.7rem 0;border-bottom:1px solid #eef1f5">
                <span class="text-muted">Código</span>
                <span class="mono fw-bold text-navy">{{ $test['codigo'] }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:0.7rem 0;border-bottom:1px solid #eef1f5">
                <span class="text-muted">Total preguntas</span>
                <span class="fw-bold">{{ $test['preguntas_count'] ?? '—' }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:0.7rem 0;border-bottom:1px solid #eef1f5">
                <span class="text-muted">Tiempo límite</span>
                <span class="fw-bold">{{ $test['tiempo_limite'] }} minutos</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:0.7rem 0">
                <span class="text-muted">Versión</span>
                <span class="fw-bold">{{ $test['version'] }}</span>
            </div>
        </div>

        <div style="margin-top:1.5rem">
            @if($sesionActiva)
            <div class="alert alert-info mb-2">⚡ Tienes una sesión en progreso para esta prueba.</div>
            <a href="{{ route('prueba.responder', $sesionActiva['id']) }}" class="btn btn-accent btn-lg" style="width:100%;justify-content:center">
                Continuar evaluación →
            </a>
            @else
            <form action="{{ route('sesiones.store') }}" method="POST">
                @csrf
                <input type="hidden" name="test_id" value="{{ $test['id'] }}">
                <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center">
                    🚀 Iniciar evaluación
                </button>
            </form>
            @endif
        </div>
    </div>

    <div class="card">
        <h2 class="serif mb-2" style="font-size:1.3rem;color:#0f1f3d">Factores evaluados</h2>
        @foreach($test['categorias'] as $cat)
        <div style="padding:1rem;border:1px solid #eef1f5;border-radius:12px;margin-bottom:0.75rem;display:flex;align-items:center;gap:12px">
            <div style="width:44px;height:44px;background:#1a3a6b;border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:'DM Serif Display',serif;color:#e8a020;font-size:1.1rem;flex-shrink:0">
                {{ substr($cat['codigo'], -1) }}
            </div>
            <div style="flex:1">
                <div style="font-weight:600;color:#0f1f3d">{{ $cat['nombre'] }}</div>
                <div style="font-size:0.8rem;color:#6b7a8d">{{ $cat['preguntas_count'] ?? '—' }} preguntas · {{ $cat['tiempo_limite'] }} min</div>
            </div>
        </div>
        @endforeach
    </div>
</div>

@endsection
