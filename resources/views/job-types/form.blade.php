{{-- Crear y editar tipo de trabajo (create.blade.php y edit.blade.php incluyen esta vista). --}}
@php
    $jobType ??= null;
    $editing = $jobType !== null;
@endphp

<x-app-layout>
<div class="ds mx-auto max-w-2xl">
    <x-page-header :title="$editing ? 'Editar ' . $jobType->name : 'Nuevo tipo de trabajo'"
                   subtitle="Cómo se reparte la mano de obra entre mecánico y taller"
                   :back="route('job-types.index')" back-label="Tipos de trabajo" />

    <form action="{{ $editing ? route('job-types.update', $jobType) : route('job-types.store') }}" method="POST" class="space-y-6"
          x-data="{
              calcType: @js(old('calculation_type', $jobType->calculation_type ?? 'percentage')),
              mechanicPercent: @js((int) old('mechanic_percentage', $jobType->mechanic_percentage ?? 70)),
          }">
        @csrf
        @if($editing) @method('PUT') @endif

        <x-card title="Servicio">
            <div class="space-y-4">
                <x-input label="Nombre" name="name" required placeholder="Ej.: Cambio de aceite" :value="$jobType?->name" />
                <x-textarea label="Descripción" name="description" required rows="3" :value="$jobType?->description"
                            placeholder="Qué incluye este servicio" />
                <x-textarea label="Descripción por defecto" name="default_description" rows="2" :value="$jobType?->default_description"
                            placeholder="Opcional" help="Aparece como guía al elegir este tipo en un trabajo nuevo." />
            </div>
        </x-card>

        <x-card title="Reparto de la mano de obra">
            <input type="hidden" name="calculation_type" :value="calcType">

            <fieldset>
                <legend class="sr-only">Tipo de cálculo</legend>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach(['percentage' => ['Porcentaje', 'El mecánico gana un % de lo que se cobre.'], 'fixed' => ['Monto fijo', 'Precio único: una parte para el mecánico y otra para el taller.']] as $value => [$title, $hint])
                        <label class="flex cursor-pointer items-start gap-3 rounded-md border p-3 transition-colors"
                               :class="calcType === @js($value) ? 'border-ring bg-primary/5' : 'border-border hover:bg-muted/50'">
                            <input type="radio" value="{{ $value }}" x-model="calcType" class="mt-0.5 size-4 cursor-pointer border-input text-primary focus:ring-2 focus:ring-ring/30">
                            <span>
                                <span class="block text-sm font-medium">{{ $title }}</span>
                                <span class="block text-xs text-muted-foreground">{{ $hint }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div x-show="calcType === 'percentage'" class="mt-5">
                <div class="mb-2 flex items-baseline justify-between text-sm">
                    <label for="mechanic_percentage" class="font-medium">Mecánico <span class="tabular-nums" x-text="mechanicPercent + ' %'"></span></label>
                    <span class="text-muted-foreground">Taller <span class="tabular-nums" x-text="(100 - mechanicPercent) + ' %'"></span></span>
                </div>
                <input type="range" id="mechanic_percentage" name="mechanic_percentage" x-model.number="mechanicPercent" min="0" max="100" step="5"
                       class="w-full cursor-pointer accent-primary">
                <input type="hidden" name="workshop_percentage" :value="100 - mechanicPercent">
            </div>

            <div x-show="calcType === 'fixed'" x-cloak class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-input label="Para el mecánico" name="fixed_mechanic_amount" is-money :value="(int) ($jobType->fixed_mechanic_amount ?? 0)" />
                <x-input label="Para el taller" name="fixed_workshop_amount" is-money :value="(int) ($jobType->fixed_workshop_amount ?? 0)" />
            </div>
        </x-card>

        <x-card title="Opciones">
            <div class="space-y-4">
                <x-checkbox name="allow_custom_labor" label="Mano de obra variable" :checked="(bool) old('allow_custom_labor', $jobType->allow_custom_labor ?? true)"
                            help="Permite escribir el valor de la mano de obra en cada trabajo." />
                <x-checkbox name="allow_products" label="Usa repuestos" :checked="(bool) old('allow_products', $jobType->allow_products ?? true)"
                            help="Permite agregar repuestos del inventario al trabajo." />
                <x-checkbox name="is_active" label="Activo" :checked="(bool) old('is_active', $jobType->is_active ?? true)"
                            help="Solo los tipos activos aparecen al crear un trabajo." />
            </div>
        </x-card>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
            <x-button variant="secondary" href="{{ route('job-types.index') }}">Cancelar</x-button>
            <x-button type="submit">{{ $editing ? 'Guardar cambios' : 'Crear tipo de trabajo' }}</x-button>
        </div>
    </form>
</div>
</x-app-layout>
