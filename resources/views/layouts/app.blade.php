<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PMA-R Sistema') — Uniempresarial</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy:    #0f1f3d;
            --navy2:   #1a3a6b;
            --blue:    #2e75b6;
            --sky:     #5b9bd5;
            --accent:  #e8a020;
            --green:   #107c10;
            --red:     #c50f1f;
            --cream:   #f5f0e8;
            --white:   #ffffff;
            --gray50:  #f8f9fa;
            --gray100: #eef1f5;
            --gray300: #c8d0dc;
            --gray500: #6b7a8d;
            --gray700: #3d4a5c;
            --shadow:  0 4px 24px rgba(15,31,61,0.10);
            --shadow-lg: 0 12px 48px rgba(15,31,61,0.16);
            --radius:  12px;
            --radius-lg: 20px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-size: 16px; scroll-behavior: smooth; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--gray50);
            color: var(--gray700);
            min-height: 100vh;
        }

        /* ── NAV ── */
        .nav {
            background: var(--navy);
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 64px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 12px rgba(0,0,0,0.2);
        }
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        .nav-logo {
            width: 36px; height: 36px;
            background: var(--accent);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-family: 'DM Serif Display', serif;
            font-size: 1.1rem;
            color: var(--navy);
            font-weight: bold;
        }
        .nav-title {
            font-family: 'DM Serif Display', serif;
            color: var(--white);
            font-size: 1.1rem;
            letter-spacing: 0.01em;
        }
        .nav-title span { color: var(--accent); }
        .nav-links { display: flex; align-items: center; gap: 0.5rem; }
        .nav-link {
            color: var(--gray300);
            text-decoration: none;
            padding: 0.4rem 0.9rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        .nav-link:hover { color: var(--white); background: rgba(255,255,255,0.08); }
        .nav-link.active { color: var(--accent); }
        .nav-badge {
            background: var(--accent);
            color: var(--navy);
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
            margin-left: 4px;
        }
        .btn-logout {
            background: rgba(197,15,31,0.15);
            color: #ff6b7a;
            border: 1px solid rgba(197,15,31,0.3);
            padding: 0.4rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-logout:hover { background: var(--red); color: white; }

        /* ── MAIN ── */
        .main { max-width: 1200px; margin: 0 auto; padding: 2rem; }

        /* ── CARDS ── */
        .card {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            padding: 2rem;
            border: 1px solid var(--gray100);
        }
        .card-sm { padding: 1.25rem 1.5rem; }

        /* ── BUTTONS ── */
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 0.65rem 1.5rem;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: all 0.2s;
            font-family: 'DM Sans', sans-serif;
        }
        .btn-primary { background: var(--navy2); color: white; }
        .btn-primary:hover { background: var(--blue); transform: translateY(-1px); box-shadow: 0 4px 16px rgba(46,117,182,0.3); }
        .btn-accent { background: var(--accent); color: var(--navy); }
        .btn-accent:hover { background: #d4911c; transform: translateY(-1px); }
        .btn-outline { background: transparent; color: var(--navy2); border: 2px solid var(--navy2); }
        .btn-outline:hover { background: var(--navy2); color: white; }
        .btn-success { background: var(--green); color: white; }
        .btn-danger  { background: var(--red); color: white; }
        .btn-lg { padding: 0.85rem 2rem; font-size: 1rem; }
        .btn-sm { padding: 0.4rem 0.9rem; font-size: 0.8rem; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none !important; }

        /* ── FORMS ── */
        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; font-size: 0.875rem; font-weight: 600; color: var(--gray700); margin-bottom: 0.4rem; }
        .form-control {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 2px solid var(--gray100);
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: 'DM Sans', sans-serif;
            color: var(--gray700);
            background: var(--white);
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .form-control:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(46,117,182,0.1); }

        /* ── ALERTS ── */
        .alert { padding: 0.9rem 1.2rem; border-radius: 10px; font-size: 0.9rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 10px; }
        .alert-error   { background: #fef2f2; color: var(--red); border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: var(--green); border: 1px solid #bbf7d0; }
        .alert-info    { background: #eff6ff; color: var(--blue); border: 1px solid #bfdbfe; }

        /* ── BADGES ── */
        .badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-blue   { background: #dbeafe; color: var(--blue); }
        .badge-green  { background: #dcfce7; color: var(--green); }
        .badge-red    { background: #fee2e2; color: var(--red); }
        .badge-orange { background: #fef3c7; color: #92400e; }
        .badge-gray   { background: var(--gray100); color: var(--gray500); }

        /* ── TABLA ── */
        .table-wrap { overflow-x: auto; border-radius: var(--radius); }
        table { width: 100%; border-collapse: collapse; }
        th { background: var(--navy); color: white; padding: 0.8rem 1rem; text-align: left; font-size: 0.8rem; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; }
        td { padding: 0.85rem 1rem; border-bottom: 1px solid var(--gray100); font-size: 0.9rem; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--gray50); }

        /* ── UTILITIES ── */
        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .text-muted  { color: var(--gray500); font-size: 0.875rem; }
        .text-navy   { color: var(--navy2); }
        .text-accent { color: var(--accent); }
        .fw-bold     { font-weight: 700; }
        .serif       { font-family: 'DM Serif Display', serif; }
        .mono        { font-family: 'JetBrains Mono', monospace; }
        .mt-1 { margin-top: 0.5rem; }
        .mt-2 { margin-top: 1rem; }
        .mt-3 { margin-top: 1.5rem; }
        .mb-1 { margin-bottom: 0.5rem; }
        .mb-2 { margin-bottom: 1rem; }
        .mb-3 { margin-bottom: 1.5rem; }
        .d-flex { display: flex; }
        .align-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .gap-1 { gap: 0.5rem; }
        .gap-2 { gap: 1rem; }
        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }

        /* ── PAGE HEADER ── */
        .page-header { margin-bottom: 2rem; }
        .page-header h1 { font-family: 'DM Serif Display', serif; font-size: 2rem; color: var(--navy); margin-bottom: 0.25rem; }
        .page-header p  { color: var(--gray500); font-size: 0.95rem; }

        /* ── LOADING ── */
        .spinner { width: 20px; height: 20px; border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: spin 0.7s linear infinite; display: inline-block; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── FLASH MESSAGES ── */
        .flash { position: fixed; top: 80px; right: 1.5rem; z-index: 999; max-width: 360px; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

        @media (max-width: 768px) {
            .main { padding: 1rem; }
            .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; }
            .nav-links .nav-link span { display: none; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <nav class="nav">
        <a href="{{ route('dashboard') }}" class="nav-brand">
            <div class="nav-logo">P</div>
            <span class="nav-title">PMA<span>-R</span></span>
        </a>
        @auth
        <div class="nav-links">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span>Dashboard</span>
            </a>
            <a href="{{ route('pruebas.index') }}" class="nav-link {{ request()->routeIs('pruebas.*') ? 'active' : '' }}">
                <span>Pruebas</span>
            </a>
            <a href="{{ route('sesiones.index') }}" class="nav-link {{ request()->routeIs('sesiones.*') ? 'active' : '' }}">
                <span>Mis Sesiones</span>
            </a>
            @if(session('user_role') === 'admin' || session('user_role') === 'evaluador')
            <a href="{{ route('estadisticas') }}" class="nav-link {{ request()->routeIs('estadisticas') ? 'active' : '' }}">
                <span>Estadísticas</span>
            </a>
            @endif
            <span class="text-muted" style="color:#aaa;font-size:0.8rem;padding:0 0.5rem">
                {{ session('user_name', 'Usuario') }}
                <span class="nav-badge">{{ strtoupper(session('user_role', '')) }}</span>
            </span>
            <a href="{{ route('logout') }}" class="btn-logout">Salir</a>
        </div>
        @endauth
    </nav>

    @if(session('flash_success'))
    <div class="flash">
        <div class="alert alert-success">✅ {{ session('flash_success') }}</div>
    </div>
    @endif
    @if(session('flash_error'))
    <div class="flash">
        <div class="alert alert-error">❌ {{ session('flash_error') }}</div>
    </div>
    @endif

    <main class="main">
        @yield('content')
    </main>

    <script>
        // Auto-hide flash messages
        setTimeout(() => {
            document.querySelectorAll('.flash').forEach(el => {
                el.style.transition = 'opacity 0.5s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 500);
            });
        }, 3500);
    </script>
    @stack('scripts')
</body>
</html>
