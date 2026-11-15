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

it('does not delete an order that has a cancelled invoice', function () {
    [$admin, $branch] = branchUser(admin: true);
    $order = cashOrder($branch, 30000);
    $invoice = app(\App\Services\InvoiceService::class)->generateFor($order, 'Nequi');
    app(\App\Services\InvoiceService::class)->cancel($invoice, $admin->id);
    foreach ($order->workshopJobs as $job) {
        app(\App\Services\WorkshopJobService::class)->delete($job);
    }

    $this->actingAs($admin)->withSession(['current_branch_id' => $branch->id])
        ->from(route('service-orders.show', $order))
        ->delete(route('service-orders.destroy', $order))
        ->assertSessionHas('error', fn ($text) => str_contains($text, 'tiene facturas registradas'));

    expect($order->fresh())->not->toBeNull();
});

it('keeps the customer and vehicle on the invoice as they were when issued', function () {
    [$admin, $branch] = branchUser(admin: true);
    $order = cashOrder($branch, 30000);
    $order->update(['customer_name' => 'Ana Pérez', 'vehicle_info' => 'Honda CB 125F - ABC12D', 'customer_document' => '1075123456']);
    $invoice = app(\App\Services\InvoiceService::class)->generateFor($order->fresh(), 'Nequi');

    $order->update(['customer_name' => 'Nombre corregido después', 'vehicle_info' => 'Otra moto']);

    expect($invoice->fresh()->customer())->toMatchArray(['customer_name' => 'Ana Pérez', 'customer_document' => '1075123456']);
    $this->actingAs($admin)->withSession(['current_branch_id' => $branch->id])
        ->get(route('documents.invoice', $invoice))
        ->assertOk()->assertSee('Ana Pérez')->assertSee('Honda CB 125F - ABC12D')->assertDontSee('Nombre corregido después');

    // Facturas anteriores a la copia: leen la orden y la guardan al anularse.
    \App\Models\Invoice::whereKey($invoice->id)->update(['customer_snapshot' => null]);
    expect($invoice->fresh()->customer()['customer_name'])->toBe('Nombre corregido después');
    app(\App\Services\InvoiceService::class)->cancel($invoice->fresh(), $admin->id);
    expect($invoice->fresh()->customer_snapshot['customer_name'])->toBe('Nombre corregido después');
});
