@csrf

<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
    <x-input label="Nombre" name="name" required :value="$user->name" maxlength="255" />
    <x-input label="Correo electrónico" name="email" type="email" required :value="$user->email" maxlength="255" />
    <x-input :label="$user->exists ? 'Nueva contraseña (opcional)' : 'Contraseña'" name="password" type="password" :required="!$user->exists" autocomplete="new-password" />
    <x-input label="Confirmar contraseña" name="password_confirmation" type="password" :required="!$user->exists" autocomplete="new-password" />
</div>

<div x-data="{ isAdmin: {{ old('is_admin', $user->is_admin) ? 'true' : 'false' }} }" class="space-y-6">
    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50 dark:border-gray-800 dark:hover:bg-white/5">
        <input type="hidden" name="is_admin" value="0">
        <input type="checkbox" name="is_admin" value="1" x-model="isAdmin" class="mt-1 h-5 w-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
        <span>
            <span class="block text-sm font-bold text-slate-900 dark:text-white">Administrador</span>
            <span class="block text-xs text-slate-500 dark:text-gray-400">Puede anular ventas y facturas, ajustar inventario, gestionar usuarios, sucursales y configuración. Accede a todas las sucursales.</span>
        </span>
    </label>
    @error('is_admin')
        <p class="text-sm font-medium text-error-600 dark:text-error-400">{{ $message }}</p>
    @enderror

    <div x-show="!isAdmin" x-cloak>
        <p class="mb-2 block text-sm font-bold text-gray-700 dark:text-gray-300">Sucursales asignadas <span class="text-error-500">*</span></p>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            @foreach($branches as $branch)
                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 hover:bg-slate-50 dark:border-gray-800 dark:hover:bg-white/5">
                    <input type="checkbox" name="branch_ids[]" value="{{ $branch->id }}"
                        @checked(in_array($branch->id, old('branch_ids', $selectedBranchIds)))
                        class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm font-medium text-slate-700 dark:text-gray-300">{{ $branch->name }}</span>
                </label>
            @endforeach
        </div>
        @error('branch_ids')
            <p class="mt-2 text-sm font-medium text-error-600 dark:text-error-400">{{ $message }}</p>
        @enderror
    </div>
</div>
