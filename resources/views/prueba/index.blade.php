{{-- prueba/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Pruebas Disponibles')
@section('content')

<div class="page-header">
    <h1>Pruebas Disponibles</h1>
    <p class="text-muted">Selecciona una prueba para iniciar tu evaluación.</p>
</div>

<div class="card">
    @forelse($tests as $test)
    <div style="padding:1rem;border:1px solid #eef1f5;border-radius:12px;margin-bottom:0.75rem;display:flex;align-items:center;justify-content:space-between;transition:box-shadow 0.2s" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)'" onmouseout="this.style.boxShadow='none'">
        <div style="display:flex;align-items:center;gap:12px">
            <div style="width:44px;height:44px;background:#1a3a6b;border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:'DM Serif Display',serif;color:#e8a020;font-size:1rem;flex-shrink:0">
                {{ substr($test->codigo, 0, 1) }}
            </div>
            <div>
                <div style="font-weight:600;color:#0f1f3d;font-size:0.9rem">{{ $test->nombre }}</div>
                <div style="font-size:0.78rem;color:#6b7a8d">
                    {{ $test->preguntas_count ?? '—' }} preguntas ·
                    {{ $test->tiempo_limite }} minutos
                </div>
            </div>
        </div>
        <a href="{{ route('pruebas.show', $test->id) }}" class="btn btn-primary btn-sm">Ver detalles</a>
    </div>
    @empty
    <div class="text-center text-muted" style="padding:2rem">
        No hay pruebas disponibles aún.
    </div>
    @endforelse
</div>

@endsection
