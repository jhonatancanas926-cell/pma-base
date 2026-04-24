@extends('layouts.app')
@section('title', 'Evaluación en curso')

@push('styles')
<style>
.quiz-header{background:var(--navy);color:white;padding:1rem 2rem;border-radius:0;margin:0 -2rem 1.5rem -2rem;}
.progress-bar{height:6px;background:rgba(255,255,255,0.15);border-radius:3px;margin-top:0.75rem;overflow:hidden;}
.progress-fill{height:100%;background:var(--accent);border-radius:3px;transition:width 0.4s ease;}
.sticky-container{position:sticky;top:0;z-index:100;background:#f8fafc;padding-top:1rem;margin-top:-1rem;padding-bottom:10px;border-bottom:1px solid var(--gray100);box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);}
.factor-tabs{display:flex;gap:0.5rem;flex-wrap:wrap;}
.factor-tab{padding:0.4rem 1rem;border-radius:20px;font-size:0.8rem;font-weight:600;cursor:pointer;border:2px solid var(--gray100);background:white;color:var(--gray500);transition:all 0.2s;}
.factor-tab.active{background:var(--navy2);color:white;border-color:var(--navy2);}
.factor-tab.done{background:#dcfce7;color:var(--green);border-color:#bbf7d0;}
.question-card{background:white;border-radius:16px;padding:2rem;box-shadow:var(--shadow);margin-bottom:1rem;border:2px solid transparent;transition:border-color 0.2s;}
.question-num{font-size:0.78rem;font-weight:700;color:var(--gray500);text-transform:uppercase;letter-spacing:.06em;margin-bottom:0.5rem;}
.question-text{font-size:1.1rem;font-weight:500;color:var(--navy);margin-bottom:1.5rem;line-height:1.5;}
.options{display:grid;gap:0.6rem;}
.option-btn{display:flex;align-items:center;gap:12px;padding:0.85rem 1.2rem;border:2px solid var(--gray100);border-radius:12px;cursor:pointer;transition:all 0.15s;background:white;width:100%;text-align:left;font-family:'DM Sans',sans-serif;font-size:0.95rem;color:var(--navy);}
.option-btn:hover{border-color:var(--blue);background:#eff6ff;}
.option-btn.selected{border-color:var(--navy2);background:#eff6ff;}
.option-btn.correct{border-color:var(--green);background:#f0fdf4;color:var(--green);}
.option-btn.wrong{border-color:var(--red);background:#fef2f2;color:var(--red);}
.option-letter{width:30px;height:30px;border-radius:8px;background:var(--gray100);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;flex-shrink:0;}
.option-btn.selected .option-letter{background:var(--navy2);color:white;}
.nav-btns{display:flex;justify-content:space-between;align-items:center;margin-top:1.5rem;}
.timer{font-family:'JetBrains Mono',monospace;font-size:1rem;font-weight:600;color:var(--accent);}
.timer.warning{color:var(--red);animation:pulse 1s infinite;}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.5}}
.options-grid{display:grid;grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));gap:1rem;}
.option-btn.option-with-img{flex-direction:column;align-items:center;padding:1rem;gap:0.75rem;}
.opt-img-wrapper{flex-grow:1;display:flex;align-items:center;justify-content:center;width:100%;min-height:100px;background:#f8fafc;border-radius:8px;border:1px solid var(--gray100);}
.opt-img-wrapper img{max-width:100%;max-height:100px;object-fit:contain;}
</style>
@endpush

@section('content')
<div class="sticky-container">
    <div class="quiz-header">
        <div style="display:flex;justify-content:space-between;align-items:center">
            <div>
                <div style="font-size:0.78rem;opacity:0.6;text-transform:uppercase;letter-spacing:.05em">Evaluación en curso</div>
                <div style="font-family:'DM Serif Display',serif;font-size:1.2rem">PMA — Aptitudes Mentales Primarias</div>
            </div>
            <div class="timer" id="timer">{{ gmdate('i:s', ($sesion['test']['tiempo_limite'] ?? 25) * 60) }}</div>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" id="progressBar" style="width:{{ ($respondidas / max($totalPreguntas, 1)) * 100 }}%"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:0.78rem;opacity:0.6;margin-top:4px">
            <span>{{ $respondidas }} respondidas</span>
            <span>{{ $totalPreguntas - $respondidas }} restantes</span>
        </div>
    </div>

    <!-- Factor tabs -->
    <div class="factor-tabs">
        @foreach($categorias as $cat)
        <button class="factor-tab {{ $categoriaActual === $cat['id'] ? 'active' : '' }}"
                onclick="cambiarFactor({{ $cat['id'] }})">
            {{ $cat['nombre'] }}
            @if(($respuestasPorCategoria[$cat['id']] ?? 0) >= ($cat['preguntas_count'] ?? 0))
            ✓
            @endif
        </button>
        @endforeach
    </div>
</div>

<!-- Preguntas -->
<div id="preguntasContainer">
@foreach($preguntas as $pregunta)
<div class="question-card" id="q-{{ $pregunta['id'] }}">
    <div class="question-num">Pregunta {{ $pregunta['numero'] }}</div>
    <div class="question-text">{{ $pregunta['enunciado'] }}</div>

    @if(isset($pregunta['metadatos']['imagen_principal']))
    <div style="text-align: center; margin-bottom: 1.5rem;">
        @php
            $imagenPath = str_replace('.png', '.jpg', $pregunta['metadatos']['imagen_principal']);
        @endphp
        <img src="{{ asset($imagenPath) }}" alt="Figura de referencia" style="max-width: 100%; max-height: 180px; object-fit: contain; border: 2px solid var(--gray100); border-radius: 8px; padding: 0.5rem;">
    </div>
    @endif

    @if($pregunta['tipo'] === 'opcion_multiple' || $pregunta['tipo'] === 'verdadero_falso' || $pregunta['tipo'] === 'seleccion_multiple')
    @php
        $tieneImagen = isset($pregunta['metadatos']['requiere_imagen']) && $pregunta['metadatos']['requiere_imagen'];
    @endphp
    <div class="options {{ $tieneImagen ? 'options-grid' : '' }}" id="opts-{{ $pregunta['id'] }}">
        @foreach($pregunta['opciones'] as $index => $opcion)
        @php
            $isSelected = false;
            if (isset($respuestasGuardadas[$pregunta['id']])) {
                if ($pregunta['tipo'] === 'seleccion_multiple') {
                    $isSelected = in_array($opcion['letra'], explode(',', $respuestasGuardadas[$pregunta['id']]));
                } else {
                    $isSelected = $respuestasGuardadas[$pregunta['id']] === $opcion['letra'];
                }
            }
        @endphp
        <button class="option-btn {{ $tieneImagen ? 'option-with-img' : '' }} {{ $isSelected ? 'selected' : '' }}"
                onclick="responder{{ $pregunta['tipo'] === 'seleccion_multiple' ? 'Multiple' : '' }}({{ $pregunta['id'] }}, '{{ $opcion['letra'] }}', this)"
                data-letra="{{ $opcion['letra'] }}"
                data-pregunta="{{ $pregunta['id'] }}">
            <div style="display: flex; align-items: center; gap: 12px; width: 100%; {{ $tieneImagen ? 'justify-content: center;' : '' }}">
                <span class="option-letter">{{ $opcion['letra'] }}</span>
                @if(!$tieneImagen)<span>{{ $opcion['texto'] }}</span>@endif
            </div>
            
            @if($tieneImagen)
            <div class="opt-img-wrapper">
                <img src="{{ asset('imagenes/factor_e/' . $pregunta['numero'] . '-' . ($index + 1) . '.jpg') }}" alt="Opción {{ $opcion['letra'] }}">
            </div>
            @endif
        </button>
        @endforeach
    </div>
    @endif
</div>
@endforeach
</div>

<div class="nav-btns">
    <div class="text-muted" style="font-size:0.875rem">Sesión ID: <span class="mono">{{ $sesion['id'] }}</span></div>
    <div style="display:flex;gap:0.75rem">
        <form action="{{ route('sesiones.finalizar', $sesion['id']) }}" method="POST"
              onsubmit="return confirm('¿Estás seguro de finalizar la evaluación? No podrás cambiar tus respuestas.')">
            @csrf
            <button type="submit" class="btn btn-success btn-lg">
                ✅ Finalizar evaluación
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const SESION_ID = {{ $sesion['id'] }};
const TOTAL = {{ $totalPreguntas }};
let respondidas = {{ $respondidas }};
let tiempoSegundos = {{ ($sesion['test']['tiempo_limite'] ?? 25) * 60 }};

// Timer
const timerEl = document.getElementById('timer');
const timerInterval = setInterval(() => {
    tiempoSegundos--;
    if (tiempoSegundos <= 0) {
        clearInterval(timerInterval);
        document.querySelector('form').submit();
        return;
    }
    const m = Math.floor(tiempoSegundos / 60).toString().padStart(2,'0');
    const s = (tiempoSegundos % 60).toString().padStart(2,'0');
    timerEl.textContent = `${m}:${s}`;
    if (tiempoSegundos < 120) timerEl.classList.add('warning');
}, 1000);

// Responder pregunta
async function responder(preguntaId, letra, btn) {
    const container = document.getElementById(`opts-${preguntaId}`);
    const botones = container.querySelectorAll('.option-btn');

    // Marcar visualmente
    botones.forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');

    // Enviar a la API
    try {
        const res = await fetch(`/web/sesiones/${SESION_ID}/responder`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ pregunta_id: preguntaId, respuesta: letra })
        });
        const data = await res.json();
        if (data.nueva) respondidas++;
        document.getElementById('progressBar').style.width = `${(respondidas/TOTAL)*100}%`;
    } catch(e) {
        console.error('Error al guardar respuesta:', e);
    }
}

async function responderMultiple(preguntaId, letra, btn) {
    btn.classList.toggle('selected');
    
    const container = document.getElementById(`opts-${preguntaId}`);
    const seleccionados = Array.from(container.querySelectorAll('.option-btn.selected'))
                              .map(b => b.dataset.letra);
                              
    try {
        const res = await fetch(`/web/sesiones/${SESION_ID}/responder-multiple`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ pregunta_id: preguntaId, respuestas: seleccionados })
        });
        const data = await res.json();
        if (data.nueva) respondidas++;
        document.getElementById('progressBar').style.width = `${(respondidas/TOTAL)*100}%`;
    } catch(e) {
        console.error('Error al guardar respuesta múltiple:', e);
    }
}

function cambiarFactor(catId) {
    window.location.href = `?categoria=${catId}`;
}
</script>
@endpush
