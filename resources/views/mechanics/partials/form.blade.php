{{-- Campos del mecánico, compartidos por crear y editar. @param \App\Models\Mechanic|null $mechanic --}}
@php
    $mechanic ??= null;
    $isActive = (string) old('is_active', ($mechanic?->is_active ?? true) ? '1' : '0');
@endphp

<x-card>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <x-input label="Nombre completo" name="name" required :value="$mechanic?->name" maxlength="30" placeholder="Ej.: Juan Pérez" />
        <x-input label="Teléfono" name="phone" required :value="$mechanic?->phone" type="tel" inputmode="tel"
                 minlength="10" maxlength="10" pattern="[0-9]{10}" placeholder="3110000000"
                 title="10 dígitos, sin espacios" help="10 dígitos, sin espacios." />
        <x-input label="Correo" name="email" type="email" :value="$mechanic?->email" placeholder="Opcional" />
        <x-input label="Fecha de ingreso" name="hire_date" type="date" required
                 :value="$mechanic?->hire_date ? \Carbon\Carbon::parse($mechanic->hire_date)->format('Y-m-d') : date('Y-m-d')" />
        <x-select label="Estado" name="is_active">
            <option value="1" @selected($isActive === '1')>Activo</option>
            <option value="0" @selected($isActive === '0')>Inactivo</option>
        </x-select>
    </div>
</x-card>
