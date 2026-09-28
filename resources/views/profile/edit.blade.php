<x-app-layout>
    <x-page-header title="Mi Perfil" subtitle="Datos de tu cuenta y cambio de contraseña." />

    <div class="grid max-w-5xl grid-cols-1 gap-8 lg:grid-cols-3">
        <x-card>
            <x-slot name="header">
                <h3 class="font-bold text-slate-900 dark:text-white">Cuenta</h3>
            </x-slot>
            <dl class="space-y-4 text-sm">
                <div>
                    <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Nombre</dt>
                    <dd class="mt-1 font-bold text-slate-900 dark:text-white">{{ $user->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Correo</dt>
                    <dd class="mt-1 font-bold text-slate-900 dark:text-white">{{ $user->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Rol</dt>
                    <dd class="mt-1 font-bold text-slate-900 dark:text-white">{{ $user->is_admin ? 'Administrador' : 'Empleado' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Sucursal activa</dt>
                    <dd class="mt-1 font-bold text-slate-900 dark:text-white">{{ $currentBranch?->name ?? 'Sin sucursal' }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card class="lg:col-span-2">
            <x-slot name="header">
                <h3 class="font-bold text-slate-900 dark:text-white">Cambiar contraseña</h3>
            </x-slot>
            <form action="{{ route('profile.password') }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')
                <x-input label="Contraseña actual" name="current_password" type="password" required autocomplete="current-password" />
                <x-input label="Nueva contraseña" name="password" type="password" required autocomplete="new-password" />
                <x-input label="Confirmar nueva contraseña" name="password_confirmation" type="password" required autocomplete="new-password" />
                <p class="text-xs text-slate-500">Mínimo 8 caracteres.</p>
                <div class="flex justify-end">
                    <x-button variant="primary" type="submit">Actualizar contraseña</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
