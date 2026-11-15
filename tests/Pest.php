<?php

use App\Models\Batch;
use App\Models\Branch;
use App\Models\JobType;
use App\Models\Mechanic;
use App\Models\Product;
use App\Models\ServiceOrder;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Crea un usuario asignado a una sucursal (se crea una si no se indica).
 *
 * @return array{0: \App\Models\User, 1: \App\Models\Branch}
 */
function branchUser(bool $admin = false, ?\App\Models\Branch $branch = null): array
{
    $branch ??= \App\Models\Branch::create(['name' => 'Sede Test ' . uniqid(), 'is_active' => true]);

    $user = \App\Models\User::factory()->create();
    $user->forceFill(['is_admin' => $admin, 'is_active' => true])->save();
    $user->branches()->attach($branch);

    return [$user, $branch];
}

function stockedProduct(Branch $branch, int $quantity, float $cost = 10000, float $price = 20000): Product
{
    $product = Product::create([
        'name' => 'Repuesto ' . uniqid(),
        'category' => 'Repuestos',
        'branch_id' => $branch->id,
        'purchase_price' => $cost,
        'sale_price' => $price,
        'stock' => 0,
        'min_stock' => 0,
    ]);

    app(InventoryService::class)->registerPurchaseBatch([
        'product_id' => $product->id,
        'cost_price' => $cost,
        'sale_price' => $price,
        'quantity' => $quantity,
    ]);

    return $product->fresh();
}

function batchStock(Product $product): int
{
    return (int) Batch::where('product_id', $product->id)->sum('remaining_stock');
}

function demoJobType(array $overrides = []): JobType
{
    return JobType::create(array_merge([
        'name' => 'Mantenimiento ' . uniqid(),
        'description' => 'Mantenimiento preventivo',
        'calculation_type' => 'percentage',
        'mechanic_percentage' => 60,
        'workshop_percentage' => 40,
        'allow_products' => true,
        'is_active' => true,
    ], $overrides));
}

function branchMechanic(Branch $branch): Mechanic
{
    return Mechanic::create([
        'name' => 'Mecanico ' . substr(uniqid(), -5),
        'phone' => '3001234567',
        'hire_date' => now()->subYear()->toDateString(),
        'branch_id' => $branch->id,
        'is_active' => true,
    ]);
}

function branchOrder(Branch $branch, string $status = 'pending'): ServiceOrder
{
    return ServiceOrder::create([
        'customer_name' => 'Cliente Demo',
        'customer_phone' => '3009998877',
        'vehicle_info' => 'Yamaha FZ 2.0 - ABC12D',
        'service_description' => 'Revision general',
        'branch_id' => $branch->id,
        'status' => $status,
    ]);
}

/** Orden en progreso con un trabajo completado (sin repuestos), lista para facturar. */
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
