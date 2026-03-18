@php
    $settings = \App\Models\Setting::pluck('value', 'key')->all();
@endphp

<x-app-layout>
<div class="py-6" x-data="{ activeTab: 'general' }">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Configuración del Sistema</h1>
            <p class="mt-2 text-slate-500 dark:text-gray-400 font-medium">Administra los datos de tu taller, facturación y preferencias globales.</p>
        </div>

        @if (session('status'))
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20 rounded-2xl flex items-center gap-3 animate-in fade-in slide-in-from-top-4 duration-300">
                <div class="h-8 w-8 bg-green-500 rounded-full flex items-center justify-center text-white shadow-lg shadow-green-500/20">
                    <i data-lucide="check" class="h-5 w-5"></i>
                </div>
                <p class="text-sm font-bold text-green-800 dark:text-green-400">{{ session('status') }}</p>
            </div>
        @endif

        <div class="flex flex-col md:flex-row gap-8">
            <!-- Sidebar Navigation -->
            <div class="w-full md:w-64 space-y-1">
                <button 
                    @click="activeTab = 'general'"
                    :class="activeTab === 'general' ? 'bg-[#3B3BF9] text-white shadow-lg shadow-blue-500/20' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-200 dark:hover:bg-gray-900'"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-bold transition-all duration-200"
                >
                    <i data-lucide="settings" class="h-5 w-5"></i>
                    General
                </button>
                <button 
                    @click="activeTab = 'billing'"
                    :class="activeTab === 'billing' ? 'bg-[#3B3BF9] text-white shadow-lg shadow-blue-500/20' : 'text-slate-600 dark:text-gray-400 hover:bg-slate-200 dark:hover:bg-gray-900'"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-bold transition-all duration-200"
                >
                    <i data-lucide="receipt" class="h-5 w-5"></i>
                    Facturación
                </button>
            </div>

            <!-- Content Area -->
            <div class="flex-1 bg-white dark:bg-gray-900/50 rounded-3xl shadow-sm border border-slate-200 dark:border-gray-800 overflow-hidden">
                <form action="{{ route('settings.update') }}" method="POST" class="p-8">
                    @csrf
                    @method('PUT')

                    <!-- General Tab -->
                    <div x-show="activeTab === 'general'" x-cloak class="space-y-6">
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-gray-300 mb-2">Nombre del Taller</label>
                                <input type="text" name="workshop_name" value="{{ $settings['workshop_name'] ?? '' }}" class="w-full rounded-2xl border-slate-200 dark:border-gray-800 bg-slate-50 dark:bg-gray-950 px-4 py-3 focus:ring-4 focus:ring-blue-500/10 focus:border-[#3B3BF9] transition-all dark:text-white" placeholder="MotoTaller El Flaco">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-gray-300 mb-2">NIT / Identificación</label>
                                <input type="text" name="workshop_nit" value="{{ $settings['workshop_nit'] ?? '' }}" class="w-full rounded-2xl border-slate-200 dark:border-gray-800 bg-slate-50 dark:bg-gray-950 px-4 py-3 focus:ring-4 focus:ring-blue-500/10 focus:border-[#3B3BF9] transition-all dark:text-white" placeholder="123456789-0">
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 dark:text-gray-300 mb-2">Teléfono</label>
                                    <input type="text" name="workshop_phone" value="{{ $settings['workshop_phone'] ?? '' }}" class="w-full rounded-2xl border-slate-200 dark:border-gray-800 bg-slate-50 dark:bg-gray-950 px-4 py-3 focus:ring-4 focus:ring-blue-500/10 focus:border-[#3B3BF9] transition-all dark:text-white" placeholder="+57 321 000 0000">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 dark:text-gray-300 mb-2">Correo Electrónico</label>
                                    <input type="email" name="workshop_email" value="{{ $settings['workshop_email'] ?? '' }}" class="w-full rounded-2xl border-slate-200 dark:border-gray-800 bg-slate-50 dark:bg-gray-950 px-4 py-3 focus:ring-4 focus:ring-blue-500/10 focus:border-[#3B3BF9] transition-all dark:text-white" placeholder="contacto@elflaco.com">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-gray-300 mb-2">Dirección</label>
                                <input type="text" name="workshop_address" value="{{ $settings['workshop_address'] ?? '' }}" class="w-full rounded-2xl border-slate-200 dark:border-gray-800 bg-slate-50 dark:bg-gray-950 px-4 py-3 focus:ring-4 focus:ring-blue-500/10 focus:border-[#3B3BF9] transition-all dark:text-white" placeholder="Calle 123 # 45 - 67, Medellín">
                            </div>
                        </div>
                    </div>

                    <!-- Billing Tab -->
                    <div x-show="activeTab === 'billing'" x-cloak class="space-y-6">
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-gray-300 mb-2">Porcentaje de Impuesto (IVA %)</label>
                                <div class="relative">
                                    <input type="number" step="0.01" name="tax_percentage" value="{{ $settings['tax_percentage'] ?? '19' }}" class="w-full rounded-2xl border-slate-200 dark:border-gray-800 bg-slate-50 dark:bg-gray-950 pl-4 pr-12 py-3 focus:ring-4 focus:ring-blue-500/10 focus:border-[#3B3BF9] transition-all dark:text-white">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">%</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-gray-300 mb-2">Pie de Página de Factura</label>
                                <textarea name="footer_text_invoice" rows="4" class="w-full rounded-2xl border-slate-200 dark:border-gray-800 bg-slate-50 dark:bg-gray-950 px-4 py-3 focus:ring-4 focus:ring-blue-500/10 focus:border-[#3B3BF9] transition-all dark:text-white" placeholder="Gracias por confiar en MotoTaller El Flaco.">{{ $settings['footer_text_invoice'] ?? '' }}</textarea>
                                <p class="mt-2 text-xs text-slate-400 italic">Este texto aparecerá al final de todas tus facturas y órdenes generadas.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-10 pt-6 border-t border-slate-100 dark:border-gray-800 flex justify-end">
                        <button type="submit" class="bg-[#3B3BF9] text-white px-8 py-4 rounded-2xl font-black shadow-lg shadow-blue-500/20 hover:bg-blue-600 active:scale-[0.98] transition-all">
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
