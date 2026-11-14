<x-app-layout>
<div class="ds mx-auto max-w-3xl">
    <x-page-header title="Nuevo usuario" subtitle="Acceso para un empleado o un administrador"
                   :back="route('users.index')" back-label="Usuarios" />

    <form action="{{ route('users.store') }}" method="POST" class="space-y-6">
        @include('users.partials.form')

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
            <x-button variant="secondary" href="{{ route('users.index') }}">Cancelar</x-button>
            <x-button type="submit">Crear usuario</x-button>
        </div>
    </form>
</div>
</x-app-layout>
