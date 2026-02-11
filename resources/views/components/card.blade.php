<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl bg-white shadow-sm border border-slate-200']) }}>
    @if(isset($header))
        <div class="border-b border-slate-200 bg-slate-50/50 px-6 py-4">
            {{ $header }}
        </div>
    @endif
    
    <div class="px-6 py-5">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="border-t border-slate-200 bg-slate-50/50 px-6 py-4">
            {{ $footer }}
        </div>
    @endif
</div>
