<x-app-layout>
{{-- El estado del modal vive aquí: <x-app-layout> no reenvía atributos como x-data. --}}
<div class="ds" x-data="{ showJobModal: {{ $errors->any() && old('job_type_id') ? 'true' : 'false' }} }">
    @php
        $orderNumber = 'Orden ' . $serviceOrder->displayNumber();
        $billableJobs = $serviceOrder->workshopJobs->where('status', '!=', 'cancelled');
        $totalLabor = $billableJobs->sum('total_amount');
        $totalProductsCost = $billableJobs->sum(fn ($j) => $j->jobProducts->sum('total_price'));
        $invoice = $serviceOrder->invoices->first(fn ($i) => !$i->isCancelled());
        $cancelledInvoices = $serviceOrder->invoices->filter(fn ($i) => $i->isCancelled());
        $canInvoice = !$invoice
            && in_array($serviceOrder->status, ['in_progress', 'completed'], true)
            && $billableJobs->isNotEmpty()
            && $billableJobs->every(fn ($j) => $j->status === 'completed');
        $canDeleteJobs = $serviceOrder->canReceiveJobs() && $serviceOrder->invoices->every(fn ($i) => $i->isCancelled());
    @endphp

    <x-page-header :title="$orderNumber"
                   :subtitle="$serviceOrder->customer_name . ' · ' . $serviceOrder->vehicle_info"
                   :back="route('service-orders.index')" back-label="Órdenes">
        <x-slot name="badge">
            <x-status-badge :tone="$serviceOrder->statusTone()">{{ $serviceOrder->statusLabel() }}</x-status-badge>
        </x-slot>
        <x-slot name="actions">
            <x-button variant="secondary" href="{{ route('documents.intake', ['serviceOrder' => $serviceOrder, 'auto' => 1]) }}" target="_blank"
                      title="Para que el cliente la firme al dejar la moto">
                <i data-lucide="printer" class="size-4"></i>
                Orden de ingreso
            </x-button>
            <x-button variant="secondary" href="{{ route('service-orders.edit', $serviceOrder) }}">Editar</x-button>
            @if($serviceOrder->canReceiveJobs())
                <x-button @click="showJobModal = true">Añadir trabajo</x-button>
            @endif
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Motivo del servicio">
                <p class="whitespace-pre-wrap text-sm leading-relaxed">{{ $serviceOrder->service_description }}</p>
            </x-card>

            <section class="space-y-3">
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-semibold">Trabajos</h2>
                    <x-status-badge>{{ $serviceOrder->workshopJobs->count() }}</x-status-badge>
                </div>

                @forelse($serviceOrder->workshopJobs as $job)
                    @php $jobProductsTotal = $job->jobProducts->sum('total_price'); @endphp
                    <x-card flush>
                        <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-semibold">{{ $job->jobType?->name ?? 'Servicio' }}</h3>
                                    <x-status-badge :tone="$job->statusTone()">{{ $job->statusLabel() }}</x-status-badge>
                                </div>
                                <p class="mt-1 text-sm text-muted-foreground">Mecánico: {{ $job->mechanic?->name ?? 'Sin asignar' }}</p>
                                @if($job->description)
                                    <p class="mt-2 text-sm">{{ $job->description }}</p>
                                @endif
                            </div>

                            <div class="flex shrink-0 items-center gap-1">
                                @if(in_array($job->status, ['pending', 'in_progress'], true))
                                    <form action="{{ route('jobs.complete', $job) }}" method="POST">
                                        @csrf
                                        <x-button type="submit" variant="secondary" size="sm">Completar</x-button>
                                    </form>
                                @endif
                                @if($canDeleteJobs)
                                    <form action="{{ route('jobs.destroy', $job) }}" method="POST"
                                          data-confirm="¿Eliminar este trabajo?"
                                          data-confirm-description="Los repuestos que usó vuelven al inventario.{{ isset($paidCommissionWarnings[$job->id]) ? ' ' . $paidCommissionWarnings[$job->id] : '' }}"
                                          data-confirm-label="Eliminar trabajo"
                                          data-confirm-destructive>
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" variant="ghost" size="icon" class="hover:!text-destructive-text" title="Eliminar trabajo">
                                            <i data-lucide="trash-2" class="size-4"></i>
                                            <span class="sr-only">Eliminar trabajo</span>
                                        </x-button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        @if($job->jobProducts->isNotEmpty())
                            <div class="border-t border-border">
                                <x-table :headers="['Repuesto', ['label' => 'Cant.', 'align' => 'right'], ['label' => 'Valor', 'align' => 'right']]">
                                    @foreach($job->jobProducts as $jp)
                                        <tr>
                                            <td class="px-4 py-2.5">{{ $jp->product?->name ?? 'Producto eliminado' }}</td>
                                            <td class="px-4 py-2.5 text-right tabular-nums">{{ $jp->quantity }}</td>
                                            <td class="px-4 py-2.5 text-right tabular-nums">{{ money($jp->total_price) }}</td>
                                        </tr>
                                    @endforeach
                                </x-table>
                            </div>
                        @endif

                        <dl class="flex flex-wrap justify-end gap-x-6 gap-y-1 border-t border-border bg-muted/40 px-5 py-3 text-sm">
                            <div class="flex gap-2">
                                <dt class="text-muted-foreground">Mano de obra</dt>
                                <dd class="tabular-nums">{{ money($job->total_amount) }}</dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="text-muted-foreground">Repuestos</dt>
                                <dd class="tabular-nums">{{ money($jobProductsTotal) }}</dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="font-medium">Subtotal</dt>
                                <dd @class(['font-semibold tabular-nums', 'text-muted-foreground line-through' => $job->status === 'cancelled'])>{{ money($job->total_amount + $jobProductsTotal) }}</dd>
                            </div>
                        </dl>
                    </x-card>
                @empty
                    <x-empty-state icon="wrench">
                        Esta orden aún no tiene trabajos.
                        @if($serviceOrder->canReceiveJobs())
                            <x-slot name="action"><x-button variant="secondary" @click="showJobModal = true">Añadir trabajo</x-button></x-slot>
                        @endif
                    </x-empty-state>
                @endforelse
            </section>
        </div>

        <div class="space-y-6">
            <x-card title="Resumen">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Mano de obra</dt>
                        <dd class="tabular-nums">{{ money($totalLabor) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Repuestos</dt>
                        <dd class="tabular-nums">{{ money($totalProductsCost) }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4 border-t border-border pt-3">
                        <dt class="font-medium">Total</dt>
                        <dd class="text-2xl font-semibold tabular-nums">{{ money($totalLabor + $totalProductsCost) }}</dd>
                    </div>
                </dl>

                <div class="mt-5 space-y-3">
                    @if($invoice)
                        <x-button variant="secondary" class="w-full" href="{{ route('invoices.show', $invoice) }}">
                            Ver factura <span class="font-mono">{{ $invoice->invoice_number }}</span>
                        </x-button>
                    @elseif($canInvoice)
                        <form action="{{ route('invoices.generate', $serviceOrder) }}" method="POST" class="space-y-3">
                            @csrf
                            @if($cashClosed)
                                <x-alert type="warning" message="La caja de hoy ya se cerró: no se reciben pagos en efectivo. Si te pagan en efectivo, anótalo en el cuaderno, deja la plata en el cajón y regístralo mañana. Nequi, transferencia y los demás métodos sí se pueden registrar." />
                            @endif
                            <x-select label="¿Cómo pagó?" name="payment_method" required>
                                @foreach(\App\Support\PaymentMethod::ALL as $method)
                                    <option value="{{ $method }}" @selected(old('payment_method', \App\Support\PaymentMethod::CASH) === $method)>{{ $method }}</option>
                                @endforeach
                            </x-select>
                            <x-button type="submit" class="w-full">Generar factura</x-button>
                        </form>
                    @elseif($billableJobs->isNotEmpty() && $serviceOrder->canReceiveJobs())
                        <p class="text-center text-xs text-muted-foreground">Completa todos los trabajos para poder facturar.</p>
                    @elseif($serviceOrder->status === 'cancelled')
                        <p class="text-center text-xs text-muted-foreground">Orden cancelada: los repuestos volvieron al inventario.</p>
                    @endif

                    @if($cancelledInvoices->isNotEmpty())
                        <p class="text-center text-xs text-muted-foreground">
                            {{ $cancelledInvoices->count() === 1 ? 'Factura anulada:' : 'Facturas anuladas:' }}
                            @foreach($cancelledInvoices as $cancelled)
                                <a href="{{ route('invoices.show', $cancelled) }}" class="font-mono line-through hover:text-foreground">{{ $cancelled->invoice_number }}</a>@if(!$loop->last), @endif
                            @endforeach
                        </p>
                    @endif
                </div>
            </x-card>

            <x-card title="Cliente">
                <dl class="space-y-4 text-sm">
                    <div>
                        <dt class="text-xs font-medium text-muted-foreground">Nombre</dt>
                        <dd class="mt-0.5">{{ $serviceOrder->customer_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted-foreground">Teléfono</dt>
                        <dd class="mt-0.5">
                            @if($serviceOrder->customer_phone)
                                <a href="tel:{{ $serviceOrder->customer_phone }}" class="tabular-nums hover:underline">{{ $serviceOrder->customer_phone }}</a>
                            @else
                                <span class="text-muted-foreground">—</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-muted-foreground">Vehículo</dt>
                        <dd class="mt-0.5">{{ $serviceOrder->vehicle_info }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </div>

    @if($serviceOrder->canReceiveJobs())
        @include('jobs.partials.job-modal', [
            'action' => route('service-orders.jobs.store', $serviceOrder),
            'serviceOrder' => $serviceOrder,
        ])
    @endif
</div>
</x-app-layout>
