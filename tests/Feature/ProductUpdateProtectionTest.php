<?php

use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;

it('does not allow direct stock changes from the product edit endpoint', function () {
    $user = User::factory()->create();

    $supplier = Supplier::create([
        'name' => 'Proveedor Central',
        'phone' => '3001111111',
        'email' => 'proveedor@example.com',
        'address' => 'Zona industrial',
        'active' => true,
    ]);

    $product = Product::create([
        'name' => 'Kit arrastre',
        'category' => 'Repuestos',
        'purchase_price' => 100,
        'sale_price' => 160,
        'stock' => 8,
        'min_stock' => 2,
    ]);

    $product->suppliers()->sync([$supplier->id]);

    $this->actingAs($user)
        ->put(route('products.update', $product), [
            'name' => 'Kit arrastre premium',
            'category' => 'Repuestos',
            'supplier_ids' => [$supplier->id],
            'purchase_price' => 110,
            'sale_price' => 170,
            'stock' => 999,
            'min_stock' => 3,
            'upc' => 'ABC123',
        ])
        ->assertRedirect(route('products.index'));

    $product->refresh();

    expect($product->name)->toBe('Kit arrastre premium')
        ->and($product->stock)->toBe(8)
        ->and($product->min_stock)->toBe(3);
});
