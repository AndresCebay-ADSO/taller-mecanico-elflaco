<?php

use App\Models\Batch;
use App\Models\CashDay;
use App\Models\CashMovement;
use App\Models\Mechanic;
use App\Models\ServiceOrder;
use App\Models\Supplier;
use App\Services\CashService;
use App\Services\InvoiceService;
use App\Services\SaleService;
use Carbon\CarbonImmutable;

/** Orden con un trabajo completado (mano de obra $labor, comisión 60 %) lista para facturar. */
function cashOrder($branch, float $labor = 50000, ?Mechanic $mechanic = null): ServiceOrder
{
    $order = ServiceOrder::create([
        'customer_name' => 'Cliente Caja',
        'vehicle_info' => 'Suzuki GN 125',
        'service_description' => 'Revisión',
        'status' => 'in_progress',
        'branch_id' => $branch->id,
    ]);

    $job = app(\App\Services\WorkshopJobService::class)->addToOrder($order, [
        'job_type_id' => demoJobType()->id,
        'mechanic_id' => ($mechanic ?? branchMechanic($branch))->id,
        'description' => 'Revisión general',
        'labor_cost' => $labor,
    ]);
    $job->update(['status' => 'completed', 'completed_at' => now()]);

    return $order->fresh();
}

function cashSession($branch): array
{
    return ['current_branch_id' => $branch->id];
}

describe('caja diaria', function () {
    it('expects base plus cash sales and receipts minus cash outflows, keeping other methods apart', function () {
        [$admin, $branch] = branchUser(admin: true);
        $product = stockedProduct($branch, 10, cost: 10000, price: 20000);
        $mechanic = branchMechanic($branch);
        $session = cashSession($branch);

        $this->actingAs($admin)->withSession($session)->get(route('cash.index'))
            ->assertOk()->assertSee('Caja de hoy')->assertSee('Abierta')->assertSee('Se empiezan a contar');

        $this->actingAs($admin)->withSession($session)
            ->put(route('cash.opening'), ['date' => today()->toDateString(), 'opening_cash' => 50000])
            ->assertRedirect(route('cash.index'));

        app(SaleService::class)->create($branch->id, $admin->id, null, 'Efectivo', [['id' => $product->id, 'quantity' => 2]]); // 40.000
        app(SaleService::class)->create($branch->id, $admin->id, null, 'Nequi', [['id' => $product->id, 'quantity' => 1]]);    // 20.000
        app(InvoiceService::class)->generateFor(cashOrder($branch, 30000, $mechanic), 'Efectivo');                               // 30.000

        $post = fn (array $data) => $this->actingAs($admin)->withSession($session)
            ->post(route('cash.movements.store'), $data + ['date' => today()->toDateString(), 'payment_method' => 'Efectivo']);

        $post(['type' => 'expense', 'concept' => 'Almuerzo', 'amount' => 12000])->assertSessionHas('success');
        $post(['type' => 'mechanic', 'concept' => 'Pago de comisiones', 'amount' => 10000, 'mechanic_id' => $mechanic->id])->assertSessionHas('success');
        $post(['type' => 'expense', 'concept' => 'Recibo de luz', 'amount' => 25000, 'payment_method' => 'Nequi'])->assertSessionHas('success');
        $post(['type' => 'income', 'concept' => 'Vuelto de proveedor', 'amount' => 3000])->assertSessionHas('success');
        $post(['type' => 'mechanic', 'concept' => 'Sin mecánico', 'amount' => 1000])->assertSessionHasErrors('mechanic_id');

        $summary = app(CashService::class)->summary($branch->id, CarbonImmutable::today());

        // 50.000 + 40.000 + 30.000 + 3.000 − 12.000 − 10.000
        expect($summary['cash']['expected'])->toBe(101000.0)
            ->and($summary['cash']['out_by_type']['expense'])->toBe(12000.0)
            ->and($summary['cash']['out_by_type']['mechanic'])->toBe(10000.0)
            ->and($summary['other_methods']->sole())->toMatchArray(['method' => 'Nequi', 'sales' => 20000.0, 'out' => 25000.0, 'net' => -5000.0]);

        $this->actingAs($admin)->withSession($session)->get(route('cash.index'))
            ->assertOk()
            ->assertSeeInOrder(['Debe haber en el cajón', '$101.000'])
            ->assertSee('Almuerzo')
            ->assertSee('Otros medios de pago');
    });

    it('closes with the counted cash, locks the day and carries what is left as the next base', function () {
        [$admin, $branch] = branchUser(admin: true);
        $session = cashSession($branch);
        $yesterday = CarbonImmutable::yesterday();

        $this->actingAs($admin)->withSession($session)->post(route('cash.movements.store'), [
            'date' => $yesterday->toDateString(), 'type' => 'income', 'concept' => 'Base inicial', 'amount' => 80000, 'payment_method' => 'Efectivo',
        ]);

        $this->actingAs($admin)->withSession($session)
            ->post(route('cash.close'), ['date' => $yesterday->toDateString(), 'counted_cash' => 90000, 'withdrawn_cash' => 100000])
            ->assertSessionHas('error', 'No se puede retirar más de lo que hay en el cajón.');

        $this->actingAs($admin)->withSession($session)
            ->post(route('cash.close'), ['date' => $yesterday->toDateString(), 'counted_cash' => 78000, 'withdrawn_cash' => 50000, 'closing_notes' => 'Faltó una vuelta'])
            ->assertSessionHas('info', 'Caja cerrada: faltan $2.000.');

        $day = CashDay::sole();
        expect($day->isClosed())->toBeTrue()
            ->and((float) $day->expected_cash)->toBe(80000.0)
            ->and($day->difference())->toBe(-2000.0)
            ->and($day->cashLeft())->toBe(28000.0);

        // Día cerrado: no admite más movimientos.
        $this->actingAs($admin)->withSession($session)->post(route('cash.movements.store'), [
            'date' => $yesterday->toDateString(), 'type' => 'expense', 'concept' => 'Tarde', 'amount' => 1000, 'payment_method' => 'Efectivo',
        ])->assertSessionHas('error', 'La caja del ' . $yesterday->format('d/m/Y') . ' ya está cerrada.');

        // Hoy arranca con lo que quedó en el cajón.
        $this->actingAs($admin)->withSession($session)->get(route('cash.index'))
            ->assertOk()->assertSeeInOrder(['Base con la que se abrió', '$28.000']);

        $this->actingAs($admin)->withSession($session)->get(route('cash.history'))
            ->assertOk()->assertSee('Faltan $2.000');
    });

    it('warns when cash changes after closing and lets only administrators reopen or delete', function () {
        [$admin, $branch] = branchUser(admin: true);
        [$employee] = branchUser(branch: $branch);
        $product = stockedProduct($branch, 5);
        $session = cashSession($branch);
        $today = today()->toDateString();

        $this->actingAs($employee)->withSession($session)->post(route('cash.movements.store'), [
            'date' => $today, 'type' => 'expense', 'concept' => 'Domicilio', 'amount' => 5000, 'payment_method' => 'Efectivo',
        ])->assertSessionHas('success');
        $movement = CashMovement::sole();

        $this->actingAs($employee)->withSession($session)->delete(route('cash.movements.destroy', $movement))->assertForbidden();
        // Base 0 y un gasto de 5.000: debía haber −5.000 y se contó 0 → sobran 5.000.
        $this->actingAs($employee)->withSession($session)->post(route('cash.close'), ['date' => $today, 'counted_cash' => 0])
            ->assertSessionHas('info', 'Caja cerrada: sobran $5.000.');

        app(SaleService::class)->create($branch->id, $employee->id, null, 'Efectivo', [['id' => $product->id, 'quantity' => 1]]);

        $this->actingAs($employee)->withSession($session)->get(route('cash.index'))
            ->assertOk()->assertSee('Después del cierre cambió')->assertDontSee('Reabrir caja');

        $day = CashDay::sole();
        $this->actingAs($employee)->withSession($session)->post(route('cash.reopen', $day))->assertForbidden();
        $this->actingAs($admin)->withSession($session)->post(route('cash.reopen', $day))->assertRedirect();
        expect($day->fresh()->isClosed())->toBeFalse();

        $this->actingAs($admin)->withSession($session)->delete(route('cash.movements.destroy', $movement))->assertSessionHas('success');
        expect(CashMovement::count())->toBe(0);
    });

    it('does not allow future days or another branch’s movements', function () {
        [$admin, $branch] = branchUser(admin: true);
        [, $otherBranch] = branchUser();
        $session = cashSession($branch);

        $this->actingAs($admin)->withSession($session)->post(route('cash.movements.store'), [
            'date' => today()->addDay()->toDateString(), 'type' => 'expense', 'concept' => 'Mañana', 'amount' => 1000, 'payment_method' => 'Efectivo',
        ])->assertSessionHasErrors('date');

        $foreign = app(CashService::class)->addMovement($otherBranch->id, CarbonImmutable::today(), [
            'type' => 'expense', 'concept' => 'Ajeno', 'amount' => 1000, 'payment_method' => 'Efectivo',
        ], null);

        $this->actingAs($admin)->withSession($session)->delete(route('cash.movements.destroy', $foreign))->assertNotFound();
        $this->actingAs($admin)->withSession($session)->get(route('cash.index'))->assertDontSee('Ajeno');
    });

    it('tracks commissions owed since the cash register started', function () {
        [$admin, $branch] = branchUser(admin: true);
        $mechanic = branchMechanic($branch);
        $session = cashSession($branch);

        // Antes de usar la caja: no cuenta.
        expect(app(CashService::class)->mechanicBalances($branch->id))->toBeEmpty();

        $this->actingAs($admin)->withSession($session)
            ->put(route('cash.opening'), ['date' => today()->toDateString(), 'opening_cash' => 0]);

        cashOrder($branch, 50000, $mechanic); // comisión 60 % = 30.000
        app(CashService::class)->addMovement($branch->id, CarbonImmutable::today(), [
            'type' => 'mechanic', 'concept' => 'Abono', 'amount' => 20000, 'payment_method' => 'Efectivo', 'mechanic_id' => $mechanic->id,
        ], $admin->id);

        $row = app(CashService::class)->mechanicBalances($branch->id)->firstWhere('mechanic.id', $mechanic->id);
        expect($row)->toMatchArray(['earned' => 30000.0, 'paid' => 20000.0, 'balance' => 10000.0]);

        $this->actingAs($admin)->withSession($session)->get(route('cash.index'))
            ->assertOk()->assertSee('Comisiones por pagar')->assertSee($mechanic->name)->assertSee('Pagar');
    });

    it('takes parts purchases paid from the day’s money out of the cash register', function () {
        [$admin, $branch] = branchUser(admin: true);
        $product = stockedProduct($branch, 1);
        $supplier = Supplier::create(['name' => 'Repuestos del Huila', 'phone' => '3001112233', 'active' => true]);
        $product->suppliers()->attach($supplier);
        $session = cashSession($branch);
        $purchase = fn (string $payment) => $this->actingAs($admin)->withSession($session)->post(route('inventory.store-purchase'), [
            'product_id' => $product->id, 'supplier_id' => $supplier->id, 'quantity' => 3, 'unit_price' => 15000, 'cash_payment' => $payment,
        ]);

        $this->actingAs($admin)->withSession($session)->post(route('inventory.store-purchase'), [
            'product_id' => $product->id, 'supplier_id' => $supplier->id, 'quantity' => 3, 'unit_price' => 15000,
        ])->assertSessionHasErrors('cash_payment');

        $purchase('none')->assertRedirect(route('inventory.index'));
        expect(CashMovement::count())->toBe(0);

        $purchase('Efectivo')->assertSessionHas('success', 'Compra registrada, stock actualizado y descontada de la caja de hoy.');
        $movement = CashMovement::sole();
        expect($movement->type)->toBe(CashMovement::PURCHASE)
            ->and((float) $movement->amount)->toBe(45000.0)
            ->and($movement->concept)->toContain('Repuestos del Huila');

        // Caja cerrada: la compra no se registra (ni el lote ni la salida).
        app(CashService::class)->close($branch->id, CarbonImmutable::today(), 0, 0, null, $admin->id);
        $batches = Batch::count();
        $purchase('Efectivo')->assertSessionHas('error');
        expect(Batch::count())->toBe($batches)->and(CashMovement::count())->toBe(1);
    });
});
