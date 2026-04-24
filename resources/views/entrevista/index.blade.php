@extends('layouts.app')
@section('title', 'Entrevista y Antecedentes')
@section('content')

<style>
.seccion-card{background:#fff;border-radius:16px;border:1px solid #eef1f5;margin-bottom:1.5rem;overflow:hidden}
.seccion-header{background:#1a3a6b;color:#fff;padding:1rem 1.5rem;display:flex;align-items:center;gap:12px;cursor:pointer;user-select:none}
.seccion-header h3{margin:0;font-size:1rem;font-weight:600;flex:1}
.badge-estado{font-size:.72rem;padding:.25rem .75rem;border-radius:99px;font-weight:600}
.badge-completa{background:#d1fae5;color:#065f46}
.badge-pendiente{background:#fef3c7;color:#92400e}
.seccion-body{padding:1.5rem;display:none}
.seccion-body.abierta{display:block}
.pregunta-item{margin-bottom:1.25rem}
.pregunta-item label{display:block;font-size:.82rem;font-weight:600;color:#1a3a6b;margin-bottom:.4rem}
.obligatoria{color:#c50f1f;margin-left:2px}
.form-input{width:100%;padding:.6rem .9rem;border:2px solid #eef1f5;border-radius:10px;font-size:.9rem;font-family:inherit;color:#0f1f3d;transition:border-color .2s}
.form-input:focus{outline:none;border-color:#2e75b6;box-shadow:0 0 0 3px rgba(46,117,182,.1)}
textarea.form-input{resize:vertical;min-height:80px}
.opciones-radio{display:flex;gap:1rem;flex-wrap:wrap;margin-top:.25rem}
.opciones-radio label{display:flex;align-items:center;gap:.4rem;font-size:.85rem;cursor:pointer;padding:.35rem .75rem;border:2px solid #eef1f5;border-radius:8px;transition:all .15s}
.opciones-radio label:hover{border-color:#2e75b6;background:#f0f7ff}
.opciones-radio label:has(input:checked){border-color:#2e75b6;background:#eff6ff;font-weight:600}
.save-indicator{font-size:.75rem;color:#107c10;margin-left:.5rem;opacity:0;transition:opacity .3s;font-weight:400}
.save-indicator.visible{opacity:1}
.save-indicator.error{color:#c50f1f}
.progreso-bar-wrap{background:#eef1f5;border-radius:99px;height:10px;overflow:hidden;margin:1rem 0}
.progreso-bar-fill{height:100%;background:linear-gradient(90deg,#2e75b6,#1a3a6b);border-radius:99px;transition:width .4s ease}
.btn-completar{width:100%;padding:1rem;background:#107c10;color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:700;cursor:pointer;transition:all .2s;font-family:inherit}
.btn-completar:hover{background:#0a5c0a;transform:translateY(-1px)}
.alerta-verde{background:#d1fae5;border:1px solid #6ee7b7;border-radius:12px;padding:1rem 1.5rem;color:#065f46;font-weight:600;margin-bottom:1.5rem}
.alerta-amarilla{background:#fef3c7;border:1px solid #fcd34d;border-radius:12px;padding:.75rem 1.25rem;color:#92400e;font-size:.85rem;margin-bottom:1rem}
</style>

<div class="page-header" style="margin-bottom:1.5rem">
    <h1 style="font-family:'DM Serif Display',serif;color:#0f1f3d;font-size:1.8rem">Entrevista y Antecedentes</h1>
    <p style="color:#6b7a8d;margin-top:.25rem">Completa toda la información antes de acceder a la prueba PMA-R.</p>
</div>

@if(session('flash_success'))
<div class="alerta-verde">✅ {{ session('flash_success') }}</div>
@endif
@if(session('warning'))
<div class="alerta-amarilla">⚠ {{ session('warning') }}</div>
@endif

@if($entrevista && $entrevista['estado'] === 'completada')
<div class="alerta-verde">
    ✅ Entrevista completada el {{ \Carbon\Carbon::parse($entrevista['completada_en'])->format('d/m/Y H:i') }}.
    <a href="{{ route('dashboard') }}" style="color:#065f46;font-weight:700">Ir al panel principal →</a>
</div>
@endif

{{-- Barra de progreso --}}
<div style="background:#fff;border-radius:16px;border:1px solid #eef1f5;padding:1.25rem 1.5rem;margin-bottom:1.5rem">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
        <span style="font-size:.85rem;font-weight:600;color:#1a3a6b">Progreso del formulario</span>
        <span id="txt-progreso" style="font-size:.85rem;color:#2e75b6;font-weight:700">
            {{ $progreso['respondidas'] }}/{{ $progreso['total'] }} ({{ $progreso['porcentaje'] }}%)
        </span>
    </div>
    <div class="progreso-bar-wrap">
        <div class="progreso-bar-fill" id="barra-progreso" style="width:{{ $progreso['porcentaje'] }}%"></div>
    </div>
</div>

@php $esCompletada = $entrevista && $entrevista['estado'] === 'completada'; @endphp

@foreach($secciones as $seccion)
@php
    $resp  = collect($seccion['preguntas'])->filter(fn($p) => $p['respuesta'] !== null)->count();
    $tot   = count($seccion['preguntas']);
    $ok    = $resp === $tot && $tot > 0;
@endphp
<div class="seccion-card">
    <div class="seccion-header" onclick="toggleSeccion({{ $seccion['id'] }})">
        <span style="font-size:1.2rem">
            @switch($seccion['slug'])
                @case('datos_identificacion')          🪪  @break
                @case('datos_familiares')               👨‍👩‍👧 @break
                @case('antecedentes_medicos_familiares') 🏥 @break
                @case('antecedentes_medicos_aspirante')  💊 @break
                @case('antecedentes_toxicologicos')      🧪 @break
                @case('historia_educativa')              🎓 @break
                @case('experiencia_laboral')             💼 @break
                @case('motivacion_aeronautica')          ✈️ @break
                @case('area_psicosocial')                🧠 @break
                @case('entorno_familiar')                🏠 @break
                @case('motivacion_escala')               ⭐ @break
                @default 📋
            @endswitch
        </span>
        <h3>{{ $seccion['nombre'] }}</h3>
        <span style="font-size:.78rem;color:#9eb3d4">{{ $resp }}/{{ $tot }}</span>
        <span class="badge-estado {{ $ok ? 'badge-completa' : 'badge-pendiente' }}">
            {{ $ok ? 'Completa' : 'Pendiente' }}
        </span>
        <span id="arrow-{{ $seccion['id'] }}" style="transition:transform .2s">▼</span>
    </div>

    <div class="seccion-body" id="body-{{ $seccion['id'] }}">
        @if($seccion['descripcion'])
        <p style="font-size:.82rem;color:#6b7a8d;margin-bottom:1rem">{{ $seccion['descripcion'] }}</p>
        @endif
        @if($seccion['tipo'] === 'escala')
        <div class="alerta-amarilla">📊 Selecciona la opción que mejor describe tu situación.</div>
        @endif

        @foreach($seccion['preguntas'] as $pregunta)
        @php
            $esMedicaDesc = in_array($pregunta['clave_word'], [
                'ama_patologicas_desc', 'ama_quirurgicos_desc', 'ama_hospitalizaciones_desc',
                'ama_traumas_desc', 'ama_alergias_desc', 'ama_psiquiatrico_desc', 'ama_farmacologicos_desc',
                'edu_tecnico', 'edu_universitario', 'edu_otros'
            ]);
        @endphp
        <div class="pregunta-item" id="item_{{ $pregunta['clave_word'] }}" data-clave="{{ $pregunta['clave_word'] }}" style="{{ $pregunta['clave_word'] === 'tox_concepto' ? 'display:none;' : '' }}">
            <label for="p{{ $pregunta['id'] }}">
                {{ $pregunta['orden'] }}. {{ $pregunta['enunciado'] }}
                @if($pregunta['obligatoria'])<span class="obligatoria">*</span>@endif
                <span class="save-indicator" id="saved-{{ $pregunta['id'] }}">✓ Guardado</span>
            </label>

            @if($esMedicaDesc)
                @php
                    $ans = $pregunta['respuesta'] ?? '';
                    $isYes = str_starts_with($ans, 'Sí:');
                    $isNo = $ans === 'No';
                    $descText = $isYes ? trim(substr($ans, 3)) : '';
                @endphp
                <div class="opciones-radio">
                    <label>
                        <input type="radio" name="preg_med_{{ $pregunta['id'] }}" value="Sí" {{ $isYes ? 'checked' : '' }} {{ $esCompletada ? 'disabled' : '' }} onchange="handleMedicaDesc({{ $pregunta['id'] }}, this.value)">
                        <span>Sí</span>
                    </label>
                    <label>
                        <input type="radio" name="preg_med_{{ $pregunta['id'] }}" value="No" {{ $isNo ? 'checked' : '' }} {{ $esCompletada ? 'disabled' : '' }} onchange="handleMedicaDesc({{ $pregunta['id'] }}, this.value)">
                        <span>No</span>
                    </label>
                </div>
                <textarea id="p{{ $pregunta['id'] }}" class="form-input mt-2" placeholder="Describa..."
                    style="{{ $isYes ? 'display:block; margin-top:0.5rem;' : 'display:none; margin-top:0.5rem;' }}"
                    {{ $esCompletada ? 'readonly' : '' }}
                    oninput="autoGuardar({{ $pregunta['id'] }}, 'Sí: ' + this.value)"
                >{{ $descText }}</textarea>

            @elseif($pregunta['tipo_respuesta'] === 'textarea')
                <textarea id="p{{ $pregunta['id'] }}" class="form-input"
                    {{ $esCompletada ? 'readonly' : '' }}
                    oninput="autoGuardar({{ $pregunta['id'] }}, this.value)"
                >{{ $pregunta['respuesta'] ?? '' }}</textarea>

            @elseif($pregunta['tipo_respuesta'] === 'fecha')
                <input id="p{{ $pregunta['id'] }}" type="date" class="form-input"
                    value="{{ $pregunta['respuesta'] ?? '' }}"
                    {{ $esCompletada ? 'readonly' : '' }}
                    {{ $pregunta['clave_word'] === 'fecha_nacimiento' ? 'onchange=calcularEdad(this.value);autoGuardar('.$pregunta['id'].',this.value)' : 'onchange=autoGuardar('.$pregunta['id'].',this.value)' }}>

            @elseif($pregunta['tipo_respuesta'] === 'numero')
                <input id="p{{ $pregunta['id'] }}" type="number" class="form-input" min="0"
                    value="{{ $pregunta['respuesta'] ?? '' }}"
                    {{ $esCompletada || $pregunta['clave_word'] === 'edad' ? 'readonly' : '' }}
                    oninput="autoGuardar({{ $pregunta['id'] }}, this.value)">

            @elseif(in_array($pregunta['tipo_respuesta'], ['si_no','escala_3','seleccion']))
                <div class="opciones-radio">
                @foreach($pregunta['opciones'] as $opcion)
                    <label>
                        <input type="radio" name="preg_{{ $pregunta['id'] }}"
                            value="{{ $opcion }}"
                            {{ $pregunta['respuesta'] === $opcion ? 'checked' : '' }}
                            {{ $esCompletada ? 'disabled' : '' }}
                            onchange="autoGuardar({{ $pregunta['id'] }}, this.value); handleDependencies('{{ $pregunta['clave_word'] }}', this.value);">
                        <span>{{ $opcion }}</span>
                    </label>
                @endforeach
                </div>

            @else
                <input id="p{{ $pregunta['id'] }}" type="text" class="form-input"
                    value="{{ $pregunta['respuesta'] ?? '' }}"
                    placeholder="Escribe tu respuesta"
                    {{ $esCompletada ? 'readonly' : '' }}
                    {{ in_array($pregunta['clave_word'], ['documento', 'telefono']) ? 'oninput=this.value=this.value.replace(/[^0-9]/g,\'\');autoGuardar('.$pregunta['id'].',this.value)' : 'oninput=autoGuardar('.$pregunta['id'].',this.value)' }}>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endforeach

{{-- Botón completar: form POST web — sin dependencia de token API --}}
@if(!$esCompletada)
<div style="background:#fff;border-radius:16px;border:1px solid #eef1f5;padding:1.5rem;margin-top:1rem">
    <p style="font-size:.85rem;color:#6b7a8d;margin-bottom:.75rem">
        Al completar confirmas que la información es verídica.
        Los campos con <strong style="color:#c50f1f">*</strong> son obligatorios.
    </p>
    <form method="POST" action="{{ route('entrevista.completar') }}"
          onsubmit="return confirm('¿Confirmas completar la entrevista? Ya no podrás modificar las respuestas.')">
        @csrf
        <button type="submit" class="btn-completar">
            ✅ Completar entrevista y desbloquear PMA-R
        </button>
    </form>
</div>
@endif

<script>
// Auto-guardado usa CSRF (no Bearer token) — ruta web normal
const CSRF = '{{ csrf_token() }}';
const URL_GUARDAR = '{{ route("entrevista.responder") }}';
let timers = {};

function autoGuardar(id, val) {
    clearTimeout(timers[id]);
    timers[id] = setTimeout(() => guardar(id, val), 700);
}

async function guardar(id, val) {
    const ind = document.getElementById('saved-' + id);
    try {
        const r = await fetch(URL_GUARDAR, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ pregunta_id: id, respuesta: val })
        });
        if (r.ok) {
            const d = await r.json();
            if (ind) { ind.classList.remove('error'); ind.textContent = '✓ Guardado'; ind.classList.add('visible'); setTimeout(() => ind.classList.remove('visible'), 2500); }
            if (d.progreso) actualizarProgreso(d.progreso);
        } else {
            if (ind) { ind.classList.add('error'); ind.textContent = '⚠ Error'; ind.classList.add('visible'); }
        }
    } catch(e) {
        if (ind) { ind.classList.add('error'); ind.textContent = '⚠ Sin conexión'; ind.classList.add('visible'); }
    }
}

function actualizarProgreso(p) {
    const b = document.getElementById('barra-progreso');
    const t = document.getElementById('txt-progreso');
    if (b) b.style.width = p.porcentaje + '%';
    if (t) t.textContent = p.respondidas + '/' + p.total + ' (' + p.porcentaje + '%)';
}

function toggleSeccion(id) {
    const body = document.getElementById('body-' + id);
    const arr  = document.getElementById('arrow-' + id);
    const open = body.classList.toggle('abierta');
    if (arr) arr.style.transform = open ? 'rotate(180deg)' : '';
}

function handleMedicaDesc(id, val) {
    const textarea = document.getElementById('p' + id);
    if (val === 'Sí') {
        textarea.style.display = 'block';
        if(textarea.value.trim() === '') {
            autoGuardar(id, 'Sí: ');
        } else {
            autoGuardar(id, 'Sí: ' + textarea.value);
        }
    } else {
        textarea.style.display = 'none';
        autoGuardar(id, 'No');
    }
}

function calcularEdad(fechaStr) {
    if(!fechaStr) return;
    const nacimiento = new Date(fechaStr);
    const hoy = new Date();
    let edad = hoy.getFullYear() - nacimiento.getFullYear();
    const m = hoy.getMonth() - nacimiento.getMonth();
    if (m < 0 || (m === 0 && hoy.getDate() < nacimiento.getDate())) {
        edad--;
    }
    const edadInputItem = document.getElementById('item_edad');
    if (edadInputItem) {
        const inputEdad = edadInputItem.querySelector('input[type="number"]');
        if (inputEdad) {
            inputEdad.value = edad;
            // Disparar input event para autoguardar
            inputEdad.dispatchEvent(new Event('input'));
        }
    }
}

function handleDependencies(clave_word, value) {
    const rules = {
        'tox_fuma': ['tox_cigarrillos_dia', 'tox_anios_fumando'],
        'tox_alcohol': ['tox_alcohol_frecuencia'],
        'tox_sustancias': ['tox_sustancias_desc'],
        'lab_ha_trabajado': ['lab_empresa_reciente', 'lab_tiempo_reciente', 'lab_funciones', 'lab_experiencia_previa'],
        'mot_atencion_cliente': ['mot_atencion_cliente_desc', 'mot_proyeccion']
    };

    if (rules[clave_word]) {
        const show = value === 'Sí';
        rules[clave_word].forEach(childClave => {
            const el = document.getElementById('item_' + childClave);
            if (el) {
                el.style.display = show ? 'block' : 'none';
            }
        });
    }
}

function initDependencies() {
    const triggers = ['tox_fuma', 'tox_alcohol', 'tox_sustancias', 'lab_ha_trabajado', 'mot_atencion_cliente'];
    triggers.forEach(clave => {
        const item = document.getElementById('item_' + clave);
        if (item) {
            const checkedRadio = item.querySelector('input[type="radio"]:checked');
            // Por defecto ocultamos si no hay respuesta o es "No"
            const val = checkedRadio ? checkedRadio.value : 'No';
            handleDependencies(clave, val);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const first = document.querySelector('.seccion-body');
    if (first) {
        first.classList.add('abierta');
        const id  = first.id.replace('body-', '');
        const arr = document.getElementById('arrow-' + id);
        if (arr) arr.style.transform = 'rotate(180deg)';
    }
    initDependencies();
});
</script>


@endsection