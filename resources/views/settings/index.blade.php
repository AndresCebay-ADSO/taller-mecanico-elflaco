@php
    $settings = \App\Models\Setting::pluck('value', 'key')->all();
    $hasLogo = ! empty($settings['workshop_logo']);
@endphp

<x-app-layout>
<div class="ds mx-auto max-w-3xl">
    <x-page-header title="Configuración" subtitle="Datos del taller que salen en los comprobantes y en la orden de ingreso" />

    <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <x-card title="Taller">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-input label="Nombre del taller" name="workshop_name" :value="$settings['workshop_name'] ?? ''" placeholder="MotoTaller El Flaco" maxlength="255" />
                <x-input label="NIT o cédula" name="workshop_nit" :value="$settings['workshop_nit'] ?? ''" placeholder="123456789-0" maxlength="50" />
                <x-input label="Régimen tributario" name="workshop_tax_regime" :value="$settings['workshop_tax_regime'] ?? ''" maxlength="100"
                         placeholder="Ej.: No responsable de IVA" help="Sale debajo del NIT. Confírmalo en el RUT." />
                <x-input label="Teléfono" name="workshop_phone" type="tel" :value="$settings['workshop_phone'] ?? ''" placeholder="321 000 0000" maxlength="30" />
                <x-input label="Correo" name="workshop_email" type="email" :value="$settings['workshop_email'] ?? ''" placeholder="Opcional" maxlength="255" />
                <x-input label="Dirección" name="workshop_address" :value="$settings['workshop_address'] ?? ''" placeholder="Calle 1 # 2-3, municipio" maxlength="255" />
            </div>
        </x-card>

        <x-card title="Logo" description="Opcional. Sin logo, los documentos llevan el nombre del taller.">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-md border border-dashed border-border bg-muted">
                    @if($hasLogo)
                        <img src="{{ route('branding.logo') }}?v={{ md5($settings['workshop_logo']) }}" alt="Logo actual" class="max-h-full max-w-full object-contain">
                    @else
                        <i data-lucide="image" class="size-6 text-muted-foreground"></i>
                    @endif
                </div>
                <div class="flex-1 space-y-3">
                    <div class="space-y-1.5">
                        <label for="workshop_logo" class="block text-sm font-medium">{{ $hasLogo ? 'Cambiar logo' : 'Subir logo' }}</label>
                        <input id="workshop_logo" name="workshop_logo" type="file" accept="image/png,image/jpeg,image/webp"
                               class="block w-full cursor-pointer text-sm text-muted-foreground file:mr-3 file:cursor-pointer file:rounded-md file:border file:border-border file:bg-card file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-foreground hover:file:bg-muted">
                        @error('workshop_logo')
                            <p class="text-xs text-destructive-text">{{ $message }}</p>
                        @else
                            <p class="text-xs text-muted-foreground">PNG, JPG o WEBP, hasta 1 MB. Mejor con fondo transparente.</p>
                        @enderror
                    </div>
                    @if($hasLogo)
                        <x-checkbox name="remove_logo" label="Quitar el logo" />
                    @endif
                </div>
            </div>
        </x-card>

        <x-card title="Documentos">
            <div class="space-y-4">
                <x-textarea label="Mensaje de agradecimiento" name="footer_text_invoice" rows="2" maxlength="1000"
                            :value="$settings['footer_text_invoice'] ?? ''" placeholder="¡Gracias por su confianza!"
                            help="Va al pie de los comprobantes de servicio y de venta." />
                <x-textarea label="Condiciones y garantía" name="document_conditions" rows="4" maxlength="1500"
                            :value="$settings['document_conditions'] ?? ''"
                            placeholder="Ej.: Garantía de 30 días en mano de obra por el mismo trabajo. Los repuestos tienen la garantía de su fabricante. No cubre mal uso ni accidentes."
                            help="Sale en los comprobantes. Vacío: no se muestra." />
                <x-textarea label="Condiciones de la orden de ingreso" name="intake_conditions" rows="4" maxlength="1500"
                            :value="$settings['intake_conditions'] ?? ''"
                            placeholder="Ej.: El taller no responde por objetos dejados en la moto. Motos no reclamadas después de 30 días de terminadas pagan parqueadero."
                            help="Sale en la orden de ingreso, que firma el cliente al dejar la moto." />
                <x-input label="Nota legal" name="document_legal_note" maxlength="255" :value="$settings['document_legal_note'] ?? ''"
                         placeholder="Documento interno de soporte. No es factura electrónica de venta."
                         help="Línea pequeña al final de cada documento. Vacío: se usa el texto de ejemplo." />
                <div class="max-w-48">
                    <x-input label="IVA (%)" name="tax_percentage" inputmode="decimal" :value="$settings['tax_percentage'] ?? '19'"
                             help="Se guarda, pero hoy no se aplica." />
                </div>
            </div>
        </x-card>

        <div class="flex justify-end">
            <x-button type="submit">Guardar configuración</x-button>
        </div>
    </form>
</div>
</x-app-layout>
