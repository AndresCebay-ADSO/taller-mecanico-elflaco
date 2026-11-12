{{--
    Toda sección de una página es una tarjeta (DESIGN.md §7, §9).
    `title` (+ `description` y slot `actions`) arma el encabezado con su h2; `header` queda para pantallas heredadas.
    `flush` quita el relleno del cuerpo (tablas que llegan al borde).
--}}
@props([
    'title' => null,
    'description' => null,
    'flush' => false,
])

<section {{ $attributes->merge(['class' => 'ds overflow-hidden rounded-lg border border-border bg-card text-foreground shadow-xs']) }}>
    @if($title)
        <header class="flex items-center justify-between gap-3 border-b border-border px-5 py-4">
            <div class="min-w-0">
                <h2 class="text-base font-semibold">{{ $title }}</h2>
                @if($description)
                    <p class="mt-0.5 text-sm text-muted-foreground">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @elseif(isset($header))
        <header class="border-b border-border px-5 py-4">
            {{ $header }}
        </header>
    @endif

    <div @class(['p-5' => ! $flush])>
        {{ $slot }}
    </div>

    @isset($footer)
        <footer class="border-t border-border px-5 py-4">
            {{ $footer }}
        </footer>
    @endisset
</section>
