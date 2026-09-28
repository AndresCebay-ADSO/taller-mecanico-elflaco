{{--
    Modal "Nuevo Trabajo" compartido por el detalle de orden y el listado de trabajos.
    Debe incluirse dentro de un elemento con x-data="{ showJobModal: ... }".

    @param string                 $action        URL del formulario
    @param \App\Models\ServiceOrder|null $serviceOrder  null = trabajo individual (crea su propia orden)
    @param \Illuminate\Support\Collection $products     InventoryService::sellableProducts()
    @param \Illuminate\Support\Collection $jobTypes
    @param \Illuminate\Support\Collection $mechanics
--}}
@php
    $jobTypeOptions = $jobTypes->map(fn ($type) => [
        'id' => $type->id,
        'name' => $type->name,
        'calculation_type' => $type->calculation_type,
        'mechanic_percentage' => (float) $type->mechanic_percentage,
        'fixed_mechanic_amount' => (float) $type->fixed_mechanic_amount,
        'fixed_workshop_amount' => (float) $type->fixed_workshop_amount,
        'allow_products' => (bool) $type->allow_products,
        'default_description' => $type->default_description ?: $type->description,
    ])->values();

    $inputClass = 'w-full rounded-2xl border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all shadow-sm';
    $readonlyClass = 'w-full rounded-2xl border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-500 cursor-not-allowed';
    $labelClass = 'block text-xs font-black text-slate-400 uppercase tracking-widest mb-2';
@endphp

<div x-show="showJobModal"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-on:keydown.escape.window="showJobModal = false"
     x-cloak>

    <div class="bg-white rounded-3xl w-full max-w-xl shadow-2xl overflow-hidden"
         x-on:click.outside="showJobModal = false"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="scale-95 opacity-0"
         x-transition:enter-end="scale-100 opacity-100">

        <div class="flex items-center justify-between p-6 border-b border-slate-100">
            <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight">Nuevo Trabajo</h3>
            <button type="button" x-on:click="showJobModal = false" class="text-slate-400 hover:text-slate-600 transition-colors" title="Cerrar">
                <i data-lucide="x" class="h-6 w-6"></i>
            </button>
        </div>

        <form action="{{ $action }}"
              method="POST"
              class="p-6 overflow-y-auto max-h-[80vh] custom-scrollbar"
              x-data="jobForm({
                  products: @js($products),
                  jobTypes: @js($jobTypeOptions),
                  old: @js(old()),
              })">
            @csrf

            <div class="space-y-6">
                {{-- Cliente y vehículo --}}
                @if($serviceOrder)
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $labelClass }}">Cliente</label>
                            <input type="text" value="{{ $serviceOrder->customer_name }}" readonly class="{{ $readonlyClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Vehículo</label>
                            <input type="text" value="{{ $serviceOrder->vehicle_info }}" readonly class="{{ $readonlyClass }}">
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $labelClass }}">Nombre del Cliente</label>
                            <input type="text" name="customer_name" value="{{ old('customer_name') }}" required maxlength="255" placeholder="Nombre del cliente" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Teléfono (opcional)</label>
                            <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}" maxlength="20" placeholder="3001234567" class="{{ $inputClass }}">
                        </div>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Vehículo</label>
                        <input type="text" name="vehicle_info" value="{{ old('vehicle_info') }}" required maxlength="255" placeholder="Ej: Honda CB190R - ABC12D" class="{{ $inputClass }}">
                    </div>
                @endif

                {{-- Tipo de trabajo --}}
                <div>
                    <label class="{{ $labelClass }}">Tipo de Trabajo</label>
                    <select name="job_type_id" x-model="jobTypeId" required class="{{ $inputClass }} cursor-pointer">
                        <option value="">Seleccionar tipo...</option>
                        @foreach($jobTypeOptions as $type)
                            <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                        @endforeach
                    </select>

                    <div x-show="jobType?.default_description" class="mt-3 p-3 bg-blue-50 rounded-2xl border border-blue-100 flex gap-3 text-xs text-blue-700" x-cloak>
                        <i data-lucide="info" class="h-4 w-4 shrink-0"></i>
                        <span x-text="jobType?.default_description"></span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $labelClass }}">Descripción</label>
                        <input type="text" name="description" value="{{ old('description') }}" required maxlength="255" placeholder="Ej. Cambio de llanta trasera" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Mecánico</label>
                        <select name="mechanic_id" required class="{{ $inputClass }} cursor-pointer">
                            <option value="">Seleccionar mecánico</option>
                            @foreach($mechanics as $mech)
                                <option value="{{ $mech->id }}" @selected(old('mechanic_id') == $mech->id)>{{ $mech->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Repuestos --}}
                <div class="space-y-4 pt-4 border-t border-slate-100">
                    <div class="flex items-center justify-between gap-3">
                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest">Repuestos utilizados</label>
                        <select x-show="allowsProducts"
                                x-on:change="addProduct($event.target.value); $event.target.value = ''"
                                class="max-w-[60%] text-xs font-bold text-blue-600 bg-blue-50 border-none rounded-xl py-1 px-3 cursor-pointer hover:bg-blue-100 transition-colors">
                            <option value="">+ Agregar repuesto</option>
                            @foreach($products as $p)
                                <option value="{{ $p['id'] }}">{{ $p['name'] }} — ${{ number_format($p['price'], 0, ',', '.') }} · Stock {{ $p['stock'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <p x-show="!allowsProducts" x-cloak class="text-xs font-medium text-slate-500">Este tipo de trabajo no usa repuestos.</p>
                    <p x-show="allowsProducts && selectedProducts.length === 0" class="text-xs font-medium text-slate-400">Sin repuestos agregados.</p>

                    <div class="space-y-2">
                        <template x-for="(p, index) in selectedProducts" :key="p.id">
                            <div class="flex items-center justify-between gap-3 bg-slate-50 p-3 rounded-2xl border border-slate-100">
                                <div class="flex items-center gap-3 min-w-0">
                                    <input type="hidden" :name="`products[${index}][id]`" :value="p.id">
                                    <input type="number" :name="`products[${index}][quantity]`" x-model.number="p.quantity"
                                           min="1" :max="stockOf(p)" required
                                           class="w-16 h-8 px-1 text-center font-black rounded-lg border-slate-200 text-xs">
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-slate-700 truncate" x-text="p.name"></p>
                                        <p class="text-[10px] font-bold uppercase"
                                           :class="p.quantity > stockOf(p) ? 'text-red-500' : 'text-slate-400'"
                                           x-text="p.quantity > stockOf(p) ? `Solo hay ${stockOf(p)} en stock` : `Stock: ${stockOf(p)}`"></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <span class="text-sm font-black text-slate-900" x-text="money(lineTotal(p))"></span>
                                    <button type="button" x-on:click="removeProduct(index)" class="p-1 text-slate-400 hover:text-red-500 transition-colors" title="Quitar repuesto">
                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Mano de obra --}}
                <div>
                    <label class="{{ $labelClass }}">Mano de Obra ($)</label>
                    <input type="number" name="labor_cost" x-model="laborCost" min="0" step="1" required
                           :readonly="isFixed"
                           :class="isFixed ? 'bg-slate-50 text-slate-500 cursor-not-allowed' : ''"
                           class="{{ $inputClass }} font-bold">
                    <p x-show="isFixed" x-cloak class="mt-1 text-xs text-slate-500">Monto fijo definido por el tipo de trabajo.</p>
                </div>

                {{-- Resumen --}}
                <div class="bg-slate-50 rounded-3xl p-6 border border-slate-200">
                    <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-4">Resumen del Trabajo</h4>
                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between text-sm font-medium text-slate-500">
                            <span>Repuestos:</span>
                            <span class="text-slate-900" x-text="money(productsTotal)"></span>
                        </div>
                        <div class="flex justify-between text-sm font-medium text-slate-500">
                            <span>Mano de obra:</span>
                            <span class="text-slate-900" x-text="money(labor)"></span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center py-4 border-t border-slate-200">
                        <span class="text-lg font-black text-slate-900 uppercase">Total:</span>
                        <span class="text-2xl font-black text-blue-600" x-text="money(grandTotal)"></span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm text-center">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1" x-text="mechanicLabel"></p>
                            <p class="text-lg font-black text-blue-600" x-text="money(mechanicShare)"></p>
                        </div>
                        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm text-center">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Taller (resto + repuestos)</p>
                            <p class="text-lg font-black text-emerald-600" x-text="money(workshopShare)"></p>
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
