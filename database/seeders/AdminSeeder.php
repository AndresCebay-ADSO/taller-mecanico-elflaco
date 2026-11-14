<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Crea el usuario administrador inicial del sistema.
     *
     * Si ya existe no lo toca: volver a correr los seeders no debe cambiarle la contraseña a nadie.
     * Para una contraseña olvidada está `php artisan taller:restablecer-clave`.
     */
    public function run(): void
    {
        $email = config('auth.admin.email', 'admin@tallerflacos.com');

        if (User::where('email', $email)->exists()) {
            $this->command?->info("El administrador {$email} ya existe: no se cambió.");

            return;
        }

        $password = config('auth.admin.password');
        $generatedPassword = false;

        if (!$password) {
            $password = Str::password(16);
            $generatedPassword = true;
        }

        $user = User::create([
            'name' => config('auth.admin.name', 'Administrador'),
            'email' => $email,
            'password' => Hash::make($password),
        ]);
        $user->forceFill(['is_admin' => true, 'is_active' => true])->save();

        $message = "Usuario admin listo: {$email}";

        if ($generatedPassword) {
            $message .= " / password temporal: {$password}";
        } else {
            $message .= ' / password tomada desde ADMIN_PASSWORD';
        }

        $this->command?->info($message);
    }
}
