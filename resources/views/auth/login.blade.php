<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="h-full"
    x-data="{
        darkMode: localStorage.getItem('darkMode') === 'true',
    }"
    :class="{ 'dark': darkMode }"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Iniciar Sesión — {{ config('app.name', 'MotoTaller') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Scripts / Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        [x-cloak] { display: none !important; }

        body {
            font-family: 'Inter', sans-serif;
            background: #030712;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 1.25rem;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
        }

        .login-input {
            width: 100%;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 0.625rem;
            color: #f1f5f9;
            font-size: 0.875rem;
            padding: 0.75rem 1rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .login-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        .login-input::placeholder {
            color: #475569;
        }

        .login-input.error {
            border-color: #ef4444;
        }

        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            font-weight: 700;
            font-size: 0.9rem;
            padding: 0.8rem 1rem;
            border-radius: 0.625rem;
            border: none;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.1s;
            letter-spacing: 0.02em;
        }

        .login-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .error-msg {
            color: #f87171;
            font-size: 0.78rem;
            margin-top: 0.35rem;
        }

        label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #94a3b8;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 0.4rem;
        }
    </style>
</head>
<body>

    <div class="login-card">

        {{-- Logo / Brand --}}
        <div style="text-align:center; margin-bottom: 2rem;">
            <div style="display:inline-flex; align-items:center; justify-content:center;
                        width:3.5rem; height:3.5rem; border-radius:1rem;
                        background: linear-gradient(135deg, #6366f1, #8b5cf6);
                        box-shadow: 0 8px 24px rgba(99,102,241,0.35);
                        margin-bottom: 1rem;">
                <i data-lucide="bike" style="width:1.6rem; height:1.6rem; color:white;"></i>
            </div>
            <h1 style="font-size:1.6rem; font-weight:800; color:#f1f5f9; letter-spacing:-0.02em; margin:0;">
                MotoTaller
            </h1>
            <p style="font-size:0.8rem; color:#475569; font-weight:600; letter-spacing:0.1em; text-transform:uppercase; margin-top:0.25rem;">
                Sistema de Gestión
            </p>
        </div>

        {{-- Session Status --}}
        @if (session('status'))
            <div style="background:#052e16; border:1px solid #14532d; border-radius:0.5rem; padding:0.75rem 1rem; margin-bottom:1.25rem;">
                <p style="color:#4ade80; font-size:0.85rem; margin:0;">{{ session('status') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" style="display:flex; flex-direction:column; gap:1.25rem;">
            @csrf

            {{-- Email --}}
            <div>
                <label for="email">Correo Electrónico</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    class="login-input {{ $errors->has('email') ? 'error' : '' }}"
                    value="{{ old('email') }}"
                    placeholder="admin@ejemplo.com"
                    required
                    autofocus
                    autocomplete="username"
                >
                @error('email')
                    <p class="error-msg">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <label for="password">Contraseña</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="login-input {{ $errors->has('password') ? 'error' : '' }}"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                >
                @error('password')
                    <p class="error-msg">{{ $message }}</p>
                @enderror
            </div>

            {{-- Remember Me --}}
            <div style="display:flex; align-items:center; gap:0.5rem;">
                <input
                    id="remember_me"
                    type="checkbox"
                    name="remember"
                    style="accent-color:#6366f1; width:1rem; height:1rem; cursor:pointer;"
                >
                <label for="remember_me" style="text-transform:none; letter-spacing:normal; color:#64748b; font-size:0.85rem; font-weight:500; cursor:pointer; margin:0;">
                    Recordarme
                </label>
            </div>

            {{-- Submit --}}
            <button type="submit" class="login-btn">
                Iniciar Sesión
            </button>
        </form>

        {{-- Footer --}}
        <p style="text-align:center; color:#334155; font-size:0.75rem; margin-top:2rem; margin-bottom:0;">
            Taller Mecánico El Flaco &copy; {{ date('Y') }}
        </p>

    </div>

    <script>lucide.createIcons();</script>

</body>
</html>
