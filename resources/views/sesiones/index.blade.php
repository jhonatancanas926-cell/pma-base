{{-- sesiones/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Mis Sesiones')
@section('content')
<div class="page-header">
    <h1>Mis Sesiones</h1>
    <p class="text-muted">Historial completo de evaluaciones realizadas</p>
</div>

<div class="card">
    @if($sesiones->isEmpty())
    <div class="text-center" style="padding:3rem">
        <div style="font-size:3rem;margin-bottom:1rem">📋</div>
        <div class="fw-bold text-navy mb-1">Sin evaluaciones aún</div>
        <p class="text-muted mb-2">Inicia tu primera evaluación PMA-R</p>
        <a href="{{ route('pruebas.index') }}" class="btn btn-primary">Ver pruebas disponibles</a>
    </div>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Prueba</th>
                    <th>Estado</th>
                    <th>Inicio</th>
                    <th>Duración</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sesiones as $sesion)
                @php
                    $badge = match($sesion->estado) {
                        'completada'  => 'badge-green',
                        'en_progreso' => 'badge-orange',
                        'abandonada'  => 'badge-red',
                        default       => 'badge-gray',
                    };
                    $texto = match($sesion->estado) {
                        'completada'  => 'Completada',
                        'en_progreso' => 'En progreso',
                        'abandonada'  => 'Abandonada',
                        default       => $sesion->estado,
                    };
                @endphp
                <tr>
                    <td class="mono text-muted">#{{ $sesion->id }}</td>
                    <td class="fw-bold">{{ $sesion->test->nombre ?? '—' }}</td>
                    <td><span class="badge {{ $badge }}">{{ $texto }}</span></td>
                    <td class="text-muted">{{ $sesion->iniciada_en?->format('d/m/Y H:i') }}</td>
                    <td class="text-muted">{{ $sesion->tiempo_total ? round($sesion->tiempo_total/60, 1).' min' : '—' }}</td>
                    <td>
                        @if($sesion->estado === 'completada')
                        <a href="{{ route('sesiones.resultados', $sesion->id) }}" class="btn btn-primary btn-sm">Ver resultados</a>
                        @elseif($sesion->estado === 'en_progreso')
                        <a href="{{ route('prueba.responder', $sesion->id) }}" class="btn btn-accent btn-sm">Continuar</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="margin-top:1rem">{{ $sesiones->links() }}</div>
    @endif
</div>
@endsection
