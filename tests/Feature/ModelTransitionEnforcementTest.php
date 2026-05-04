<?php

use App\Models\JobType;
use App\Models\Mechanic;
use App\Models\ServiceOrder;
use App\Models\WorkshopJob;

it('enforces service order transitions at model level', function () {
    $serviceOrder = ServiceOrder::create([
        'customer_name' => 'Andrea Diaz',
        'customer_phone' => '3009991111',
        'vehicle_info' => 'Suzuki GN',
        'service_description' => 'Revision electrica',
        'status' => ServiceOrder::STATUS_COMPLETED,
        'started_at' => now()->subDay(),
        'completed_at' => now(),
    ]);

    expect(fn () => $serviceOrder->update(['status' => ServiceOrder::STATUS_PENDING]))
        ->toThrow(InvalidArgumentException::class);

    expect($serviceOrder->fresh()->status)->toBe(ServiceOrder::STATUS_COMPLETED);
});

it('enforces workshop job transitions at model level', function () {
    $mechanic = Mechanic::create([
        'name' => 'Mario Lopez',
        'phone' => '3002223344',
        'email' => 'mario@example.com',
        'hire_date' => now()->subMonths(3)->toDateString(),
        'is_active' => true,
    ]);

    $jobType = JobType::create([
        'name' => 'Frenos',
        'calculation_type' => 'percentage',
        'mechanic_percentage' => 50,
        'workshop_percentage' => 50,
        'allow_products' => true,
        'allow_custom_labor' => true,
        'is_active' => true,
        'is_system' => false,
    ]);

    $serviceOrder = ServiceOrder::create([
        'customer_name' => 'Laura Navas',
        'customer_phone' => '3001231234',
        'vehicle_info' => 'Honda Wave',
        'service_description' => 'Cambio de bandas',
        'status' => ServiceOrder::STATUS_IN_PROGRESS,
        'started_at' => now()->subHour(),
    ]);

    $job = WorkshopJob::create([
        'service_order_id' => $serviceOrder->id,
        'job_type_id' => $jobType->id,
        'mechanic_id' => $mechanic->id,
        'customer_name' => $serviceOrder->customer_name,
        'customer_phone' => $serviceOrder->customer_phone,
        'vehicle_info' => $serviceOrder->vehicle_info,
        'description' => 'Cambio de bandas completo',
        'labor_cost' => 150,
        'status' => WorkshopJob::STATUS_COMPLETED,
        'started_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    expect(fn () => $job->update(['status' => WorkshopJob::STATUS_PENDING]))
        ->toThrow(InvalidArgumentException::class);

    expect($job->fresh()->status)->toBe(WorkshopJob::STATUS_COMPLETED);
});
