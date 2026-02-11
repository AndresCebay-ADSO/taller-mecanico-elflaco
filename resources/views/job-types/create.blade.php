<x-app-layout>
    <x-page-header :title="isset($jobType) ? 'Editar Tipo de Trabajo' : 'Nuevo Tipo de Trabajo'" 
                   :subtitle="isset($jobType) ? 'Modifica las reglas de este tipo de trabajo' : 'Configura un nuevo tipo y sus reglas de pago'">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('job-types.index') }}'">
                Cancelar
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="max-w-2xl">
        <x-card>
            <form action="{{ isset($jobType) ? route('job-types.update', $jobType) : route('job-types.store') }}" 
                  method="POST" 
                  id="jobTypeForm"
                  class="space-y-8"
                  x-data="{ 
                    calcType: '{{ old('calculation_type', $jobType->calculation_type ?? 'percentage') }}',
                    mechanicPercent: {{ old('mechanic_percentage', $jobType->mechanic_percentage ?? 70) }},
                    allowCustom: {{ old('allow_custom_labor', $jobType->allow_custom_labor ?? 1) ? 'true' : 'false' }},
                    allowProducts: {{ old('allow_products', $jobType->allow_products ?? 1) ? 'true' : 'false' }}
                  }">
                @csrf
                @if(isset($jobType)) @method('PUT') @endif

                <!-- Basic Info -->
                <div class="space-y-6 pb-6 border-b border-slate-100">
                    <x-input label="Nombre" name="name" required placeholder="Ej. Cambio de Aceite" value="{{ old('name', $jobType->name ?? '') }}" />
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Descripción</label>
                        <textarea name="description" required rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" placeholder="Describe brevemente este servicio...">{{ old('description', $jobType->description ?? '') }}</textarea>
                    </div>
                </div>

                <!-- Calculation Type Selector -->
                <div class="space-y-4">
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest">Tipo de Cálculo</label>
                    <input type="hidden" name="calculation_type" :value="calcType">
                    <div class="grid grid-cols-2 gap-4">
                        <button type="button" 
                                @click="calcType = 'percentage'"
                                :class="calcType === 'percentage' ? 'border-blue-600 bg-blue-50 text-blue-700 ring-2 ring-blue-500/10' : 'border-slate-200 bg-white text-slate-500'"
                                class="flex flex-col items-center justify-center p-6 rounded-2xl border-2 transition-all duration-200">
                            <i data-lucide="percent" class="h-8 w-8 mb-2"></i>
                            <span class="font-bold">Porcentaje</span>
                            <span class="text-[10px] opacity-60">Split 70/30, 60/40, etc.</span>
                        </button>
                        <button type="button" 
                                @click="calcType = 'fixed'"
                                :class="calcType === 'fixed' ? 'border-emerald-600 bg-emerald-50 text-emerald-700 ring-2 ring-emerald-500/10' : 'border-slate-200 bg-white text-slate-500'"
                                class="flex flex-col items-center justify-center p-6 rounded-2xl border-2 transition-all duration-200">
                            <i data-lucide="dollar-sign" class="h-8 w-8 mb-2"></i>
                            <span class="font-bold">Monto Fijo</span>
                            <span class="text-[10px] opacity-60">Precio único configurado</span>
                        </button>
                    </div>
                </div>

                <!-- Percentage Settings -->
                <div x-show="calcType === 'percentage'" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform -translate-y-4"
                     class="space-y-6 bg-slate-50/50 p-6 rounded-2xl border border-slate-100">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <label class="text-sm font-bold text-slate-700">Porcentaje Mecánico: <span class="text-blue-600" x-text="mechanicPercent + '%'">70%</span></label>
                            <label class="text-sm font-bold text-slate-400">Taller: <span class="text-emerald-600" x-text="(100-mechanicPercent) + '%'">30%</span></label>
                        </div>
                        <input type="range" name="mechanic_percentage" x-model="mechanicPercent" min="0" max="100" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
                        <input type="hidden" name="workshop_percentage" :value="100 - mechanicPercent">
                    </div>
                </div>

                <!-- Fixed Settings -->
                <div x-show="calcType === 'fixed'" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform -translate-y-4"
                     class="grid grid-cols-2 gap-4 bg-emerald-50/30 p-6 rounded-2xl border border-emerald-100">
                    <x-input label="Monto Mecánico" name="fixed_mechanic_amount" type="number" step="0.01" value="{{ old('fixed_mechanic_amount', $jobType->fixed_mechanic_amount ?? 0) }}" />
                    <x-input label="Monto Taller" name="fixed_workshop_amount" type="number" step="0.01" value="{{ old('fixed_workshop_amount', $jobType->fixed_workshop_amount ?? 0) }}" />
                </div>

                <!-- Feature Toggles -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-white">
                        <div>
                            <span class="block text-sm font-bold text-slate-700">Mano de obra variable</span>
                            <span class="text-[10px] text-slate-400 uppercase font-bold">Permite editar costo manual</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="allow_custom_labor" class="sr-only peer" value="1" :checked="allowCustom" @change="allowCustom = !allowCustom">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-white">
                        <div>
                            <span class="block text-sm font-bold text-slate-700">Permitir productos</span>
                            <span class="text-[10px] text-slate-400 uppercase font-bold">Agregar repuestos al trabajo</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="allow_products" class="sr-only peer" value="1" :checked="allowProducts" @change="allowProducts = !allowProducts">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>
                    <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 bg-white" x-data="{ active: {{ old('is_active', $jobType->is_active ?? 1) ? 'true' : 'false' }} }">
                        <div>
                            <span class="block text-sm font-bold text-slate-700">Estado Activo</span>
                            <span class="text-[10px] text-slate-400 uppercase font-bold">Visible en formularios</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" class="sr-only peer" value="1" :checked="active" @change="active = !active">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>

                <!-- Default Description -->
                <div>
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Descripción por defecto (Opcional)</label>
                    <textarea name="default_description" rows="2" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" placeholder="Este texto aparecerá al seleccionar este tipo de trabajo...">{{ old('default_description', $jobType->default_description ?? '') }}</textarea>
                </div>

                <div class="pt-6 border-t border-slate-100 flex justify-end">
                    <x-button variant="primary" type="submit" class="px-12">
                        {{ isset($jobType) ? 'Guardar Cambios' : 'Crear Tipo de Trabajo' }}
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
