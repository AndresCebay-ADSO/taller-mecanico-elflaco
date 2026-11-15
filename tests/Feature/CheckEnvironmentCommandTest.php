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

it('flags PHP versions with known security holes or without support', function (string $version, string $status) {
    expect(\App\Console\Commands\CheckEnvironment::phpSecurity($version)[0])->toBe($status);
})->with([
    'parche de octubre 2026' => ['8.5.12', 'OK'],
    'antes del parche' => ['8.5.11', 'AVISO'],
    'rama 8.4 parchada' => ['8.4.27', 'OK'],
    'rama 8.3 sin parche' => ['8.3.35', 'AVISO'],
    'rama 8.2 sin soporte' => ['8.2.35', 'AVISO'],
    'rama más nueva' => ['8.6.0', 'OK'],
]);

it('warns when Apache listens on the whole network', function () {
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'apache-' . uniqid();
    mkdir($dir . DIRECTORY_SEPARATOR . 'extra', 0777, true);
    $conf = $dir . DIRECTORY_SEPARATOR . 'httpd.conf';
    config(['taller.apache_conf' => $conf]);

    try {
        file_put_contents($conf, "# Listen 12.34.56.78:80\nListen 80\n");
        $this->artisan('taller:verificar')->expectsOutputToContain('Escucha en 80');

        file_put_contents($conf, "Listen 127.0.0.1:80\n");
        file_put_contents($dir . DIRECTORY_SEPARATOR . 'extra' . DIRECTORY_SEPARATOR . 'httpd-ssl.conf', "Listen 127.0.0.1:443\n");
        $this->artisan('taller:verificar')->expectsOutputToContain('Listen 127.0.0.1')->doesntExpectOutputToContain('Escucha en');
    } finally {
        array_map('unlink', glob($dir . DIRECTORY_SEPARATOR . 'extra' . DIRECTORY_SEPARATOR . '*') ?: []);
        array_map('unlink', glob($dir . DIRECTORY_SEPARATOR . '*.conf') ?: []);
        rmdir($dir . DIRECTORY_SEPARATOR . 'extra');
        rmdir($dir);
    }
});
