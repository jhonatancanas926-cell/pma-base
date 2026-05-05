@extends('layouts.app')
@section('title', 'Mi perfil profesional')
@section('content')

<div style="max-width:600px;margin:0 auto">
    <h1 style="font-family:'DM Serif Display',serif;color:#0f1f3d;font-size:1.6rem;margin-bottom:1.5rem">
        Mi perfil profesional
    </h1>

    @if(session('flash_success'))
    <div style="background:#d1fae5;border:1px solid #6ee7b7;border-radius:12px;padding:.85rem 1.25rem;margin-bottom:1rem;color:#065f46;font-weight:600">
        ✅ {{ session('flash_success') }}
    </div>
    @endif

    <div class="card" style="padding:1.75rem">
        <form method="POST" action="{{ route('evaluador.perfil.update') }}" enctype="multipart/form-data">
            @csrf
            <div style="margin-bottom:1.25rem">
                <label style="display:block;font-size:.82rem;font-weight:700;color:#1a3a6b;margin-bottom:.4rem">
                    Nombre completo (aparecerá en el informe PDF)
                </label>
                <input type="text" name="nombre_completo"
                    value="{{ $perfil->nombre_completo ?? '' }}"
                    style="width:100%;padding:.6rem .9rem;border:2px solid #eef1f5;border-radius:10px;font-size:.9rem;font-family:inherit">
            </div>

            <div style="margin-bottom:1.25rem">
                <label style="display:block;font-size:.82rem;font-weight:700;color:#1a3a6b;margin-bottom:.4rem">
                    Número de tarjeta profesional
                </label>
                <input type="text" name="tarjeta_profesional"
                    value="{{ $perfil->tarjeta_profesional ?? '' }}"
                    style="width:100%;padding:.6rem .9rem;border:2px solid #eef1f5;border-radius:10px;font-size:.9rem;font-family:inherit">
            </div>

            <div style="margin-bottom:1.5rem">
                <label style="display:block;font-size:.82rem;font-weight:700;color:#1a3a6b;margin-bottom:.4rem">
                    Firma escaneada (PNG o JPG, fondo blanco o transparente)
                </label>
                @if($perfil && $perfil->firma_path)
                <div style="margin-bottom:.75rem;padding:.75rem;background:#f8fafd;border-radius:10px;border:1px solid #eef1f5;display:inline-block">
                    <img src="{{ asset('storage/' . $perfil->firma_path) }}" style="height:70px;object-fit:contain" alt="Firma actual">
                    <div style="font-size:.75rem;color:#6b7a8d;margin-top:.25rem">Firma actual</div>
                </div>
                @endif
                <input type="file" name="firma" accept="image/png,image/jpeg"
                    style="display:block;margin-top:.25rem;font-size:.875rem">
                <div style="font-size:.75rem;color:#6b7a8d;margin-top:.25rem">Sube una nueva imagen para reemplazar la firma actual.</div>
            </div>

            <button type="submit"
                style="width:100%;padding:.85rem;background:#1a3a6b;color:#fff;border:none;border-radius:12px;font-size:.9rem;font-weight:700;cursor:pointer;font-family:inherit">
                💾 Guardar perfil
            </button>
        </form>
    </div>
</div>

@endsection
