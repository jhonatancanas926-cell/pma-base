@extends('layouts.app')
@section('title', 'Gestión de Aspirantes')
@section('content')

<style>
.tabla-aspirantes{width:100%;border-collapse:collapse;font-size:.875rem}
.tabla-aspirantes th{background:#1a3a6b;color:#fff;padding:.75rem 1rem;text-align:left;font-weight:600;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em}
.tabla-aspirantes td{padding:.85rem 1rem;border-bottom:1px solid #eef1f5;vertical-align:middle}
.tabla-aspirantes tr:hover td{background:#f8fafd}
.badge{display:inline-block;padding:.25rem .75rem;border-radius:99px;font-size:.72rem;font-weight:700}
.badge-green{background:#d1fae5;color:#065f46}
.badge-orange{background:#fef3c7;color:#92400e}
.badge-gray{background:#f3f4f6;color:#374151}
.btn-sm{padding:.3rem .85rem;border-radius:8px;font-size:.78rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:4px;border:none;cursor:pointer}
.btn-primary-sm{background:#1a3a6b;color:#fff}
.btn-primary-sm:hover{background:#2e75b6;color:#fff}
.btn-success-sm{background:#107c10;color:#fff}
.btn-success-sm:hover{background:#0a5c0a;color:#fff}
.btn-warning-sm{background:#e8a020;color:#fff}
.btn-warning-sm:hover{background:#c8851a;color:#fff}
</style>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem">
    <div>
        <h1 style="font-family:'DM Serif Display',serif;color:#0f1f3d;font-size:1.8rem">Aspirantes</h1>
        <p style="color:#6b7a8d;margin-top:.25rem">Gestión de entrevistas psicosociales</p>
    </div>
    <div style="display:flex;gap:.5rem;align-items:center">
        <span style="font-size:.82rem;color:#6b7a8d">Total: <strong>{{ $data['total'] ?? 0 }}</strong></span>
    </div>
</div>

<div class="card" style="padding:0;overflow:hidden">
    <table class="tabla-aspirantes">
        <thead>
            <tr>
                <th>Aspirante</th>
                <th>Documento</th>
                <th>Programa</th>
                <th>Estado entrevista</th>
                <th>Completada</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['data'] ?? [] as $item)
            @php
                $estado    = $item['estado'] ?? 'pendiente';
                $badgeClase = match($estado) {
                    'completada'  => 'badge-green',
                    'en_progreso' => 'badge-orange',
                    default       => 'badge-gray',
                };
                $badgeTxt = match($estado) {
                    'completada'  => 'Completada',
                    'en_progreso' => 'En progreso',
                    default       => 'Pendiente',
                };
            @endphp
            <tr>
                <td>
                    <div style="font-weight:600;color:#0f1f3d">{{ $item['user']['name'] ?? '—' }}</div>
                    <div style="font-size:.75rem;color:#6b7a8d">{{ $item['user']['email'] ?? '' }}</div>
                </td>
                <td style="color:#374151">{{ $item['user']['documento'] ?? '—' }}</td>
                <td style="color:#374151;font-size:.82rem">{{ $item['user']['programa'] ?? '—' }}</td>
                <td><span class="badge {{ $badgeClase }}">{{ $badgeTxt }}</span></td>
                <td style="font-size:.8rem;color:#6b7a8d">
                    {{ $item['completada_en'] ? \Carbon\Carbon::parse($item['completada_en'])->format('d/m/Y') : '—' }}
                </td>
                <td>
                    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                        <a href="{{ route('evaluador.entrevista.show', $item['user_id']) }}"
                           class="btn-sm btn-primary-sm">👁 Ver / Editar</a>

                        @if($estado !== 'completada')
                        <form method="POST" action="{{ route('evaluador.entrevista.estado', $item['user_id']) }}"
                              onsubmit="return confirm('¿Marcar como COMPLETADA?')">
                            @csrf
                            <input type="hidden" name="estado" value="completada">
                            <button type="submit" class="btn-sm btn-success-sm">✅ Completar</button>
                        </form>
                        @else
                        <form method="POST" action="{{ route('evaluador.entrevista.estado', $item['user_id']) }}"
                              onsubmit="return confirm('¿Revertir a EN PROGRESO?')">
                            @csrf
                            <input type="hidden" name="estado" value="en_progreso">
                            <button type="submit" class="btn-sm btn-warning-sm">↩ Revertir</button>
                        </form>
                        @endif

                        @if(!($item['pma_habilitado'] ?? false))
                        <form method="POST" action="{{ route('evaluador.entrevista.habilitar_pma', $item['user_id']) }}"
                              onsubmit="return confirm('¿Habilitar acceso a la prueba PMA-R para este aspirante?')">
                            @csrf
                            <button type="submit" class="btn-sm btn-primary-sm" style="background:#1a3a6b">🔓 Habilitar PMA-R</button>
                        </form>
                        @else
                        <form method="POST" action="{{ route('evaluador.entrevista.habilitar_pma', $item['user_id']) }}"
                              onsubmit="return confirm('¿Deshabilitar el acceso a la prueba PMA-R para este aspirante?')">
                            @csrf
                            <button type="submit" class="btn-sm btn-success-sm">✅ PMA-R Habilitada</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center;padding:3rem;color:#6b7a8d">
                    No hay aspirantes registrados aún.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Paginación --}}
@if(($data['last_page'] ?? 1) > 1)
<div style="display:flex;justify-content:center;gap:.5rem;margin-top:1.5rem">
    @for($p = 1; $p <= $data['last_page']; $p++)
    <a href="?page={{ $p }}"
       style="padding:.4rem .85rem;border-radius:8px;border:1px solid #eef1f5;font-size:.82rem;text-decoration:none;
              {{ $p == ($data['current_page'] ?? 1) ? 'background:#1a3a6b;color:#fff' : 'background:#fff;color:#0f1f3d' }}">
        {{ $p }}
    </a>
    @endfor
</div>
@endif

@endsection
