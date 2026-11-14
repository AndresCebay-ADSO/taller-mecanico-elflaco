<x-app-layout>
<div class="ds">
    <x-page-header title="Usuarios" subtitle="Quién entra al sistema, con qué rol y en qué sucursal">
        <x-slot name="actions">
            <x-button href="{{ route('users.create') }}">Nuevo usuario</x-button>
        </x-slot>
    </x-page-header>

    <x-card flush>
        @if($users->isNotEmpty())
            <x-table :headers="['Usuario', 'Rol', 'Sucursales', 'Estado', ['label' => '', 'align' => 'right']]">
                @foreach($users as $user)
                    <tr @class(['transition-colors hover:bg-muted/50', 'text-muted-foreground' => ! $user->is_active])>
                        <td class="px-4 py-3">
                            <a href="{{ route('users.edit', $user) }}" class="font-medium text-primary hover:underline">{{ $user->name }}</a>
                            @if($user->is(auth()->user()))
                                <span class="text-xs text-muted-foreground">(tú)</span>
                            @endif
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ $user->email }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $user->is_admin ? 'Administrador' : 'Empleado' }}</td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ $user->is_admin ? 'Todas' : ($user->branches->pluck('name')->join(', ') ?: 'Sin asignar') }}
                        </td>
                        <td class="px-4 py-3"><x-active-badge :active="$user->is_active" /></td>
                        <td class="whitespace-nowrap px-4 py-2 text-right">
                            @unless($user->is(auth()->user()))
                                <form action="{{ route('users.toggle-active', $user) }}" method="POST"
                                      data-confirm="¿{{ $user->is_active ? 'Desactivar' : 'Activar' }} a {{ $user->name }}?"
                                      data-confirm-description="{{ $user->is_active ? 'No podrá iniciar sesión y, si está conectado, su sesión se cierra.' : 'Podrá volver a iniciar sesión.' }}"
                                      data-confirm-label="{{ $user->is_active ? 'Desactivar' : 'Activar' }}"
                                      @if($user->is_active) data-confirm-destructive @endif>
                                    @csrf
                                    @method('PATCH')
                                    <x-button type="submit" variant="secondary" size="sm">{{ $user->is_active ? 'Desactivar' : 'Activar' }}</x-button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @else
            <div class="p-5">
                <x-empty-state icon="shield">Aún no hay usuarios.</x-empty-state>
            </div>
        @endif

        @if($users->hasPages())
            <x-slot name="footer">{{ $users->links() }}</x-slot>
        @endif
    </x-card>
</div>
</x-app-layout>
