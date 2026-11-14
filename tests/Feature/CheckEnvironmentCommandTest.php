<?php

use App\Models\Branch;
use App\Models\User;

it('reports a missing administrator and branch as errors', function () {
    $this->artisan('taller:verificar')
        ->expectsOutputToContain('Administrador activo')
        ->assertFailed();
});

it('passes when the database has an administrator and an active branch', function () {
    Branch::create(['name' => 'Sede Principal', 'is_active' => true]);
    User::factory()->create(['is_admin' => true, 'is_active' => true]);
    config(['app.timezone' => 'America/Bogota']);

    // El build de la interfaz no existe en el entorno de pruebas: se simula para revisar el resto.
    $manifest = public_path('build/manifest.json');
    $created = ! file_exists($manifest);
    if ($created) {
        @mkdir(dirname($manifest), 0777, true);
        file_put_contents($manifest, '{}');
    }

    try {
        $this->artisan('taller:verificar')->assertSuccessful();
    } finally {
        if ($created) {
            unlink($manifest);
        }
    }
});

it('warns in production about the install steps that are done by hand', function () {
    app()->detectEnvironment(fn () => 'production');
    config([
        'app.debug' => false,
        'auth.admin.password' => 'la-del-primer-ingreso',
        'database.connections.' . config('database.default') . '.username' => 'root',
    ]);

    $this->artisan('taller:verificar')
        ->expectsOutputToContain('ADMIN_PASSWORD')
        ->expectsOutputToContain('Usuario de la base');
});
