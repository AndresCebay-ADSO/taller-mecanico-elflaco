<?php

use App\Models\Batch;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;

it('deducts stock for jobs using fifo batches and job usage movements', function () {
    $product = Product::create([
        'name' => 'Filtro de aceite',
        'category' => 'Filtros',
        'purchase_price' => 50,
        'sale_price' => 150,
        'stock' => 5,
        'min_stock' => 1,
    ]);

    $firstBatch = Batch::create([
        'product_id' => $product->id,
        'cost_price' => 40,
        'selling_price' => 140,
        'sale_price' => 145,
        'quantity' => 2,
        'remaining_stock' => 2,
        'purchased_at' => now()->subDays(2),
    ]);

    $secondBatch = Batch::create([
        'product_id' => $product->id,
        'cost_price' => 45,
        'selling_price' => 150,
        'sale_price' => 155,
        'quantity' => 3,
        'remaining_stock' => 3,
        'purchased_at' => now()->subDay(),
    ]);

    $tramos = DB::transaction(function () use ($product) {
        return app(InventoryService::class)->deductStock(
            $product->id,
            4,
            'Trabajo #1',
            'job_usage'
        );
    });

    expect($tramos)->toHaveCount(2)
        ->and($tramos[0]['quantity'])->toBe(2)
        ->and((float) $tramos[0]['unit_price'])->toBe(145.0)
        ->and($tramos[1]['quantity'])->toBe(2)
        ->and((float) $tramos[1]['unit_price'])->toBe(155.0);

    expect($product->fresh()->stock)->toBe(1)
        ->and($firstBatch->fresh()->remaining_stock)->toBe(0)
        ->and($secondBatch->fresh()->remaining_stock)->toBe(1);

    $movements = InventoryMovement::where('reference', 'Trabajo #1')->orderBy('id')->get();

    expect($movements)->toHaveCount(2)
        ->and($movements->pluck('movement_type')->unique()->all())->toBe(['job_usage'])
        ->and($movements->pluck('quantity')->all())->toBe([-2, -2]);
});

it('restores sale stock and revives the product when it was soft deleted', function () {
    $product = Product::create([
        'name' => 'Pastilla de freno',
        'category' => 'Frenos',
        'purchase_price' => 80,
        'sale_price' => 180,
        'stock' => 2,
        'min_stock' => 1,
    ]);

    $batch = Batch::create([
        'product_id' => $product->id,
        'cost_price' => 70,
        'selling_price' => 170,
        'sale_price' => 175,
        'quantity' => 2,
        'remaining_stock' => 0,
        'purchased_at' => now()->subDay(),
    ]);

    InventoryMovement::create([
        'product_id' => $product->id,
        'batch_id' => $batch->id,
        'movement_type' => 'sale',
        'quantity' => -2,
        'unit_price' => 175,
        'reference' => 'Venta #99',
        'movement_date' => now(),
    ]);

    $product->delete();

    DB::transaction(function () {
        app(InventoryService::class)->reverseStockFromSale(99);
    });

    $restoredProduct = Product::withTrashed()->find($product->id);

    expect($restoredProduct?->stock)->toBe(4)
        ->and($restoredProduct?->deleted_at)->toBeNull()
        ->and($batch->fresh()->remaining_stock)->toBe(2)
        ->and(InventoryMovement::where('reference', 'Anulacion Venta #99')->count())->toBe(1);
});
