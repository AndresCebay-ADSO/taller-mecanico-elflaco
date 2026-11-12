{{--
    El único h1 de la pantalla (DESIGN.md §4, §9). Sin icono junto al título.
    `back` + `backLabel`: enlace al listado sobre el título, en los detalles («← Ventas»).
--}}
@props([
    'title',
    'subtitle' => null,
    'back' => null,
    'backLabel' => null,
])

<div class="ds mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
    <div class="min-w-0">
        @if($back)
            <a href="{{ $back }}" class="mb-2 inline-flex items-center gap-1 text-sm text-muted-foreground transition-colors hover:text-foreground">
                <i data-lucide="chevron-left" class="size-4"></i>
                {{ $backLabel }}
            </a>
        @endif
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-semibold tracking-tight text-foreground">{{ $title }}</h1>
            @isset($badge)
                {{ $badge }}
            @endisset
        </div>
        @if($subtitle)
            <p class="mt-1 text-sm text-muted-foreground">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
