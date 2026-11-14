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

    $inputClass = 'block h-10 w-full rounded-md border border-input bg-card px-3 text-sm text-foreground shadow-xs placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30';
    $readonlyClass = 'block h-10 w-full cursor-not-allowed rounded-md border border-border bg-muted px-3 text-sm text-muted-foreground';
    $labelClass = 'mb-1.5 block text-sm font-medium text-foreground';
@endphp

<div x-show="showJobModal"
     class="ds fixed inset-0 z-50 flex items-center justify-center p-4"
     role="dialog"
     aria-modal="true"
     aria-labelledby="job-modal-title"
     x-on:keydown.escape.window="showJobModal = false"
     x-cloak>
    <div class="absolute inset-0 bg-overlay" x-show="showJobModal" x-transition.opacity></div>

    <div class="relative flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-lg border border-border bg-card text-foreground shadow-lg"
         x-on:click.outside="showJobModal = false"
         x-show="showJobModal"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0">

        <div class="flex items-center justify-between border-b border-border px-5 py-4">
            <h2 id="job-modal-title" class="text-base font-semibold">Nuevo trabajo</h2>
            <button type="button" x-on:click="showJobModal = false" class="-m-1 cursor-pointer rounded-md p-1 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground" title="Cerrar">
                <i data-lucide="x" class="size-4"></i>
                <span class="sr-only">Cerrar</span>
            </button>
        </div>

        <form action="{{ $action }}"
              method="POST"
              class="flex min-h-0 flex-1 flex-col"
              x-data="jobForm({
                  products: @js($products),
                  jobTypes: @js($jobTypeOptions),
                  old: @js(old()),
              })">
            @csrf

            <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5 custom-scrollbar">
                {{-- Cliente y vehículo --}}
                @if($serviceOrder)
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="job-customer" class="{{ $labelClass }}">Cliente</label>
                            <input id="job-customer" type="text" value="{{ $serviceOrder->customer_name }}" readonly class="{{ $readonlyClass }}">
                        </div>
                        <div>
                            <label for="job-vehicle" class="{{ $labelClass }}">Vehículo</label>
                            <input id="job-vehicle" type="text" value="{{ $serviceOrder->vehicle_info }}" readonly class="{{ $readonlyClass }}">
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="job-customer" class="{{ $labelClass }}">Cliente <span class="text-destructive-text">*</span></label>
                            <input id="job-customer" type="text" name="customer_name" value="{{ old('customer_name') }}" required maxlength="255" placeholder="Nombre del cliente" class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label for="job-phone" class="{{ $labelClass }}">Teléfono</label>
                            <input id="job-phone" type="tel" name="customer_phone" value="{{ old('customer_phone') }}" maxlength="20" placeholder="3001234567" class="{{ $inputClass }}">
                        </div>
                    </div>
                    <div>
                        <label for="job-vehicle" class="{{ $labelClass }}">Vehículo <span class="text-destructive-text">*</span></label>
                        <input id="job-vehicle" type="text" name="vehicle_info" value="{{ old('vehicle_info') }}" required maxlength="255" placeholder="Ej.: Honda CB190R - ABC12D" class="{{ $inputClass }}">
                    </div>
                @endif

                {{-- Tipo de trabajo --}}
                <div>
                    <label for="job-type" class="{{ $labelClass }}">Tipo de trabajo <span class="text-destructive-text">*</span></label>
                    <select id="job-type" name="job_type_id" x-model="jobTypeId" required class="{{ $inputClass }} cursor-pointer pr-8">
                        <option value="">Selecciona un tipo</option>
                        @foreach($jobTypeOptions as $type)
                            <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                        @endforeach
                    </select>
                    <p x-show="jobType?.default_description" x-cloak class="mt-1.5 text-xs text-muted-foreground" x-text="jobType?.default_description"></p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="job-description" class="{{ $labelClass }}">Descripción <span class="text-destructive-text">*</span></label>
                        <input id="job-description" type="text" name="description" value="{{ old('description') }}" required maxlength="255" placeholder="Ej.: Cambio de llanta trasera" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label for="job-mechanic" class="{{ $labelClass }}">Mecánico <span class="text-destructive-text">*</span></label>
                        <select id="job-mechanic" name="mechanic_id" required class="{{ $inputClass }} cursor-pointer pr-8">
                            <option value="">Selecciona un mecánico</option>
                            @foreach($mechanics as $mech)
                                <option value="{{ $mech->id }}" @selected(old('mechanic_id') == $mech->id)>{{ $mech->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Repuestos --}}
                <div class="space-y-3 border-t border-border pt-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-sm font-medium">Repuestos utilizados</h3>
                        <label for="job-add-product" class="sr-only">Agregar repuesto</label>
                        <select id="job-add-product"
                                x-show="allowsProducts"
                                x-on:change="addProduct($event.target.value); $event.target.value = ''"
                                class="h-9 w-full cursor-pointer rounded-md border border-input bg-card pl-3 pr-8 text-sm text-foreground shadow-xs focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30 sm:w-72">
                            <option value="">Agregar repuesto…</option>
                            @foreach($products as $p)
                                <option value="{{ $p['id'] }}">{{ $p['name'] }} — {{ money($p['price']) }} · Stock {{ $p['stock'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <p x-show="!allowsProducts" x-cloak class="text-xs text-muted-foreground">Este tipo de trabajo no usa repuestos.</p>
                    <p x-show="allowsProducts && selectedProducts.length === 0" class="text-xs text-muted-foreground">Sin repuestos agregados.</p>

                    <ul class="divide-y divide-border rounded-md border border-border" x-show="selectedProducts.length > 0" x-cloak>
                        <template x-for="(p, index) in selectedProducts" :key="p.id">
                            <li class="flex items-center justify-between gap-3 px-3 py-2">
                                <div class="flex min-w-0 items-center gap-3">
                                    <input type="hidden" :name="`products[${index}][id]`" :value="p.id">
                                    <label :for="`job-qty-${index}`" class="sr-only" x-text="`Cantidad de ${p.name}`"></label>
                                    <input :id="`job-qty-${index}`" type="text" inputmode="numeric" pattern="[0-9]*"
                                           :name="`products[${index}][quantity]`" x-model.number="p.quantity" required
                                           :class="p.quantity > stockOf(p) && '!border-destructive'"
                                           class="h-8 w-14 rounded-md border border-input bg-card px-2 text-center text-sm tabular-nums text-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/30">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm" x-text="p.name" :title="p.name"></p>
                                        <p class="text-xs"
                                           :class="p.quantity > stockOf(p) ? 'text-destructive-text' : 'text-muted-foreground'"
                                           x-text="p.quantity > stockOf(p) ? `Solo hay ${stockOf(p)} en stock` : `Stock: ${stockOf(p)}`"></p>
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <span class="text-sm tabular-nums" x-text="money(lineTotal(p))"></span>
                                    <button type="button" x-on:click="removeProduct(index)" class="cursor-pointer rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-destructive-text" title="Quitar repuesto">
                                        <i data-lucide="trash-2" class="size-4"></i>
                                        <span class="sr-only">Quitar repuesto</span>
                                    </button>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>

                {{-- Mano de obra: se ve con puntos de miles; al servidor viaja el número limpio. --}}
                <div>
                    <label for="job-labor" class="{{ $labelClass }}">Mano de obra <span class="text-destructive-text">*</span></label>
                    <input type="hidden" name="labor_cost" :value="labor">
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">$</span>
                        <input id="job-labor" type="text" inputmode="numeric" required placeholder="0"
                               :value="labor ? labor.toLocaleString('es-CO') : ''"
                               x-on:input="laborCost = $event.target.value.replace(/\D/g, ''); $event.target.value = labor ? labor.toLocaleString('es-CO') : ''"
                               :readonly="isFixed"
                               :class="isFixed ? 'cursor-not-allowed !bg-muted !text-muted-foreground' : ''"
                               class="{{ $inputClass }} pl-7 tabular-nums">
                    </div>
                    <p x-show="isFixed" x-cloak class="mt-1.5 text-xs text-muted-foreground">Monto fijo definido por el tipo de trabajo.</p>
                </div>

                {{-- Resumen --}}
                <div class="rounded-md border border-border bg-muted/40 p-4">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Repuestos</dt>
                            <dd class="tabular-nums" x-text="money(productsTotal)"></dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Mano de obra</dt>
                            <dd class="tabular-nums" x-text="money(labor)"></dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-t border-border pt-2">
                            <dt class="font-medium">Total</dt>
                            <dd class="text-lg font-semibold tabular-nums" x-text="money(grandTotal)"></dd>
                        </div>
                    </dl>
                    <dl class="mt-3 grid grid-cols-2 gap-3 border-t border-border pt-3 text-sm">
                        <div>
                            <dt class="text-xs text-muted-foreground" x-text="mechanicLabel"></dt>
                            <dd class="mt-0.5 font-medium tabular-nums" x-text="money(mechanicShare)"></dd>
                        </div>
                        <div class="text-right">
                            <dt class="text-xs text-muted-foreground">Taller (resto + repuestos)</dt>
                            <dd class="mt-0.5 font-medium tabular-nums" x-text="money(workshopShare)"></dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-border px-5 py-4 sm:flex-row sm:justify-between">
                <x-button variant="secondary" x-on:click="showJobModal = false">Cancelar</x-button>
                <x-button type="submit">Registrar trabajo</x-button>
            </div>
        </form>
    </div>
</div>
