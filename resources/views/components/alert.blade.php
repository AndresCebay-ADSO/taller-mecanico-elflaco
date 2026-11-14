{{-- Mensajes flash y avisos (DESIGN.md §3, §9). El de éxito se cierra solo a los 5 s. --}}
@props([
    'type' => 'success', // success, error, warning, info
    'message' => null,
])

@php
    $tone = match ($type) {
        'error', 'danger' => 'danger',
        'warning' => 'warning',
        'info' => 'info',
        default => 'success',
    };
    $icon = match ($tone) {
        'danger' => 'alert-circle',
        'warning' => 'alert-triangle',
        'info' => 'info',
        default => 'check-circle',
    };
@endphp

<div x-data="{ show: true }"
     x-show="show"
     x-init="if (@js($tone) === 'success') setTimeout(() => show = false, 5000)"
     x-transition:leave="transition-opacity ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     role="{{ $tone === 'danger' ? 'alert' : 'status' }}"
     {{ $attributes->merge(['class' => 'ds mb-4 flex items-start gap-3 rounded-lg border px-4 py-3 text-sm ' . \App\Support\Tone::alert($tone)]) }}>
    <i data-lucide="{{ $icon }}" class="mt-0.5 size-4 shrink-0"></i>
    <div class="min-w-0 flex-1 font-medium">
        {{ $message ?? $slot }}
    </div>
    <button type="button" @click="show = false" class="-m-1 shrink-0 rounded-md p-1 opacity-70 transition-opacity hover:opacity-100 cursor-pointer" title="Cerrar">
        <i data-lucide="x" class="size-4"></i>
        <span class="sr-only">Cerrar</span>
    </button>
</div>
