<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — PMA-R</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #0f1f3d; --navy2: #1a3a6b; --blue: #2e75b6;
            --accent: #e8a020; --red: #c50f1f; --gray100: #eef1f5;
            --gray500: #6b7a8d; --white: #ffffff;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--navy);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        /* Animated background */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 20%, rgba(46,117,182,0.15) 0%, transparent 60%),
                radial-gradient(ellipse 60% 80% at 80% 80%, rgba(232,160,32,0.08) 0%, transparent 60%);
            pointer-events: none;
        }
        /* Grid pattern */
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
        }

        .login-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            max-width: 900px;
            width: 90%;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 32px 80px rgba(0,0,0,0.5);
            position: relative;
            z-index: 1;
            animation: fadeUp 0.6s ease;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Left panel */
        .login-hero {
            background: linear-gradient(145deg, var(--navy2) 0%, #0d2952 100%);
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        .login-hero::before {
            content: 'PMA';
            position: absolute;
            bottom: -20px;
            right: -20px;
            font-family: 'DM Serif Display', serif;
            font-size: 8rem;
            color: rgba(255,255,255,0.04);
            line-height: 1;
        }
        .hero-logo {
            display: flex; align-items: center; gap: 12px;
        }
        .hero-logo-icon {
            width: 44px; height: 44px;
            background: var(--accent);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-family: 'DM Serif Display', serif;
            font-size: 1.3rem;
            color: var(--navy);
        }
        .hero-logo-text {
            font-family: 'DM Serif Display', serif;
            color: white;
            font-size: 1.3rem;
        }
        .hero-logo-text span { color: var(--accent); }

        .hero-content { }
        .hero-content h1 {
            font-family: 'DM Serif Display', serif;
            color: white;
            font-size: 2rem;
            line-height: 1.2;
            margin-bottom: 1rem;
        }
        .hero-content h1 em { color: var(--accent); font-style: normal; }
        .hero-content p {
            color: rgba(255,255,255,0.55);
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        .hero-factors {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.6rem;
        }
        .hero-factor {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 0.6rem 0.8rem;
            display: flex; align-items: center; gap: 8px;
        }
        .hero-factor-icon {
            width: 28px; height: 28px;
            background: var(--accent);
            border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            font-family: 'DM Serif Display', serif;
            font-size: 0.85rem;
            color: var(--navy);
            font-weight: bold;
            flex-shrink: 0;
        }
        .hero-factor-text { font-size: 0.75rem; color: rgba(255,255,255,0.7); line-height: 1.3; }
        .hero-factor-text strong { color: white; display: block; font-size: 0.8rem; }

        .hero-footer {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.3);
        }

        /* Right panel */
        .login-form-panel {
            background: white;
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .form-header { margin-bottom: 2rem; }
        .form-header h2 {
            font-family: 'DM Serif Display', serif;
            font-size: 1.75rem;
            color: var(--navy);
            margin-bottom: 0.25rem;
        }
        .form-header p { color: var(--gray500); font-size: 0.875rem; }

        .form-group { margin-bottom: 1.25rem; }
        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--navy2);
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray100);
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: 'DM Sans', sans-serif;
            color: var(--navy);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(46,117,182,0.1);
        }

        .btn-login {
            width: 100%;
            padding: 0.85rem;
            background: var(--navy2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: all 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            margin-top: 0.5rem;
        }
        .btn-login:hover { background: var(--blue); transform: translateY(-1px); box-shadow: 0 4px 16px rgba(46,117,182,0.3); }
        .btn-login:active { transform: translateY(0); }

        .form-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.85rem;
            color: var(--gray500);
        }
        .form-footer a { color: var(--blue); text-decoration: none; font-weight: 500; }
        .form-footer a:hover { text-decoration: underline; }

        .alert-error {
            background: #fef2f2;
            color: var(--red);
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 0.8rem 1rem;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
            display: flex; align-items: center; gap: 8px;
        }

        .spinner {
            width: 18px; height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 640px) {
            .login-wrapper { grid-template-columns: 1fr; }
            .login-hero { display: none; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- Hero Panel -->
        <div class="login-hero">
            <div class="hero-logo">
                <div class="hero-logo-icon">P</div>
                <span class="hero-logo-text">PMA<span>-R</span></span>
            </div>

            <div class="hero-content">
                <h1>Evaluaciones<br><em>Psicométricas</em><br>Digitales</h1>
                <p>Sistema de automatización de la batería PMA-R para evaluación de aptitudes mentales primarias.</p>
                <div class="hero-factors">
                    <div class="hero-factor">
                        <div class="hero-factor-icon">V</div>
                        <div class="hero-factor-text"><strong>Verbal</strong>50 ítems</div>
                    </div>
                    <div class="hero-factor">
                        <div class="hero-factor-icon">E</div>
                        <div class="hero-factor-text"><strong>Espacial</strong>20 ítems</div>
                    </div>
                    <div class="hero-factor">
                        <div class="hero-factor-icon">R</div>
                        <div class="hero-factor-text"><strong>Razonamiento</strong>30 ítems</div>
                    </div>
                    <div class="hero-factor">
                        <div class="hero-factor-icon">N</div>
                        <div class="hero-factor-text"><strong>Numérico</strong>70 ítems</div>
                    </div>
                </div>
            </div>

            <div class="hero-footer">© Uniempresarial · Sistema PMA-R v1.0</div>
        </div>

        <!-- Form Panel -->
        <div class="login-form-panel">
            <div class="form-header">
                <h2>Bienvenido</h2>
                <p>Ingresa con tus credenciales para continuar</p>
            </div>

            @if($error ?? false)
            <div class="alert-error">⚠ {{ $error }}</div>
            @endif

            <form id="loginForm" action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="tu@correo.com" required
                           value="{{ old('email') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn-login" id="btnLogin">
                    <span id="btnText">Iniciar Sesión</span>
                    <div class="spinner" id="spinner"></div>
                </button>
            </form>

            <div class="form-footer">
                ¿No tienes cuenta?
                <a href="{{ route('register') }}">Regístrate aquí</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function() {
            document.getElementById('btnText').textContent = 'Ingresando...';
            document.getElementById('spinner').style.display = 'block';
            document.getElementById('btnLogin').disabled = true;
        });
    </script>
</body>
</html>
