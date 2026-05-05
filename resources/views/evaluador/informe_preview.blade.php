@extends('layouts.app')
@section('title', 'Preview informe — ' . ($resumen['usuario'] ?? ''))
@section('content')

<style>
.preview-wrap{background:#fff;border:1px solid #d1d5db;border-radius:4px;padding:2.5rem;max-width:900px;margin:0 auto;font-family:Arial,sans-serif;font-size:10pt;color:#000;line-height:1.4}
.preview-wrap h2{font-size:11pt;font-weight:bold;color:#1a3a6b;text-transform:uppercase;background:#1a3a6b;color:#fff;padding:.4rem 1rem;margin:1.2rem 0 .6rem}
.preview-wrap h3{font-size:10pt;font-weight:bold;color:#1a3a6b;margin:.8rem 0 .3rem}
table.datos{width:100%;border-collapse:collapse;font-size:9pt}
table.datos td{padding:.3rem .6rem;border:1px solid #ccc}
table.datos td.lbl{background:#eef1f5;font-weight:bold;width:40%}
table.check{width:100%;border-collapse:collapse;font-size:8.5pt}
table.check th{background:#1a3a6b;color:#fff;padding:.3rem .5rem;text-align:center;border:1px solid #ccc}
table.check td{padding:.3rem .5rem;border:1px solid #ccc;text-align:center}
table.check td.nombre{text-align:left;font-weight:600}
table.cond{width:100%;border-collapse:collapse;font-size:8.5pt}
table.cond th{background:#1a3a6b;color:#fff;padding:.3rem .5rem;text-align:left;border:1px solid #ccc}
table.cond td{padding:.4rem .6rem;border:1px solid #ccc;vertical-align:top}
.firma-wrap{text-align:center;margin-top:2rem;padding-top:1rem;border-top:1px solid #ccc}
.concepto-badge{display:inline-block;padding:.3rem 1rem;border-radius:4px;font-weight:bold;font-size:10pt}
.concepto-admitido{background:#d1fae5;color:#065f46}
.concepto-seguimiento{background:#fef3c7;color:#92400e}
.concepto-no{background:#fee2e2;color:#991b1b}
</style>

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem">
    <div>
        <a href="{{ route('evaluador.informe.show', $sesionId) }}" style="color:#6b7a8d;font-size:.85rem;text-decoration:none">← Volver a editar</a>
        <h1 style="font-family:'DM Serif Display',serif;color:#0f1f3d;font-size:1.5rem;margin:.25rem 0">Preview del Informe</h1>
    </div>
    <a href="{{ route('evaluador.informe.pdf', $sesionId) }}"
       style="padding:.75rem 1.75rem;background:#107c10;color:#fff;border-radius:12px;font-weight:700;font-size:.9rem;text-decoration:none">
        ⬇ Descargar PDF sellado
    </a>
</div>

<div class="preview-wrap">

    {{-- Encabezado --}}
    <div style="text-align:center;margin-bottom:1rem;position:relative;">
        @if(file_exists(storage_path('app/plantillas/ecotet_header.png')))
        <img src="{{ route('asset.plantilla', 'ecotet_header.png') }}" style="max-width:100%;height:auto;margin-bottom:.5rem;" alt="Ecotet Header">
        @else
        <div style="font-size:13pt;font-weight:bold;color:#1a3a6b">ECOTET AVIATION ACADEMY</div>
        <div style="font-size:11pt;font-weight:bold;margin:.25rem 0">INFORME DE EVALUACIÓN PSICOLÓGICA</div>
        <div style="font-size:9pt;color:#555">PROCESO DE ADMISIÓN — PROGRAMA TRIPULANTE CABINA DE PASAJEROS</div>
        @endif

        @if(file_exists(storage_path('app/plantillas/ecotet_logo.jpg')))
        <div style="margin-top:1rem;">
            <img src="{{ route('asset.plantilla', 'ecotet_logo.jpg') }}" style="height:80px;object-fit:contain;" alt="Ecotet Logo">
        </div>
        @endif
    </div>

    {{-- 1. Datos identificación --}}
    <h2>1. Datos de Identificación</h2>
    <table class="datos">
        <tr><td class="lbl">Apellidos y Nombres</td><td>{{ $datos['NOMBRE_COMPLETO'] }}</td>
            <td class="lbl">Edad</td><td>{{ $datos['EDAD'] }}</td></tr>
        <tr><td class="lbl">Estado civil</td><td>{{ $datos['ESTADO_CIVIL'] }}</td>
            <td class="lbl">Doc. Identidad</td><td>{{ $datos['DOCUMENTO'] }}</td></tr>
        <tr><td class="lbl">Teléfono</td><td>{{ $datos['TELEFONO'] }}</td>
            <td class="lbl">Fecha de evaluación</td><td>{{ $datos['FECHA_EVALUACION'] }}</td></tr>
        <tr><td class="lbl">Dirección</td><td colspan="3">{{ $datos['DIRECCION'] }}</td></tr>
    </table>

    {{-- 2. Datos familiares --}}
    <h2>2. Datos Familiares</h2>
    <p style="margin:.25rem 0">{{ $datos['DATOS_FAMILIARES'] ?: '—' }}</p>

    {{-- 3. Antecedentes médicos familiares --}}
    <h2>3. Antecedentes Médicos Familiares</h2>
    <table class="check">
        <tr>
            <th style="text-align:left;width:45%">Enfermedad</th><th>SÍ</th><th>NO</th>
            <th style="text-align:left;width:30%">Enfermedad</th><th>SÍ</th><th>NO</th>
        </tr>
        @php $amf = [
            ['CÁNCER','AMF_CANCER'],['ENF. AUTOINMUNES','AMF_AUTOINMUNES'],
            ['DIABETES','AMF_DIABETES'],['ARTRITIS','AMF_ARTRITIS'],
            ['HIPERTENSIÓN','AMF_HIPERTENSION'],['ENF. RENALES','AMF_RENALES'],
            ['CARDIOPATÍAS','AMF_CARDIOPATIAS'],['ENF. MENTALES','AMF_MENTALES'],
            ['ALERGIAS','AMF_ALERGIAS'],['ENF. NEUROLÓGICAS','AMF_NEUROLOGICAS'],
            ['ASMA','AMF_ASMA'],
        ]; $mid = (int)ceil(count($amf)/2); @endphp
        @for($i = 0; $i < $mid; $i++)
        <tr>
            <td class="nombre">{{ $amf[$i][0] }}</td>
            <td>{{ $datos[$amf[$i][1].'_SI'] }}</td>
            <td>{{ $datos[$amf[$i][1].'_NO'] }}</td>
            @if(isset($amf[$i+$mid]))
            <td class="nombre">{{ $amf[$i+$mid][0] }}</td>
            <td>{{ $datos[$amf[$i+$mid][1].'_SI'] }}</td>
            <td>{{ $datos[$amf[$i+$mid][1].'_NO'] }}</td>
            @else
            <td colspan="3"></td>
            @endif
        </tr>
        @endfor
    </table>

    {{-- 4. Antecedentes médicos aspirante --}}
    <h2>4. Antecedentes Médicos del Aspirante</h2>
    @php $ama = [
        ['HIPERTENSIÓN ART.','AMA_HIPERTENSION'],['TUMOR CEREBRAL','AMA_TUMOR'],
        ['CÁNCER','AMA_CANCER'],['TRAST. CONVULSIVOS','AMA_CONVULSIVOS'],
        ['DIABETES','AMA_DIABETES'],['ETS o VIH','AMA_ETS'],
        ['ENF. RENALES','AMA_RENALES'],['ALCOHOLISMO','AMA_ALCOHOLISMO'],
        ['ENF. HEPÁTICAS','AMA_HEPATICAS'],['TRAST. ANSIEDAD','AMA_ANSIEDAD'],
        ['ENF. CARDÍACAS','AMA_CARDIACAS'],['TRAST. DEPRESIVOS','AMA_DEPRESIVOS'],
        ['ENF. RESPIRATORIAS','AMA_RESPIRATORIAS'],['TDAH','AMA_TDAH'],
        ['TCE','AMA_TCE'],['TRAST. APRENDIZAJE','AMA_APRENDIZAJE'],
    ]; $mid2 = (int)ceil(count($ama)/2); @endphp
    <table class="check">
        <tr>
            <th style="text-align:left;width:32%">Antecedente</th><th>SÍ</th><th>NO</th>
            <th style="text-align:left;width:32%">Antecedente</th><th>SÍ</th><th>NO</th>
        </tr>
        @for($i = 0; $i < $mid2; $i++)
        <tr>
            <td class="nombre">{{ $ama[$i][0] }}</td>
            <td>{{ $datos[$ama[$i][1].'_SI'] }}</td>
            <td>{{ $datos[$ama[$i][1].'_NO'] }}</td>
            @if(isset($ama[$i+$mid2]))
            <td class="nombre">{{ $ama[$i+$mid2][0] }}</td>
            <td>{{ $datos[$ama[$i+$mid2][1].'_SI'] }}</td>
            <td>{{ $datos[$ama[$i+$mid2][1].'_NO'] }}</td>
            @else
            <td colspan="3"></td>
            @endif
        </tr>
        @endfor
    </table>

    {{-- 5. Toxicológicos --}}
    <h2>5. Antecedentes Toxicológicos</h2>
    <table class="datos">
        <tr><td class="lbl">Fuma</td><td>{{ $datos['TOX_FUMA_SI'] ? 'SÍ' : 'NO' }}</td>
            <td class="lbl">Cigarrillos/día</td><td>{{ $datos['TOX_CIGARRILLOS'] ?: '—' }}</td></tr>
        <tr><td class="lbl">Años fumando</td><td>{{ $datos['TOX_ANIOS'] ?: '—' }}</td>
            <td class="lbl">Consume alcohol</td><td>{{ $datos['TOX_ALCOHOL_SI'] ? 'SÍ' : 'NO' }}</td></tr>
        <tr><td class="lbl">Frecuencia alcohol</td><td>{{ $datos['TOX_FRECUENCIA'] ?: '—' }}</td>
            <td class="lbl">Sustancias</td><td>{{ $datos['TOX_SUSTANCIAS'] ?: '—' }}</td></tr>
        <tr><td class="lbl">Concepto toxicológico</td><td colspan="3">{{ $informe->concepto_toxicologico ?: '—' }}</td></tr>
    </table>

    {{-- 6. Historia educativa --}}
    <h2>6. Historia Educativa</h2>
    <p style="margin:.25rem 0">{{ $datos['HISTORIA_EDUCATIVA'] ?: '—' }}</p>

    {{-- 7. Experiencia laboral --}}
    <h2>7. Experiencia Laboral</h2>
    <p style="margin:.25rem 0">{{ $datos['EXPERIENCIA_LABORAL'] ?: '—' }}</p>

    {{-- 8. Motivación --}}
    <h2>8. Motivación hacia el Ámbito Aeronáutico</h2>
    <p style="margin:.25rem 0">{{ $datos['MOTIVACION'] ?: '—' }}</p>

    {{-- 9. NEO PI-R (placeholder) --}}
    <h2>9. Prueba de Personalidad: NEO PI-R</h2>
    <p style="color:#888;font-size:9pt;font-style:italic">Pendiente de integración.</p>

    {{-- 9.1 PMA-R --}}
    <h2>9.1 Prueba de Aptitudes Generales: PMA-R</h2>
    <table class="check">
        <tr><th style="text-align:left;width:50%">Factor</th><th>S:</th><th style="text-align:left">Interpretación</th></tr>
        @foreach([
            ['Verbal (V)',             'PMA_V'],
            ['Espacial (E)',           'PMA_E'],
            ['Razonamiento Lógico (R)','PMA_R'],
            ['Numérico (N)',           'PMA_N'],
            ['Fluidez verbal (F)',     'PMA_F'],
            ['Índice Global',          'PMA_GLOBAL'],
        ] as [$nom, $key])
        <tr>
            <td class="nombre">{{ $nom }}</td>
            <td>{{ $datos[$key.'_SCORE'] ?: '—' }}</td>
            <td style="text-align:left">{{ $datos[$key.'_INT'] ?: '—' }}</td>
        </tr>
        @endforeach
    </table>

    {{-- 10. Condiciones --}}
    <h2>10. Condiciones</h2>
    <table class="cond">
        <tr><th style="width:28%">Indicador</th><th>Descripción</th><th>Desarrollo</th></tr>
        @php $conds = [
            ['Seguridad personal',                     'cond_seguridad_personal'],
            ['Relaciones interpersonales',              'cond_relaciones_interpersonales'],
            ['Procesos cognitivos',                     'cond_procesos_cognitivos'],
            ['Tolerancia al estrés y fatiga',           'cond_tolerancia_estres'],
            ['Evaluación y asunción de riesgos',        'cond_evaluacion_riesgos'],
            ['Proyección ante exigencias TCP',          'cond_proyeccion_tcp'],
            ['Manejo de conflictos',                    'cond_manejo_conflictos'],
            ['Seguimiento de normas',                   'cond_seguimiento_normas'],
            ['Manejo de presiones externas',            'cond_manejo_presiones'],
            ['Reacción ante emergencias',               'cond_reaccion_emergencias'],
            ['Afiliación social',                       'cond_afiliacion_social'],
        ]; @endphp
        @foreach($conds as [$titulo, $key])
        <tr>
            <td style="font-weight:600">{{ $titulo }}</td>
            <td>{{ $informe->{$key.'_desc'} ?: '—' }}</td>
            <td>{{ $informe->{$key.'_dev'} ?: '—' }}</td>
        </tr>
        @endforeach
    </table>

    {{-- 11. Conclusiones --}}
    <h2>11. Conclusiones</h2>
    <p style="margin:.25rem 0">{{ $informe->conclusiones ?: '—' }}</p>

    {{-- 12. Concepto --}}
    <h2>12. Concepto</h2>
    @php $cg = $informe->concepto_global ?? ''; @endphp
    <p>
        <span class="concepto-badge {{ $cg === 'Admitido' ? 'concepto-admitido' : ($cg === 'No admitido' ? 'concepto-no' : 'concepto-seguimiento') }}">
            {{ $cg ?: '— Sin definir —' }}
        </span>
    </p>

    {{-- 13. Recomendaciones --}}
    <h2>13. Recomendaciones y/o Observaciones</h2>
    <p style="margin:.25rem 0">{{ $informe->recomendaciones ?: '—' }}</p>

    {{-- Firma --}}
    <div class="firma-wrap">
        @if($perfilEvaluador && $perfilEvaluador->firma_path)
        <img src="{{ asset('storage/' . $perfilEvaluador->firma_path) }}"
             style="height:80px;object-fit:contain;margin-bottom:.5rem" alt="Firma">
        <br>
        @endif
        <div style="border-top:1px solid #000;display:inline-block;padding-top:.3rem;min-width:200px;text-align:center">
            <div style="font-weight:bold">{{ $perfilEvaluador->nombre_completo ?? 'Psicólogo(a) Evaluador(a)' }}</div>
            <div style="font-size:8.5pt;color:#555">T.P. No. {{ $perfilEvaluador->tarjeta_profesional ?? '___________' }}</div>
        </div>
    </div>

</div>

@endsection
