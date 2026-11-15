{{--
    Páginas de error en español (403, 404, 419, 429, 500, 503). Independientes del layout de la app: se muestran
    aunque no haya sesión o la base no responda. Si la interfaz no está compilada, se ven sin estilos pero se leen.
--}}
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <script>
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('app.name', 'MotoTaller') }}</title>
    @if(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @endif
</head>
<body class="ds flex min-h-full items-center justify-center bg-background px-4 py-16 font-sans text-foreground antialiased">
    <main class="w-full max-w-md text-center">
        <p class="text-sm font-medium text-muted-foreground">Error @yield('code')</p>
        <h1 class="mt-2 text-2xl font-semibold">@yield('title')</h1>
        <p class="mt-3 text-sm text-muted-foreground">@yield('message')</p>
        <div class="mt-8 flex flex-col justify-center gap-2 sm:flex-row">
            <a href="javascript:history.back()"
               class="inline-flex h-10 items-center justify-center rounded-md border border-border bg-card px-4 text-sm font-medium shadow-xs hover:bg-muted">Volver</a>
            <a href="{{ url('/') }}"
               class="inline-flex h-10 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-xs hover:bg-primary-hover">Ir al inicio</a>
        </div>
    </main>
</body>
</html>
