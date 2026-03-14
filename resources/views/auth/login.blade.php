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
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Scripts / Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100 antialiased font-sans transition-colors duration-500">
    <div class="flex min-h-screen">
        <!-- Lado Izquierdo: Formulario de Login -->
        <div class="flex flex-1 flex-col justify-center px-6 py-12 sm:px-12 lg:flex-none lg:w-1/2 xl:px-24">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                
                <!-- Identidad MotoTaller (Solo visible en móviles) -->
                <div class="lg:hidden mb-10 flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-500 shadow-xl shadow-brand-500/20">
                        <i data-lucide="bike" class="h-7 w-7 text-white"></i>
                    </div>
                    <span class="text-2xl font-black tracking-tight text-gray-900 dark:text-white leading-none">MotoTaller</span>
                </div>

                <div class="mb-10">
                    <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Iniciar Sesión</h2>
                    <p class="mt-2 text-sm text-slate-500 dark:text-gray-400 font-medium">
                        Ingresa tu correo y contraseña para acceder al panel.
                    </p>
                </div>

                @if (session('status'))
                    <div class="mb-6 rounded-xl bg-success-50 p-4 dark:bg-success-500/10 border border-success-200 dark:border-success-500/20">
                        <p class="text-sm font-medium text-success-800 dark:text-success-400">{{ session('status') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf
                    
                    <div>
                        <label for="email" class="block text-sm font-bold text-slate-700 dark:text-gray-300">
                            Correo Electrónico <span class="text-error-500">*</span>
                        </label>
                        <div class="mt-2">
                            <input 
                                id="email" 
                                name="email" 
                                type="email" 
                                autocomplete="email" 
                                required 
                                placeholder="taller@ejemplo.com" 
                                value="{{ old('email') }}"
                                class="block w-full rounded-2xl border border-slate-300 px-4 py-3.5 bg-white text-slate-900 shadow-theme-xs transition-all duration-200 placeholder:text-slate-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 sm:text-sm dark:bg-gray-900 dark:border-gray-800 dark:text-white dark:placeholder:text-gray-600 dark:focus:border-brand-500 dark:focus:ring-brand-500/20 @error('email') border-error-500 focus:border-error-500 focus:ring-error-500/10 @enderror"
                            >
                        </div>
                        @error('email')
                            <p class="mt-1 text-sm font-medium text-error-600 dark:text-error-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-bold text-slate-700 dark:text-gray-300">
                            Contraseña <span class="text-error-500">*</span>
                        </label>
                        <div class="mt-2">
                            <input 
                                id="password" 
                                name="password" 
                                type="password" 
                                autocomplete="current-password" 
                                required 
                                placeholder="••••••••"
                                class="block w-full rounded-2xl border border-slate-300 px-4 py-3.5 bg-white text-slate-900 shadow-theme-xs transition-all duration-200 placeholder:text-slate-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 sm:text-sm dark:bg-gray-900 dark:border-gray-800 dark:text-white dark:placeholder:text-gray-600 dark:focus:border-brand-500 dark:focus:ring-brand-500/20 @error('password') border-error-500 focus:border-error-500 focus:ring-error-500/10 @enderror"
                            >
                        </div>
                        @error('password')
                            <p class="mt-1 text-sm font-medium text-error-600 dark:text-error-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <input 
                                id="remember_me" 
                                name="remember" 
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:ring-offset-gray-950 cursor-pointer"
                            >
                            <label for="remember_me" class="block text-sm font-medium text-slate-600 dark:text-gray-400 cursor-pointer select-none">
                                Mantener sesión iniciada
                            </label>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                            class="flex w-full justify-center rounded-2xl bg-brand-600 px-4 py-4 text-sm font-bold text-white shadow-theme-sm transition-all hover:bg-brand-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 active:scale-[0.98]">
                            Iniciar Sesión
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lado Derecho: Branding (Premium Blue Background) -->
        <div class="relative hidden w-0 flex-1 lg:block bg-brand-950 overflow-hidden">
            <!-- Decorative Subtle Grid Pattern -->
            <div class="absolute inset-0 opacity-[0.03] dark:opacity-5 mix-blend-overlay">
                <svg class="absolute inset-0 h-full w-full" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="grid-pattern" width="60" height="60" patternUnits="userSpaceOnUse">
                            <path d="M 60 0 L 0 0 0 60" fill="none" stroke="currentColor" stroke-width="1.5"/>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#grid-pattern)" />
                </svg>
            </div>
            
            <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-16 z-10 transition-transform duration-700 hover:scale-105">
                <div class="flex items-center justify-center gap-4 mb-6">
                    <div class="flex h-[72px] w-[72px] items-center justify-center rounded-2xl bg-brand-500 shadow-2xl shadow-brand-500/40">
                        <i data-lucide="bike" class="h-10 w-10 text-white"></i>
                    </div>
                    <span class="text-5xl font-black text-white tracking-tight">MotoTaller</span>
                </div>
                <h3 class="text-xl font-medium text-brand-200 max-w-lg mt-2">
                    Sistema de Gestión Profesional
                </h3>
                <p class="mt-6 text-brand-400/80 text-sm font-medium leading-relaxed max-w-md">
                    Control absoluto de inventario, órdenes de servicio, registro de ventas directas y administración de personal desde un solo lugar.
                </p>
            </div>

            <!-- Theme Toggle en la esquina inferior derecha -->
            <div class="absolute bottom-10 right-10 z-20">
                <button 
                    @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)" 
                    type="button" 
                    class="relative p-4 rounded-full border border-brand-800 bg-brand-900 text-brand-400 shadow-2xl transition-all hover:bg-brand-800 hover:scale-105 active:scale-95 hover:text-white"
                    title="Alternar tema"
                >
                    <i x-show="!darkMode" data-lucide="moon" class="h-6 w-6"></i>
                    <i x-show="darkMode" data-lucide="sun" x-cloak class="h-6 w-6 text-brand-200"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
