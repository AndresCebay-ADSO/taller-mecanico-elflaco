<x-app-layout>
<div class="ds mx-auto max-w-3xl">
    <x-page-header :title="'Editar ' . $user->name" :subtitle="$user->email"
                   :back="route('users.index')" back-label="Usuarios">
        <x-slot name="badge"><x-active-badge :active="$user->is_active" /></x-slot>
    </x-page-header>

    <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-6">
        @method('PUT')
        @include('users.partials.form')

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
            <x-button variant="secondary" href="{{ route('users.index') }}">Cancelar</x-button>
            <x-button type="submit">Guardar cambios</x-button>
        </div>
    </form>
</div>
</x-app-layout>
