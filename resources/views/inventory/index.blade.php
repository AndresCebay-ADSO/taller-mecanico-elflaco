<x-app-layout>
    <x-page-header title="Movimientos de Inventario" subtitle="Trazabilidad completa de entradas y salidas.">
        <x-slot name="actions">
            <x-button variant="secondary" onclick="window.location.href='{{ route('inventory.adjustment') }}'">
                <i data-lucide="sliders" class="mr-2 h-4 w-4"></i>
                Ajuste Manual
            </x-button>
            <x-button variant="primary" onclick="window.location.href='{{ route('inventory.purchase') }}'">
                <i data-lucide="shopping-cart" class="mr-2 h-4 w-4"></i>
                Registrar Compra
            </x-button>
        </x-slot>
    </x-page-header>

    <x-search-filter action="{{ route('inventory.index') }}" searchPlaceholder="Producto, proveedor o referencia...">
        <div class="space-y-2">
            <label for="type" class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest ml-1">Tipo de Movimiento</label>
            <select name="type" id="type" class="w-full rounded-2xl border-slate-200 bg-white shadow-sm focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 py-4 px-4 text-sm font-bold text-slate-700 transition-all dark:bg-slate-950/40 dark:border-slate-800 dark:text-white dark:focus:bg-slate-950/60">
                <option value="">Todos los tipos</option>
                <option value="purchase" {{ request('type') == 'purchase' ? 'selected' : '' }}>Compra</option>
                <option value="sale" {{ request('type') == 'sale' ? 'selected' : '' }}>Venta</option>
                <option value="job_usage" {{ request('type') == 'job_usage' ? 'selected' : '' }}>Uso en Orden</option>
                <option value="adjustment" {{ request('type') == 'adjustment' ? 'selected' : '' }}>Ajuste</option>
                <option value="reversal" {{ request('type') == 'reversal' ? 'selected' : '' }}>Anulación</option>
            </select>
        </div>
    </x-search-filter>

    <div x-data="batchCorrection()">
    <x-card>
        <x-table :headers="['Fecha', 'Producto', 'Tipo', 'Cantidad', 'Precio Unit.', 'Proveedor / Ref', 'Acciones']">
            @forelse($movements as $mov)
            <tr>
                <td class="px-6 py-4 text-sm text-slate-500 font-medium">
                    {{ $mov->movement_date->format('d/m/Y') }}
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm font-bold text-slate-900">{{ $mov->product->name }}</div>
                    <div class="text-[10px] text-slate-400 font-bold uppercase">{{ $mov->product->category }}</div>
                </td>
                <td class="px-6 py-4">
                    @php
                        $typeBadge = match($mov->movement_type) {
                            'purchase'  => 'emerald',
                            'sale'      => 'blue',
                            'job_usage' => 'amber',
                            'adjustment'=> 'slate',
                            'reversal'  => 'red',
                            default     => 'slate'
                        };
                        $typeLabel = match($mov->movement_type) {
                            'purchase'  => 'Compra',
                            'sale'      => 'Venta',
                            'job_usage' => 'Uso en Orden',
                            'adjustment'=> 'Ajuste',
                            'reversal'  => 'Anulación',
                            default     => $mov->movement_type
                        };
                    @endphp
                    <x-badge :variant="$typeBadge" class="uppercase text-[9px]">
                        {{ $typeLabel }}
                    </x-badge>
                </td>
                <td class="px-6 py-4 text-sm font-black {{ $mov->quantity > 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $mov->quantity > 0 ? '+' : '' }}{{ $mov->quantity }}
                </td>
                <td class="px-6 py-4 text-sm font-bold text-slate-600">
                    ${{ number_format($mov->unit_price, 0, ',', '.') }}
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-slate-700 font-medium">{{ $mov->supplier->name ?? $mov->reference ?? 'N/A' }}</div>
                </td>
                <td class="px-6 py-4 text-right text-sm font-medium">
                    <div class="flex justify-end gap-2">
                        <!-- Eye Icon -->
                        <button type="button" 
                                @click='openDetails(@json($mov))' 
                                class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-300 transition-colors cursor-pointer" 
                                title="Ver Detalles">
                            <i data-lucide="eye" class="h-4 w-4"></i>
                        </button>
                        
                        <!-- Pencil Icon -->
                        @if($mov->movement_type === 'purchase' && $mov->batch)
                            <button type="button" 
                                    @click='openModal(@json($mov->batch))' 
                                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-300 transition-colors cursor-pointer" 
                                    title="Corregir Lote">
                                <i data-lucide="pencil" class="h-4 w-4"></i>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                    No hay movimientos registrados.
                </td>
            </tr>
            @endforelse
        </x-table>
        <div class="mt-4">
            {{ $movements->links() }}
        </div>

        <!-- Details Modal -->
        <template x-if="showDetailsModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm"
                 @click.self="showDetailsModal = false"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100">
                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden"
                     x-transition:enter="transition ease-out duration-300 transform"
                     x-transition:enter-start="scale-95 opacity-0"
                     x-transition:enter-end="scale-100 opacity-100">
                    <div class="flex items-center justify-between p-6 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Detalles del Movimiento</h3>
                        <button type="button" @click="showDetailsModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors cursor-pointer">
                            ✕
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Tipo</span>
                                <p class="text-sm font-bold text-slate-900 dark:text-white mt-1" x-text="movement?.movement_type === 'purchase' ? 'Compra' : (movement?.movement_type === 'sale' ? 'Venta' : (movement?.movement_type === 'adjustment' ? 'Ajuste' : (movement?.movement_type === 'job_usage' ? 'Uso en Orden' : 'Anulación')))"></p>
                            </div>
                            <div>
                                <span class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Fecha</span>
                                <p class="text-sm font-bold text-slate-900 dark:text-white mt-1" x-text="movement ? new Date(movement.movement_date).toLocaleDateString('es-ES') : ''"></p>
                            </div>
                            <div>
                                <span class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Producto</span>
                                <p class="text-sm font-bold text-slate-900 dark:text-white mt-1" x-text="movement?.product?.name"></p>
                            </div>
                            <div>
                                <span class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Cantidad</span>
                                <p class="text-sm font-bold mt-1" :class="movement?.quantity > 0 ? 'text-emerald-600' : 'text-red-600'" x-text="movement ? (movement.quantity > 0 ? '+' : '') + movement.quantity : ''"></p>
                            </div>
                            <div>
                                <span class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Precio Unitario</span>
                                <p class="text-sm font-bold text-slate-900 dark:text-white mt-1" x-text="movement ? '$' + Number(movement.unit_price).toLocaleString('es-CO') : ''"></p>
                            </div>
                            <div>
                                <span class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Proveedor / Ref</span>
                                <p class="text-sm font-bold text-slate-900 dark:text-white mt-1" x-text="movement?.supplier?.name || movement?.reference || 'N/A'"></p>
                            </div>
                            <div class="col-span-2">
                                <span class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Notas</span>
                                <p class="text-sm text-slate-700 dark:text-slate-300 mt-1 italic" x-text="movement?.notes || 'Sin notas registradas.'"></p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 flex justify-end">
                        <button type="button" @click="showDetailsModal = false" class="px-4 py-2 text-sm font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">Cerrar</button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Correction Modal -->
        <template x-if="showModal">
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm"
                 @click.self="showModal = false"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100">
                <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden"
                     x-transition:enter="transition ease-out duration-300 transform"
                     x-transition:enter-start="scale-95 opacity-0"
                     x-transition:enter-end="scale-100 opacity-100">
                    <div class="flex items-center justify-between p-6 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Corregir Lote de Compra</h3>
                        <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors cursor-pointer">
                            ✕
                        </button>
                    </div>

                    <form :action="'{{ url('batches') }}/' + batch?.id" method="POST" class="p-6 space-y-4 text-left">
                        @csrf
                        @method('PATCH')
                        
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Proveedor</label>
                            <select name="supplier_id" x-model="form.supplier_id" required class="w-full rounded-xl border-slate-200 bg-slate-50 dark:bg-slate-950/40 dark:border-slate-800 focus:ring-brand-500 focus:border-brand-500 text-sm">
                                <option value="">Seleccione un proveedor...</option>
                                @foreach($activeSuppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Costo Unitario</label>
                                <input type="number" step="0.01" name="cost_price" x-model="form.cost_price" required class="w-full rounded-xl border-slate-200 bg-slate-50 dark:bg-slate-950/40 dark:border-slate-800 focus:ring-brand-500 focus:border-brand-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Precio Venta</label>
                                <input type="number" step="0.01" name="sale_price" x-model="form.sale_price" required class="w-full rounded-xl border-slate-200 bg-slate-50 dark:bg-slate-950/40 dark:border-slate-800 focus:ring-brand-500 focus:border-brand-500 text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Cantidad</label>
                            <input type="number" name="quantity" x-model="form.quantity" :disabled="!canEditQuantity" :class="{'opacity-50 bg-slate-100 cursor-not-allowed': !canEditQuantity}" required class="w-full rounded-xl border-slate-200 bg-slate-50 dark:bg-slate-950/40 dark:border-slate-800 focus:ring-brand-500 focus:border-brand-500 text-sm">
                            <p x-show="!canEditQuantity" class="mt-1 text-xs text-amber-600 dark:text-amber-500 font-medium whitespace-normal leading-tight">Este lote ya tiene ventas registradas. Para ajustar el stock usa Ajuste Manual.</p>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1">Motivo de corrección (Mín. 10 caracteres)</label>
                            <textarea name="notes" x-model="form.notes" required minlength="10" rows="3" class="w-full rounded-xl border-slate-200 bg-slate-50 dark:bg-slate-950/40 dark:border-slate-800 focus:ring-brand-500 focus:border-brand-500 text-sm" placeholder="Especificar el porqué de la corrección..."></textarea>
                        </div>
                        
                        <div class="pt-4 flex justify-end space-x-3">
                            <button type="button" @click="showModal = false" class="px-4 py-2 text-sm font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">Cancelar</button>
                            <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-brand-600 rounded-xl hover:bg-brand-700 transition-colors shadow-sm">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

    </x-card>
    </div>

    <script>
        function batchCorrection() {
            return {
                showModal: false,
                showDetailsModal: false,
                batch: null,
                movement: null,
                form: {
                    supplier_id: '',
                    cost_price: '',
                    sale_price: '',
                    quantity: '',
                    notes: '',
                },
                get canEditQuantity() {
                    return this.batch && this.batch.remaining_stock == this.batch.quantity;
                },
                openModal(batch) {
                    this.batch = batch;
                    this.form.supplier_id = batch.supplier_id;
                    this.form.cost_price = batch.cost_price;
                    this.form.sale_price = batch.sale_price;
                    this.form.quantity = batch.quantity;
                    this.form.notes = '';
                    this.showModal = true;
                },
                openDetails(movement) {
                    this.movement = movement;
                    this.showDetailsModal = true;
                }
            }
        }
    </script>
</x-app-layout>