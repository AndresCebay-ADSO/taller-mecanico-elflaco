<?php

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;

it('registers positive inventory adjustments as adjustment movements', function () {
    $user = User::factory()->create();

    $product = Product::create([
        'name' => 'Bujia',
        'category' => 'Repuestos',
        'purchase_price' => 20,
        'sale_price' => 35,
        'stock' => 3,
        'min_stock' => 1,
    ]);

    $this->actingAs($user)
        ->post(route('inventory.store-adjustment'), [
            'product_id' => $product->id,
            'quantity' => 2,
            'reason' => 'adjustment',
            'notes' => 'Conteo fisico',
        ])
        ->assertRedirect(route('inventory.index'));

    $movement = InventoryMovement::latest('id')->first();

    expect($product->fresh()->stock)->toBe(5)
        ->and($movement->movement_type)->toBe('adjustment')
        ->and($movement->quantity)->toBe(2)
        ->and($movement->reference)->toBe('ADJUSTMENT')
        ->and($movement->notes)->toBe('Conteo fisico');
});

it('rejects negative inventory adjustments that exceed current stock', function () {
    $user = User::factory()->create();

    $product = Product::create([
        'name' => 'Luz led',
        'category' => 'Accesorios',
        'purchase_price' => 15,
        'sale_price' => 25,
        'stock' => 1,
        'min_stock' => 1,
    ]);

    $this->from(route('inventory.adjustment'))
        ->actingAs($user)
        ->post(route('inventory.store-adjustment'), [
            'product_id' => $product->id,
            'quantity' => -5,
            'reason' => 'loss',
            'notes' => 'Producto extraviado',
        ])
        ->assertRedirect(route('inventory.adjustment'))
        ->assertSessionHasErrors('quantity');

    expect($product->fresh()->stock)->toBe(1)
        ->and(InventoryMovement::count())->toBe(0);
});
