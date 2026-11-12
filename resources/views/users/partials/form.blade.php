@csrf

<x-card title="Cuenta">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <x-input label="Nombre" name="name" required :value="$user->name" maxlength="255" />
        <x-input label="Correo" name="email" type="email" required :value="$user->email" maxlength="255" autocomplete="off" />
        <x-input :label="$user->exists ? 'Nueva contraseña' : 'Contraseña'" name="password" type="password" :required="! $user->exists"
                 autocomplete="new-password" :help="$user->exists ? 'Déjala vacía para no cambiarla. Mínimo 8 caracteres.' : 'Mínimo 8 caracteres.'" />
        <x-input label="Confirmar contraseña" name="password_confirmation" type="password" :required="! $user->exists" autocomplete="new-password" />
    </div>
</x-card>

<x-card title="Permisos" x-data="{ isAdmin: {{ old('is_admin', $user->is_admin) ? 'true' : 'false' }} }">
    <div class="space-y-5">
        <div>
            <input type="hidden" name="is_admin" value="0">
            <div class="flex items-start gap-3">
                <input type="checkbox" id="is_admin" name="is_admin" value="1" x-model="isAdmin"
                       class="mt-0.5 size-4 shrink-0 cursor-pointer rounded border-input text-primary focus:ring-2 focus:ring-ring/30">
                <div>
                    <label for="is_admin" class="cursor-pointer text-sm font-medium">Administrador</label>
                    <p class="text-xs text-muted-foreground">Puede anular ventas y facturas, ajustar inventario y manejar usuarios, sucursales y configuración. Ve todas las sucursales.</p>
                    @error('is_admin')
                        <p class="text-xs text-destructive-text">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <fieldset x-show="!isAdmin" x-cloak class="border-t border-border pt-5">
            <legend class="text-sm font-medium">Sucursales asignadas <span class="text-destructive-text">*</span></legend>
            <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                @foreach($branches as $branch)
                    <label class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm transition-colors hover:bg-muted">
                        <input type="checkbox" name="branch_ids[]" value="{{ $branch->id }}"
                               @checked(in_array($branch->id, old('branch_ids', $selectedBranchIds)))
                               class="size-4 cursor-pointer rounded border-input text-primary focus:ring-2 focus:ring-ring/30">
                        {{ $branch->name }}
                    </label>
                @endforeach
            </div>
            @error('branch_ids')
                <p class="mt-2 text-xs text-destructive-text">{{ $message }}</p>
            @enderror
        </fieldset>
    </div>
</x-card>
