<?php

use App\Models\Branch;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\ServiceOrder;
use App\Models\WorkshopJob;
use App\Services\InventoryService;
use App\Services\InvoiceService;
use App\Services\ReportService;
use App\Services\SaleService;
use App\Services\WorkshopJobService;
use Carbon\CarbonImmutable;

/**
 * Crea una orden con un trabajo (con repuestos) ya completado, lista para facturar.
 *
 * @return array{0: ServiceOrder, 1: WorkshopJob}
 */
function completedJobOrder(Branch $branch, \App\Models\Product $product, int $quantity = 1, float $labor = 50000): array
{
    $order = branchOrder($branch, ServiceOrder::STATUS_IN_PROGRESS);
    $job = app(WorkshopJobService::class)->addToOrder($order, [
        'job_type_id' => demoJobType()->id,
        'mechanic_id' => branchMechanic($branch)->id,
        'labor_cost' => $labor,
        'description' => 'Mantenimiento',
    ], [['id' => $product->id, 'quantity' => $quantity]]);
    $job->update(['status' => WorkshopJob::STATUS_COMPLETED, 'completed_at' => now()]);

    return [$order->fresh(), $job->fresh()];
}

describe('pantallas de la demo', function () {
    it('renders the add-job modal state inside the order page, not on the layout component', function () {
        [$user, $branch] = branchUser();
        $order = branchOrder($branch, ServiceOrder::STATUS_IN_PROGRESS);

        $this->actingAs($user)->withSession(['current_branch_id' => $branch->id])
            ->get(route('service-orders.show', $order))
            ->assertOk()
            ->assertSee('x-data="{ showJobModal: false }"', false)
            ->assertSee('x-data="jobForm(', false)
            ->assertSee(route('service-orders.jobs.store', $order), false);
    });

    it('reopens the job modal with the typed data when the server rejects it', function () {
        [$user, $branch] = branchUser();
        $order = branchOrder($branch, ServiceOrder::STATUS_IN_PROGRESS);
        $product = stockedProduct($branch, 1);

        $this->actingAs($user)->withSession(['current_branch_id' => $branch->id])
            ->from(route('service-orders.show', $order))
            ->post(route('service-orders.jobs.store', $order), [
                'job_type_id' => demoJobType()->id,
                'mechanic_id' => branchMechanic($branch)->id,
                'labor_cost' => 30000,
                'description' => 'Frenos',
                'products' => [['id' => $product->id, 'quantity' => 5]],
            ])
            ->assertRedirect(route('service-orders.show', $order));

        $this->actingAs($user)->withSession(['current_branch_id' => $branch->id])
            ->get(route('service-orders.show', $order))
            ->assertSee('x-data="{ showJobModal: true }"', false);
    });

    it('turns dashboard quick actions into real links', function () {
        [$user, $branch] = branchUser();

        $this->actingAs($user)->withSession(['current_branch_id' => $branch->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="' . route('sales.create') . '"', false)
            ->assertDontSee('<button type="button" href=', false)
            ->assertSee('Ingresos del mes');
    });

    it('shows the product detail page with a working supplier link', function () {
        [$user, $branch] = branchUser();
        $supplier = \App\Models\Supplier::create(['name' => 'Distrimotos', 'phone' => '3001112233', 'active' => true]);
        $product = stockedProduct($branch, 3);
        $product->suppliers()->sync([$supplier->id]);

        $this->actingAs($user)->withSession(['current_branch_id' => $branch->id])
            ->get(route('products.show', $product))
            ->assertOk()
            ->assertSee(route('suppliers.index', ['search' => 'Distrimotos']), false);
    });

    it('starts administrators on the first branch that was created', function () {
        $main = Branch::create(['name' => 'Sede Principal', 'is_active' => true]);
        Branch::create(['name' => 'Sede Centro', 'is_active' => true]);
        [$admin] = branchUser(admin: true, branch: $main);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSessionHas('current_branch_id', $main->id);
    });

    it('exposes one price per product for the sale and job forms without purchase costs', function () {
        [, $branch] = branchUser();
        $product = stockedProduct($branch, 2, cost: 10000, price: 20000);
        app(InventoryService::class)->registerPurchaseBatch([
            'product_id' => $product->id,
            'cost_price' => 12000,
            'sale_price' => 25000,
            'quantity' => 3,
        ]);

        $item = app(InventoryService::class)->sellableProducts($branch->id)->first();

        expect($item['stock'])->toBe(5)
            // La compra nueva lleva todo el inventario a su precio (decisión 4): un solo tramo.
            ->and($item['price'])->toBe(25000.0)
            ->and($item['tiers'])->toBe([['qty' => 5, 'price' => 25000.0]])
            ->and($item)->not->toHaveKey('purchase_price');
    });
});

describe('reportes', function () {
    it('reports real monthly income, profit, commissions and best sellers for the active branch', function () {
        [$admin, $branch] = branchUser(admin: true);
        $product = stockedProduct($branch, 10, cost: 10000, price: 20000);

        app(SaleService::class)->create($branch->id, $admin->id, null, 'Nequi', [['id' => $product->id, 'quantity' => 2]]);
        [$order] = completedJobOrder($branch, $product, quantity: 1, labor: 50000);
        app(InvoiceService::class)->generateFor($order, 'Nequi');

        // Otra sucursal no debe sumar.
        [, $otherBranch] = branchUser();
        $otherProduct = stockedProduct($otherBranch, 5);
        app(SaleService::class)->create($otherBranch->id, null, null, 'Efectivo', [['id' => $otherProduct->id, 'quantity' => 1]]);

        $report = app(ReportService::class)->summary($branch->id, CarbonImmutable::now()->startOfMonth(), CarbonImmutable::now()->endOfMonth());

        // Ventas 40.000 + orden (50.000 mano de obra + 20.000 repuesto)
        expect($report['income'])->toBe(['total' => 110000.0, 'sales' => 40000.0, 'orders' => 70000.0])
            // Taller 40% de 50.000 + margen (40.000 - 20.000) + (20.000 - 10.000)
            ->and($report['profit']['total'])->toBe(50000.0)
            ->and($report['commissions'])->toBe(30000.0)
            ->and($report['orders'])->toBe(['received' => 1, 'invoiced' => 1])
            ->and($report['top_products']->first())->toMatchArray(['quantity' => 3, 'revenue' => 60000.0])
            ->and((array) $report['payment_methods']->sole())->toBe(['payment_method' => 'Nequi', 'total' => 110000.0, 'sales' => 1, 'invoices' => 1])
            ->and($report['inventory']['cost_value'])->toBe(70000.0)
            ->and($report['daily']->sum('total'))->toBe(110000.0);

        $this->actingAs($admin)->withSession(['current_branch_id' => $branch->id])
            ->get(route('reports', ['month' => now()->format('Y-m')]))
            ->assertOk()
            ->assertSee('$110.000')
            ->assertDontSee('$0.00');
    });

    it('counts service income from active invoices, so it always matches what was collected', function () {
        [$admin, $branch] = branchUser(admin: true);
        $product = stockedProduct($branch, 10, cost: 10000, price: 20000);

        [$invoiced] = completedJobOrder($branch, $product, quantity: 1, labor: 50000);
        app(InvoiceService::class)->generateFor($invoiced, 'Efectivo');

        // Factura anulada y no vuelta a generar: ya no es ingreso.
        [$cancelled] = completedJobOrder($branch, $product, quantity: 1, labor: 30000);
        app(InvoiceService::class)->cancel(app(InvoiceService::class)->generateFor($cancelled), $admin->id);

        // Orden vieja marcada completada sin factura: no se cobró, no suma.
        [$withoutInvoice] = completedJobOrder($branch, $product, quantity: 1, labor: 40000);
        $withoutInvoice->update(['status' => 'completed', 'completed_at' => now()]);

        $report = app(ReportService::class)->summary($branch->id, CarbonImmutable::now()->startOfMonth(), CarbonImmutable::now()->endOfMonth());

        expect($report['income'])->toBe(['total' => 70000.0, 'sales' => 0.0, 'orders' => 70000.0])
            ->and($report['income']['total'])->toBe($report['payment_methods']->sum('total'))
            ->and($report['orders']['invoiced'])->toBe(1)
            ->and($report['daily']->sum('total'))->toBe(70000.0)
            ->and(app(ReportService::class)->income($branch->id, CarbonImmutable::today(), CarbonImmutable::today()))->toBe(70000.0);

        $this->actingAs($admin)->withSession(['current_branch_id' => $branch->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('todayEarnings', 70000.0)
            ->assertViewHas('monthEarnings', 70000.0);
    });

    it('shows on the dashboard the same monthly commissions as Reportes', function () {
        [$admin, $branch] = branchUser(admin: true);
        [, $job] = completedJobOrder($branch, stockedProduct($branch, 2), labor: 50000);
        // Un mecánico que ya no está pero trabajó este mes sigue contando, igual que en Reportes.
        $job->mechanic->update(['is_active' => false]);

        $expected = app(ReportService::class)->commissions($branch->id, CarbonImmutable::now()->startOfMonth(), CarbonImmutable::today());
        expect($expected->sum('earnings'))->toBe(30000.0);

        $this->actingAs($admin)->withSession(['current_branch_id' => $branch->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('mechanics', fn ($mechanics) => $mechanics->all() === $expected->all())
            ->assertSee($job->mechanic->name);
    });

    it('ignores cancelled sales in the report', function () {
        [$admin, $branch] = branchUser(admin: true);
        $product = stockedProduct($branch, 5);
        $sale = app(SaleService::class)->create($branch->id, $admin->id, null, 'Efectivo', [['id' => $product->id, 'quantity' => 1]])['sale'];
        app(SaleService::class)->cancel($sale);

        $report = app(ReportService::class)->summary($branch->id, CarbonImmutable::now()->startOfMonth(), CarbonImmutable::now()->endOfMonth());

        expect($report['income']['total'])->toBe(0.0)->and($report['sales_count'])->toBe(0);
    });
});

describe('facturas y órdenes', function () {
    it('reopens the order when its invoice is cancelled, while the invoice keeps the lines it was issued with', function () {
        [$admin, $branch] = branchUser(admin: true);
        $product = stockedProduct($branch, 5, price: 20000);
        [$order, $job] = completedJobOrder($branch, $product, quantity: 1, labor: 50000);
        $first = app(InvoiceService::class)->generateFor($order);
        $session = ['current_branch_id' => $branch->id];

        expect($first->lineItems())->toHaveCount(1)
            ->and($first->lineItems()[0])->toMatchArray(['labor' => 50000.0, 'description' => 'Mantenimiento'])
            ->and($first->lineItems()[0]['parts'][0])->toMatchArray(['name' => $product->name, 'quantity' => 1, 'total_price' => 20000.0]);

        $this->actingAs($admin)->withSession($session)->post(route('invoices.cancel', $first))->assertSessionHas('success');
        expect($order->fresh())->status->toBe(ServiceOrder::STATUS_IN_PROGRESS)->completed_at->toBeNull();

        // Se corrige la orden: sale el trabajo facturado y entra otro.
        app(WorkshopJobService::class)->delete($job);
        $fixed = app(WorkshopJobService::class)->addToOrder($order->fresh(), [
            'job_type_id' => demoJobType()->id,
            'mechanic_id' => branchMechanic($branch)->id,
            'labor_cost' => 65000,
            'description' => 'Ajuste de frenos',
        ]);
        $fixed->update(['status' => WorkshopJob::STATUS_COMPLETED, 'completed_at' => now()]);
        $second = app(InvoiceService::class)->generateFor($order->fresh());

        // La factura anulada se reimprime tal como se emitió; la nueva trae la corrección.
        $this->actingAs($admin)->withSession($session)->get(route('documents.invoice', $first))
            ->assertOk()->assertSee('Mantenimiento')->assertSee($product->name)->assertDontSee('Ajuste de frenos');
        $this->actingAs($admin)->withSession($session)->get(route('invoices.show', $second))
            ->assertOk()->assertSee('Ajuste de frenos')->assertDontSee($product->name);
        expect((float) $second->amount)->toBe(65000.0)
            ->and($order->fresh()->status)->toBe(ServiceOrder::STATUS_COMPLETED);
    });

    it('saves the lines of an invoice issued before the copy existed when it is cancelled', function () {
        [$admin, $branch] = branchUser(admin: true);
        [$order] = completedJobOrder($branch, stockedProduct($branch, 3), labor: 40000);
        $invoice = app(InvoiceService::class)->generateFor($order);
        Invoice::whereKey($invoice->id)->update(['line_items' => null]);

        // Sin copia lee la orden, que con la factura vigente no ha cambiado.
        expect($invoice->fresh()->lineItems()->first()['labor'])->toBe(40000.0);

        app(InvoiceService::class)->cancel($invoice->fresh(), $admin->id);

        expect($invoice->fresh()->line_items)->not->toBeNull()
            ->and($invoice->fresh()->lineItems()->first()['labor'])->toBe(40000.0);
    });

    it('keeps cancelled invoices on record and lets the order be invoiced again', function () {
        [$admin, $branch] = branchUser(admin: true);
        [$order] = completedJobOrder($branch, stockedProduct($branch, 3));
        $invoice = app(InvoiceService::class)->generateFor($order);
        $session = ['current_branch_id' => $branch->id];

        $this->actingAs($admin)->withSession($session)
            ->post(route('invoices.cancel', $invoice))
            ->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        expect($invoice->isCancelled())->toBeTrue()
            ->and($invoice->cancelled_by)->toBe($admin->id);

        $this->actingAs($admin)->withSession($session)
            ->from(route('invoices.show', $invoice))
            ->post(route('invoices.cancel', $invoice))
            ->assertSessionHas('error');

        $this->actingAs($admin)->withSession($session)
            ->post(route('invoices.generate', $order), ['payment_method' => 'Efectivo'])
            ->assertRedirect();

        expect(Invoice::count())->toBe(2)
            ->and(Invoice::active()->count())->toBe(1)
            ->and(Invoice::active()->first()->invoice_number)->not->toBe($invoice->invoice_number);

        $this->actingAs($admin)->withSession($session)
            ->get(route('invoices.show', $invoice))
            ->assertSee('Factura anulada');
    });

    it('returns parts to stock when an open order is cancelled', function () {
        [$user, $branch] = branchUser();
        $product = stockedProduct($branch, 5);
        $order = branchOrder($branch, ServiceOrder::STATUS_IN_PROGRESS);
        $job = app(WorkshopJobService::class)->addToOrder($order, [
            'job_type_id' => demoJobType()->id,
            'mechanic_id' => branchMechanic($branch)->id,
            'labor_cost' => 30000,
            'description' => 'Frenos',
        ], [['id' => $product->id, 'quantity' => 2]]);

        expect($product->fresh()->stock)->toBe(3);

        $this->actingAs($user)->withSession(['current_branch_id' => $branch->id])
            ->put(route('service-orders.update', $order), [
                'customer_name' => $order->customer_name,
                'vehicle_info' => $order->vehicle_info,
                'service_description' => $order->service_description,
                'status' => ServiceOrder::STATUS_CANCELLED,
            ])
            ->assertRedirect(route('service-orders.show', $order));

        expect($order->fresh()->status)->toBe(ServiceOrder::STATUS_CANCELLED)
            ->and($job->fresh()->status)->toBe(WorkshopJob::STATUS_CANCELLED)
            ->and($product->fresh()->stock)->toBe(5)
            ->and(batchStock($product))->toBe(5)
            ->and(InventoryMovement::where('reference', "Cancelacion Orden #{$order->id}")->sum('quantity'))->toBe(2);

        // Eliminar el trabajo cancelado no puede devolver el stock otra vez.
        expect(fn () => app(WorkshopJobService::class)->delete($job->fresh()))
            ->toThrow(\App\Exceptions\BusinessRuleException::class);
        expect($product->fresh()->stock)->toBe(5);
    });

    it('does not cancel orders whose jobs were already completed', function () {
        [$user, $branch] = branchUser();
        $product = stockedProduct($branch, 5);
        [$order] = completedJobOrder($branch, $product);

        $this->actingAs($user)->withSession(['current_branch_id' => $branch->id])
            ->from(route('service-orders.edit', $order))
            ->put(route('service-orders.update', $order), [
                'customer_name' => $order->customer_name,
                'vehicle_info' => $order->vehicle_info,
                'service_description' => $order->service_description,
                'status' => ServiceOrder::STATUS_CANCELLED,
            ])
            ->assertRedirect(route('service-orders.edit', $order))
            ->assertSessionHasErrors('status');

        expect($order->fresh()->status)->toBe(ServiceOrder::STATUS_IN_PROGRESS)
            ->and($product->fresh()->stock)->toBe(4);
    });

    it('requires finished jobs before closing an order by hand', function () {
        [$user, $branch] = branchUser();
        $order = branchOrder($branch, ServiceOrder::STATUS_IN_PROGRESS);
        app(WorkshopJobService::class)->addToOrder($order, [
            'job_type_id' => demoJobType()->id,
            'mechanic_id' => branchMechanic($branch)->id,
            'labor_cost' => 30000,
            'description' => 'Sincronizacion',
        ]);

        $this->actingAs($user)->withSession(['current_branch_id' => $branch->id])
            ->from(route('service-orders.edit', $order))
            ->put(route('service-orders.update', $order), [
                'customer_name' => $order->customer_name,
                'vehicle_info' => $order->vehicle_info,
                'service_description' => $order->service_description,
                'status' => ServiceOrder::STATUS_COMPLETED,
            ])
            ->assertSessionHasErrors('status');

        expect($order->fresh()->status)->toBe(ServiceOrder::STATUS_IN_PROGRESS);
    });

    it('deactivates job types with history instead of failing', function () {
        [$admin, $branch] = branchUser(admin: true);
        [, $job] = completedJobOrder($branch, stockedProduct($branch, 2));
        $jobType = $job->jobType;

        $this->actingAs($admin)->withSession(['current_branch_id' => $branch->id])
            ->delete(route('job-types.destroy', $jobType))
            ->assertRedirect(route('job-types.index'))
            ->assertSessionHas('info');

        expect($jobType->fresh())->not->toBeNull()
            ->and($jobType->fresh()->is_active)->toBeFalse();
    });
});
