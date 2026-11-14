<x-app-layout>
<div class="ds mx-auto max-w-3xl">
    <x-page-header title="Nuevo mecánico" :back="route('mechanics.index')" back-label="Mecánicos" />

    <form action="{{ route('mechanics.store') }}" method="POST" class="space-y-6">
        @csrf
        @include('mechanics.partials.form')

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
            <x-button variant="secondary" href="{{ route('mechanics.index') }}">Cancelar</x-button>
            <x-button type="submit">Registrar mecánico</x-button>
        </div>
    </form>
</div>
</x-app-layout>
