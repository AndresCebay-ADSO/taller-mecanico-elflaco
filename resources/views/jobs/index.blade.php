<x-app-layout>
<div x-data="{ showJobModal: false }">
    <x-page-header title="Gestión de Trabajos" subtitle="Vista global de todas las tareas y su estado actual en el taller.">
        <x-slot name="actions">
            <x-button variant="primary" x-on:click="showJobModal = true">
                <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
                Nuevo Trabajo
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('jobs.index') }}" class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all {{ !request('status') ? 'bg-slate-900 text-white shadow-lg' : 'bg-white text-slate-500 border border-slate-200 hover:bg-slate-50' }}">
            Todos
        </a>
        <a href="{{ route('jobs.index', ['status' => 'pending']) }}" class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all {{ request('status') === 'pending' ? 'bg-amber-500 text-white shadow-lg' : 'bg-white text-slate-500 border border-slate-200 hover:bg-slate-50' }}">
            Pendientes
        </a>
        <a href="{{ route('jobs.index', ['status' => 'in_progress']) }}" class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all {{ request('status') === 'in_progress' ? 'bg-blue-500 text-white shadow-lg' : 'bg-white text-slate-500 border border-slate-200 hover:bg-slate-50' }}">
            En Progreso
        </a>
        <a href="{{ route('jobs.index', ['status' => 'completed']) }}" class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all {{ request('status') === 'completed' ? 'bg-emerald-500 text-white shadow-lg' : 'bg-white text-slate-500 border border-slate-200 hover:bg-slate-50' }}">
            Completados
        </a>
    </div>

    <x-card>
        <x-table :headers="['ID', 'Trabajo / Orden', 'Mecánico', 'Vehículo', 'Costo', 'Estado', 'Acciones']">
            @forelse($jobs as $job)
                <tr>
                    <td class="px-6 py-4 text-sm font-bold text-slate-900">
                        #T{{ str_pad($job->id, 4, '0', STR_PAD_LEFT) }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-bold text-slate-900">{{ $job->jobType->name }}</div>
                        <div class="text-xs text-slate-500">
                            Orden <a href="{{ route('service-orders.show', $job->serviceOrder) }}" class="text-blue-600 hover:underline">#{{ str_pad($job->serviceOrder->id, 4, '0', STR_PAD_LEFT) }}</a>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">
                        {{ $job->mechanic->name }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-slate-900 font-medium">{{ $job->vehicle_info }}</div>
                        <div class="text-[10px] text-slate-400 font-bold uppercase">{{ $job->customer_name }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm font-bold text-slate-900">
                        ${{ number_format($job->labor_cost, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $jobBadge = match($job->status) {
                                'pending' => 'amber',
                                'in_progress' => 'blue',
                                'completed' => 'emerald',
                                default => 'slate'
                            };
                        @endphp
                        <x-badge :variant="$jobBadge" class="uppercase text-[9px]">{{ $job->status }}</x-badge>
                    </td>
                    <td class="px-6 py-4 text-right text-sm">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('service-orders.show', $job->serviceOrder) }}" class="p-2 text-slate-400 hover:text-blue-600 transition-colors" title="Ver Orden">
                                <i data-lucide="eye" class="h-4 w-4"></i>
                            </a>
                            @if($job->status !== 'completed')
                            <form action="{{ route('jobs.complete', $job) }}" method="POST">
                                @csrf
                                <button type="submit" class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg" title="Completar">
                                    <i data-lucide="check-circle" class="h-4 w-4"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                        No hay trabajos registrados.
                    </td>
                </tr>
            @endforelse
        </x-table>
        
        <div class="mt-6">
            {{ $jobs->links() }}
        </div>
    </x-card>

    <!-- MODAL: NUEVO TRABAJO (Standalone) -->
    <div x-show="showJobModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-cloak>
        
        <div class="bg-white rounded-3xl w-full max-w-xl shadow-2xl overflow-hidden" 
             x-on:click.away="showJobModal = false"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="scale-95 opacity-0"
             x-transition:enter-end="scale-100 opacity-100">
            
            <div class="flex items-center justify-between p-6 border-b border-slate-100">
                <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight">Nuevo Trabajo</h3>
                <button type="button" x-on:click="showJobModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i data-lucide="x" class="h-6 w-6"></i>
                </button>
            </div>

            <form action="{{ route('jobs.store_individual') }}" 
                  method="POST" 
                  class="p-6 overflow-y-auto max-h-[80vh] custom-scrollbar"
                  x-data="{ 
                    jobTypeId: '',
                    laborCost: 0,
                    selectedProducts: [],
                    availableProducts: @js($products),
                    jobTypes: @js($jobTypes),
                    selectedJobType: null,
                    get totalProductsPrice() {
                        return this.selectedProducts.reduce((sum, p) => sum + (p.price * p.quantity), 0);
                    },
                    get grandTotal() {
                        return parseFloat(this.laborCost || 0) + this.totalProductsPrice;
                    },
                    updateJobType(id) {
                        this.selectedJobType = this.jobTypes.find(t => t.id == id);
                        if(this.selectedJobType && this.selectedJobType.calculation_type === 'fixed') {
                            this.laborCost = (parseFloat(this.selectedJobType.fixed_mechanic_amount) || 0) + (parseFloat(this.selectedJobType.fixed_workshop_amount) || 0);
                        }
                    },
                    addProduct(id) {
                        const product = this.availableProducts.find(p => p.id == id);
                        if(product) {
                            const existing = this.selectedProducts.find(p => p.id == id);
                            if(existing) {
                                existing.quantity++;
                            } else {
                                this.selectedProducts.push({ id: product.id, name: product.name, price: product.sale_price, quantity: 1 });
                            }
                        }
                    },
                    removeProduct(index) {
                        this.selectedProducts.splice(index, 1);
                    }
                  }">
                @csrf
                
                <div class="space-y-6">
                    <!-- Tipo de Trabajo -->
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Tipo de Trabajo</label>
                        <select name="job_type_id" 
                                x-model="jobTypeId" 
                                x-on:change="updateJobType($event.target.value)"
                                required
                                class="w-full rounded-2xl border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 cursor-pointer transition-all">
                            <option value="">Seleccionar tipo...</option>
                            @foreach($jobTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        
                        <div x-show="selectedJobType" class="mt-3 p-3 bg-blue-50 rounded-2xl border border-blue-100 flex gap-3 text-xs text-blue-700" x-cloak>
                            <i data-lucide="info" class="h-4 w-4 shrink-0"></i>
                            <span x-text="selectedJobType?.default_description"></span>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Descripción del Trabajo</label>
                        <input type="text" name="description" required placeholder="Ej. Cambio de llanta trasera" class="w-full rounded-2xl border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all shadow-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Vehículo</label>
                            <input type="text" name="vehicle_info" required placeholder="Ej: Honda CB190R - ABC123" class="w-full rounded-2xl border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all shadow-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Mecánico</label>
                            <select name="mechanic_id" required class="w-full rounded-2xl border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 cursor-pointer transition-all">
                                <option value="">Seleccionar mecánico</option>
                                @foreach($mechanics as $mech)
                                <option value="{{ $mech->id }}">{{ $mech->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Nombre del Cliente</label>
                            <input type="text" name="customer_name" required placeholder="Nombre del cliente" class="w-full rounded-2xl border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all shadow-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Teléfono (opcional)</label>
                            <input type="text" name="customer_phone" placeholder="Teléfono del cliente" class="w-full rounded-2xl border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all shadow-sm">
                        </div>
                    </div>

                    <!-- Productos -->
                    <div class="space-y-4 pt-4 border-t border-slate-100">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-black text-slate-400 uppercase tracking-widest">Productos Utilizados</label>
                            <select x-on:change="addProduct($event.target.value); $event.target.value = ''" 
                                    class="text-xs font-bold text-blue-600 bg-blue-50 border-none rounded-xl py-1 px-3 cursor-pointer hover:bg-blue-100 transition-colors">
                                <option value="">+ Agregar Producto</option>
                                @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} (${{ number_format($p->sale_price, 0) }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="space-y-2">
                            <template x-for="(p, index) in selectedProducts" :key="index">
                                <div class="flex items-center justify-between bg-slate-50 p-3 rounded-2xl border border-slate-100 group">
                                    <div class="flex items-center gap-3">
                                        <input type="hidden" :name="'products['+index+'][id]'" :value="p.id">
                                        <div class="flex items-center gap-1">
                                            <input type="number" :name="'products['+index+'][quantity]'" x-model="p.quantity" class="w-12 h-8 px-1 text-center font-black rounded-lg border-slate-200 text-xs">
                                        </div>
                                        <span class="text-sm font-bold text-slate-700" x-text="p.name"></span>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="text-sm font-black text-slate-900" x-text="'$' + (p.price * p.quantity).toLocaleString()"></span>
                                        <button type="button" x-on:click="removeProduct(index)" class="text-slate-300 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Mano de Obra -->
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Mano de Obra ($)</label>
                        <input type="number" name="labor_cost" x-model="laborCost" required class="w-full rounded-2xl border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all font-bold">
                    </div>

                    <!-- RESUMEN FINAL -->
                    <div class="bg-slate-50 rounded-3xl p-6 border border-slate-200">
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-4">Resumen del Trabajo</h4>
                        <div class="space-y-3 mb-4">
                            <div class="flex justify-between text-sm font-medium text-slate-500">
                                <span>Productos:</span>
                                <span class="text-slate-900" x-text="'$' + totalProductsPrice.toLocaleString()"></span>
                            </div>
                            <div class="flex justify-between text-sm font-medium text-slate-500">
                                <span>Mano de obra:</span>
                                <span class="text-slate-900" x-text="'$' + (parseFloat(laborCost) || 0).toLocaleString()"></span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center py-4 border-t border-slate-200">
                            <span class="text-lg font-black text-slate-900 uppercase">Total:</span>
                            <span class="text-2xl font-black text-blue-600" x-text="'$' + grandTotal.toLocaleString()"></span>
                        </div>

                        <!-- Split Cards -->
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm text-center">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">
                                    Mecánico (<span x-text="selectedJobType ? selectedJobType.mechanic_percentage : '70'"></span>%)
                                </p>
                                <p class="text-lg font-black text-blue-600" 
                                   x-text="'$' + (selectedJobType && selectedJobType.calculation_type === 'percentage' 
                                            ? (parseFloat(laborCost) * (selectedJobType.mechanic_percentage/100)).toLocaleString() 
                                            : (parseFloat(selectedJobType?.fixed_mechanic_amount) || 0).toLocaleString())">
                                </p>
                            </div>
                            <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm text-center">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">
                                    Taller (Resto + Repuestos)
                                </p>
                                <p class="text-lg font-black text-emerald-600"
                                   x-text="'$' + (grandTotal - (selectedJobType && selectedJobType.calculation_type === 'percentage' 
                                            ? (parseFloat(laborCost) * (selectedJobType.mechanic_percentage/100)) 
                                            : (parseFloat(selectedJobType?.fixed_mechanic_amount) || 0))).toLocaleString()">
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 pt-4">
                        <x-button variant="primary" type="submit" class="flex-1 py-4 shadow-lg shadow-blue-500/20">Registrar Trabajo</x-button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
