<x-app-layout>
<div class="ds" x-data="batchCorrection()">
    <x-page-header title="Trazabilidad" subtitle="Cada entrada y salida de inventario, con su lote y precio">
        <x-slot name="actions">
            @can('admin')
                <x-button variant="secondary" href="{{ route('inventory.adjustment') }}">Ajuste manual</x-button>
            @endcan
            <x-button href="{{ route('inventory.purchase') }}">Registrar compra</x-button>
        </x-slot>
    </x-page-header>

    <x-search-filter action="{{ route('inventory.index') }}" searchPlaceholder="Producto, proveedor o referencia">
        <x-select label="Tipo de movimiento" name="type">
            <option value="">Todos</option>
            @foreach(\App\Models\InventoryMovement::TYPE_LABELS as $value => $label)
                <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
            @endforeach
        </x-select>
    </x-search-filter>

    <x-card flush>
        @if($movements->isNotEmpty())
            <x-table :headers="['Fecha', 'Producto', 'Movimiento', ['label' => 'Cantidad', 'align' => 'right'], ['label' => 'Precio unit.', 'align' => 'right'], 'Proveedor o referencia', ['label' => '', 'align' => 'right']]">
                @foreach($movements as $mov)
                    <tr class="transition-colors hover:bg-muted/50">
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums text-muted-foreground">{{ $mov->movement_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $mov->product?->name ?? 'Producto eliminado' }}</p>
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ $mov->product?->category ?? '—' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <x-status-badge :tone="$mov->directionTone()" :icon="$mov->directionIcon()">{{ $mov->typeLabel() }}</x-status-badge>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums {{ \App\Support\Tone::text($mov->directionTone()) }}">{{ $mov->signedQuantity() }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-muted-foreground">{{ money($mov->unit_price) }}</td>
                        <td class="max-w-56 truncate px-4 py-3" title="{{ $mov->supplier->name ?? $mov->reference }}">{{ $mov->supplier->name ?? $mov->reference ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-2 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button" x-on:click="openDetails({{ \Illuminate\Support\Js::from($mov) }})" title="Ver detalle"
                                        class="inline-flex size-9 cursor-pointer items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground">
                                    <i data-lucide="eye" class="size-4"></i>
                                    <span class="sr-only">Ver detalle del movimiento</span>
                                </button>
                                @if($mov->movement_type === 'purchase' && $mov->batch && auth()->user()->can('admin'))
                                    <button type="button" x-on:click="openModal({{ \Illuminate\Support\Js::from($mov->batch) }})" title="Corregir lote"
                                            class="inline-flex size-9 cursor-pointer items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground">
                                        <i data-lucide="pencil" class="size-4"></i>
                                        <span class="sr-only">Corregir el lote de esta compra</span>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @else
            <div class="p-5">
                <x-empty-state icon="history">
                    {{ request()->except('page') ? 'Ningún movimiento coincide con la búsqueda.' : 'Aún no hay movimientos de inventario.' }}
                </x-empty-state>
            </div>
        @endif

        @if($movements->hasPages())
            <x-slot name="footer">{{ $movements->links() }}</x-slot>
        @endif
    </x-card>

    {{-- Detalle del movimiento --}}
    <template x-if="showDetailsModal">
        <div class="ds fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="movement-title"
             x-on:keydown.escape.window="showDetailsModal = false">
            <div class="absolute inset-0 bg-overlay" @click="showDetailsModal = false"></div>
            <div class="relative w-full max-w-lg overflow-hidden rounded-lg border border-border bg-card text-foreground shadow-lg">
                <div class="flex items-center justify-between border-b border-border px-5 py-4">
                    <h2 id="movement-title" class="text-base font-semibold">Detalle del movimiento</h2>
                    <button type="button" @click="showDetailsModal = false" class="-m-1 cursor-pointer rounded-md p-1 text-muted-foreground hover:bg-muted hover:text-foreground" title="Cerrar">
                        <i data-lucide="x" class="size-4"></i>
                        <span class="sr-only">Cerrar</span>
                    </button>
                </div>
                <dl class="grid grid-cols-2 gap-4 p-5 text-sm">
                    <div>
                        <dt class="text-xs font-medium text-muted-foreground">Tipo</dt>
                        <dd class="mt-0.5" x-text="(@js(\App\Models\InventoryMovement::TYPE_LABELS))[movement?.movement_type] || movement?.movement_type"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted-foreground">Fecha</dt>
                        <dd class="mt-0.5 tabular-nums" x-text="movement ? new Date(movement.movement_date).toLocaleDateString('es-CO', { timeZone: 'UTC' }) : ''"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted-foreground">Producto</dt>
                        <dd class="mt-0.5" x-text="movement?.product?.name || 'Producto eliminado'"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted-foreground">Cantidad</dt>
                        <dd class="mt-0.5 font-medium tabular-nums" x-text="movement ? (movement.quantity > 0 ? '+' : '−') + Math.abs(movement.quantity) : ''"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted-foreground">Precio unitario</dt>
                        <dd class="mt-0.5 tabular-nums" x-text="movement ? formatCurrency(movement.unit_price) : ''"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted-foreground">Proveedor o referencia</dt>
                        <dd class="mt-0.5" x-text="movement?.supplier?.name || movement?.reference || '—'"></dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-xs font-medium text-muted-foreground">Notas</dt>
                        <dd class="mt-0.5 whitespace-pre-wrap" x-text="movement?.notes || 'Sin notas.'"></dd>
                    </div>
                </dl>
                <div class="flex justify-end border-t border-border px-5 py-4">
                    <x-button variant="secondary" @click="showDetailsModal = false">Cerrar</x-button>
                </div>
            </div>
        </div>
    </template>

    {{-- Corrección de lote (solo administrador) --}}
    <template x-if="showModal">
        <div class="ds fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="batch-title"
             x-on:keydown.escape.window="showModal = false">
            <div class="absolute inset-0 bg-overlay" @click="showModal = false"></div>
            <form :action="'{{ url('batches') }}/' + batch?.id" method="POST"
                  class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-lg border border-border bg-card text-foreground shadow-lg">
                @csrf
                @method('PATCH')
                <div class="flex items-center justify-between border-b border-border px-5 py-4">
                    <h2 id="batch-title" class="text-base font-semibold">Corregir lote de compra</h2>
                    <button type="button" @click="showModal = false" class="-m-1 cursor-pointer rounded-md p-1 text-muted-foreground hover:bg-muted hover:text-foreground" title="Cerrar">
                        <i data-lucide="x" class="size-4"></i>
                        <span class="sr-only">Cerrar</span>
                    </button>
                </div>

                <div class="flex-1 space-y-4 overflow-y-auto p-5">
                    <x-select label="Proveedor" name="supplier_id" id="batch-supplier" required x-model="form.supplier_id">
                        <option value="">Selecciona un proveedor</option>
                        @foreach($activeSuppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </x-select>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-input label="Costo unitario" name="cost_price" inputmode="decimal" required x-model="form.cost_price" />
                        <x-input label="Precio de venta" name="sale_price" inputmode="decimal" required x-model="form.sale_price" />
                    </div>
                    <div>
                        <x-input label="Cantidad" name="quantity" inputmode="numeric" pattern="[0-9]*" required x-model="form.quantity"
                                 x-bind:readonly="!canEditQuantity" />
                        <p x-show="!canEditQuantity" class="mt-1.5 text-xs text-warning-text">
                            Este lote ya tiene ventas. Para cambiar el stock usa el <a href="{{ route('inventory.adjustment') }}" class="underline">ajuste manual</a>.
                        </p>
                    </div>
                    <x-textarea label="Motivo de la corrección" name="notes" required minlength="10" rows="3" x-model="form.notes"
                                placeholder="Por qué se corrige este lote" help="Mínimo 10 caracteres." />
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-border px-5 py-4 sm:flex-row sm:justify-between">
                    <x-button variant="secondary" @click="showModal = false">Cancelar</x-button>
                    <x-button type="submit">Guardar cambios</x-button>
                </div>
            </form>
        </div>
    </template>
</div>

<script>
    function batchCorrection() {
        return {
            showModal: false,
            showDetailsModal: false,
            batch: null,
            movement: null,
            form: { supplier_id: '', cost_price: '', sale_price: '', quantity: '', notes: '' },
            get canEditQuantity() {
                return this.batch && this.batch.remaining_stock == this.batch.quantity;
            },
            openModal(batch) {
                this.batch = batch;
                this.form.supplier_id = batch.supplier_id;
                this.form.cost_price = Number(batch.cost_price);
                this.form.sale_price = Number(batch.sale_price);
                this.form.quantity = batch.quantity;
                this.form.notes = '';
                this.showModal = true;
            },
            openDetails(movement) {
                this.movement = movement;
                this.showDetailsModal = true;
            },
        };
    }
</script>
</x-app-layout>
