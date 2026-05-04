<?php

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

it('creates the admin user from environment values', function () {
    config([
        'auth.admin.name' => 'Root Taller',
        'auth.admin.email' => 'root@example.com',
        'auth.admin.password' => 'SuperSecreta123!',
    ]);

    $this->seed(AdminSeeder::class);

    $user = User::where('email', 'root@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Root Taller')
        ->and(Hash::check('SuperSecreta123!', $user->password))->toBeTrue();
});

it('database seeder does not create the legacy test user anymore', function () {
    config([
        'auth.admin.name' => 'Admin Final',
        'auth.admin.email' => 'adminfinal@example.com',
        'auth.admin.password' => 'OtraClave123!',
    ]);

    $this->seed(DatabaseSeeder::class);

    expect(User::where('email', 'test@example.com')->exists())->toBeFalse()
        ->and(User::where('email', 'adminfinal@example.com')->exists())->toBeTrue();
});
