{{--
    Píldora de estado (DESIGN.md §3). El tono viene del modelo: :tone="$order->statusTone()".
    Cada tono trae su icono (segunda señal además del color). `icon` lo reemplaza (p. ej. la flecha de un
    movimiento) y :icon="false" lo quita.
--}}
@props(['tone' => 'neutral', 'icon' => null])

@php $iconName = $icon === false ? null : ($icon ?? \App\Support\Tone::icon($tone)); @endphp

<span {{ $attributes->merge(['class' => 'ds inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium ' . \App\Support\Tone::pill($tone)]) }}>
    @if($iconName)
        <i data-lucide="{{ $iconName }}" class="size-3" aria-hidden="true"></i>
    @endif
    {{ $slot }}
</span>
