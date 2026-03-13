<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl bg-white border border-gray-200 shadow-theme-sm dark:bg-white/[0.03] dark:border-gray-800 transition-all duration-300']) }}>
    @if(isset($header))
        <div class="border-b border-gray-100 bg-gray-50/50 px-6 py-5 dark:border-gray-800 dark:bg-white/[0.02]">
            {{ $header }}
        </div>
    @endif
    
    <div class="px-6 py-6">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="border-t border-gray-100 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-white/[0.02]">
            {{ $footer }}
        </div>
    @endif
</div>
