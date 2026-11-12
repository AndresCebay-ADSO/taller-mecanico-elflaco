<x-app-layout>
<div class="ds mx-auto max-w-3xl">
    <x-page-header :title="'Editar ' . $mechanic->name" :back="route('mechanics.index')" back-label="Mecánicos">
        <x-slot name="badge"><x-active-badge :active="$mechanic->is_active" /></x-slot>
    </x-page-header>

    <form action="{{ route('mechanics.update', $mechanic) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        @include('mechanics.partials.form', ['mechanic' => $mechanic])

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
            <x-button variant="secondary" href="{{ route('mechanics.index') }}">Cancelar</x-button>
            <x-button type="submit">Guardar cambios</x-button>
        </div>
    </form>
</div>
</x-app-layout>
