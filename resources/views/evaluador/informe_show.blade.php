@extends('layouts.app')
@section('title', 'Informe — ' . ($aspirante['name'] ?? 'Aspirante'))
@section('content')

<style>
.tabs-nav{display:flex;gap:0;border-bottom:2px solid #eef1f5;margin-bottom:1.5rem;overflow-x:auto}
.tab-btn{padding:.75rem 1.5rem;background:none;border:none;border-bottom:3px solid transparent;cursor:pointer;font-size:.875rem;font-weight:600;color:#6b7a8d;white-space:nowrap;margin-bottom:-2px;transition:all .2s;font-family:inherit}
.tab-btn.active{color:#1a3a6b;border-bottom-color:#1a3a6b}
.tab-btn:hover{color:#1a3a6b;background:#f8fafd}
.tab-panel{display:none}
.tab-panel.active{display:block}
.form-group{margin-bottom:1.25rem}
.form-label{display:block;font-size:.82rem;font-weight:700;color:#1a3a6b;margin-bottom:.4rem}
.form-label .auto-badge{font-size:.68rem;background:#dbeafe;color:#1e40af;padding:.15rem .5rem;border-radius:6px;font-weight:600;margin-left:.4rem}
.form-label .manual-badge{font-size:.68rem;background:#fef3c7;color:#92400e;padding:.15rem .5rem;border-radius:6px;font-weight:600;margin-left:.4rem}
.form-input,.form-textarea,.form-select{width:100%;padding:.6rem .9rem;border:2px solid #eef1f5;border-radius:10px;font-size:.875rem;font-family:inherit;color:#0f1f3d;transition:border-color .2s;background:#fff}
.form-input:focus,.form-textarea:focus,.form-select:focus{outline:none;border-color:#2e75b6;box-shadow:0 0 0 3px rgba(46,117,182,.1)}
.form-textarea{resize:vertical;min-height:90px}
.form-textarea.auto{border-color:#bfdbfe;background:#f0f7ff}
.save-indicator{font-size:.72rem;color:#107c10;margin-left:.5rem;opacity:0;transition:opacity .3s;font-weight:400}
.save-indicator.visible{opacity:1}
.condicion-card{background:#fff;border:1px solid #eef1f5;border-radius:12px;padding:1.25rem;margin-bottom:1rem}
.condicion-card.auto-fill{border-left:4px solid #2e75b6}
.condicion-card.manual{border-left:4px solid #e8a020}
.condicion-titulo{font-size:.9rem;font-weight:700;color:#0f1f3d;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
@media(max-width:640px){.grid-2{grid-template-columns:1fr}}
.seccion-titulo{font-size:1rem;font-weight:700;color:#1a3a6b;margin:1.5rem 0 1rem;padding-bottom:.5rem;border-bottom:2px solid #eef1f5}
.btn-preview{padding:.75rem 1.75rem;background:#1a3a6b;color:#fff;border:none;border-radius:12px;font-size:.9rem;font-weight:700;cursor:pointer;font-family:inherit;transition:all .2s}
.btn-preview:hover{background:#2e75b6;transform:translateY(-1px)}
.alerta-info{background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:.75rem 1rem;font-size:.82rem;color:#1e40af;margin-bottom:1rem}
</style>

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem">
    <div>
        <a href="{{ route('evaluador.aspirantes') }}" style="color:#6b7a8d;font-size:.85rem;text-decoration:none">← Aspirantes</a>
        <h1 style="font-family:'DM Serif Display',serif;color:#0f1f3d;font-size:1.6rem;margin:.25rem 0">
            {{ $aspirante['name'] ?? 'Aspirante' }}
        </h1>
        <p style="color:#6b7a8d;font-size:.82rem">
            Doc: {{ $aspirante['documento'] ?? '—' }} &nbsp;|&nbsp;
            Sesión PMA-R #{{ $sesionId }}
            @if($sesion->estado === 'completada')
                &nbsp;|&nbsp; <span style="color:#107c10;font-weight:600">✓ PMA-R completado</span>
            @endif
        </p>
    </div>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap">
        <a href="{{ route('sesiones.resultados', $sesionId) }}"
           style="padding:.6rem 1.25rem;background:#eef1f5;color:#374151;border-radius:10px;font-weight:600;font-size:.875rem;text-decoration:none">
            📊 Ver resultados PMA-R
        </a>
        <a href="{{ route('evaluador.informe.preview', $sesionId) }}"
           style="padding:.6rem 1.25rem;background:#1a3a6b;color:#fff;border-radius:10px;font-weight:700;font-size:.875rem;text-decoration:none">
            👁 Preview informe
        </a>
    </div>
</div>

{{-- Pestañas --}}
<div class="tabs-nav">
    <button class="tab-btn active" onclick="cambiarTab('entrevista', this)">📋 Entrevista y antecedentes</button>
    <button class="tab-btn" onclick="cambiarTab('evaluador', this)">✏️ Campos del evaluador</button>
    <button class="tab-btn" onclick="cambiarTab('seguimiento', this)">📈 Seguimiento</button>
</div>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- PESTAÑA 1: Entrevista (reutiliza la vista existente en iframe-like) --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div id="tab-entrevista" class="tab-panel active">
    <div class="alerta-info">
        📝 Vista de edición de la entrevista y antecedentes del aspirante. Los cambios se guardan automáticamente.
    </div>

    @foreach($secciones as $seccion)
    <div style="background:#fff;border-radius:16px;border:1px solid #eef1f5;margin-bottom:1.25rem;overflow:hidden">
        <div style="background:#1a3a6b;color:#fff;padding:.85rem 1.25rem;display:flex;align-items:center;gap:10px;cursor:pointer"
             onclick="toggleSec({{ $seccion['id'] }})">
            <span style="font-weight:600;font-size:.95rem;flex:1">{{ $seccion['nombre'] }}</span>
            <span id="arr-{{ $seccion['id'] }}">▼</span>
        </div>
        <div id="sec-{{ $seccion['id'] }}" style="padding:1.25rem;display:none">
            <div style="display:grid;grid-template-columns:1fr 1fr;margin-bottom:.75rem;gap:.75rem">
                <div style="font-size:.72rem;font-weight:700;color:#6b7a8d;text-transform:uppercase;letter-spacing:.05em">Respuesta del aspirante</div>
                <div style="font-size:.72rem;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.05em">Edición del evaluador</div>
            </div>
            @foreach($seccion['preguntas'] as $p)
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;padding-bottom:.75rem;border-bottom:1px solid #f3f4f6;margin-bottom:.75rem">
                <div>
                    <div style="font-size:.78rem;font-weight:600;color:#374151;margin-bottom:.3rem">{{ $p['orden'] }}. {{ $p['enunciado'] }}</div>
                    <div style="font-size:.85rem;color:#0f1f3d;background:#f8fafd;border-radius:8px;padding:.5rem .75rem;border:1px solid #eef1f5;min-height:34px">
                        {{ $p['respuesta'] ?? '(sin respuesta)' }}
                    </div>
                </div>
                <div>
                    <div style="font-size:.72rem;font-weight:700;color:#92400e;margin-bottom:.3rem">
                        Corrección <span class="save-indicator" id="ev-saved-{{ $p['id'] }}">✓ Guardado</span>
                    </div>
                    @if(in_array($p['tipo_respuesta'], ['si_no','escala_3','seleccion']))
                        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                        @foreach($p['opciones'] as $op)
                            <label style="display:flex;align-items:center;gap:.3rem;font-size:.8rem;cursor:pointer;padding:.3rem .6rem;border:1.5px solid #eef1f5;border-radius:7px">
                                <input type="radio" name="ev_{{ $p['id'] }}" value="{{ $op }}"
                                    {{ $p['respuesta'] === $op ? 'checked' : '' }}
                                    onchange="guardarEval({{ $p['id'] }}, this.value)">
                                <span>{{ $op }}</span>
                            </label>
                        @endforeach
                        </div>
                    @elseif($p['tipo_respuesta'] === 'textarea')
                        <textarea style="width:100%;padding:.5rem .8rem;border:2px solid #fcd34d;border-radius:8px;font-size:.85rem;font-family:inherit;background:#fffbeb;resize:vertical;min-height:70px"
                            oninput="autoGuardarEval({{ $p['id'] }}, this.value)">{{ $p['respuesta'] ?? '' }}</textarea>
                    @else
                        <input type="{{ $p['tipo_respuesta'] === 'numero' ? 'number' : ($p['tipo_respuesta'] === 'fecha' ? 'date' : 'text') }}"
                            style="width:100%;padding:.5rem .8rem;border:2px solid #fcd34d;border-radius:8px;font-size:.85rem;font-family:inherit;background:#fffbeb"
                            value="{{ $p['respuesta'] ?? '' }}"
                            oninput="autoGuardarEval({{ $p['id'] }}, this.value)"
                            onchange="autoGuardarEval({{ $p['id'] }}, this.value)">
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- PESTAÑA 2: Campos del evaluador                               --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div id="tab-evaluador" class="tab-panel">

    <div class="alerta-info">
        💾 Todos los campos se guardan automáticamente. Los campos con <span style="background:#dbeafe;color:#1e40af;padding:.1rem .4rem;border-radius:4px;font-size:.75rem;font-weight:600">AUTO</span> fueron pre-llenados desde los resultados PMA-R — puedes editarlos.
    </div>

    {{-- Concepto toxicológico --}}
    <div class="seccion-titulo">🧪 Concepto toxicológico</div>
    <div class="form-group">
        <label class="form-label">Concepto del evaluador</label>
        <textarea class="form-textarea" id="concepto_toxicologico"
            oninput="autoGuardarInforme('concepto_toxicologico', this.value)"
        >{{ $informe->concepto_toxicologico ?? '' }}</textarea>
        <span class="save-indicator" id="inf-saved-concepto_toxicologico">✓ Guardado</span>
    </div>

    {{-- Condiciones --}}
    <div class="seccion-titulo">📊 Condiciones</div>

    @php
    $condiciones = [
        ['key' => 'seguridad_personal',           'titulo' => 'Seguridad personal',                    'auto' => true,  'factor' => 'V'],
        ['key' => 'relaciones_interpersonales',    'titulo' => 'Relaciones interpersonales',             'auto' => true,  'factor' => 'F'],
        ['key' => 'procesos_cognitivos',           'titulo' => 'Procesos cognitivos',                   'auto' => true,  'factor' => 'R+N'],
        ['key' => 'tolerancia_estres',             'titulo' => 'Tolerancia a situaciones de estrés y fatiga', 'auto' => true, 'factor' => 'R'],
        ['key' => 'evaluacion_riesgos',            'titulo' => 'Capacidad para evaluar y asumir riesgos','auto' => true,  'factor' => 'N'],
        ['key' => 'proyeccion_tcp',                'titulo' => 'Proyección ante las exigencias como TCP','auto' => true,  'factor' => 'Global'],
        ['key' => 'manejo_conflictos',             'titulo' => 'Manejo de conflictos',                  'auto' => false, 'factor' => 'NEO'],
        ['key' => 'seguimiento_normas',            'titulo' => 'Seguimiento de normas',                 'auto' => false, 'factor' => 'NEO'],
        ['key' => 'manejo_presiones',              'titulo' => 'Manejo de presiones externas y/o ambientales', 'auto' => false, 'factor' => 'NEO'],
        ['key' => 'reaccion_emergencias',          'titulo' => 'Reacción ante situaciones de emergencia','auto' => false, 'factor' => 'NEO'],
        ['key' => 'afiliacion_social',             'titulo' => 'Afiliación social',                     'auto' => false, 'factor' => 'NEO'],
    ];
    @endphp

    @foreach($condiciones as $c)
    <div class="condicion-card {{ $c['auto'] ? 'auto-fill' : 'manual' }}">
        <div class="condicion-titulo">
            {{ $c['titulo'] }}
            @if($c['auto'])
                <span style="font-size:.7rem;background:#dbeafe;color:#1e40af;padding:.15rem .5rem;border-radius:6px;font-weight:600">AUTO · PMA-R {{ $c['factor'] }}</span>
            @else
                <span style="font-size:.7rem;background:#fef3c7;color:#92400e;padding:.15rem .5rem;border-radius:6px;font-weight:600">MANUAL · Pendiente NEO PI-R</span>
            @endif
            <span class="save-indicator" id="inf-saved-cond_{{ $c['key'] }}_desc">✓ Guardado</span>
        </div>
        <div class="grid-2">
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">
                    Descripción
                    @if($c['auto'])<span class="auto-badge">AUTO</span>@endif
                </label>
                <textarea class="form-textarea {{ $c['auto'] ? 'auto' : '' }}"
                    id="cond_{{ $c['key'] }}_desc"
                    oninput="autoGuardarInforme('cond_{{ $c['key'] }}_desc', this.value)"
                >{{ $informe->{'cond_' . $c['key'] . '_desc'} ?? '' }}</textarea>
            </div>
            <div class="form-group" style="margin-bottom:0">
                <label class="form-label">
                    Desarrollo
                    @if($c['auto'])<span class="auto-badge">AUTO</span>@endif
                </label>
                <textarea class="form-textarea {{ $c['auto'] ? 'auto' : '' }}"
                    id="cond_{{ $c['key'] }}_dev"
                    oninput="autoGuardarInforme('cond_{{ $c['key'] }}_dev', this.value)"
                >{{ $informe->{'cond_' . $c['key'] . '_dev'} ?? '' }}</textarea>
            </div>
        </div>
    </div>
    @endforeach

    {{-- Conclusiones --}}
    <div class="seccion-titulo">📝 Conclusiones, concepto y recomendaciones</div>

    <div class="form-group">
        <label class="form-label">Conclusiones</label>
        <textarea class="form-textarea" id="conclusiones" rows="5"
            oninput="autoGuardarInforme('conclusiones', this.value)"
        >{{ $informe->conclusiones ?? '' }}</textarea>
        <span class="save-indicator" id="inf-saved-conclusiones">✓ Guardado</span>
    </div>

    <div class="form-group">
        <label class="form-label">Concepto global</label>
        <select class="form-select" id="concepto_global"
            onchange="autoGuardarInforme('concepto_global', this.value)">
            <option value="">— Selecciona —</option>
            @foreach(['Admitido', 'Admitido con seguimiento', 'No admitido'] as $op)
            <option value="{{ $op }}" {{ ($informe->concepto_global ?? '') === $op ? 'selected' : '' }}>
                {{ $op }}
            </option>
            @endforeach
        </select>
        <span class="save-indicator" id="inf-saved-concepto_global">✓ Guardado</span>
    </div>

    <div class="form-group">
        <label class="form-label">Recomendaciones y/o observaciones</label>
        <textarea class="form-textarea" id="recomendaciones" rows="4"
            oninput="autoGuardarInforme('recomendaciones', this.value)"
        >{{ $informe->recomendaciones ?? '' }}</textarea>
        <span class="save-indicator" id="inf-saved-recomendaciones">✓ Guardado</span>
    </div>

    <div style="text-align:center;margin-top:2rem">
        <a href="{{ route('evaluador.informe.preview', $sesionId) }}" class="btn-preview">
            👁 Ver preview del informe completo
        </a>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- PESTAÑA 3: Seguimiento                                         --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div id="tab-seguimiento" class="tab-panel">
    <div class="alerta-info">
        📈 Esta sección se completa al final del curso. No bloquea la generación del informe principal.
    </div>

    @php
    $camposSeguimiento = [
        ['key' => 'concepto_inicial',          'label' => 'Concepto inicial de evaluación'],
        ['key' => 'observaciones_profesional', 'label' => 'Observaciones relevantes del profesional'],
        ['key' => 'evolucion_seguimiento',     'label' => 'Evolución y seguimiento durante el programa'],
        ['key' => 'reporte_tutor',             'label' => 'Reporte de tutor de curso'],
        ['key' => 'acompanamiento_academico',  'label' => 'Acompañamiento académico'],
        ['key' => 'acompanamiento_psicologico','label' => 'Acompañamiento psicológico'],
        ['key' => 'resultados_seguimiento',    'label' => 'Resultados del proceso de seguimiento'],
        ['key' => 'recomendaciones_finales',   'label' => 'Recomendaciones y observaciones finales'],
    ];
    @endphp

    @foreach($camposSeguimiento as $campo)
    <div class="form-group">
        <label class="form-label">{{ $campo['label'] }}</label>
        <textarea class="form-textarea" id="seg_{{ $campo['key'] }}" rows="3"
            oninput="autoGuardarSeguimiento('{{ $campo['key'] }}', this.value)"
        >{{ $seguimiento->{'cond_' . $campo['key']} ?? $seguimiento->{$campo['key']} ?? '' }}</textarea>
        <span class="save-indicator" id="seg-saved-{{ $campo['key'] }}">✓ Guardado</span>
    </div>
    @endforeach
</div>

<script>
const CSRF     = '{{ csrf_token() }}';
const SESION   = {{ $sesionId }};
const USER_ID  = {{ $aspirante['id'] }};
let timers = {};

// ── Pestaña 1: entrevista ─────────────────────────────────────────────────
function autoGuardarEval(id, val) {
    clearTimeout(timers['ev_' + id]);
    timers['ev_' + id] = setTimeout(() => guardarEval(id, val), 700);
}
async function guardarEval(id, val) {
    const ind = document.getElementById('ev-saved-' + id);
    try {
        const r = await fetch('{{ route("evaluador.entrevista.responder", ":uid") }}'.replace(':uid', USER_ID), {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({ pregunta_id: id, respuesta: val })
        });
        if (r.ok && ind) { ind.classList.add('visible'); setTimeout(() => ind.classList.remove('visible'), 2500); }
    } catch(e) { console.error(e); }
}

// ── Pestaña 2: informe evaluador ──────────────────────────────────────────
function autoGuardarInforme(campo, val) {
    clearTimeout(timers['inf_' + campo]);
    timers['inf_' + campo] = setTimeout(() => guardarInforme(campo, val), 700);
}
async function guardarInforme(campo, val) {
    const ind = document.getElementById('inf-saved-' + campo);
    try {
        const r = await fetch('{{ route("evaluador.informe.guardar", $sesionId) }}', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({ campo, valor: val })
        });
        if (r.ok && ind) { ind.classList.add('visible'); setTimeout(() => ind.classList.remove('visible'), 2500); }
    } catch(e) { console.error(e); }
}

// ── Pestaña 3: seguimiento ────────────────────────────────────────────────
function autoGuardarSeguimiento(campo, val) {
    clearTimeout(timers['seg_' + campo]);
    timers['seg_' + campo] = setTimeout(() => guardarSeguimiento(campo, val), 700);
}
async function guardarSeguimiento(campo, val) {
    const ind = document.getElementById('seg-saved-' + campo);
    try {
        const r = await fetch('{{ route("evaluador.seguimiento.guardar", $sesionId) }}', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({ campo, valor: val })
        });
        if (r.ok && ind) { ind.classList.add('visible'); setTimeout(() => ind.classList.remove('visible'), 2500); }
    } catch(e) { console.error(e); }
}

// ── Acordeón entrevista ───────────────────────────────────────────────────
function toggleSec(id) {
    const el  = document.getElementById('sec-' + id);
    const arr = document.getElementById('arr-' + id);
    const open = el.style.display === 'none' || !el.style.display;
    el.style.display  = open ? 'block' : 'none';
    arr.style.transform = open ? 'rotate(180deg)' : '';
}

// ── Pestañas ──────────────────────────────────────────────────────────────
function cambiarTab(tab, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    btn.classList.add('active');
}
</script>

@endsection
