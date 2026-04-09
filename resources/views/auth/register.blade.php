<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro — PMA-R</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --navy:#0f1f3d;--navy2:#1a3a6b;--blue:#2e75b6;--accent:#e8a020;--red:#c50f1f;--gray100:#eef1f5;--gray500:#6b7a8d;--white:#fff; }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'DM Sans',sans-serif;background:var(--navy);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem;}
        body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 80% 60% at 20% 20%,rgba(46,117,182,0.15) 0%,transparent 60%),radial-gradient(ellipse 60% 80% at 80% 80%,rgba(232,160,32,0.08) 0%,transparent 60%);pointer-events:none;}
        body::after{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(255,255,255,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,0.03) 1px,transparent 1px);background-size:48px 48px;pointer-events:none;}
        .register-box{background:white;border-radius:24px;box-shadow:0 32px 80px rgba(0,0,0,0.5);padding:2.5rem;width:100%;max-width:520px;position:relative;z-index:1;animation:fadeUp 0.5s ease;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        .box-header{text-align:center;margin-bottom:2rem;}
        .box-logo{width:48px;height:48px;background:var(--navy2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-family:'DM Serif Display',serif;font-size:1.4rem;color:var(--accent);margin:0 auto 1rem;}
        .box-header h1{font-family:'DM Serif Display',serif;color:var(--navy);font-size:1.75rem;margin-bottom:0.25rem;}
        .box-header p{color:var(--gray500);font-size:0.875rem;}
        .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
        .form-group{margin-bottom:1.1rem;}
        .form-label{display:block;font-size:0.78rem;font-weight:600;color:var(--navy2);margin-bottom:0.35rem;text-transform:uppercase;letter-spacing:0.05em;}
        .form-control{width:100%;padding:0.7rem 1rem;border:2px solid var(--gray100);border-radius:10px;font-size:0.9rem;font-family:'DM Sans',sans-serif;color:var(--navy);outline:none;transition:border-color 0.2s,box-shadow 0.2s;}
        .form-control:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(46,117,182,0.1);}
        .btn-register{width:100%;padding:0.85rem;background:var(--navy2);color:white;border:none;border-radius:10px;font-size:1rem;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;transition:all 0.2s;margin-top:0.5rem;}
        .btn-register:hover{background:var(--blue);transform:translateY(-1px);box-shadow:0 4px 16px rgba(46,117,182,0.3);}
        .form-footer{margin-top:1.25rem;text-align:center;font-size:0.85rem;color:var(--gray500);}
        .form-footer a{color:var(--blue);text-decoration:none;font-weight:500;}
        .alert-error{background:#fef2f2;color:var(--red);border:1px solid #fecaca;border-radius:10px;padding:0.8rem 1rem;font-size:0.875rem;margin-bottom:1.25rem;}
        @media(max-width:480px){.grid-2{grid-template-columns:1fr;}}
    </style>
</head>
<body>
    <div class="register-box">
        <div class="box-header">
            <div class="box-logo">P</div>
            <h1>Crear Cuenta</h1>
            <p>Regístrate para acceder a las evaluaciones PMA-R</p>
        </div>

        @if($error ?? false)
        <div class="alert-error">⚠ {{ $error }}</div>
        @endif

        <form action="{{ route('register.post') }}" method="POST">
            @csrf
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Nombre completo</label>
                    <input type="text" name="name" class="form-control" placeholder="Tu nombre" required value="{{ old('name') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Documento (cédula)</label>
                    <input type="text" name="documento" class="form-control" placeholder="1234567890" value="{{ old('documento') }}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Correo electrónico</label>
                <input type="email" name="email" class="form-control" placeholder="tu@correo.com" required value="{{ old('email') }}">
            </div>
            <div class="form-group">
                <label class="form-label">Programa académico / Cargo</label>
                <input type="text" name="programa" class="form-control" placeholder="Administración de Empresas" value="{{ old('programa') }}">
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" placeholder="Mínimo 8 caracteres" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirmar contraseña</label>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Repite la contraseña" required>
                </div>
            </div>

            <button type="submit" class="btn-register">Crear mi cuenta</button>
        </form>

        <div class="form-footer">
            ¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a>
        </div>
    </div>
</body>
</html>
