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

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    

    <style>
        [x-cloak] { display: none !important; }

        @media print {
            aside, .no-print { display: none !important; }
            html, body, main { height: auto !important; overflow: visible !important; background: #fff !important; }
            .invoice-sheet { border: 0 !important; box-shadow: none !important; max-width: 100% !important; padding: 0 !important; background: #fff !important; }
            .invoice-sheet, .invoice-sheet * { color: #0f172a !important; }
        }
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
                    <!-- Global Notifications -->
                    <div class="no-print">
                    @if(empty($currentBranch))
                        <x-alert type="warning" message="No tienes una sucursal activa asignada. Pide a un administrador que te asigne una para poder registrar operaciones." />
                    @endif

                    @if(session('success'))
                        <x-alert type="success" :message="session('success')" />
                    @endif

                    @if(session('info'))
                        <x-alert type="info" :message="session('info')" />
                    @endif

                    @if(session('error'))
                        <x-alert type="error" :message="session('error')" />
                    @endif

                    @if($errors->any())
                        <x-alert type="error">
                            <ul class="list-inside list-disc">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-alert>
                    @endif
                    </div>

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

    {{-- En el dashboard el aviso ya está en la página, y en formularios taparía el botón de guardar. --}}
    @if(isset($lowStockCount) && $lowStockCount > 0 && ! request()->routeIs('dashboard', '*.create', '*.edit', 'inventory.purchase', 'inventory.adjustment'))
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
            class="ds fixed bottom-4 right-4 z-40 w-full max-w-sm rounded-lg border border-border bg-card p-4 text-foreground shadow-lg sm:bottom-6 sm:right-6"
            role="status"
            x-cloak>
            <div class="flex items-start gap-3">
                <i data-lucide="alert-triangle" class="mt-0.5 size-4 shrink-0 text-warning-text"></i>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium">Stock bajo</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ $lowStockCount }} {{ $lowStockCount === 1 ? 'producto necesita' : 'productos necesitan' }} reposición.
                    </p>
                    <a href="{{ route('products.index', ['low_stock' => 1]) }}" class="mt-2 inline-block text-sm font-medium text-primary hover:underline">
                        Ver inventario
                    </a>
                </div>
                <button type="button" @click="closeToast" class="-m-1 shrink-0 rounded-md p-1 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground cursor-pointer" title="Cerrar">
                    <span class="sr-only">Cerrar</span>
                    <i data-lucide="x" class="size-4"></i>
                </button>
            </div>
        </div>
    @endif

    <x-confirm-dialog />

</body>
</html>
