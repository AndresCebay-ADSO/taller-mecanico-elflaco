<?php

use App\Models\Invoice;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\User;
use Database\Seeders\DemoSeeder;

it('seeds a consistent demo dataset', function () {
    $this->seed(DemoSeeder::class);

    expect(User::where('email', 'admin@elflaco.test')->first()?->is_admin)->toBeTrue()
        ->and(Sale::count())->toBeGreaterThan(50)
        ->and(Invoice::count())->toBeGreaterThan(20)
        ->and(ServiceOrder::where('status', 'in_progress')->exists())->toBeTrue();

    $this->artisan('inventory:check')->assertExitCode(0);
});
