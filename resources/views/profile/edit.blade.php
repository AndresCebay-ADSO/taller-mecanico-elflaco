<x-app-layout>
<div class="ds mx-auto max-w-4xl">
    <x-page-header title="Mi perfil" subtitle="Tu cuenta y tu contraseña" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-card title="Cuenta">
            <dl class="space-y-4 text-sm">
                <div>
                    <dt class="text-xs font-medium text-muted-foreground">Nombre</dt>
                    <dd class="mt-0.5">{{ $user->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-muted-foreground">Correo</dt>
                    <dd class="mt-0.5 break-all">{{ $user->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-muted-foreground">Rol</dt>
                    <dd class="mt-0.5">{{ $user->is_admin ? 'Administrador' : 'Empleado' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-muted-foreground">Sucursal activa</dt>
                    <dd class="mt-0.5">{{ $currentBranch?->name ?? 'Sin sucursal' }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card title="Cambiar contraseña" class="lg:col-span-2">
            <form action="{{ route('profile.password') }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <x-input label="Contraseña actual" name="current_password" type="password" required autocomplete="current-password" />
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-input label="Nueva contraseña" name="password" type="password" required autocomplete="new-password" help="Mínimo 8 caracteres." />
                    <x-input label="Confirmar nueva contraseña" name="password_confirmation" type="password" required autocomplete="new-password" />
                </div>
                <div class="flex justify-end pt-2">
                    <x-button type="submit">Cambiar contraseña</x-button>
                </div>
            </form>
        </x-card>
    </div>
</div>
</x-app-layout>
