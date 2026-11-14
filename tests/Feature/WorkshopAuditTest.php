<?php

use App\Models\ServiceOrder;

it('validates the phone of a standalone job like the one of an order, so the order stays editable', function () {
    [$employee, $branch] = branchUser();
    $session = ['current_branch_id' => $branch->id];
    $job = fn (string $phone) => $this->actingAs($employee)->withSession($session)->post(route('jobs.store_individual'), [
        'customer_name' => 'Cliente', 'customer_phone' => $phone, 'vehicle_info' => 'AKT 125',
        'job_type_id' => demoJobType()->id, 'mechanic_id' => branchMechanic($branch)->id,
        'labor_cost' => 20000, 'description' => 'Cambio de aceite',
    ]);

    $job('300 123 4567')->assertSessionHasErrors('customer_phone');
    expect(ServiceOrder::count())->toBe(0);

    $job('3001234567')->assertSessionHasNoErrors();
    $order = ServiceOrder::sole();

    $this->actingAs($employee)->withSession($session)
        ->put(route('service-orders.update', $order), [
            'customer_name' => 'Cliente', 'customer_phone' => $order->customer_phone, 'vehicle_info' => 'AKT 125',
            'service_description' => 'Cambio de aceite', 'status' => $order->status,
        ])
        ->assertSessionHasNoErrors();
});
