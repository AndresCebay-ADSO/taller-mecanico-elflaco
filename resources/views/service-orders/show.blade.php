<x-app-layout x-data="{ showJobModal: false }">
    <x-page-header title="Orden #{{ str_pad($serviceOrder->id, 4, '0', STR_PAD_LEFT) }}" subtitle="Detalle completo de la orden de servicio.">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('service-orders.index') }}'">
                <i data-lucide="arrow-left" class="mr-2 h-4 w-4"></i>
                Volver
            </x-button>
            <x-button variant="outline" onclick="window.location.href='{{ route('service-orders.edit', $serviceOrder) }}'">
                <i data-lucide="pencil" class="mr-2 h-4 w-4"></i>
                Editar
            </x-button>
            <x-button variant="primary" @click="showJobModal = true">
                <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
                Añadir Trabajo
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <!-- Main Details -->
        <div class="lg:col-span-2 space-y-8">
            <x-card>
                <x-slot name="header">
                    <h3 class="font-bold text-slate-900">Motivo del Servicio</h3>
                </x-slot>
                <p class="text-slate-700 whitespace-pre-wrap leading-relaxed">{{ $serviceOrder->service_description }}</p>
            </x-card>

            <!-- Trabajos Realizados -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-black text-slate-900 tracking-tight">Trabajos y Servicios</h3>
                    <x-badge variant="slate">{{ $serviceOrder->workshopJobs->count() }} tareas</x-badge>
                </div>

                @forelse($serviceOrder->workshopJobs as $job)
                <x-card class="overflow-hidden border-slate-200">
                    <div class="flex flex-col md:flex-row gap-6">
                        <div class="flex-1 space-y-4">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 flex items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                        <i data-lucide="{{ $job->jobType->calculation_type === 'percentage' ? 'percent' : 'dollar-sign' }}" class="h-5 w-5"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-900 leading-none">{{ $job->jobType->name }}</h4>
                                        <p class="mt-1 text-xs text-slate-500 font-medium italic">Responsable: {{ $job->mechanic->name }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    @if($job->status !== 'completed')
                                    <form action="{{ route('jobs.complete', $job) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors">
                                            <i data-lucide="check-circle" class="h-4 w-4"></i>
                                        </button>
                                    </form>
                                    @endif
                                    <form action="{{ route('jobs.destroy', $job) }}" method="POST" onsubmit="return confirm('¿Eliminar este trabajo? Se devolverán los productos al stock.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-red-400 hover:bg-red-50 rounded-lg transition-colors">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <p class="text-sm text-slate-600 px-1">{{ $job->description }}</p>
                            
                            <!-- Productos del Trabajo -->
                            @if($job->jobProducts->count() > 0)
                            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Repuestos y Materiales</span>
                                <div class="space-y-2">
                                    @foreach($job->jobProducts as $jp)
                                    <div class="flex justify-between items-center bg-white p-2 rounded-xl border border-slate-100 shadow-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="h-6 w-6 flex items-center justify-center rounded-lg bg-slate-100 text-[10px] font-bold text-slate-600">{{ $jp->quantity }}x</span>
                                            <span class="text-xs font-bold text-slate-800">{{ $jp->product->name }}</span>
                                        </div>
                                        <span class="text-xs font-bold text-slate-900">${{ number_format($jp->total_price, 0, ',', '.') }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                        
                        <!-- Cost Summary for this job -->
                        <div class="md:w-48 bg-slate-50/50 rounded-2xl p-4 border border-slate-100 flex flex-col justify-between">
                            <div class="space-y-2">
                                <div class="flex justify-between items-center text-[10px] font-bold text-slate-500 uppercase">
                                    <span>Mano de Obra</span>
                                    <span>${{ number_format($job->labor_cost, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between items-center text-[10px] font-bold text-slate-500 uppercase">
                                    <span>Productos</span>
                                    <span>${{ number_format($job->jobProducts->sum('total_price'), 0, ',', '.') }}</span>
                                </div>
                            </div>
                            <div class="pt-4 border-t border-slate-200 mt-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-black text-slate-800 uppercase">Subtotal</span>
                                    <span class="text-lg font-black text-blue-600">${{ number_format($job->total_amount + $job->jobProducts->sum('total_price'), 0, ',', '.') }}</span>
                                </div>
                                <div class="mt-2">
                                    @php
                                        $jobBadge = match($job->status) {
                                            'pending' => 'amber',
                                            'in_progress' => 'blue',
                                            'completed' => 'emerald',
                                            default => 'slate'
                                        };
                                    @endphp
                                    <x-badge :variant="$jobBadge" class="w-full justify-center py-1 uppercase text-[9px]">{{ $job->status }}</x-badge>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-card>
                @empty
                <div class="text-center py-20 bg-white rounded-3xl border-2 border-dashed border-slate-200">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-50 text-slate-300 mx-auto mb-4">
                        <i data-lucide="wrench" class="h-8 w-8"></i>
                    </div>
                    <p class="text-slate-500 font-bold">No hay trabajos registrados en esta orden.</p>
                    <x-button variant="outline" size="sm" class="mt-4" @click="showJobModal = true">Añadir el primero</x-button>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Sidebar Details -->
        <div class="space-y-8">
            <x-card>
                <x-slot name="header">
                    <h3 class="font-bold text-slate-900">Resumen Financiero</h3>
                </x-slot>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-slate-500">Mano de Obra Total:</span>
                        <span class="text-sm font-bold text-slate-900">${{ number_format($serviceOrder->workshopJobs->sum('labor_cost'), 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-slate-500">Repuestos Total:</span>
                        @php
                            $totalProductsCost = $serviceOrder->workshopJobs->sum(fn($j) => $j->jobProducts->sum('total_price'));
                        @endphp
                        <span class="text-sm font-bold text-slate-900">${{ number_format($totalProductsCost, 0, ',', '.') }}</span>
                    </div>
                    <div class="pt-4 border-t border-slate-100">
                        <div class="bg-blue-600 rounded-2xl p-5 text-white">
                            <span class="text-[10px] font-black text-blue-200 uppercase tracking-widest">Total Orden</span>
                            <p class="text-3xl font-black">${{ number_format($serviceOrder->workshopJobs->sum('labor_cost') + $totalProductsCost, 0, ',', '.') }}</p>
                        </div>
                    </div>
                    @if($serviceOrder->status === 'completed' || $serviceOrder->workshopJobs->where('status', 'completed')->count() === $serviceOrder->workshopJobs->count() && $serviceOrder->workshopJobs->count() > 0)
                    <form action="{{ route('invoices.generate', $serviceOrder) }}" method="POST">
                        @csrf
                        <x-button variant="primary" class="w-full py-4 shadow-lg shadow-blue-500/25">
                            <i data-lucide="file-text" class="mr-2 h-5 w-5"></i>
                            Generar Factura
                        </x-button>
                    </form>
                    @endif
                </div>
            </x-card>

            <x-card>
                <x-slot name="header">
                    <h3 class="font-bold text-slate-900">Información del Cliente</h3>
                </x-slot>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="user" class="h-5 w-5 text-slate-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-900 leading-tight">{{ $serviceOrder->customer_name }}</p>
                            <p class="text-xs text-slate-500 mt-1 font-medium">{{ $serviceOrder->customer_phone ?? 'Sin teléfono' }}</p>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-slate-100">
                        <span class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Vehículo Registrado</span>
                        <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-700 font-bold italic border border-slate-100 shadow-inner">
                            {{ $serviceOrder->vehicle_info }}
                        </div>
                    </div>
                </div>
            </x-card>
        </div>
    </div>

    <!-- MODAL: NUEVO TRABAJO (Alpine.js) -->
    <div x-show="showJobModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-cloak>
        
        <div class="bg-white rounded-3xl w-full max-w-xl shadow-2xl overflow-hidden" 
             @click.away="showJobModal = false"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="scale-95 opacity-0"
             x-transition:enter-end="scale-100 opacity-100">
            
            <div class="flex items-center justify-between p-6 border-b border-slate-100">
                <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight">Nuevo Trabajo</h3>
                <button @click="showJobModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i data-lucide="x" class="h-6 w-6"></i>
                </button>
            </div>

            <form action="{{ route('service-orders.jobs.store', $serviceOrder) }}" 
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
                                @change="updateJobType($event.target.value)"
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
                            <input type="text" value="{{ $serviceOrder->vehicle_info }}" readonly class="w-full rounded-2xl border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-500 transition-all cursor-not-allowed">
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
                            <input type="text" value="{{ $serviceOrder->customer_name }}" readonly class="w-full rounded-2xl border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-500 transition-all cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Teléfono (opcional)</label>
                            <input type="text" value="{{ $serviceOrder->customer_phone }}" readonly class="w-full rounded-2xl border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-500 transition-all cursor-not-allowed">
                        </div>
                    </div>

                    <!-- Productos -->
                    <div class="space-y-4 pt-4 border-t border-slate-100">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-black text-slate-400 uppercase tracking-widest">Productos Utilizados</label>
                            <select @change="addProduct($event.target.value); $event.target.value = ''" 
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
                                        <button type="button" @click="removeProduct(index)" class="text-slate-300 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100">
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
</x-app-layout>
