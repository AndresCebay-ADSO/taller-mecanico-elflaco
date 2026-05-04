<x-app-layout>
    <x-page-header title="Sucursales" subtitle="Gestión de sucursales">
        <x-slot name="actions">
            <x-button variant="primary" onclick="document.getElementById('createModal').classList.remove('hidden')">
                <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
                Nueva Sucursal
            </x-button>
        </x-slot>
    </x-page-header>

    <x-card class="overflow-hidden p-0">
        <x-table :headers="['NOMBRE', 'DIRECCIÓN', 'TELÉFONO', 'EMAIL', 'ESTADO', 'ACCIONES']">
            @forelse($branches as $branch)
                <tr>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                                <i data-lucide="building" class="h-5 w-5"></i>
                            </div>
                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ $branch->name }}</div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-slate-600 dark:text-slate-400">
                        {{ $branch->address ?: 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-slate-600 dark:text-slate-400">
                        {{ $branch->phone ?: 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-slate-600 dark:text-slate-400">
                        {{ $branch->email ?: 'N/A' }}
                    </td>
                    <td class="px-6 py-4">
                        <x-badge :variant="$branch->is_active ? 'emerald' : 'red'">
                            {{ $branch->is_active ? 'Activa' : 'Inactiva' }}
                        </x-badge>
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('branches.edit', $branch) }}" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors">
                                <i data-lucide="pencil" class="h-4 w-4"></i>
                            </a>
                            <form action="{{ route('branches.toggle', $branch) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                    title="{{ $branch->is_active ? 'Desactivar' : 'Activar' }}"
                                    class="rounded-lg p-2 {{ $branch->is_active ? 'text-red-400 hover:bg-red-50 hover:text-red-600' : 'text-emerald-400 hover:bg-emerald-50 hover:text-emerald-600' }} transition-colors cursor-pointer">
                                    <i data-lucide="{{ $branch->is_active ? 'eye-off' : 'eye' }}" class="h-4 w-4"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                        No hay sucursales registradas.
                    </td>
                </tr>
            @endforelse
        </x-table>
    </x-card>

    <div id="createModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('createModal').classList.add('hidden')"></div>
            <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Nueva Sucursal</h3>
                    <button onclick="document.getElementById('createModal').classList.add('hidden')" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <form action="{{ route('branches.store') }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-xs font-bold text-slate-400 dark:text-gray-500 uppercase mb-2 ml-1">Nombre</label>
                            <input type="text" name="name" id="name" required class="w-full rounded-2xl border-slate-200 dark:border-gray-800 bg-white dark:bg-gray-950/50 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-4 px-4 text-sm font-bold text-slate-700 dark:text-gray-300 transition-all">
                        </div>
                        <div>
                            <label for="address" class="block text-xs font-bold text-slate-400 dark:text-gray-500 uppercase mb-2 ml-1">Dirección</label>
                            <input type="text" name="address" id="address" class="w-full rounded-2xl border-slate-200 dark:border-gray-800 bg-white dark:bg-gray-950/50 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-4 px-4 text-sm font-bold text-slate-700 dark:text-gray-300 transition-all">
                        </div>
                        <div>
                            <label for="phone" class="block text-xs font-bold text-slate-400 dark:text-gray-500 uppercase mb-2 ml-1">Teléfono</label>
                            <input type="text" name="phone" id="phone" class="w-full rounded-2xl border-slate-200 dark:border-gray-800 bg-white dark:bg-gray-950/50 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-4 px-4 text-sm font-bold text-slate-700 dark:text-gray-300 transition-all">
                        </div>
                        <div>
                            <label for="email" class="block text-xs font-bold text-slate-400 dark:text-gray-500 uppercase mb-2 ml-1">Email</label>
                            <input type="email" name="email" id="email" class="w-full rounded-2xl border-slate-200 dark:border-gray-800 bg-white dark:bg-gray-950/50 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-4 px-4 text-sm font-bold text-slate-700 dark:text-gray-300 transition-all">
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" class="rounded-2xl px-4 py-2.5 text-sm font-bold text-slate-600 border border-slate-200 hover:bg-slate-50 transition-colors">
                            Cancelar
                        </button>
                        <x-button variant="primary" type="submit">
                            Crear Sucursal
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</x-app-layout>
