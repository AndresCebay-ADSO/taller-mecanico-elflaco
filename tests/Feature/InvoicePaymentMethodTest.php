<?php

use App\Models\Invoice;
use App\Models\ServiceOrder;
use App\Services\ReportService;
use App\Services\SaleService;
use Carbon\CarbonImmutable;

function orderReadyToInvoice($branch): ServiceOrder
{
    $order = ServiceOrder::create([
        'customer_name' => 'Jhon Cuéllar',
        'vehicle_info' => 'Yamaha XTZ 125',
        'service_description' => 'Cambio de kit de arrastre',
        'status' => 'in_progress',
        'branch_id' => $branch->id,
    ]);

    $job = app(\App\Services\WorkshopJobService::class)->addToOrder($order, [
        'job_type_id' => demoJobType()->id,
        'mechanic_id' => branchMechanic($branch)->id,
        'description' => 'Kit de arrastre',
        'labor_cost' => 60000,
    ]);
    $job->update(['status' => 'completed', 'completed_at' => now()]);

    return $order->fresh();
}

describe('método de pago del comprobante de servicio', function () {
    it('asks how the customer paid and saves it on the invoice', function () {
        [$user, $branch] = branchUser();
        $order = orderReadyToInvoice($branch);
        $session = ['current_branch_id' => $branch->id];

        $this->actingAs($user)->withSession($session)
            ->get(route('service-orders.show', $order))
            ->assertOk()->assertSee('¿Cómo pagó?')->assertSee('Daviplata');

        $this->actingAs($user)->withSession($session)->from(route('service-orders.show', $order))
            ->post(route('invoices.generate', $order))
            ->assertSessionHasErrors(['payment_method' => 'Elige cómo pagó el cliente.']);

        $this->actingAs($user)->withSession($session)->from(route('service-orders.show', $order))
            ->post(route('invoices.generate', $order), ['payment_method' => 'Bitcoin'])
            ->assertSessionHasErrors('payment_method');

        expect(Invoice::count())->toBe(0);

        $this->actingAs($user)->withSession($session)
            ->post(route('invoices.generate', $order), ['payment_method' => 'Daviplata'])
            ->assertRedirect();

        $invoice = Invoice::sole();
        expect($invoice->payment_method)->toBe('Daviplata');

        $this->actingAs($user)->withSession($session)->get(route('invoices.show', $invoice))->assertSee('Daviplata');
        $this->actingAs($user)->withSession($session)->get(route('invoices.index'))->assertSee('Daviplata');
        $this->actingAs($user)->withSession($session)->get(route('documents.invoice', $invoice))
            ->assertOk()->assertSeeInOrder(['Pago', 'Daviplata']);
    });

    it('shows older invoices without a method as not recorded', function () {
        [$user, $branch] = branchUser();
        $invoice = app(\App\Services\InvoiceService::class)->generateFor(orderReadyToInvoice($branch));
        $invoice->update(['payment_method' => null]);

        expect($invoice->fresh()->paymentMethodLabel())->toBe('Sin registrar');

        $this->actingAs($user)->withSession(['current_branch_id' => $branch->id])
            ->get(route('documents.invoice', $invoice))
            ->assertOk()->assertDontSee('Sin registrar');
    });

    it('adds counter sales and service receipts by method, leaving out cancelled ones', function () {
        [$admin, $branch] = branchUser(admin: true);
        $product = stockedProduct($branch, 10, cost: 10000, price: 20000);

        app(SaleService::class)->create($branch->id, $admin->id, null, 'Efectivo', [['id' => $product->id, 'quantity' => 1]]);
        app(\App\Services\InvoiceService::class)->generateFor(orderReadyToInvoice($branch), 'Efectivo');
        app(\App\Services\InvoiceService::class)->generateFor(orderReadyToInvoice($branch), 'Nequi');
        $cancelled = app(\App\Services\InvoiceService::class)->generateFor(orderReadyToInvoice($branch), 'Tarjeta');
        app(\App\Services\InvoiceService::class)->cancel($cancelled, $admin->id);

        $today = CarbonImmutable::today();
        $methods = app(ReportService::class)->paymentMethods($branch->id, $today, $today)->keyBy('payment_method');

        expect($methods->keys()->all())->toBe(['Efectivo', 'Nequi'])
            ->and($methods['Efectivo']->total)->toBe(80000.0)
            ->and($methods['Efectivo']->sales)->toBe(1)
            ->and($methods['Efectivo']->invoices)->toBe(1)
            ->and($methods['Nequi']->total)->toBe(60000.0);

        $this->actingAs($admin)->withSession(['current_branch_id' => $branch->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Cobrado hoy por método')
            ->assertSeeInOrder(['Efectivo', '$80.000', 'Nequi', '$60.000']);
    });
});
