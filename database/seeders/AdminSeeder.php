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
     */
    public function run(): void
    {
        $email = config('auth.admin.email', 'admin@tallerflacos.com');
        $password = config('auth.admin.password');
        $generatedPassword = false;

        if (!$password) {
            $password = Str::password(16);
            $generatedPassword = true;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => config('auth.admin.name', 'Administrador'),
                'password' => Hash::make($password),
                'is_admin' => true,
            ]
        );

        $message = "Usuario admin listo: {$email}";

        if ($generatedPassword) {
            $message .= " / password temporal: {$password}";
        } else {
            $message .= ' / password tomada desde ADMIN_PASSWORD';
        }

        $this->command?->info($message);
    }
}
