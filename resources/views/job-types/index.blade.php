<x-app-layout>
    <x-page-header title="Tipos de Trabajo" subtitle="Configura los tipos de trabajo y sus reglas de pago">
        <x-slot name="actions">
            <x-button variant="primary" onclick="window.location.href='{{ route('job-types.create') }}'">
                <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
                Nuevo Tipo
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($jobTypes as $type)
        <x-card class="relative flex flex-col h-full">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl {{ $type->calculation_type === 'percentage' ? 'bg-blue-50 text-blue-600' : 'bg-emerald-50 text-emerald-600' }}">
                        <i data-lucide="{{ $type->calculation_type === 'percentage' ? 'percent' : 'dollar-sign' }}" class="h-6 w-6"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-slate-900">{{ $type->name }}</h3>
                            <x-badge :variant="$type->is_active ? 'blue' : 'slate'" class="text-[8px] uppercase tracking-tighter">
                                {{ $type->is_active ? 'Activo' : 'Inactivo' }}
                            </x-badge>
                        </div>
                        @if($type->is_system)
                        <x-badge variant="slate" class="text-[9px] uppercase tracking-tighter">Sistema</x-badge>
                        @endif
                    </div>
                </div>
                <div class="flex gap-2">
                    <x-button variant="outline" size="sm" class="p-2" onclick="window.location.href='{{ route('job-types.edit', $type) }}'">
                        <i data-lucide="edit-3" class="h-4 w-4"></i>
                    </x-button>
                    @if(!$type->is_system)
                    <form action="{{ route('job-types.destroy', $type) }}" method="POST" onsubmit="return confirm('¿Eliminar este tipo de trabajo?')">
                        @csrf
                        @method('DELETE')
                        <x-button variant="outline" size="sm" class="p-2 text-red-500 hover:text-red-600">
                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                        </x-button>
                    </form>
                    @endif
                </div>
            </div>

            <p class="text-sm text-slate-500 mb-6 flex-grow line-clamp-2">
                {{ $type->description }}
            </p>

            <div class="space-y-4 pt-4 border-t border-slate-100">
                @if($type->calculation_type === 'percentage')
                <div class="flex items-center justify-between text-xs font-bold text-slate-400 uppercase tracking-widest">
                    <div class="flex items-center gap-2">
                        <i data-lucide="wrench" class="h-3.5 w-3.5 text-blue-600"></i>
                        Mecánico: <span class="text-slate-900">{{ number_format($type->mechanic_percentage, 0) }}%</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="box" class="h-3.5 w-3.5 text-emerald-600"></i>
                        Taller: <span class="text-slate-900">{{ number_format($type->workshop_percentage, 0) }}%</span>
                    </div>
                </div>
                @else
                <div class="flex items-center justify-between text-xs font-black text-slate-400 uppercase tracking-widest">
                    <div class="flex items-center gap-2">
                        Total Fijo: <span class="text-slate-900">${{ number_format(($type->fixed_mechanic_amount + $type->fixed_workshop_amount), 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-1 text-[10px] font-bold text-slate-400">
                    <span>Mecánico: ${{ number_format($type->fixed_mechanic_amount, 0, ',', '.') }}</span>
                    <span>Taller: ${{ number_format($type->fixed_workshop_amount, 0, ',', '.') }}</span>
                </div>
                @endif

                <div class="flex flex-wrap gap-2 pt-2">
                    @if($type->allow_custom_labor)
                    <x-badge variant="blue" class="text-[9px] uppercase">Mano de obra variable</x-badge>
                    @endif
                    @if($type->allow_products)
                    <x-badge variant="emerald" class="text-[9px] uppercase">Permite productos</x-badge>
                    @endif
                </div>
            </div>
        </x-card>
        @endforeach
    </div>
</x-app-layout>
