<?php

use App\Models\Batch;
use App\Models\CashDay;
use App\Models\CashMovement;
use App\Models\Mechanic;
use App\Models\Sale;
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

    it('keeps the cash outflow in step when a purchase batch paid from the drawer is corrected', function () {
        [$admin, $branch] = branchUser(admin: true);
        $product = stockedProduct($branch, 1);
        $supplier = Supplier::create(['name' => 'Repuestos del Huila', 'phone' => '3001112233', 'active' => true]);
        $product->suppliers()->attach($supplier);
        $session = cashSession($branch);

        $this->actingAs($admin)->withSession($session)->post(route('inventory.store-purchase'), [
            'product_id' => $product->id, 'supplier_id' => $supplier->id, 'quantity' => 3, 'unit_price' => 15000, 'cash_payment' => 'Efectivo',
        ]);
        $movement = CashMovement::sole();
        $batch = Batch::findOrFail($movement->batch_id);
        $correct = fn (array $data) => $this->actingAs($admin)->withSession($session)->patch(route('batches.update', $batch), $data + [
            'supplier_id' => $supplier->id, 'sale_price' => 25000, 'notes' => 'Se digitó mal la factura del proveedor',
        ]);

        $correct(['cost_price' => 12000, 'quantity' => 4])->assertSessionHas('success');
        expect((float) $movement->fresh()->amount)->toBe(48000.0)
            ->and($movement->fresh()->concept)->toContain('× 4');

        // Con la caja de ese día cerrada no se cambia el valor: hay que reabrirla.
        app(CashService::class)->close($branch->id, CarbonImmutable::today(), 0, 0, null, $admin->id);
        $correct(['cost_price' => 10000])->assertSessionHas('error');
        expect((float) $batch->fresh()->cost_price)->toBe(12000.0)
            ->and((float) $movement->fresh()->amount)->toBe(48000.0);

        // Corregir solo el precio de venta no toca la caja: se permite.
        $correct(['cost_price' => 12000])->assertSessionHas('success');
    });

    it('refuses cash sales and receipts once today’s cash is closed, but accepts other methods', function () {
        [$admin, $branch] = branchUser(admin: true);
        $product = stockedProduct($branch, 10, cost: 10000, price: 20000);
        $session = cashSession($branch);
        $sell = fn (string $method) => $this->actingAs($admin)->withSession($session)
            ->post(route('sales.store'), ['payment_method' => $method, 'products' => [['id' => $product->id, 'quantity' => 1]]]);

        // Caja abierta: se vende en efectivo sin problema.
        $sell('Efectivo')->assertSessionHas('success');
        $this->actingAs($admin)->withSession($session)->get(route('sales.create'))->assertDontSee('La caja de hoy ya se cerró');

        app(CashService::class)->close($branch->id, CarbonImmutable::today(), 20000, 0, null, $admin->id);

        // Con la caja cerrada, el aviso sale apenas se abre la pantalla.
        $this->actingAs($admin)->withSession($session)->get(route('sales.create'))->assertSee('La caja de hoy ya se cerró');

        $sell('Nequi')->assertSessionHas('success');
        $sell('Efectivo')->assertSessionHasErrors(['payment_method' => CashService::CLOSED_TODAY_MESSAGE]);
        expect(Sale::count())->toBe(2)->and($product->fresh()->stock)->toBe(8);

        $order = cashOrder($branch, 30000);
        $this->actingAs($admin)->withSession($session)->get(route('service-orders.show', $order))->assertSee('La caja de hoy ya se cerró');
        $this->actingAs($admin)->withSession($session)
            ->post(route('invoices.generate', $order), ['payment_method' => 'Efectivo'])
            ->assertSessionHas('error', CashService::CLOSED_TODAY_MESSAGE);
        expect($order->invoices()->count())->toBe(0);

        $this->actingAs($admin)->withSession($session)
            ->post(route('invoices.generate', $order), ['payment_method' => 'Nequi'])
            ->assertSessionHas('success');
    });

    it('warns when a day starts with a base different from what was left at the previous close', function () {
        [$admin, $branch] = branchUser(admin: true);
        $session = cashSession($branch);
        $cash = app(CashService::class);
        $monday = CarbonImmutable::today()->subDays(2);
        $tuesday = CarbonImmutable::today()->subDay();
        $page = fn (CarbonImmutable $day) => $this->actingAs($admin)->withSession($session)->get(route('cash.index', ['date' => $day->toDateString()]));

        $cash->close($branch->id, $monday, 100000, 0, null, $admin->id);
        $cash->addMovement($branch->id, $tuesday, ['type' => 'expense', 'concept' => 'Domicilio', 'amount' => 5000, 'payment_method' => 'Efectivo'], $admin->id);

        // Flujo normal: el martes arrancó con lo que quedó el lunes, sin aviso.
        expect($cash->openingMismatches($branch->id, $tuesday))->toBeEmpty();
        $page($tuesday)->assertDontSee('como base de');

        // Se reabre el lunes y se vuelve a cerrar con otra cifra: la base del martes no cambia sola.
        $cash->reopen(CashDay::whereDate('date', $monday->toDateString())->sole());
        $cash->close($branch->id, $monday, 90000, 0, null, $admin->id);
        expect((float) CashDay::whereDate('date', $tuesday->toDateString())->value('opening_cash'))->toBe(100000.0);

        // Se avisa en los dos días, con botón para usar la cifra nueva.
        $page($monday)->assertSee('Usar $90.000 como base del ' . $tuesday->translatedFormat('l'));
        $page($tuesday)->assertSee('Usar $90.000 como base del ' . $tuesday->translatedFormat('l'))->assertSee('quedaron');

        $this->actingAs($admin)->withSession($session)
            ->put(route('cash.opening'), ['date' => $tuesday->toDateString(), 'opening_cash' => 90000])
            ->assertSessionHas('success');
        expect($cash->openingMismatches($branch->id, $tuesday))->toBeEmpty()
            ->and($cash->openingMismatches($branch->id, $monday))->toBeEmpty();

        // Si el día siguiente ya está cerrado, no se ofrece el botón: hay que reabrirlo.
        $cash->close($branch->id, $tuesday, 85000, 0, null, $admin->id);
        $cash->reopen(CashDay::whereDate('date', $monday->toDateString())->sole());
        $cash->close($branch->id, $monday, 70000, 0, null, $admin->id);
        $page($monday)->assertSee('ya está cerrado: para corregir su base hay que reabrirlo')->assertDontSee('como base de');
    });

    it('asks whether the money was refunded when cancelling a cash sale from an earlier day', function () {
        [$admin, $branch] = branchUser(admin: true);
        $product = stockedProduct($branch, 10, cost: 10000, price: 20000);
        $session = cashSession($branch);

        $this->travelTo(now()->subDays(2));
        $refunded = app(SaleService::class)->create($branch->id, $admin->id, null, 'Efectivo', [['id' => $product->id, 'quantity' => 1]])['sale'];
        $typo = app(SaleService::class)->create($branch->id, $admin->id, null, 'Efectivo', [['id' => $product->id, 'quantity' => 2]])['sale'];
        app(CashService::class)->close($branch->id, CarbonImmutable::today(), 60000, 0, null, $admin->id);
        $this->travelBack();
        $todaySale = app(SaleService::class)->create($branch->id, $admin->id, null, 'Efectivo', [['id' => $product->id, 'quantity' => 1]])['sale'];

        // El botón de anular pregunta solo en efectivo de un día anterior.
        $this->actingAs($admin)->withSession($session)->get(route('sales.show', $refunded))->assertSee('data-confirm-choice="refunded"', false);
        $this->actingAs($admin)->withSession($session)->get(route('sales.show', $todaySale))->assertDontSee('data-confirm-choice', false);

        $cancel = fn (Sale $sale, array $data = []) => $this->actingAs($admin)->withSession($session)->post(route('sales.cancel', $sale), $data);

        // Sin respuesta no se anula.
        $cancel($refunded)->assertSessionHasErrors('refunded');
        expect($refunded->fresh()->isCancelled())->toBeFalse();

        $cancel($refunded, ['refunded' => '1'])->assertSessionHas('success', fn ($text) => str_contains($text, 'La devolución de $20.000 salió de la caja de hoy'));
        $movement = CashMovement::sole();
        expect($movement->type)->toBe(CashMovement::REFUND)
            ->and($movement->sale_id)->toBe($refunded->id)
            ->and((float) $movement->amount)->toBe(20000.0)
            ->and($movement->cashDay->date->isToday())->toBeTrue();

        // Error de digitación: anula sin tocar la caja.
        $cancel($typo, ['refunded' => '0'])->assertSessionHas('success');
        // Venta de hoy: deja de contar sola, no pregunta ni crea salida.
        $cancel($todaySale)->assertSessionHas('success');
        expect(CashMovement::count())->toBe(1);

        // Base 60.000 (lo que quedó al cerrar) − devolución 20.000; la venta de hoy ya no suma.
        expect(app(CashService::class)->summary($branch->id, CarbonImmutable::today())['cash']['expected'])->toBe(40000.0);

        $this->actingAs($admin)->withSession($session)->get(route('cash.index'))
            ->assertOk()->assertSee('Devoluciones a clientes')->assertSee('Venta ' . $refunded->displayNumber() . ' anulada');
    });

    it('records the refund of a cancelled receipt and refuses it once today’s cash is closed', function () {
        [$admin, $branch] = branchUser(admin: true);
        $session = cashSession($branch);

        $this->travelTo(now()->subDay());
        $first = app(InvoiceService::class)->generateFor(cashOrder($branch, 30000), 'Efectivo');
        $second = app(InvoiceService::class)->generateFor(cashOrder($branch, 45000), 'Efectivo');
        $this->travelBack();

        $this->actingAs($admin)->withSession($session)->get(route('invoices.show', $first))->assertSee('data-confirm-choice="refunded"', false);

        $this->actingAs($admin)->withSession($session)->post(route('invoices.cancel', $first), ['refunded' => '1'])->assertSessionHas('success');
        expect(CashMovement::sole())->invoice_id->toBe($first->id)->type->toBe(CashMovement::REFUND);

        app(CashService::class)->close($branch->id, CarbonImmutable::today(), 0, 0, null, $admin->id);

        // Con la caja de hoy cerrada la devolución no puede salir de ahí: no se anula nada.
        $this->actingAs($admin)->withSession($session)->post(route('invoices.cancel', $second), ['refunded' => '1'])
            ->assertSessionHas('error', fn ($text) => str_contains($text, 'Reábrela, o anula sin devolver plata'));
        expect($second->fresh()->isCancelled())->toBeFalse()->and(CashMovement::count())->toBe(1);

        $this->actingAs($admin)->withSession($session)->post(route('invoices.cancel', $second), ['refunded' => '0'])->assertSessionHas('success');
        expect($second->fresh()->isCancelled())->toBeTrue();
    });
});

describe('revisión externa: caja', function () {
    it('warns about a day with cash sales that nobody opened in the cash register', function () {
        [$admin, $branch] = branchUser(admin: true);
        $product = stockedProduct($branch, 10, cost: 10000, price: 20000);
        $cash = app(CashService::class);
        $monday = CarbonImmutable::today()->subDays(2);

        $cash->close($branch->id, $monday, 20000, 0, null, $admin->id);
        // Martes: dos ventas en efectivo y nadie abrió la Caja.
        $this->travelTo(now()->subDay());
        app(SaleService::class)->create($branch->id, $admin->id, null, 'Efectivo', [['id' => $product->id, 'quantity' => 2]]);
        $this->travelBack();

        expect($cash->unclosedDaysBefore($branch->id, CarbonImmutable::today())->map->toDateString()->all())
            ->toBe([CarbonImmutable::today()->subDay()->toDateString()]);
        $this->actingAs($admin)->withSession(cashSession($branch))->get(route('cash.index'))
            ->assertOk()->assertSee('Quedó un día sin cerrar');

        // Antes del martes no queda nada: el lunes se cerró.
        expect($cash->unclosedDaysBefore($branch->id, CarbonImmutable::today()->subDay()))->toBeEmpty();
    });

    it('keeps a refunded sale in the cash of the day it was paid', function () {
        [$admin, $branch] = branchUser(admin: true);
        $product = stockedProduct($branch, 10, cost: 10000, price: 20000);
        $cash = app(CashService::class);
        $yesterday = CarbonImmutable::today()->subDay();

        $this->travelTo(now()->subDay());
        $sale = app(SaleService::class)->create($branch->id, $admin->id, null, 'Efectivo', [['id' => $product->id, 'quantity' => 1]])['sale'];
        $cash->close($branch->id, CarbonImmutable::today(), 20000, 0, null, $admin->id);
        $this->travelBack();

        $this->actingAs($admin)->withSession(cashSession($branch))
            ->post(route('sales.cancel', $sale), ['refunded' => '1'])->assertSessionHas('success');

        // Ayer entró la plata y hoy salió: ayer sigue cuadrando, sin aviso de «cambió después del cierre».
        expect($cash->summary($branch->id, $yesterday)['cash']['expected'])->toBe(20000.0)
            ->and($cash->summary($branch->id, CarbonImmutable::today())['cash']['expected'])->toBe(0.0);
        $this->actingAs($admin)->withSession(cashSession($branch))
            ->get(route('cash.index', ['date' => $yesterday->toDateString()]))
            ->assertOk()->assertDontSee('Después del cierre cambió');
    });

    it('lets the cash register pay an inactive mechanic who is still owed', function () {
        [$admin, $branch] = branchUser(admin: true);
        // Las comisiones por pagar cuentan desde el primer día de caja.
        app(CashService::class)->setOpening($branch->id, CarbonImmutable::today(), 0);
        $mechanic = branchMechanic($branch);
        $order = cashOrder($branch, 50000, $mechanic);
        app(InvoiceService::class)->generateFor($order, 'Nequi');
        $mechanic->update(['is_active' => false]);

        $this->actingAs($admin)->withSession(cashSession($branch))->get(route('cash.index'))
            ->assertOk()
            ->assertSee('cash-pay-mechanic', false)
            ->assertViewHas('mechanics', fn ($mechanics) => $mechanics->contains('id', $mechanic->id));
    });
});
