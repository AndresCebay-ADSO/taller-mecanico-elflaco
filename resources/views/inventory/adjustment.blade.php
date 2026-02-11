<x-app-layout>
    <x-page-header title="Ajuste de Inventario" subtitle="Corrección manual de stock por daño, pérdida o inventario físico.">
        <x-slot name="actions">
            <x-button variant="outline" onclick="window.location.href='{{ route('inventory.index') }}'">
                Cancelar
            </x-button>
        </x-slot>
    </x-page-header>

    <div class="max-w-xl">
        <x-card>
            <form action="{{ route('inventory.store-adjustment') }}" method="POST" class="space-y-6">
                @csrf
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Producto</label>
                        <select name="product_id" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm cursor-pointer">
                            @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} (Stock actual: {{ $product->stock }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Cantidad (Positivo para sumar, Negativo para restar)</label>
                        <x-input name="quantity" type="number" required placeholder="Ej. -2" />
                        <p class="mt-1 text-[10px] text-slate-400 font-medium">Usa números negativos para registrar pérdidas o salidas manuales.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Razón del Ajuste</label>
                        <select name="reason" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm cursor-pointer">
                            <option value="adjustment">Inventario Físico / Ajuste General</option>
                            <option value="damage">Producto Dañado</option>
                            <option value="loss">Pérdida / Robo</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <x-input label="Notas adicionales / Explicación" name="notes" placeholder="Explica el motivo del ajuste..." />
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100">
                    <x-button variant="primary" type="submit" class="w-full md:w-auto">
                        Aplicar Ajuste
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
