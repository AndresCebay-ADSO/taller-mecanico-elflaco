<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full"
      x-data="{
          darkMode: localStorage.getItem('darkMode') === 'true',
          toggleTheme() {
              this.darkMode = !this.darkMode;
              localStorage.setItem('darkMode', this.darkMode);
              document.documentElement.classList.toggle('dark', this.darkMode);
          },
      }">
<head>
    {{-- Antes de pintar: el tema oscuro se aplica sin parpadeo. --}}
    <script>
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Iniciar sesión — {{ config('app.name', 'MotoTaller') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="ds flex min-h-full flex-col bg-background font-sans text-foreground antialiased">
    <div class="flex justify-end p-4">
        <button type="button" @click="toggleTheme()"
                class="cursor-pointer rounded-md p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                :title="darkMode ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro'">
            <span x-show="darkMode" class="block"><i data-lucide="sun" class="size-4"></i></span>
            <span x-show="!darkMode" class="block"><i data-lucide="moon" class="size-4"></i></span>
            <span class="sr-only" x-text="darkMode ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro'"></span>
        </button>
    </div>

    <main class="flex flex-1 items-center justify-center px-4 pb-16">
        <div class="w-full max-w-sm">
            <div class="mb-8 flex flex-col items-center text-center">
                <span class="mb-4 flex size-10 items-center justify-center rounded-md bg-primary text-primary-foreground">
                    <i data-lucide="bike" class="size-5"></i>
                </span>
                <h1 class="text-2xl font-semibold tracking-tight">Iniciar sesión</h1>
                <p class="mt-1 text-sm text-muted-foreground">MotoTaller El Flaco</p>
            </div>

            <div class="rounded-lg border border-border bg-card p-6 shadow-xs">
                @if (session('status'))
                    <x-alert type="success" :message="session('status')" />
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <x-input label="Correo" name="email" type="email" autocomplete="email" required autofocus />

                    <div class="space-y-1.5" x-data="{ showPassword: false }">
                        <label for="password" class="block text-sm font-medium">Contraseña<span class="text-destructive-text"> *</span></label>
                        <div class="relative">
                            <input id="password" name="password" :type="showPassword ? 'text' : 'password'" type="password"
                                   autocomplete="current-password" required
                                   @error('password') aria-invalid="true" @enderror
                                   @class([
                                       'block h-10 w-full rounded-md border bg-card pl-3 pr-10 text-sm text-foreground shadow-xs focus:outline-none focus:ring-2',
                                       'border-input focus:border-ring focus:ring-ring/30' => ! $errors->has('password'),
                                       'border-destructive focus:ring-destructive/20' => $errors->has('password'),
                                   ])>
                            <button type="button" @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 flex cursor-pointer items-center px-3 text-muted-foreground transition-colors hover:text-foreground"
                                    :title="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                                <span x-show="!showPassword" class="block"><i data-lucide="eye" class="size-4"></i></span>
                                <span x-show="showPassword" x-cloak class="block"><i data-lucide="eye-off" class="size-4"></i></span>
                                <span class="sr-only" x-text="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"></span>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-xs text-destructive-text">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-checkbox name="remember" id="remember_me" label="Mantener la sesión iniciada" />

                    <x-button type="submit" class="w-full">Entrar</x-button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
