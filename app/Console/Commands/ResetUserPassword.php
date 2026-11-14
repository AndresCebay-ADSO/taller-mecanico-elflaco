<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Contraseña olvidada: el sistema no envía correos, así que se restablece desde la consola del portátil
 * (o por AnyDesk). Deja una contraseña temporal que el usuario cambia en Perfil.
 */
class ResetUserPassword extends Command
{
    protected $signature = 'taller:restablecer-clave {email : Correo con el que entra el usuario}';

    protected $description = 'Le pone una contraseña temporal a un usuario que olvidó la suya';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (!$user) {
            $this->error('No hay ningún usuario con ese correo. Usuarios: ' . User::orderBy('email')->pluck('email')->join(', '));

            return self::FAILURE;
        }

        $password = Str::password(12, symbols: false);
        $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

        $this->info("Contraseña temporal de {$user->email}: {$password}");
        $this->line('Que entre con ella y la cambie en Perfil. Sus otras sesiones abiertas se cerraron.');
        if (!$user->is_active) {
            $this->warn('Ojo: este usuario está desactivado. Actívalo en Usuarios para que pueda entrar.');
        }

        return self::SUCCESS;
    }
}
