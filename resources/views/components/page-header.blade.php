@props([
    'title',
    'subtitle' => null,
])

<div class="mb-8 md:flex md:items-center md:justify-between">
    <div class="min-w-0 flex-1">
        <h2 class="text-2xl font-bold leading-7 text-slate-900 sm:truncate sm:text-3xl sm:tracking-tight">
            {{ $title }}
        </h2>
        @if($subtitle)
            <p class="mt-1 text-sm text-slate-500">
                {{ $subtitle }}
            </p>
        @endif
    </div>
    
    @if(isset($actions))
        <div class="mt-4 flex md:ml-4 md:mt-0 gap-3">
            {{ $actions }}
        </div>
    @endif
</div>
