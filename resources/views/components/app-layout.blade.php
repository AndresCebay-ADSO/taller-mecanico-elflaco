<!DOCTYPE html>
<html 
    lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
    class="h-full"
    x-data="{ 
        darkMode: localStorage.getItem('darkMode') === 'true',
        toggleTheme() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('darkMode', this.darkMode);
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    }"
    :class="{ 'dark': darkMode }"
>
<head>
    <script>
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'MotoTaller') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        [x-cloak] { display: none !important; }
        body { opacity: 0; }
    </style>
</head>
<body class="h-full font-sans antialiased">
    <div class="flex h-full overflow-hidden">
        <!-- Sidebar -->
        <x-sidebar />

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <div class="py-6">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();

        // Page transitions
        document.addEventListener('DOMContentLoaded', () => {
            // Fade in al cargar
            document.body.style.transition = 'opacity 0.15s ease';
            document.body.style.opacity = '1';
        });

        document.addEventListener('click', e => {
            const link = e.target.closest('a[href]');
            if (!link) return;
            
            const href = link.getAttribute('href');
            
            // Ignorar: links externos, anclas, target blank, links de logout/forms
            if (!href || 
                href.startsWith('#') || 
                href.startsWith('http') || 
                href.startsWith('mailto') ||
                link.target === '_blank' ||
                link.closest('form')) return;

            e.preventDefault();
            document.body.style.transition = 'opacity 0.15s ease';
            document.body.style.opacity = '0';
            setTimeout(() => { window.location.href = href; }, 150);
        });

        // Fade in también al usar botón atrás del navegador
        window.addEventListener('pageshow', () => {
            document.body.style.transition = 'opacity 0.15s ease';
            document.body.style.opacity = '1';
        });
    </script>
</body>
</html>
