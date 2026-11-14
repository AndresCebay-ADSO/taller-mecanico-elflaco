<?php

use App\Models\Batch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;

function productForm(Product $product, Supplier $supplier, array $overrides = []): array
{
    return $overrides + [
        'name' => $product->name,
        'category' => $product->category,
        'supplier_ids' => [$supplier->id],
        'purchase_price' => (int) $product->purchase_price,
        'sale_price' => (int) $product->sale_price,
        'min_stock' => $product->min_stock,
    ];
}

it('charges the new sale price after it is changed on the product', function () {
    [$admin, $branch] = branchUser(admin: true);
    $product = stockedProduct($branch, 5, cost: 10000, price: 20000);
    $supplier = Supplier::create(['name' => 'Proveedor', 'phone' => '3001112233', 'active' => true]);
    $session = ['current_branch_id' => $branch->id];

    $this->actingAs($admin)->withSession($session)
        ->put(route('products.update', $product), productForm($product, $supplier, ['sale_price' => 25000]))
        ->assertRedirect(route('products.index'));

    $this->actingAs($admin)->withSession($session)
        ->post(route('sales.store'), ['payment_method' => 'Efectivo', 'products' => [['id' => $product->id, 'quantity' => 2]]]);

    expect((float) Sale::sole()->total_amount)->toBe(50000.0)
        // El costo de cada lote no cambia: sigue sirviendo para la utilidad.
        ->and((float) Batch::where('product_id', $product->id)->value('cost_price'))->toBe(10000.0);
});

it('leaves batch prices alone when the sale price is not changed', function () {
    [$admin, $branch] = branchUser(admin: true);
    $product = stockedProduct($branch, 5, price: 20000);
    $supplier = Supplier::create(['name' => 'Proveedor', 'phone' => '3001112233', 'active' => true]);
    Batch::where('product_id', $product->id)->update(['sale_price' => 22000]);

    $this->actingAs($admin)->withSession(['current_branch_id' => $branch->id])
        ->put(route('products.update', $product), productForm($product, $supplier, ['name' => 'Nombre corregido']));

    expect((float) Batch::where('product_id', $product->id)->value('sale_price'))->toBe(22000.0);
});

it('does not deactivate a product that still has units in stock', function () {
    [$admin, $branch] = branchUser(admin: true);
    $product = stockedProduct($branch, 3);
    $session = ['current_branch_id' => $branch->id];

    $this->actingAs($admin)->withSession($session)
        ->delete(route('products.destroy', $product))
        ->assertSessionHas('error');

    expect($product->fresh()->trashed())->toBeFalse();

    $this->actingAs($admin)->withSession($session)
        ->post(route('inventory.store-adjustment'), ['product_id' => $product->id, 'quantity' => -3, 'reason' => 'loss', 'notes' => 'Se perdieron']);

    $this->actingAs($admin)->withSession($session)
        ->delete(route('products.destroy', $product))
        ->assertSessionHas('info');

    expect($product->fresh()->trashed())->toBeTrue();
});

it('only corrects purchase batches', function () {
    [$admin, $branch] = branchUser(admin: true);
    $product = stockedProduct($branch, 2);
    $supplier = Supplier::create(['name' => 'Proveedor', 'phone' => '3001112233', 'active' => true]);
    $session = ['current_branch_id' => $branch->id];

    $this->actingAs($admin)->withSession($session)
        ->post(route('inventory.store-adjustment'), ['product_id' => $product->id, 'quantity' => 4, 'reason' => 'adjustment']);
    $adjustmentBatch = Batch::where('product_id', $product->id)->latest('id')->first();

    $this->actingAs($admin)->withSession($session)
        ->patch(route('batches.update', $adjustmentBatch), [
            'supplier_id' => $supplier->id, 'cost_price' => 1, 'sale_price' => 1, 'quantity' => 400, 'notes' => 'Intento de inflar el stock',
        ])
        ->assertNotFound();

    expect($product->fresh()->stock)->toBe(6);
});
