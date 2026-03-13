<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminSeeder extends Seeder
{
    /**
     * Crea el usuario administrador inicial del sistema.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@tallerflacos.com'],
            [
                'name'     => 'Administrador',
                'password' => Hash::make('password'),
            ]
        );

        $this->command->info('✓ Usuario admin creado: admin@tallerflacos.com / password');
    }
}
