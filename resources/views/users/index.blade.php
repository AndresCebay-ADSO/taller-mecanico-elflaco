<x-app-layout>
    <x-page-header title="Usuarios" subtitle="Accesos al sistema, roles y sucursales asignadas.">
        <x-slot name="actions">
            <x-button variant="primary" onclick="window.location.href='{{ route('users.create') }}'">
                <i data-lucide="user-plus" class="mr-2 h-4 w-4"></i>
                Nuevo Usuario
            </x-button>
        </x-slot>
    </x-page-header>

    <x-card class="overflow-hidden p-0">
        <x-table :headers="['USUARIO', 'ROL', 'SUCURSALES', 'ESTADO', 'ACCIONES']">
            @forelse($users as $user)
                <tr class="{{ $user->is_active ? '' : 'opacity-60' }}">
                    <td class="px-6 py-4">
                        <div class="text-sm font-bold text-slate-900 dark:text-white">{{ $user->name }}</div>
                        <div class="text-xs text-slate-500">{{ $user->email }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <x-badge :variant="$user->is_admin ? 'blue' : 'slate'">{{ $user->is_admin ? 'Administrador' : 'Empleado' }}</x-badge>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600 dark:text-gray-400">
                        {{ $user->is_admin ? 'Todas' : ($user->branches->pluck('name')->join(', ') ?: 'Sin asignar') }}
                    </td>
                    <td class="px-6 py-4">
                        <x-badge :variant="$user->is_active ? 'emerald' : 'red'">{{ $user->is_active ? 'Activo' : 'Inactivo' }}</x-badge>
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('users.edit', $user) }}" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors" title="Editar">
                                <i data-lucide="pencil" class="h-4 w-4"></i>
                            </a>
                            @unless($user->is(auth()->user()))
                                <form action="{{ route('users.toggle-active', $user) }}" method="POST" class="inline"
                                    onsubmit="return confirm('¿{{ $user->is_active ? 'Desactivar' : 'Activar' }} a {{ $user->name }}?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" title="{{ $user->is_active ? 'Desactivar' : 'Activar' }}"
                                        class="rounded-lg p-2 {{ $user->is_active ? 'text-red-400 hover:bg-red-50 hover:text-red-600' : 'text-emerald-500 hover:bg-emerald-50' }} transition-colors cursor-pointer">
                                        <i data-lucide="{{ $user->is_active ? 'user-x' : 'user-check' }}" class="h-4 w-4"></i>
                                    </button>
                                </form>
                            @endunless
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">No hay usuarios registrados.</td>
                </tr>
            @endforelse
        </x-table>
        @if($users->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 dark:border-gray-800">
                {{ $users->links() }}
            </div>
        @endif
    </x-card>
</x-app-layout>
