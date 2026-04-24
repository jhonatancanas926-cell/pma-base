@extends('layouts.app')
@section('title', $esEvaluador ? 'Sesiones — Todos los aspirantes' : 'Mis Sesiones')
@section('content')

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem">
    <div>
        <h1 style="font-family:'DM Serif Display',serif;color:#0f1f3d;font-size:1.8rem">
            {{ $esEvaluador ? 'Sesiones — Todos los aspirantes' : 'Mis Sesiones' }}
        </h1>
        <p style="color:#6b7a8d;margin-top:.25rem">
            {{ $esEvaluador ? 'Historial de evaluaciones PMA-R de todos los evaluados' : 'Historial completo de evaluaciones realizadas' }}
        </p>
    </div>
    @if($esEvaluador)
    <a href="{{ route('evaluador.aspirantes') }}" style="padding:.6rem 1.25rem;background:#1a3a6b;color:#fff;border-radius:10px;font-weight:600;font-size:.875rem;text-decoration:none">
        📋 Entrevistas
    </a>
    @endif
</div>

<div class="card" style="padding:0;overflow:hidden">
    @if($sesiones->isEmpty())
    <div style="text-align:center;padding:4rem 2rem">
        <div style="font-size:3rem;margin-bottom:1rem">📋</div>
        <div style="font-weight:700;color:#0f1f3d;font-size:1.1rem;margin-bottom:.5rem">Sin evaluaciones aún</div>
        <p style="color:#6b7a8d;margin-bottom:1.5rem">
            {{ $esEvaluador ? 'Ningún aspirante ha realizado evaluaciones todavía.' : 'Inicia tu primera evaluación PMA-R.' }}
        </p>
        @if(!$esEvaluador)
        <a href="{{ route('pruebas.index') }}" style="padding:.7rem 1.5rem;background:#1a3a6b;color:#fff;border-radius:10px;font-weight:600;text-decoration:none">
            Ver pruebas disponibles
        </a>
        @endif
    </div>
    @else
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:.875rem">
            <thead>
                <tr style="background:#1a3a6b">
                    <th style="padding:.75rem 1rem;text-align:left;color:#fff;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;font-weight:600">#</th>
                    @if($esEvaluador)
                    <th style="padding:.75rem 1rem;text-align:left;color:#fff;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;font-weight:600">Aspirante</th>
                    @endif
                    <th style="padding:.75rem 1rem;text-align:left;color:#fff;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;font-weight:600">Prueba</th>
                    <th style="padding:.75rem 1rem;text-align:left;color:#fff;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;font-weight:600">Estado</th>
                    <th style="padding:.75rem 1rem;text-align:left;color:#fff;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;font-weight:600">Fecha</th>
                    <th style="padding:.75rem 1rem;text-align:left;color:#fff;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;font-weight:600">Duración</th>
                    <th style="padding:.75rem 1rem;text-align:left;color:#fff;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;font-weight:600">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sesiones as $sesion)
                @php
                    $badge = match($sesion->estado) {
                        'completada'  => ['bg' => '#d1fae5', 'color' => '#065f46', 'txt' => 'Completada'],
                        'en_progreso' => ['bg' => '#fef3c7', 'color' => '#92400e', 'txt' => 'En progreso'],
                        'abandonada'  => ['bg' => '#fee2e2', 'color' => '#991b1b', 'txt' => 'Abandonada'],
                        default       => ['bg' => '#f3f4f6', 'color' => '#374151', 'txt' => ucfirst($sesion->estado)],
                    };
                @endphp
                <tr style="border-bottom:1px solid #eef1f5" onmouseover="this.style.background='#f8fafd'" onmouseout="this.style.background=''">
                    <td style="padding:.85rem 1rem;color:#6b7a8d;font-family:monospace;font-size:.8rem">#{{ $sesion->id }}</td>

                    @if($esEvaluador)
                    <td style="padding:.85rem 1rem">
                        <div style="font-weight:600;color:#0f1f3d;font-size:.875rem">{{ $sesion->user->name ?? '—' }}</div>
                        <div style="font-size:.75rem;color:#6b7a8d">{{ $sesion->user->documento ?? '' }}</div>
                    </td>
                    @endif

                    <td style="padding:.85rem 1rem;font-weight:600;color:#0f1f3d">{{ $sesion->test->nombre ?? '—' }}</td>

                    <td style="padding:.85rem 1rem">
                        <span style="display:inline-block;padding:.2rem .7rem;border-radius:99px;font-size:.72rem;font-weight:700;background:{{ $badge['bg'] }};color:{{ $badge['color'] }}">
                            {{ $badge['txt'] }}
                        </span>
                    </td>

                    <td style="padding:.85rem 1rem;color:#6b7a8d;font-size:.82rem">
                        {{ $sesion->iniciada_en?->format('d/m/Y H:i') ?? '—' }}
                    </td>

                    <td style="padding:.85rem 1rem;color:#6b7a8d;font-size:.82rem">
                        {{ $sesion->tiempo_total ? round($sesion->tiempo_total / 60, 1).' min' : '—' }}
                    </td>

                    <td style="padding:.85rem 1rem">
                        <div style="display:flex;gap:.4rem;flex-wrap:wrap;align-items:center">
                            @if($sesion->estado === 'completada')
                                <a href="{{ route('sesiones.resultados', $sesion->id) }}"
                                   style="padding:.3rem .8rem;background:#1a3a6b;color:#fff;border-radius:7px;font-size:.75rem;font-weight:600;text-decoration:none;white-space:nowrap">
                                    📊 Resultados
                                </a>
                                <a href="{{ route('sesiones.reporte.word', $sesion->id) }}"
                                   style="padding:.3rem .8rem;background:#2e75b6;color:#fff;border-radius:7px;font-size:.75rem;font-weight:600;text-decoration:none;white-space:nowrap">
                                    📄 Informe Word
                                </a>
                                <a href="{{ route('sesiones.reporte', $sesion->id) }}" target="_blank"
                                   style="padding:.3rem .8rem;background:#eef1f5;color:#374151;border-radius:7px;font-size:.75rem;font-weight:600;text-decoration:none;white-space:nowrap">
                                    📄 PDF
                                </a>
                            @elseif($sesion->estado === 'en_progreso' && !$esEvaluador)
                                <a href="{{ route('prueba.responder', $sesion->id) }}"
                                   style="padding:.3rem .8rem;background:#e8a020;color:#fff;border-radius:7px;font-size:.75rem;font-weight:600;text-decoration:none;white-space:nowrap">
                                    ▶ Continuar
                                </a>
                            @else
                                <span style="font-size:.78rem;color:#9ca3af">—</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="padding:1rem 1.5rem;border-top:1px solid #eef1f5">
        {{ $sesiones->links() }}
    </div>
    @endif
</div>

@endsection
