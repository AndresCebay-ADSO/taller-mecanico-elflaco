<?php

use App\Models\JobType;
use App\Models\Mechanic;
use App\Models\ServiceOrder;
use App\Models\WorkshopJob;

it('deactivates mechanics with history instead of deleting them', function () {
    [$admin, $branch] = branchUser(admin: true);

    $mechanic = Mechanic::create([
        'name' => 'Carlos Llaves',
        'phone' => '3005556677',
        'email' => 'carlos@example.com',
        'hire_date' => now()->subMonths(8)->toDateString(),
        'is_active' => true,
        'branch_id' => $branch->id,
    ]);

    $jobType = JobType::create([
        'name' => 'Alineacion',
        'calculation_type' => 'percentage',
        'mechanic_percentage' => 50,
        'workshop_percentage' => 50,
        'allow_products' => true,
        'is_active' => true,
        'is_system' => false,
    ]);

    $serviceOrder = ServiceOrder::create([
        'customer_name' => 'Cliente Uno',
        'customer_phone' => '3008889999',
        'vehicle_info' => 'Pulsar 200',
        'service_description' => 'Revision',
        'status' => ServiceOrder::STATUS_IN_PROGRESS,
        'started_at' => now(),
        'branch_id' => $branch->id,
    ]);

    WorkshopJob::create([
        'service_order_id' => $serviceOrder->id,
        'job_type_id' => $jobType->id,
        'mechanic_id' => $mechanic->id,
        'customer_name' => $serviceOrder->customer_name,
        'customer_phone' => $serviceOrder->customer_phone,
        'vehicle_info' => $serviceOrder->vehicle_info,
        'description' => 'Trabajo historico',
        'labor_cost' => 90,
        'status' => WorkshopJob::STATUS_COMPLETED,
        'started_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    $this->actingAs($admin)
        ->withSession(['current_branch_id' => $branch->id])
        ->delete(route('mechanics.destroy', $mechanic))
        ->assertRedirect(route('mechanics.index'));

    expect(Mechanic::find($mechanic->id))->not->toBeNull()
        ->and($mechanic->fresh()->is_active)->toBeFalse();
});
