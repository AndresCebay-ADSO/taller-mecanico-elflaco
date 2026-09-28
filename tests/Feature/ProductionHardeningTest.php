<?php

use App\Models\Batch;
use App\Models\Branch;
use App\Models\BranchTransfer;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\JobType;
use App\Models\Mechanic;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Models\WorkshopJob;
use App\Services\InventoryService;
use App\Services\InvoiceService;
use App\Services\SaleService;
use App\Services\WorkshopJobService;
use Illuminate\Support\Facades\Hash;

describe('inventario sincronizado con lotes', function () {
    it('allows selling units added through a positive adjustment', function () {
        [$admin, $branch] = branchUser(admin: true);
        $product = stockedProduct($branch, 5);
        $session = ['current_branch_id' => $branch->id];

        $this->actingAs($admin)->withSession($session)
            ->post(route('inventory.store-adjustment'), ['product_id' => $product->id, 'quantity' => 3, 'reason' => 'adjustment'])
            ->assertRedirect(route('inventory.index'));

        expect($product->fresh()->stock)->toBe(8)->and(batchStock($product))->toBe(8);

        $this->actingAs($admin)->withSession($session)
            ->post(route('sales.store'), ['payment_method' => 'Efectivo', 'products' => [['id' => $product->id, 'quantity' => 8]]])
            ->assertRedirect(route('sales.index'));

        expect($product->fresh()->stock)->toBe(0)->and(batchStock($product))->toBe(0);
    });

    it('consumes batches when stock is removed by an adjustment', function () {
        [$admin, $branch] = branchUser(admin: true);
        $product = stockedProduct($branch, 5, cost: 12000);

        $this->actingAs($admin)->withSession(['current_branch_id' => $branch->id])
            ->post(route('inventory.store-adjustment'), ['product_id' => $product->id, 'quantity' => -2, 'reason' => 'damage', 'notes' => 'Llegaron rotos'])
            ->assertRedirect(route('inventory.index'));

        $movement = InventoryMovement::where('movement_type', 'adjustment')->firstOrFail();

        expect($product->fresh()->stock)->toBe(3)
            ->and(batchStock($product))->toBe(3)
            ->and($movement->quantity)->toBe(-2)
            ->and((float) $movement->unit_price)->toBe(12000.0);
    });

    it('returns job products to their original batch when a job is deleted', function () {
        [$employee, $branch] = branchUser();
        $product = stockedProduct($branch, 5);
        $order = branchOrder($branch);
        $session = ['current_branch_id' => $branch->id];

        $this->actingAs($employee)->withSession($session)
            ->post(route('service-orders.jobs.store', $order), [
                'job_type_id' => demoJobType()->id,
                'mechanic_id' => branchMechanic($branch)->id,
                'labor_cost' => 50000,
                'description' => 'Cambio de filtro',
                'products' => [['id' => $product->id, 'quantity' => 2]],
            ])
            ->assertRedirect(route('service-orders.show', $order));

        expect($product->fresh()->stock)->toBe(3)->and(batchStock($product))->toBe(3);

        $job = WorkshopJob::firstOrFail();

        $this->actingAs($employee)->withSession($session)
            ->delete(route('jobs.destroy', $job))
            ->assertRedirect(route('service-orders.show', $order));

        expect($product->fresh()->stock)->toBe(5)
            ->and(batchStock($product))->toBe(5)
            ->and((int) InventoryMovement::where('reference', "Eliminacion Trabajo #{$job->id}")->sum('quantity'))->toBe(2);
    });

    it('blocks deleting jobs from invoiced orders', function () {
        [$employee, $branch] = branchUser();
        $order = branchOrder($branch);
        $job = app(WorkshopJobService::class)->addToOrder($order, [
            'job_type_id' => demoJobType()->id,
            'mechanic_id' => branchMechanic($branch)->id,
            'labor_cost' => 30000,
            'description' => 'Revision',
        ]);
        $job->update(['status' => 'completed', 'completed_at' => now()]);
        app(InvoiceService::class)->generateFor($order);

        $this->actingAs($employee)->withSession(['current_branch_id' => $branch->id])
            ->delete(route('jobs.destroy', $job))
            ->assertSessionHas('error', 'No se puede eliminar un trabajo de una orden ya facturada.');

        expect(WorkshopJob::find($job->id))->not->toBeNull();
    });

    it('moves batches with their cost when a transfer is completed', function () {
        [$admin, $origin] = branchUser(admin: true);
        $destination = Branch::create(['name' => 'Sede Destino', 'is_active' => true]);
        $product = stockedProduct($origin, 10, cost: 12000, price: 18000);
        $session = ['current_branch_id' => $origin->id];

        $transfer = BranchTransfer::create([
            'from_branch_id' => $origin->id,
            'to_branch_id' => $destination->id,
            'product_id' => $product->id,
            'quantity' => 4,
            'status' => BranchTransfer::STATUS_IN_TRANSIT,
        ]);

        $this->actingAs($admin)->withSession($session)
            ->patch(route('branch-transfers.update-status', $transfer), ['status' => 'completed'])
            ->assertRedirect(route('branch-transfers.index'));

        $copy = Product::where('branch_id', $destination->id)->firstOrFail();

        expect($product->fresh()->stock)->toBe(6)
            ->and(batchStock($product))->toBe(6)
            ->and($copy->stock)->toBe(4)
            ->and(batchStock($copy))->toBe(4)
            ->and((float) Batch::where('product_id', $copy->id)->value('cost_price'))->toBe(12000.0);

        $this->actingAs($admin)->withSession($session)
            ->patch(route('branch-transfers.update-status', $transfer), ['status' => 'completed']);

        expect($product->fresh()->stock)->toBe(6)->and($copy->fresh()->stock)->toBe(4);
    });

    it('reports and reconciles products whose stock has no batches', function () {
        $branch = Branch::create(['name' => 'Sede Legacy', 'is_active' => true]);
        $legacy = Product::create([
            'name' => 'Producto antiguo',
            'category' => 'Repuestos',
            'branch_id' => $branch->id,
            'purchase_price' => 5000,
            'sale_price' => 9000,
            'stock' => 7,
            'min_stock' => 1,
        ]);

        $this->artisan('inventory:check')->assertExitCode(1);
        $this->artisan('inventory:check', ['--fix' => true])->assertExitCode(0);

        expect(batchStock($legacy))->toBe(7);

        $this->artisan('inventory:check')->assertExitCode(0);
    });
});

describe('ventas y facturacion', function () {
    it('does not return stock twice when a sale is cancelled twice', function () {
        [$admin, $branch] = branchUser(admin: true);
        $product = stockedProduct($branch, 5);
        $session = ['current_branch_id' => $branch->id];

        $this->actingAs($admin)->withSession($session)
            ->post(route('sales.store'), ['payment_method' => 'Nequi', 'products' => [['id' => $product->id, 'quantity' => 2]]])
            ->assertRedirect(route('sales.index'));

        $sale = Sale::firstOrFail();

        $this->actingAs($admin)->withSession($session)->post(route('sales.cancel', $sale))->assertRedirect(route('sales.index'));
        $this->actingAs($admin)->withSession($session)->post(route('sales.cancel', $sale))->assertSessionHas('error');

        expect($sale->fresh()->status)->toBe('anulada')
            ->and($product->fresh()->stock)->toBe(5)
            ->and(batchStock($product))->toBe(5);
    });

    it('shows a message instead of an error when stock runs out', function () {
        [$employee, $branch] = branchUser();
        $product = stockedProduct($branch, 1);

        $this->actingAs($employee)->withSession(['current_branch_id' => $branch->id])
            ->post(route('sales.store'), ['payment_method' => 'Efectivo', 'products' => [['id' => $product->id, 'quantity' => 3]]])
            ->assertSessionHasErrors('products');

        expect(Sale::count())->toBe(0)->and($product->fresh()->stock)->toBe(1);
    });

    it('refuses to invoice orders with unfinished jobs and numbers invoices consecutively', function () {
        [$employee, $branch] = branchUser();
        $product = stockedProduct($branch, 3, price: 30000);
        $order = branchOrder($branch);
        $session = ['current_branch_id' => $branch->id];

        $this->actingAs($employee)->withSession($session)
            ->post(route('service-orders.jobs.store', $order), [
                'job_type_id' => demoJobType()->id,
                'mechanic_id' => branchMechanic($branch)->id,
                'labor_cost' => 40000,
                'description' => 'Cambio de pastillas',
                'products' => [['id' => $product->id, 'quantity' => 1]],
            ])
            ->assertRedirect(route('service-orders.show', $order));

        $this->from(route('service-orders.show', $order))
            ->actingAs($employee)->withSession($session)
            ->post(route('invoices.generate', $order))
            ->assertRedirect(route('service-orders.show', $order))
            ->assertSessionHas('error', 'Completa todos los trabajos antes de generar la factura.');

        $job = WorkshopJob::firstOrFail();
        $this->actingAs($employee)->withSession($session)->post(route('jobs.complete', $job));

        $this->actingAs($employee)->withSession($session)
            ->post(route('invoices.generate', $order))
            ->assertRedirect();

        $invoice = Invoice::firstOrFail();

        expect($invoice->invoice_number)->toBe(sprintf('FAC-%06d', $invoice->id))
            ->and((float) $invoice->amount)->toBe(70000.0)
            ->and($order->fresh()->status)->toBe('completed');
    });

    it('charges the fixed amount as labor for fixed job types', function () {
        [$employee, $branch] = branchUser();
        $order = branchOrder($branch);
        $fixedType = demoJobType([
            'calculation_type' => 'fixed',
            'mechanic_percentage' => null,
            'workshop_percentage' => null,
            'fixed_mechanic_amount' => 8000,
            'fixed_workshop_amount' => 7000,
        ]);

        $this->actingAs($employee)->withSession(['current_branch_id' => $branch->id])
            ->post(route('service-orders.jobs.store', $order), [
                'job_type_id' => $fixedType->id,
                'mechanic_id' => branchMechanic($branch)->id,
                'labor_cost' => 99999,
                'description' => 'Cambio de aceite',
            ])
            ->assertRedirect(route('service-orders.show', $order));

        $job = WorkshopJob::firstOrFail();

        expect((float) $job->labor_cost)->toBe(15000.0)
            ->and((float) $job->total_amount)->toBe(15000.0)
            ->and((float) $job->mechanic_cost)->toBe(8000.0);
    });

    it('creates standalone jobs inside the active branch', function () {
        [$employee, $branch] = branchUser();
        $session = ['current_branch_id' => $branch->id];

        $this->actingAs($employee)->withSession($session)
            ->post(route('jobs.store_individual'), [
                'job_type_id' => demoJobType()->id,
                'mechanic_id' => branchMechanic($branch)->id,
                'labor_cost' => 30000,
                'description' => 'Revision rapida',
                'vehicle_info' => 'Honda CB 190R - XYZ98A',
                'customer_name' => 'Cliente de paso',
            ])
            ->assertRedirect(route('jobs.index'));

        $order = ServiceOrder::firstOrFail();

        expect($order->branch_id)->toBe($branch->id);

        $this->actingAs($employee)->withSession($session)
            ->get(route('service-orders.show', $order))
            ->assertOk();
    });
});

describe('permisos y sucursales', function () {
    it('hides records that belong to another branch', function () {
        [$admin, $branchA] = branchUser(admin: true);
        $branchB = Branch::create(['name' => 'Sede B', 'is_active' => true]);

        $saleB = Sale::create([
            'customer_name' => 'Cliente B',
            'total_amount' => 100000,
            'branch_id' => $branchB->id,
            'sale_date' => now(),
            'payment_method' => 'Efectivo',
            'status' => 'completada',
        ]);
        $orderB = branchOrder($branchB, 'completed');
        $invoiceB = Invoice::create([
            'invoice_number' => 'FAC-999999',
            'service_order_id' => $orderB->id,
            'amount' => 500000,
            'invoice_date' => now(),
        ]);
        $productB = stockedProduct($branchB, 2);
        $session = ['current_branch_id' => $branchA->id];

        $this->actingAs($admin)->withSession($session)->get(route('sales.show', $saleB))->assertNotFound();
        $this->actingAs($admin)->withSession($session)->post(route('sales.cancel', $saleB))->assertNotFound();
        $this->actingAs($admin)->withSession($session)->get(route('invoices.show', $invoiceB))->assertNotFound();
        $this->actingAs($admin)->withSession($session)->post(route('invoices.cancel', $invoiceB))->assertNotFound();
        $this->actingAs($admin)->withSession($session)->get(route('products.edit', $productB))->assertNotFound();
        $this->actingAs($admin)->withSession($session)->get(route('service-orders.show', $orderB))->assertNotFound();

        expect($saleB->fresh()->status)->toBe('completada')
            ->and(Invoice::find($invoiceB->id))->not->toBeNull();
    });

    it('restricts money and stock corrections to administrators', function () {
        [$employee, $branch] = branchUser();
        $sale = Sale::create([
            'customer_name' => 'Cliente',
            'total_amount' => 50000,
            'branch_id' => $branch->id,
            'sale_date' => now(),
            'payment_method' => 'Efectivo',
            'status' => 'completada',
        ]);
        $order = branchOrder($branch, 'completed');
        $invoice = Invoice::create([
            'invoice_number' => 'FAC-000100',
            'service_order_id' => $order->id,
            'amount' => 80000,
            'invoice_date' => now(),
        ]);
        $product = stockedProduct($branch, 2);
        $batch = Batch::where('product_id', $product->id)->firstOrFail();

        $this->actingAs($employee)->post(route('sales.cancel', $sale))->assertForbidden();
        $this->actingAs($employee)->post(route('invoices.cancel', $invoice))->assertForbidden();
        $this->actingAs($employee)->get(route('inventory.adjustment'))->assertForbidden();
        $this->actingAs($employee)->patch(route('batches.update', $batch), [])->assertForbidden();
        $this->actingAs($employee)->delete(route('products.destroy', $product))->assertForbidden();
        $this->actingAs($employee)->get(route('users.index'))->assertForbidden();

        expect($sale->fresh()->status)->toBe('completada')
            ->and(Invoice::find($invoice->id))->not->toBeNull();
    });

    it('rejects products from another branch when adding them to a job', function () {
        [$employee, $branch] = branchUser();
        $otherBranch = Branch::create(['name' => 'Otra sede', 'is_active' => true]);
        $foreignProduct = stockedProduct($otherBranch, 5);
        $order = branchOrder($branch);

        $this->actingAs($employee)->withSession(['current_branch_id' => $branch->id])
            ->post(route('service-orders.jobs.store', $order), [
                'job_type_id' => demoJobType()->id,
                'mechanic_id' => branchMechanic($branch)->id,
                'labor_cost' => 20000,
                'description' => 'Intento con repuesto ajeno',
                'products' => [['id' => $foreignProduct->id, 'quantity' => 1]],
            ])
            ->assertSessionHasErrors('products.0.id');

        expect($foreignProduct->fresh()->stock)->toBe(5);
    });

    it('drops the session branch when the user loses access to it', function () {
        [$employee, $branchA] = branchUser();
        $branchB = Branch::create(['name' => 'Sede B', 'is_active' => true]);
        $employee->branches()->attach($branchB);

        $this->actingAs($employee)->withSession(['current_branch_id' => $branchB->id])
            ->get(route('dashboard'))
            ->assertOk();

        $employee->branches()->detach($branchB);

        $this->actingAs($employee)->withSession(['current_branch_id' => $branchB->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSessionHas('current_branch_id', $branchA->id);
    });
});

describe('usuarios', function () {
    it('lets administrators create employees assigned to a branch', function () {
        [$admin, $branch] = branchUser(admin: true);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Cajera Nueva',
                'email' => 'cajera@taller.test',
                'password' => 'Segura2026!',
                'password_confirmation' => 'Segura2026!',
                'is_admin' => '0',
                'branch_ids' => [$branch->id],
            ])
            ->assertRedirect(route('users.index'));

        $cashier = User::where('email', 'cajera@taller.test')->firstOrFail();

        expect($cashier->is_admin)->toBeFalse()
            ->and($cashier->is_active)->toBeTrue()
            ->and($cashier->branches->pluck('id')->all())->toBe([$branch->id]);

        $this->actingAs($admin)->patch(route('users.toggle-active', $cashier))->assertRedirect(route('users.index'));

        expect($cashier->fresh()->is_active)->toBeFalse();
    });

    it('requires a branch for employees', function () {
        [$admin] = branchUser(admin: true);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Sin sede',
                'email' => 'sinsede@taller.test',
                'password' => 'Segura2026!',
                'password_confirmation' => 'Segura2026!',
                'is_admin' => '0',
            ])
            ->assertSessionHasErrors('branch_ids');

        expect(User::where('email', 'sinsede@taller.test')->exists())->toBeFalse();
    });

    it('does not let deactivated users log in', function () {
        $user = User::factory()->create();
        $user->forceFill(['is_active' => false])->save();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    });

    it('logs out a user deactivated during their session', function () {
        [$employee] = branchUser();
        $employee->forceFill(['is_active' => false])->save();

        $this->actingAs($employee)->get(route('dashboard'))->assertRedirect(route('login'));

        $this->assertGuest();
    });

    it('lets users change their own password', function () {
        [$employee] = branchUser();

        $this->actingAs($employee)
            ->put(route('profile.password'), [
                'current_password' => 'password',
                'password' => 'NuevaClave2026!',
                'password_confirmation' => 'NuevaClave2026!',
            ])
            ->assertSessionHasNoErrors();

        expect(Hash::check('NuevaClave2026!', $employee->fresh()->password))->toBeTrue();
    });
});

it('renders the main screens for employees and administrators', function () {
    [$admin, $branch] = branchUser(admin: true);
    [$employee] = branchUser(branch: $branch);
    $product = stockedProduct($branch, 4);
    $order = branchOrder($branch);

    $job = app(WorkshopJobService::class)->addToOrder($order, [
        'job_type_id' => demoJobType()->id,
        'mechanic_id' => branchMechanic($branch)->id,
        'labor_cost' => 45000,
        'description' => 'Mantenimiento',
    ], [['id' => $product->id, 'quantity' => 1]]);
    $job->update(['status' => 'completed', 'completed_at' => now()]);
    $invoice = app(InvoiceService::class)->generateFor($order);
    $sale = app(SaleService::class)->create($branch->id, $employee->id, null, 'Nequi', [['id' => $product->id, 'quantity' => 1]])['sale'];

    $session = ['current_branch_id' => $branch->id];

    $employeePages = [
        route('dashboard'), route('reports'), route('profile.edit'),
        route('products.index'), route('products.create'), route('products.edit', $product),
        route('service-orders.index'), route('service-orders.create'), route('service-orders.show', $order),
        route('jobs.index'), route('sales.index'), route('sales.create'), route('sales.show', $sale),
        route('invoices.index'), route('invoices.show', $invoice),
        route('inventory.index'), route('inventory.purchase'),
        route('mechanics.index'), route('mechanics.create'), route('suppliers.index'),
    ];

    foreach ($employeePages as $url) {
        $this->actingAs($employee)->withSession($session)->get($url)->assertOk();
    }

    $adminPages = [
        route('users.index'), route('users.create'), route('users.edit', $employee),
        route('branches.index'), route('branch-transfers.index'), route('branch-transfers.create'),
        route('job-types.index'), route('settings'), route('inventory.adjustment'),
    ];

    foreach ($adminPages as $url) {
        $this->actingAs($admin)->withSession($session)->get($url)->assertOk();
    }
});
