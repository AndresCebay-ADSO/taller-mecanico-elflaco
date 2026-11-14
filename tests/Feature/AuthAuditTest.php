<?php

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

it('creates the initial user as an active administrator', function () {
    config(['auth.admin.email' => 'duena@example.com', 'auth.admin.password' => 'ClaveInicial123!']);

    $this->seed(AdminSeeder::class);

    $user = User::where('email', 'duena@example.com')->firstOrFail();
    expect($user->is_admin)->toBeTrue()->and($user->is_active)->toBeTrue();

    $this->post('/login', ['email' => 'duena@example.com', 'password' => 'ClaveInicial123!']);
    $this->get(route('users.index'))->assertOk();
});

it('does not reset the administrator password or the settings when the seeders run again', function () {
    config(['auth.admin.email' => 'duena@example.com', 'auth.admin.password' => null]);
    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'duena@example.com')->firstOrFail();
    $admin->update(['password' => 'LaQueEllaEligio1']);
    Setting::updateOrCreate(['key' => 'workshop_name'], ['value' => 'Taller Real de Prueba']);

    $this->seed(DatabaseSeeder::class);

    expect(Hash::check('LaQueEllaEligio1', $admin->fresh()->password))->toBeTrue()
        ->and(Setting::where('key', 'workshop_name')->value('value'))->toBe('Taller Real de Prueba');
});

it('resets a forgotten password from the console with a temporary one', function () {
    [$user] = branchUser();
    $oldToken = $user->remember_token;

    $this->artisan('taller:restablecer-clave', ['email' => $user->email])
        ->expectsOutputToContain('Contraseña temporal')
        ->assertSuccessful();

    $user->refresh();
    expect(Hash::check('password', $user->password))->toBeFalse()
        ->and($user->remember_token)->not->toBe($oldToken);

    $this->artisan('taller:restablecer-clave', ['email' => 'nadie@example.com'])->assertFailed();
});
