{{--
    Hoja imprimible tamaño carta (comprobante de servicio, comprobante de venta, orden de ingreso).
    Página completa e independiente: sin menú de la app y siempre en tema claro, para que lo impreso
    no dependa de la pantalla. Con ?auto=1 abre el diálogo de impresión al cargar.

    @props title     Título del documento («Comprobante de servicio»)
    @props number    Número visible («FAC-000123», «#S0042»)
    @props settings  Configuración del taller (Setting::pluck)
    @props back      URL de «Volver» en la barra de pantalla
    @props cancelled Texto de anulación; si viene, la hoja lleva la marca ANULADO
    @props legal     false en documentos que no son de venta (orden de ingreso): sin nota legal
    Slots: meta (filas <x-document.meta>), parties (bloques cliente/vehículo), default (detalle), totals,
           signatures, notes (pie izquierdo)
--}}
@props([
    'title',
    'number',
    'settings' => [],
    'back' => null,
    'cancelled' => null,
    'legal' => true,
])

@php
    $workshopName = $settings['workshop_name'] ?? null ?: config('app.name');
    $hasLogo = ! empty($settings['workshop_logo']);
    $legalNote = $settings['document_legal_note'] ?? null
        ?: 'Documento interno de soporte. No es factura electrónica de venta.';
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} {{ $number }} — {{ $workshopName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /*
         * Margen de página 0 y el espacio lo da la hoja: así el navegador no tiene dónde imprimir la URL
         * y la fecha (sus «encabezados y pies de página»), que de otro modo salen por defecto.
         */
        @page { size: letter; margin: 0; }
        @media print {
            html, body { background: white !important; }
            .doc-toolbar { display: none !important; }
            .doc-page { padding: 0 !important; }
            .doc-sheet { box-shadow: none !important; border: 0 !important; margin: 0 !important; padding: 14mm 16mm !important; max-width: none !important; }
            .doc-sheet tr, .doc-sheet section { break-inside: avoid; }
        }
        /* Los colores de las líneas y el título se imprimen tal cual (sin que el navegador los quite). */
        .doc-sheet { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    </style>
</head>
<body class="ds bg-muted font-sans text-foreground antialiased">
    <div class="doc-toolbar sticky top-0 z-10 border-b border-border bg-card/95 backdrop-blur">
        <div class="mx-auto flex max-w-[8.5in] items-center justify-between gap-3 px-4 py-3">
            @if($back)
                <a href="{{ $back }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                    <i data-lucide="chevron-left" class="size-4"></i> Volver
                </a>
            @else
                <span></span>
            @endif
            <x-button onclick="window.print()">
                <i data-lucide="printer" class="size-4"></i>
                Imprimir
            </x-button>
        </div>
    </div>

    <div class="doc-page px-4 py-8">
        <article class="doc-sheet relative mx-auto max-w-[8.5in] overflow-hidden bg-card p-10 text-sm shadow-lg sm:p-12">
            @if($cancelled)
                {{-- Marca de agua: un documento anulado no se puede confundir con uno válido, ni impreso. --}}
                <div class="pointer-events-none absolute inset-0 flex items-center justify-center" aria-hidden="true">
                    <span class="-rotate-30 select-none text-8xl font-semibold tracking-widest text-destructive/15">ANULADO</span>
                </div>
            @endif

            {{-- Encabezado: título y datos del taller a la izquierda, logo a la derecha. --}}
            <header class="flex items-start justify-between gap-8">
                <div class="min-w-0">
                    <h1 class="text-3xl font-semibold uppercase tracking-tight text-primary">{{ $title }}</h1>
                    <div class="mt-4 space-y-0.5">
                        <p class="text-base font-semibold">{{ $workshopName }}</p>
                        @if(!empty($settings['workshop_nit']))<p>NIT {{ $settings['workshop_nit'] }}</p>@endif
                        @if(!empty($settings['workshop_tax_regime']))<p>{{ $settings['workshop_tax_regime'] }}</p>@endif
                        @if(!empty($settings['workshop_address']))<p class="text-muted-foreground">{{ $settings['workshop_address'] }}</p>@endif
                        <p class="text-muted-foreground">
                            {{ collect([$settings['workshop_phone'] ?? null ? 'Tel. ' . $settings['workshop_phone'] : null, $settings['workshop_email'] ?? null])->filter()->join(' · ') }}
                        </p>
                    </div>
                </div>
                @if($hasLogo)
                    <img src="{{ route('branding.logo') }}" alt="{{ $workshopName }}" class="max-h-24 max-w-40 shrink-0 object-contain">
                @endif
            </header>

            @if($cancelled)
                <p class="mt-6 rounded-md border border-destructive/40 px-3 py-2 text-sm font-medium text-destructive-text">{{ $cancelled }}</p>
            @endif

            {{-- Partes (cliente, vehículo) y datos del documento. --}}
            <section class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-[1fr_auto]">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    {{ $parties ?? '' }}
                </div>
                <dl class="grid grid-cols-[auto_auto] content-start gap-x-6 gap-y-1">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-primary">Número</dt>
                    <dd class="text-right font-mono font-semibold">{{ $number }}</dd>
                    {{ $meta ?? '' }}
                </dl>
            </section>

            {{-- Detalle --}}
            <section class="mt-8">
                {{ $slot }}
            </section>

            @isset($totals)
                <section class="mt-4 flex justify-end">
                    <div class="w-full max-w-xs">{{ $totals }}</div>
                </section>
            @endisset

            @isset($signatures)
                <section class="mt-16 grid grid-cols-2 gap-12">
                    {{ $signatures }}
                </section>
            @endisset

            <footer class="mt-12 grid grid-cols-1 gap-6 border-t border-border pt-6 sm:grid-cols-2">
                <div class="text-sm">{{ $notes ?? '' }}</div>
                @isset($conditions)
                    <div class="border-l-2 border-primary pl-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-primary">Condiciones</p>
                        <div class="mt-1 whitespace-pre-line text-xs text-muted-foreground">{{ $conditions }}</div>
                    </div>
                @endisset
            </footer>

            @if($legal)
                <p class="mt-8 text-center text-xs text-muted-foreground">{{ $legalNote }}</p>
            @endif
        </article>
    </div>

    @if(request('auto'))
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
