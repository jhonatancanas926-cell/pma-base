@extends('layouts.app')
@section('title', 'Entrevista de ' . ($aspirante['name'] ?? 'Aspirante'))
@section('content')

<style>
.seccion-card{background:#fff;border-radius:16px;border:1px solid #eef1f5;margin-bottom:1.25rem;overflow:hidden}
.seccion-header{background:#1a3a6b;color:#fff;padding:.85rem 1.25rem;display:flex;align-items:center;gap:10px;cursor:pointer;user-select:none}
.seccion-header h3{margin:0;font-size:.95rem;font-weight:600;flex:1}
.seccion-body{padding:1.25rem;display:none}
.seccion-body.abierta{display:block}
.pregunta-row{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1rem;align-items:start}
@media(max-width:640px){.pregunta-row{grid-template-columns:1fr}}
.pregunta-label{font-size:.8rem;font-weight:600;color:#374151;margin-bottom:.3rem}
.pregunta-original{font-size:.85rem;color:#0f1f3d;background:#f8fafd;border-radius:8px;padding:.5rem .75rem;border:1px solid #eef1f5;min-height:36px}
.form-input-eval{width:100%;padding:.5rem .8rem;border:2px solid #fcd34d;border-radius:8px;font-size:.85rem;font-family:inherit;color:#0f1f3d;background:#fffbeb;transition:border-color .2s}
.form-input-eval:focus{outline:none;border-color:#e8a020;box-shadow:0 0 0 3px rgba(232,160,32,.15)}
textarea.form-input-eval{resize:vertical;min-height:70px}
.save-indicator{font-size:.72rem;color:#107c10;margin-left:.5rem;opacity:0;transition:opacity .3s}
.save-indicator.visible{opacity:1}
.editado-badge{font-size:.68rem;background:#fef3c7;color:#92400e;padding:.15rem .5rem;border-radius:6px;font-weight:700}
.opciones-radio{display:flex;gap:.6rem;flex-wrap:wrap}
.opciones-radio label{display:flex;align-items:center;gap:.3rem;font-size:.8rem;cursor:pointer;padding:.3rem .6rem;border:1.5px solid #eef1f5;border-radius:7px}
.opciones-radio label:has(input:checked){border-color:#e8a020;background:#fffbeb}
</style>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
    <div>
        <a href="{{ route('evaluador.aspirantes') }}" style="color:#6b7a8d;font-size:.85rem;text-decoration:none">← Volver</a>
        <h1 style="font-family:'DM Serif Display',serif;color:#0f1f3d;font-size:1.6rem;margin:.25rem 0">
            {{ $aspirante['name'] ?? 'Aspirante' }}
        </h1>
        <p style="color:#6b7a8d;font-size:.85rem">
            Doc: {{ $aspirante['documento'] ?? '—' }} &nbsp;|&nbsp;
            Programa: {{ $aspirante['programa'] ?? '—' }} &nbsp;|&nbsp;
            Estado: <strong style="color:{{ $entrevista['estado'] === 'completada' ? '#107c10' : '#e8a020' }}">
                {{ ucfirst($entrevista['estado'] ?? 'pendiente') }}
            </strong>
        </p>
    </div>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap">
        @if(($entrevista['estado'] ?? '') !== 'completada')
        <form method="POST" action="{{ route('evaluador.entrevista.estado', $userId) }}"
              onsubmit="return confirm('¿Marcar como completada?')">
            @csrf
            <input type="hidden" name="estado" value="completada">
            <button type="submit" style="padding:.6rem 1.25rem;background:#107c10;color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:.875rem">
                ✅ Marcar completada
            </button>
        </form>
        @else
        <form method="POST" action="{{ route('evaluador.entrevista.estado', $userId) }}"
              onsubmit="return confirm('¿Revertir a en progreso?')">
            @csrf
            <input type="hidden" name="estado" value="en_progreso">
            <button type="submit" style="padding:.6rem 1.25rem;background:#e8a020;color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:.875rem">
                ↩ Revertir estado
            </button>
        </form>
        @endif
        
        @if(!($entrevista['pma_habilitado'] ?? false))
        <form method="POST" action="{{ route('evaluador.entrevista.habilitar_pma', $userId) }}"
              onsubmit="return confirm('¿Habilitar acceso a la prueba PMA-R para este aspirante?')">
            @csrf
            <button type="submit" style="padding:.6rem 1.25rem;background:#1a3a6b;color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:.875rem">
                🔓 Habilitar PMA-R
            </button>
        </form>
        @else
        <form method="POST" action="{{ route('evaluador.entrevista.habilitar_pma', $userId) }}"
              onsubmit="return confirm('¿Deshabilitar el acceso a la prueba PMA-R para este aspirante?')">
            @csrf
            <button type="submit" style="padding:.6rem 1.25rem;background:#107c10;color:#fff;border:none;border-radius:10px;font-weight:700;cursor:pointer;font-size:.875rem">
                ✅ PMA-R Habilitada
            </button>
        </form>
        @endif
    </div>
</div>

<div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:12px;padding:.75rem 1.25rem;margin-bottom:1.5rem;font-size:.83rem;color:#92400e">
    📝 <strong>Modo evaluador:</strong> La columna izquierda muestra la respuesta original del aspirante.
    En la columna derecha puedes editar o complementar. Los cambios se guardan automáticamente.
</div>

@foreach($secciones as $seccion)
<div class="seccion-card">
    <div class="seccion-header" onclick="toggleSeccion({{ $seccion['id'] }})">
        <h3>{{ $seccion['nombre'] }}</h3>
        <span id="arrow-{{ $seccion['id'] }}">▼</span>
    </div>
    <div class="seccion-body" id="body-{{ $seccion['id'] }}">
        <div style="display:grid;grid-template-columns:1fr 1fr;margin-bottom:.75rem;gap:.75rem">
            <div style="font-size:.75rem;font-weight:700;color:#6b7a8d;text-transform:uppercase;letter-spacing:.05em">Respuesta del aspirante</div>
            <div style="font-size:.75rem;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.05em">Edición del evaluador</div>
        </div>

        @foreach($seccion['preguntas'] as $pregunta)
        <div class="pregunta-row" style="padding-bottom:.75rem;border-bottom:1px solid #f3f4f6;margin-bottom:.75rem">
            {{-- Columna izquierda: respuesta original --}}
            <div>
                <div class="pregunta-label">
                    {{ $pregunta['orden'] }}. {{ $pregunta['enunciado'] }}
                </div>
                <div class="pregunta-original">
                    {{ $pregunta['respuesta'] ?? '(sin respuesta)' }}
                </div>
            </div>

            {{-- Columna derecha: edición --}}
            <div>
                <div class="pregunta-label" style="color:#92400e">
                    Corrección / complemento
                    <span class="save-indicator" id="saved-{{ $pregunta['id'] }}">✓ Guardado</span>
                </div>

                @php
                    $esMedicaDesc = in_array($pregunta['clave_word'], [
                        'ama_patologicas_desc', 'ama_quirurgicos_desc', 'ama_hospitalizaciones_desc',
                        'ama_traumas_desc', 'ama_alergias_desc', 'ama_psiquiatrico_desc', 'ama_farmacologicos_desc',
                        'edu_tecnico', 'edu_universitario', 'edu_otros'
                    ]);
                @endphp

                @if($esMedicaDesc)
                    @php
                        $ans = $pregunta['respuesta'] ?? '';
                        $isYes = str_starts_with($ans, 'Sí:');
                        $isNo = $ans === 'No';
                        $descText = $isYes ? trim(substr($ans, 3)) : '';
                    @endphp
                    <div class="opciones-radio">
                        <label>
                            <input type="radio" name="eval_med_{{ $pregunta['id'] }}" value="Sí" {{ $isYes ? 'checked' : '' }} onchange="handleEvalMedicaDesc({{ $pregunta['id'] }}, this.value)">
                            <span>Sí</span>
                        </label>
                        <label>
                            <input type="radio" name="eval_med_{{ $pregunta['id'] }}" value="No" {{ $isNo ? 'checked' : '' }} onchange="handleEvalMedicaDesc({{ $pregunta['id'] }}, this.value)">
                            <span>No</span>
                        </label>
                    </div>
                    <textarea id="eval_p{{ $pregunta['id'] }}" class="form-input-eval mt-2" placeholder="Describa..."
                        style="{{ $isYes ? 'display:block; margin-top:0.5rem;' : 'display:none; margin-top:0.5rem;' }}"
                        oninput="autoGuardarEval({{ $pregunta['id'] }}, 'Sí: ' + this.value)"
                    >{{ $descText }}</textarea>

                @elseif(in_array($pregunta['tipo_respuesta'], ['si_no', 'escala_3', 'seleccion']))
                    <div class="opciones-radio">
                        @foreach($pregunta['opciones'] as $opcion)
                        <label>
                            <input type="radio"
                                name="eval_{{ $pregunta['id'] }}"
                                value="{{ $opcion }}"
                                {{ $pregunta['respuesta'] === $opcion ? 'checked' : '' }}
                                onchange="guardarEval({{ $pregunta['id'] }}, this.value)">
                            <span>{{ $opcion }}</span>
                        </label>
                        @endforeach
                    </div>
                @elseif($pregunta['tipo_respuesta'] === 'textarea')
                    <textarea class="form-input-eval"
                        oninput="autoGuardarEval({{ $pregunta['id'] }}, this.value)"
                    >{{ $pregunta['respuesta'] ?? '' }}</textarea>
                @else
                    <input type="{{ in_array($pregunta['tipo_respuesta'], ['numero']) ? 'number' : ($pregunta['tipo_respuesta'] === 'fecha' ? 'date' : 'text') }}"
                        class="form-input-eval"
                        value="{{ $pregunta['respuesta'] ?? '' }}"
                        oninput="autoGuardarEval({{ $pregunta['id'] }}, this.value)"
                        onchange="autoGuardarEval({{ $pregunta['id'] }}, this.value)">
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endforeach

<script>
const TOKEN  = '{{ session("api_token") }}';
const USERID = {{ $userId }};
let timers = {};

function autoGuardarEval(preguntaId, valor) {
    clearTimeout(timers[preguntaId]);
    timers[preguntaId] = setTimeout(() => guardarEval(preguntaId, valor), 600);
}

async function guardarEval(preguntaId, valor) {
    try {
        const res = await fetch('/api/v1/evaluador/entrevistas/' + USERID + '/responder', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + TOKEN },
            body: JSON.stringify({ pregunta_id: preguntaId, respuesta: valor })
        });
        if (res.ok) {
            const el = document.getElementById('saved-' + preguntaId);
            if (el) { el.classList.add('visible'); setTimeout(() => el.classList.remove('visible'), 2500); }
        }
    } catch(e) { console.error(e); }
}

function toggleSeccion(id) {
    const body  = document.getElementById('body-' + id);
    const arrow = document.getElementById('arrow-' + id);
    const abierta = body.classList.toggle('abierta');
    arrow.style.transform = abierta ? 'rotate(180deg)' : '';
}

function handleEvalMedicaDesc(id, val) {
    const textarea = document.getElementById('eval_p' + id);
    if (val === 'Sí') {
        textarea.style.display = 'block';
        if(textarea.value.trim() === '') {
            guardarEval(id, 'Sí: ');
        } else {
            guardarEval(id, 'Sí: ' + textarea.value);
        }
    } else {
        textarea.style.display = 'none';
        guardarEval(id, 'No');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const primera = document.querySelector('.seccion-body');
    if (primera) {
        primera.classList.add('abierta');
        const id = primera.id.replace('body-', '');
        const arrow = document.getElementById('arrow-' + id);
        if (arrow) arrow.style.transform = 'rotate(180deg)';
    }
});
</script>

@endsection
