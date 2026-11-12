@php
    $settings = \App\Models\Setting::pluck('value', 'key')->all();
@endphp

<x-app-layout>
<div class="ds mx-auto max-w-3xl">
    <x-page-header title="Configuración" subtitle="Datos del taller que salen en las facturas" />

    <form action="{{ route('settings.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <x-card title="Taller">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-input label="Nombre del taller" name="workshop_name" :value="$settings['workshop_name'] ?? ''" placeholder="MotoTaller El Flaco" maxlength="255" />
                <x-input label="NIT o cédula" name="workshop_nit" :value="$settings['workshop_nit'] ?? ''" placeholder="123456789-0" maxlength="50" />
                <x-input label="Teléfono" name="workshop_phone" type="tel" :value="$settings['workshop_phone'] ?? ''" placeholder="321 000 0000" maxlength="30" />
                <x-input label="Correo" name="workshop_email" type="email" :value="$settings['workshop_email'] ?? ''" placeholder="Opcional" maxlength="255" />
                <div class="md:col-span-2">
                    <x-input label="Dirección" name="workshop_address" :value="$settings['workshop_address'] ?? ''" placeholder="Calle 1 # 2-3, municipio" maxlength="255" />
                </div>
            </div>
        </x-card>

        <x-card title="Facturación">
            <div class="space-y-4">
                <div class="max-w-48">
                    <x-input label="IVA (%)" name="tax_percentage" inputmode="decimal" :value="$settings['tax_percentage'] ?? '19'"
                             help="Se guarda, pero hoy las facturas no lo aplican." />
                </div>
                <x-textarea label="Pie de página de la factura" name="footer_text_invoice" rows="3" maxlength="1000"
                            :value="$settings['footer_text_invoice'] ?? ''" placeholder="Gracias por su confianza."
                            help="Aparece al final de cada factura impresa." />
            </div>
        </x-card>

        <div class="flex justify-end">
            <x-button type="submit">Guardar configuración</x-button>
        </div>
    </form>
</div>
</x-app-layout>
