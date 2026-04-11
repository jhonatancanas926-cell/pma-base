@extends('layouts.app')
@section('title', 'Resultados de la evaluación')

@push('styles')
    <style>
        .resultado-hero {
            background: linear-gradient(135deg, #0f1f3d 0%, #1a3a6b 100%);
            border-radius: 20px;
            padding: 2.5rem;
            color: white;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .resultado-hero::before {
            content: 'PMA';
            position: absolute;
            right: -20px;
            bottom: -30px;
            font-family: 'DM Serif Display', serif;
            font-size: 9rem;
            color: rgba(255, 255, 255, 0.04);
            line-height: 1;
        }

        .score-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 6px solid var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            background: rgba(255, 255, 255, 0.05);
        }

        .score-num {
            font-family: 'DM Serif Display', serif;
            font-size: 1.8rem;
            color: white;
            line-height: 1;
        }

        .score-label {
            font-size: 0.65rem;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .factor-result {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray100);
        }

        .factor-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1rem;
        }

        .factor-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: #1a3a6b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'DM Serif Display', serif;
            font-size: 1.3rem;
            color: #e8a020;
        }

        .progress-track {
            height: 10px;
            background: #eef1f5;
            border-radius: 5px;
            overflow: hidden;
            margin: 0.5rem 0;
        }

        .progress-fill-green {
            height: 100%;
            border-radius: 5px;
            background: linear-gradient(90deg, #107c10, #22c55e);
        }

        .progress-fill-orange {
            height: 100%;
            border-radius: 5px;
            background: linear-gradient(90deg, #d97706, #f59e0b);
        }

        .progress-fill-blue {
            height: 100%;
            border-radius: 5px;
            background: linear-gradient(90deg, #1a3a6b, #2e75b6);
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            padding: 0.4rem 0;
            border-bottom: 1px solid #eef1f5;
        }

        .stat-row:last-child {
            border: none;
        }

        .nivel-badge {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .nivel-Muy.Alto,
        .nivel-Alto {
            background: #dcfce7;
            color: #166534;
        }

        .nivel-Medio {
            background: #dbeafe;
            color: #1e40af;
        }

        .nivel-Bajo,
        .nivel-Muy.Bajo {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
@endpush

@section('content')
    <!-- Hero -->
    <div class="resultado-hero">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1.5rem">
            <div>
                <div
                    style="font-size:0.78rem;opacity:0.6;text-transform:uppercase;letter-spacing:.05em;margin-bottom:0.5rem">
                    Evaluación completada</div>
                <h1 style="font-family:'DM Serif Display',serif;font-size:1.8rem;margin-bottom:0.25rem">
                    {{ $resumen['prueba'] }}
                </h1>
                <p style="opacity:0.65;font-size:0.9rem">{{ $resumen['usuario'] }} · {{ $resumen['fecha'] }} ·
                    {{ $resumen['tiempo_empleado'] }}
                </p>
            </div>
            <div style="display:flex;align-items:center;gap:1.5rem">
                <div class="score-circle">
                    <span class="score-num">{{ $resumen['porcentaje_global'] }}%</span>
                    <span class="score-label">Global</span>
                </div>
                <div>
                    <div style="font-size:0.78rem;opacity:0.6">Puntaje total</div>
                    <div style="font-family:'DM Serif Display',serif;font-size:2.5rem;color:#e8a020">
                        {{ $resumen['puntaje_total'] }}
                    </div>
                </div>
            </div>
        </div>
        @if($resumen['interpretacion'])
            <div
                style="margin-top:1.5rem;padding:1rem;background:rgba(255,255,255,0.07);border-radius:10px;border-left:4px solid #e8a020;font-size:0.9rem;opacity:0.9">
                💡 {{ $resumen['interpretacion'] }}
            </div>
        @endif
    </div>

    <!-- Resultados por factor -->
    <h2 class="serif mb-2" style="font-size:1.5rem;color:#0f1f3d">Resultados por Factor</h2>
    <div class="grid-2 mb-3">
        @foreach($resumen['resultados'] as $r)
            @php
                $colorClass = match (true) {
                    in_array($r['nivel'], ['Alto', 'Muy Alto']) => 'progress-fill-green',
                    $r['nivel'] === 'Medio' => 'progress-fill-blue',
                    default => 'progress-fill-orange',
                };
            @endphp
            <div class="factor-result">
                <div class="factor-header">
                    <div class="factor-icon">{{ substr($r['codigo'], -1) }}</div>
                    <div style="flex:1">
                        <div style="font-weight:700;color:#0f1f3d">{{ $r['factor'] }}</div>
                        <div style="font-size:0.8rem;color:#6b7a8d">
                            {{ $r['correctas'] }}/{{ $r['correctas'] + $r['incorrectas'] + $r['omitidas'] }} correctas
                        </div>
                    </div>
                    <span class="nivel-badge nivel-{{ str_replace(' ', '.', $r['nivel']) }}">{{ $r['nivel'] }}</span>
                </div>

                <div class="progress-track">
                    <div class="{{ $colorClass }}" style="width:{{ $r['porcentaje'] }}%"></div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:0.78rem;color:#6b7a8d;margin-bottom:0.75rem">
                    <span>0%</span>
                    <span style="font-weight:700;color:#0f1f3d">{{ $r['porcentaje'] }}%</span>
                    <span>100%</span>
                </div>

                <div>
                    <div class="stat-row"><span class="text-muted">Puntaje bruto</span><span
                            class="fw-bold mono">{{ $r['puntaje_bruto'] }}</span></div>
                    <div class="stat-row"><span class="text-muted">Percentil</span><span
                            class="fw-bold">{{ $r['percentil'] ? 'P' . $r['percentil'] : '—' }}</span></div>
                    <div class="stat-row"><span class="text-muted">Correctas</span><span
                            style="color:#107c10;font-weight:600">{{ $r['correctas'] }}</span></div>
                    <div class="stat-row"><span class="text-muted">Incorrectas</span><span
                            style="color:#c50f1f;font-weight:600">{{ $r['incorrectas'] }}</span></div>
                    <div class="stat-row"><span class="text-muted">Omitidas</span><span>{{ $r['omitidas'] }}</span></div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Acciones -->
    <div class="card" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
        <div>
            <div class="fw-bold text-navy">¿Deseas descargar el reporte completo?</div>
            <div class="text-muted">Documento Word con análisis detallado y recomendaciones</div>
        </div>
        <div style="display:flex;gap:0.75rem;flex-wrap:wrap">
            <a href="{{ route('sesiones.reporte', $sesionId) }}" class="btn btn-primary" target="_blank">
                📄 Descargar PDF
            </a>
            <a href="{{ route('sesiones.reporte.word', $sesionId) }}" class="btn btn-primary">
                📄 Word
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-outline">← Volver al inicio</a>
        </div>
    </div>
@endsection