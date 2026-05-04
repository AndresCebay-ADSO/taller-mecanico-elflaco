<?php

use App\Models\JobType;
use App\Models\Mechanic;
use App\Models\ServiceOrder;
use App\Models\User;

it('rejects invalid backward transitions for service orders', function () {
    $user = User::factory()->create();

    $serviceOrder = ServiceOrder::create([
        'customer_name' => 'Maria Gomez',
        'customer_phone' => '3001112233',
        'vehicle_info' => 'AKT NKD',
        'service_description' => 'Revision general',
        'status' => 'completed',
        'started_at' => now()->subDay(),
        'completed_at' => now(),
    ]);

    $this->from(route('service-orders.edit', $serviceOrder))
        ->actingAs($user)
        ->put(route('service-orders.update', $serviceOrder), [
            'customer_name' => $serviceOrder->customer_name,
            'customer_phone' => $serviceOrder->customer_phone,
            'vehicle_info' => $serviceOrder->vehicle_info,
            'service_description' => $serviceOrder->service_description,
            'status' => 'pending',
        ])
        ->assertRedirect(route('service-orders.edit', $serviceOrder))
        ->assertSessionHasErrors('status');

    expect($serviceOrder->fresh()->status)->toBe('completed');
});

it('rejects new jobs on completed service orders', function () {
    $user = User::factory()->create();

    $serviceOrder = ServiceOrder::create([
        'customer_name' => 'Pedro Ruiz',
        'customer_phone' => '3004445566',
        'vehicle_info' => 'Yamaha FZ',
        'service_description' => 'Cambio de kit de arrastre',
        'status' => 'completed',
        'started_at' => now()->subDay(),
        'completed_at' => now(),
    ]);

    $mechanic = Mechanic::create([
        'name' => 'Luis Taller',
        'phone' => '3007778899',
        'email' => 'luis@example.com',
        'hire_date' => now()->subMonths(6)->toDateString(),
        'is_active' => true,
    ]);

    $jobType = JobType::create([
        'name' => 'Mantenimiento',
        'calculation_type' => 'percentage',
        'mechanic_percentage' => 50,
        'workshop_percentage' => 50,
        'allow_products' => true,
        'allow_custom_labor' => true,
        'is_active' => true,
        'is_system' => false,
    ]);

    $this->from(route('service-orders.show', $serviceOrder))
        ->actingAs($user)
        ->post(route('service-orders.jobs.store', $serviceOrder), [
            'job_type_id' => $jobType->id,
            'mechanic_id' => $mechanic->id,
            'labor_cost' => 120,
            'description' => 'Intento tardio',
        ])
        ->assertRedirect(route('service-orders.show', $serviceOrder))
        ->assertSessionHasErrors('service_order');

    expect($serviceOrder->workshopJobs()->count())->toBe(0);
});
