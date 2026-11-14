{{-- Listado o sección vacía (DESIGN.md §7): caja punteada, mensaje y, si aplica, la acción principal. --}}
@props(['icon' => null])

<div {{ $attributes->merge(['class' => 'ds flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-border px-6 py-10 text-center']) }}>
    @if($icon)
        <i data-lucide="{{ $icon }}" class="size-5 text-muted-foreground"></i>
    @endif
    <p class="text-sm text-muted-foreground">{{ $slot }}</p>
    @isset($action)
        <div>{{ $action }}</div>
    @endisset
</div>
