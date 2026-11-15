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

it('completes an order only by generating its receipt', function () {
    [$employee, $branch] = branchUser();
    $order = cashOrder($branch, 40000); // trabajo completado, orden en progreso
    $session = ['current_branch_id' => $branch->id];

    $this->actingAs($employee)->withSession($session)->get(route('service-orders.edit', $order))
        ->assertOk()->assertDontSee('<option value="completed"', false);

    $this->actingAs($employee)->withSession($session)
        ->from(route('service-orders.edit', $order))
        ->put(route('service-orders.update', $order), [
            'customer_name' => $order->customer_name, 'vehicle_info' => $order->vehicle_info,
            'service_description' => $order->service_description, 'status' => 'completed',
        ])
        ->assertSessionHasErrors(['status' => 'La orden se completa al generar su factura, desde el detalle de la orden.']);
    expect($order->fresh()->status)->toBe('in_progress');

    $this->actingAs($employee)->withSession($session)
        ->post(route('invoices.generate', $order), ['payment_method' => 'Efectivo']);
    expect($order->fresh()->status)->toBe('completed');

    // Ya completada, se puede seguir editando el cliente sin cambiar el estado.
    $this->actingAs($employee)->withSession($session)
        ->put(route('service-orders.update', $order), [
            'customer_name' => 'Nombre corregido', 'vehicle_info' => $order->vehicle_info,
            'service_description' => $order->service_description, 'status' => 'completed',
        ])
        ->assertSessionHasNoErrors();
    expect($order->fresh()->customer_name)->toBe('Nombre corregido');
});
