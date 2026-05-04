@props([
    'type' => 'success', // success, error, warning, info
    'message' => null
])

@php
    $colors = match($type) {
        'success' => [
            'bg' => 'bg-emerald-50 dark:bg-emerald-900/30',
            'border' => 'border-emerald-200 dark:border-emerald-800',
            'text' => 'text-emerald-800 dark:text-emerald-200',
            'icon' => 'text-emerald-500',
            'iconName' => 'check-circle'
        ],
        'error' => [
            'bg' => 'bg-rose-50 dark:bg-rose-900/30',
            'border' => 'border-rose-200 dark:border-rose-800',
            'text' => 'text-rose-800 dark:text-rose-200',
            'icon' => 'text-rose-500',
            'iconName' => 'alert-circle'
        ],
        'warning' => [
            'bg' => 'bg-amber-50 dark:bg-amber-900/30',
            'border' => 'border-amber-200 dark:border-amber-800',
            'text' => 'text-amber-800 dark:text-amber-200',
            'icon' => 'text-amber-500',
            'iconName' => 'alert-triangle'
        ],
        default => [
            'bg' => 'bg-blue-50 dark:bg-blue-900/30',
            'border' => 'border-blue-200 dark:border-blue-800',
            'text' => 'text-blue-800 dark:text-blue-200',
            'icon' => 'text-blue-500',
            'iconName' => 'info'
        ]
    };
@endphp

<div x-data="{ show: true }" 
     x-show="show" 
     x-init="if(@js($type) === 'success') setTimeout(() => show = false, 5000)"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95"
     {{ $attributes->merge(['class' => "mb-6 flex items-center justify-between p-4 rounded-2xl border {$colors['bg']} {$colors['border']} shadow-sm"]) }}>
    
    <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/50 dark:bg-black/20 {{ $colors['icon'] }}">
            <i data-lucide="{{ $colors['iconName'] }}" class="h-6 w-6"></i>
        </div>
        <div>
            <div class="font-bold {{ $colors['text'] }} leading-tight">
                {{ $message ?? $slot }}
            </div>
        </div>
    </div>

    <button type="button" @click="show = false" class="{{ $colors['text'] }} opacity-50 hover:opacity-100 transition-opacity">
        <i data-lucide="x" class="h-5 w-5"></i>
    </button>
</div>
