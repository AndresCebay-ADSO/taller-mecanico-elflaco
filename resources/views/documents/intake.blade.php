{{--
    Orden de ingreso: se imprime al recibir la moto. Deja por escrito qué se recibió, qué falla reporta el
    cliente y las condiciones del taller; la firman las dos partes.
--}}
<x-document title="Orden de ingreso" :number="'#' . str_pad($order->id, 4, '0', STR_PAD_LEFT)" :settings="$settings"
            :back="route('service-orders.show', $order)" :legal="false">
    <x-slot name="parties">
        <x-document.party title="Cliente">
            <p class="font-medium">{{ $order->customer_name }}</p>
            @if($order->customer_document)<p>C.C./NIT {{ $order->customer_document }}</p>@endif
            @if($order->customer_phone)<p class="tabular-nums">Tel. {{ $order->customer_phone }}</p>@endif
        </x-document.party>
        <x-document.party title="Vehículo">
            <p class="font-medium">{{ $order->vehicle_info }}</p>
        </x-document.party>
    </x-slot>

    <x-slot name="meta">
        <x-document.meta label="Ingreso">{{ $order->created_at->format('d/m/Y') }}</x-document.meta>
        <x-document.meta label="Hora">{{ $order->created_at->format('h:i a') }}</x-document.meta>
        @if($order->branch)
            <x-document.meta label="Sede">{{ $order->branch->name }}</x-document.meta>
        @endif
    </x-slot>

    <div class="space-y-6">
        <div>
            <p class="border-b-2 border-primary pb-1 text-xs font-semibold uppercase tracking-wide text-primary">Motivo de ingreso / fallas reportadas</p>
            <p class="mt-2 whitespace-pre-wrap">{{ $order->service_description }}</p>
        </div>

        {{-- Se llena a mano al recibir la moto. --}}
        <div>
            <p class="border-b-2 border-primary pb-1 text-xs font-semibold uppercase tracking-wide text-primary">Estado al recibir</p>
            <div class="mt-4 grid grid-cols-2 gap-x-10 gap-y-5">
                <p class="flex items-end gap-2">Kilometraje <span class="flex-1 border-b border-foreground/40"></span></p>
                <p class="flex items-end gap-2">Combustible
                    <span class="flex flex-1 justify-between text-xs text-muted-foreground">
                        @foreach(['V', '¼', '½', '¾', 'Ll'] as $level)
                            <span class="flex items-center gap-1"><span class="inline-block size-3 border border-foreground/60"></span>{{ $level }}</span>
                        @endforeach
                    </span>
                </p>
            </div>
            <p class="mt-5">Golpes, rayones, accesorios y objetos que deja el cliente:</p>
            <div class="mt-1 space-y-6">
                <div class="h-px bg-foreground/40"></div>
                <div class="h-px bg-foreground/40"></div>
                <div class="h-px bg-foreground/40"></div>
            </div>
        </div>
    </div>

    <x-slot name="signatures">
        <x-document.signature label="Recibió" :hint="$settings['workshop_name'] ?? null" />
        <x-document.signature label="Cliente" hint="Firma, nombre y C.C. Acepta las condiciones." />
    </x-slot>

    <x-slot name="notes">
        <p class="text-muted-foreground">Presente esta orden al recoger su moto.</p>
    </x-slot>

    @if(!empty($settings['intake_conditions']))
        <x-slot name="conditions">{{ $settings['intake_conditions'] }}</x-slot>
    @endif
</x-document>
