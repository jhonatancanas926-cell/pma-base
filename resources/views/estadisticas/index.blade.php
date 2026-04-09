{{-- estadisticas/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Estadísticas')
@section('content')
<div class="page-header">
    <h1>Panel de Estadísticas</h1>
    <p class="text-muted">Resumen global de evaluaciones PMA-R</p>
</div>

<div class="grid-3 mb-3">
    <div class="card card-sm" style="border-left:4px solid #2e75b6;text-align:center">
        <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600">Total sesiones</div>
        <div style="font-family:'DM Serif Display',serif;font-size:2.5rem;color:#0f1f3d">{{ $totalSesiones }}</div>
    </div>
    <div class="card card-sm" style="border-left:4px solid #107c10;text-align:center">
        <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600">Completadas</div>
        <div style="font-family:'DM Serif Display',serif;font-size:2.5rem;color:#0f1f3d">{{ $completadas }}</div>
    </div>
    <div class="card card-sm" style="border-left:4px solid #e8a020;text-align:center">
        <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:.05em;font-weight:600">Evaluados</div>
        <div style="font-family:'DM Serif Display',serif;font-size:2.5rem;color:#0f1f3d">{{ $totalUsuarios }}</div>
    </div>
</div>

<div class="card">
    <h2 class="serif mb-2" style="font-size:1.3rem;color:#0f1f3d">Promedios por Factor</h2>
    @foreach($promediosPorFactor as $pf)
    <div style="padding:1rem;border:1px solid #eef1f5;border-radius:12px;margin-bottom:0.75rem;display:flex;align-items:center;gap:1rem">
        <div style="width:44px;height:44px;background:#1a3a6b;border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:'DM Serif Display',serif;color:#e8a020;font-size:1.1rem;flex-shrink:0">
            {{ substr($pf->categoria->codigo ?? 'F', -1) }}
        </div>
        <div style="flex:1">
            <div style="font-weight:600;color:#0f1f3d;margin-bottom:4px">{{ $pf->categoria->nombre ?? 'Factor' }}</div>
            <div style="height:8px;background:#eef1f5;border-radius:4px;overflow:hidden">
                <div style="height:100%;background:#2e75b6;border-radius:4px;width:{{ min(100, ($pf->promedio_correctas / 50) * 100) }}%"></div>
            </div>
        </div>
        <div style="text-align:right;min-width:80px">
            <div style="font-family:'JetBrains Mono',monospace;font-weight:700;color:#0f1f3d">{{ round($pf->promedio_puntaje, 1) }}</div>
            <div style="font-size:0.75rem;color:#6b7a8d">{{ $pf->total }} evaluaciones</div>
        </div>
    </div>
    @endforeach
    @if($promediosPorFactor->isEmpty())
    <div class="text-center text-muted" style="padding:2rem">No hay datos suficientes aún.</div>
    @endif
</div>
@endsection
