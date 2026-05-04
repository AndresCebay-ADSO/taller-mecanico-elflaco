<x-app-layout>
    <x-page-header title="Nueva Transferencia" subtitle="Transferir inventario entre sucursales">
        <x-slot name="actions">
            <x-button variant="slate" onclick="window.location.href='{{ route('branch-transfers.index') }}'">
                <i data-lucide="arrow-left" class="mr-2 h-4 w-4"></i>
                Volver
            </x-button>
        </x-slot>
    </x-page-header>

    <x-card>
        <form action="{{ route('branch-transfers.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="from_branch_id" class="block text-xs font-bold text-slate-400 dark:text-gray-500 uppercase mb-2 ml-1">Sucursal Origen</label>
                    <select name="from_branch_id" id="from_branch_id" required
                        class="w-full rounded-2xl border-slate-200 dark:border-gray-800 bg-white dark:bg-gray-950/50 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-4 px-4 text-sm font-bold text-slate-700 dark:text-gray-300 transition-all">
                        <option value="">Seleccionar sucursal</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('from_branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('from_branch_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="to_branch_id" class="block text-xs font-bold text-slate-400 dark:text-gray-500 uppercase mb-2 ml-1">Sucursal Destino</label>
                    <select name="to_branch_id" id="to_branch_id" required
                        class="w-full rounded-2xl border-slate-200 dark:border-gray-800 bg-white dark:bg-gray-950/50 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-4 px-4 text-sm font-bold text-slate-700 dark:text-gray-300 transition-all">
                        <option value="">Seleccionar sucursal</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('to_branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('to_branch_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="product_id" class="block text-xs font-bold text-slate-400 dark:text-gray-500 uppercase mb-2 ml-1">Producto</label>
                <select name="product_id" id="product_id" required
                    class="w-full rounded-2xl border-slate-200 dark:border-gray-800 bg-white dark:bg-gray-950/50 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-4 px-4 text-sm font-bold text-slate-700 dark:text-gray-300 transition-all">
                    <option value="">Seleccionar producto</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }} (Stock: {{ $product->stock }})
                        </option>
                    @endforeach
                </select>
                @error('product_id')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="quantity" class="block text-xs font-bold text-slate-400 dark:text-gray-500 uppercase mb-2 ml-1">Cantidad</label>
                    <input type="number" name="quantity" id="quantity" required min="1"
                        value="{{ old('quantity') }}"
                        class="w-full rounded-2xl border-slate-200 dark:border-gray-800 bg-white dark:bg-gray-950/50 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-4 px-4 text-sm font-bold text-slate-700 dark:text-gray-300 transition-all">
                    @error('quantity')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="unit_price" class="block text-xs font-bold text-slate-400 dark:text-gray-500 uppercase mb-2 ml-1">Precio Unitario (Opcional)</label>
                    <input type="number" name="unit_price" id="unit_price" step="0.01" min="0"
                        value="{{ old('unit_price') }}"
                        class="w-full rounded-2xl border-slate-200 dark:border-gray-800 bg-white dark:bg-gray-950/50 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-4 px-4 text-sm font-bold text-slate-700 dark:text-gray-300 transition-all">
                    @error('unit_price')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="reference" class="block text-xs font-bold text-slate-400 dark:text-gray-500 uppercase mb-2 ml-1">Referencia (Opcional)</label>
                <input type="text" name="reference" id="reference"
                    value="{{ old('reference') }}"
                    class="w-full rounded-2xl border-slate-200 dark:border-gray-800 bg-white dark:bg-gray-950/50 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-4 px-4 text-sm font-bold text-slate-700 dark:text-gray-300 transition-all">
                @error('reference')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="notes" class="block text-xs font-bold text-slate-400 dark:text-gray-500 uppercase mb-2 ml-1">Notas (Opcional)</label>
                <textarea name="notes" id="notes" rows="3"
                    class="w-full rounded-2xl border-slate-200 dark:border-gray-800 bg-white dark:bg-gray-950/50 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-4 px-4 text-sm font-bold text-slate-700 dark:text-gray-300 transition-all">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                <x-button variant="slate" type="button" onclick="window.location.href='{{ route('branch-transfers.index') }}'">
                    Cancelar
                </x-button>
                <x-button variant="primary" type="submit">
                    <i data-lucide="save" class="mr-2 h-4 w-4"></i>
                    Crear Transferencia
                </x-button>
            </div>
        </form>
    </x-card>

    <script>
        lucide.createIcons();
    </script>
</x-app-layout>
