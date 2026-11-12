{{-- Píldora de estado (DESIGN.md §3). El tono viene del modelo: :tone="$order->statusTone()". --}}
@props(['tone' => 'neutral'])

<span {{ $attributes->merge(['class' => 'ds inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium ' . \App\Support\Tone::pill($tone)]) }}>
    {{ $slot }}
</span>
