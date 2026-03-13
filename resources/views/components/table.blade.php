@props([
    'headers' => [],
])

<div class="overflow-x-auto custom-scrollbar">
    <table class="min-w-full divide-y divide-gray-100">
        <thead class="bg-gray-50/50">
            <tr>
                @foreach($headers as $header)
                    <th scope="col" class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-gray-500">
                        {{ $header }}
                    </th>
                @endforeach
                @if(isset($actions))
                    <th scope="col" class="relative px-6 py-4">
                        <span class="sr-only">Acciones</span>
                    </th>
                @endif
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
            {{ $slot }}
        </tbody>
    </table>
</div>
