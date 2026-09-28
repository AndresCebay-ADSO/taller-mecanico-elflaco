<x-app-layout>
    <x-page-header title="Editar Usuario" subtitle="{{ $user->email }}">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('users.index') }}'">
                Cancelar
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="max-w-3xl">
        <x-card>
            <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-6">
                @method('PUT')
                @include('users.partials.form')

                <div class="flex justify-end pt-4">
                    <x-button variant="primary" type="submit" class="w-full md:w-auto">
                        Guardar Cambios
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
