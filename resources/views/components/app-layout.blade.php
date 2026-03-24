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

    @if(session('show_low_stock_toast'))
    <script>
        sessionStorage.removeItem('hideLowStockToast_{{ session()->getId() }}');
    </script>
    @endif

    @if(isset($lowStockCount) && $lowStockCount > 0)
        <!-- Low Stock Toast Notificaton -->
        <div x-data="{ 
                showToast: false,
                sessionKey: 'hideLowStockToast_{{ session()->getId() }}',
                init() {
                    if (!sessionStorage.getItem(this.sessionKey)) {
                        setTimeout(() => this.showToast = true, 500);
                    }
                },
                closeToast() {
                    this.showToast = false;
                    sessionStorage.setItem(this.sessionKey, 'true');
                }
            }"
            x-show="showToast"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-full"
            x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed bottom-4 right-4 z-50 w-full max-w-sm rounded-2xl bg-white p-4 shadow-2xl ring-1 ring-slate-200 dark:bg-slate-800 dark:ring-slate-700 sm:bottom-6 sm:right-6"
            x-cloak>
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-500/20">
                        <i data-lucide="alert-triangle" class="h-5 w-5 text-amber-600 dark:text-amber-500"></i>
                    </div>
                </div>
                <div class="flex-1 pt-0.5">
                    <p class="text-sm font-bold text-slate-900 dark:text-white">Stock bajo detectado</p>
                    <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">
                        {{ $lowStockCount }} producto(s) necesitan reposición.
                    </p>
                    <div class="mt-3 flex gap-3">
                        <a href="{{ route('products.index', ['low_stock' => 1]) }}" class="text-sm font-bold text-brand-600 hover:text-brand-500 dark:text-brand-400 dark:hover:text-brand-300 transition-colors">
                            Ver inventario
                        </a>
                    </div>
                </div>
                <div class="ml-4 flex flex-shrink-0">
                    <button type="button" @click="closeToast" class="inline-flex rounded-md text-slate-400 hover:text-slate-500 focus:outline-none dark:text-slate-500 dark:hover:text-slate-400 cursor-pointer">
                        <span class="sr-only">Cerrar</span>
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
            </div>
        </div>
    @endif

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
