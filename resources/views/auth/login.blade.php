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
        
        /* Patrón geométrico sutil */
        .bg-pattern {
            background-image: radial-gradient(#ffffff0a 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
</head>
<body class="h-full bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100 antialiased font-sans transition-colors duration-500">
    <div class="flex min-h-screen">
        <!-- Lado Izquierdo: Formulario de Login -->
        <div class="flex flex-1 flex-col justify-between px-6 py-12 sm:px-12 lg:flex-none lg:w-1/2 xl:px-24 shadow-2xl z-20">
            <div class="my-auto">
                <div class="mx-auto w-full max-w-sm lg:w-96">
                    
                    <!-- Branding Logo -->
                    <div class="mb-10 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#3B3BF9] shadow-lg shadow-[#3B3BF9]/20">
                            <i data-lucide="bike" class="h-6 w-6 text-white"></i>
                        </div>
                        <span class="text-xl font-black tracking-tight text-slate-900 dark:text-white leading-none uppercase">MotoTaller</span>
                    </div>

                    <div class="mb-8">
                        <h2 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">Iniciar Sesión</h2>
                        <p class="mt-2 text-sm text-slate-500 dark:text-gray-400 font-medium">
                            Bienvenido de nuevo — Ingresa tus credenciales para continuar.
                        </p>
                    </div>

                    @if (session('status'))
                        <div class="mb-6 rounded-xl bg-green-50 p-4 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20">
                            <p class="text-sm font-medium text-green-800 dark:text-green-400">{{ session('status') }}</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-6">
                        @csrf
                        
                        <div>
                            <label for="email" class="block text-sm font-bold text-slate-700 dark:text-gray-300">
                                Correo Electrónico <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-2 text-slate-500">
                                <input 
                                    id="email" 
                                    name="email" 
                                    type="email" 
                                    autocomplete="email" 
                                    required 
                                    placeholder="taller@elflaco.com" 
                                    value="{{ old('email') }}"
                                    class="block w-full rounded-2xl border border-slate-300 px-4 py-3.5 bg-[#F8F9FA] text-slate-900 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:border-[#3B3BF9] focus:ring-4 focus:ring-[#3B3BF9]/10 sm:text-sm dark:bg-gray-900 dark:border-gray-800 dark:text-white dark:placeholder:text-gray-600 dark:focus:border-brand-500 dark:focus:ring-brand-500/20 @error('email') border-red-500 focus:border-red-500 focus:ring-red-500/10 @enderror"
                                >
                            </div>
                            @error('email')
                                <p class="mt-1 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-bold text-slate-700 dark:text-gray-300">
                                Contraseña <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-2 relative" x-data="{ showPassword: false }">
                                <input 
                                    id="password" 
                                    name="password" 
                                    :type="showPassword ? 'text' : 'password'" 
                                    autocomplete="current-password" 
                                    required 
                                    placeholder="••••••••"
                                    class="block w-full rounded-2xl border border-slate-300 px-4 py-3.5 bg-[#F8F9FA] text-slate-900 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:border-[#3B3BF9] focus:ring-4 focus:ring-[#3B3BF9]/10 sm:text-sm dark:bg-gray-900 dark:border-gray-800 dark:text-white dark:placeholder:text-gray-600 dark:focus:border-brand-500 dark:focus:ring-brand-500/20 @error('password') border-red-500 focus:border-red-500 focus:ring-red-500/10 @enderror"
                                >
                                <button 
                                    type="button" 
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-[#3B3BF9] transition-colors focus:outline-none cursor-pointer"
                                    title="Mostrar/Ocultar contraseña"
                                >
                                    <i x-show="!showPassword" data-lucide="eye" class="h-5 w-5"></i>
                                    <i x-show="showPassword" data-lucide="eye-off" class="h-5 w-5" x-cloak></i>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <input 
                                    id="remember_me" 
                                    name="remember" 
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-slate-300 text-[#3B3BF9] focus:ring-[#3B3BF9] dark:border-gray-700 dark:bg-gray-900 dark:ring-offset-gray-950 cursor-pointer"
                                >
                                <label for="remember_me" class="block text-sm font-medium text-slate-600 dark:text-gray-400 cursor-pointer select-none">
                                    Mantener sesión iniciada
                                </label>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                class="flex w-full justify-center rounded-2xl bg-[#3B3BF9] px-4 py-4 text-sm font-bold text-white shadow-lg shadow-blue-500/20 transition-all hover:bg-blue-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#3B3BF9] active:scale-[0.98] cursor-pointer">
                                Iniciar Sesión
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-auto pt-8 text-center lg:text-left">
                <p class="text-xs font-medium text-slate-400">
                    Sistema de Gestión de Taller Mecánico
                </p>
            </div>
        </div>

        <!-- Lado Derecho: Branding -->
        <div class="relative hidden w-0 flex-1 lg:block bg-[#0F1157] overflow-hidden">
            <!-- Patrón Geométrico Sutil -->
            <div class="absolute inset-0 bg-pattern opacity-20"></div>
            
            <!-- SVG Pattern Overlay -->
            <svg class="absolute inset-0 h-full w-full opacity-[0.05]" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="hexagons" width="50" height="43.3" patternUnits="userSpaceOnUse" patternTransform="scale(2)">
                        <path d="M25 0L50 14.4V43.3L25 57.7L0 43.3V14.4L25 0Z" fill="none" stroke="white" stroke-width="1" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#hexagons)" />
            </svg>
            
            <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-16 z-10">
                <div class="flex items-center justify-center gap-4 mb-8">
                    <div class="flex h-[80px] w-[80px] items-center justify-center rounded-2xl bg-[#3B3BF9] shadow-2xl shadow-blue-500/40">
                        <i data-lucide="bike" class="h-12 w-12 text-white"></i>
                    </div>
                </div>
                
                <h1 class="text-5xl font-black text-white tracking-tighter mb-4">
                    MotoTaller <span class="text-blue-400">El Flaco</span>
                </h1>
                
                <p class="text-xl font-medium text-blue-100/80 max-w-lg leading-relaxed">
                    Potenciando el rendimiento de tu taller con tecnología de punta.
                </p>

                <!-- Feature Highlights -->
                <div class="mt-12 grid grid-cols-3 gap-8 w-full max-w-2xl">
                    <div class="flex flex-col items-center gap-3">
                        <div class="h-12 w-12 rounded-full bg-white/10 flex items-center justify-center text-white">
                            <i data-lucide="package" class="h-6 w-6"></i>
                        </div>
                        <span class="text-sm font-bold text-white">Inventario</span>
                    </div>
                    <div class="flex flex-col items-center gap-3">
                        <div class="h-12 w-12 rounded-full bg-white/10 flex items-center justify-center text-white">
                            <i data-lucide="clipboard-list" class="h-6 w-6"></i>
                        </div>
                        <span class="text-sm font-bold text-white">Órdenes</span>
                    </div>
                    <div class="flex flex-col items-center gap-3">
                        <div class="h-12 w-12 rounded-full bg-white/10 flex items-center justify-center text-white">
                            <i data-lucide="shopping-cart" class="h-6 w-6"></i>
                        </div>
                        <span class="text-sm font-bold text-white">Ventas</span>
                    </div>
                </div>
            </div>

            <!-- Theme Toggle -->
            <div class="absolute bottom-10 right-10 z-20">
                <button 
                    @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)" 
                    type="button" 
                    class="relative p-4 rounded-full border border-white/10 bg-white/5 text-white/50 shadow-2xl transition-all hover:bg-white/10 hover:scale-110 active:scale-95 hover:text-white cursor-pointer"
                    title="Alternar tema"
                >
                    <i x-show="!darkMode" data-lucide="moon" class="h-6 w-6"></i>
                    <i x-show="darkMode" data-lucide="sun" x-cloak class="h-6 w-6 text-yellow-400"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>