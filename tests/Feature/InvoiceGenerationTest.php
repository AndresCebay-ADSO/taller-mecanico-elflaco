<?php

use App\Models\Invoice;
use App\Models\JobType;
use App\Models\JobProduct;
use App\Models\Mechanic;
use App\Models\Product;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Models\WorkshopJob;

it('does not generate a second invoice for the same service order', function () {
    $user = User::factory()->create();

    $serviceOrder = ServiceOrder::create([
        'customer_name' => 'Carlos Perez',
        'customer_phone' => '3001234567',
        'vehicle_info' => 'Mazda 3',
        'service_description' => 'Mantenimiento general',
        'status' => 'in_progress',
        'started_at' => now(),
    ]);

    $mechanic = Mechanic::create([
        'name' => 'Juan Mecanico',
        'phone' => '3007654321',
        'email' => 'juan@example.com',
        'hire_date' => now()->subYear()->toDateString(),
        'is_active' => true,
    ]);

    $jobType = JobType::create([
        'name' => 'Cambio de aceite',
        'calculation_type' => 'percentage',
        'mechanic_percentage' => 50,
        'workshop_percentage' => 50,
        'allow_products' => true,
        'allow_custom_labor' => true,
        'is_active' => true,
        'is_system' => false,
    ]);

    $product = Product::create([
        'name' => 'Aceite 10W40',
        'category' => 'Lubricantes',
        'purchase_price' => 30,
        'sale_price' => 50,
        'stock' => 10,
        'min_stock' => 1,
    ]);

    $job = WorkshopJob::create([
        'service_order_id' => $serviceOrder->id,
        'job_type_id' => $jobType->id,
        'mechanic_id' => $mechanic->id,
        'customer_name' => $serviceOrder->customer_name,
        'customer_phone' => $serviceOrder->customer_phone,
        'vehicle_info' => $serviceOrder->vehicle_info,
        'description' => 'Cambio de aceite completo',
        'labor_cost' => 200,
        'mechanic_cost' => 100,
        'workshop_cost' => 100,
        'total_amount' => 200,
        'status' => 'completed',
        'started_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    JobProduct::create([
        'job_id' => $job->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => 50,
        'total_price' => 50,
    ]);

    $this->actingAs($user)
        ->post(route('invoices.generate', $serviceOrder))
        ->assertRedirect();

    expect(Invoice::count())->toBe(1)
        ->and($serviceOrder->fresh()->status)->toBe('completed');

    $this->from(route('service-orders.show', $serviceOrder))
        ->actingAs($user)
        ->post(route('invoices.generate', $serviceOrder))
        ->assertRedirect(route('service-orders.show', $serviceOrder))
        ->assertSessionHas('error', 'Esta orden ya tiene una factura vinculada.');

    expect(Invoice::count())->toBe(1);
});
