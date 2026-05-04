<?php

use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\Mechanic;
use App\Models\BranchTransfer;
use App\Models\User;
use App\Services\BranchService;

beforeEach(function () {
    $this->branchService = new BranchService();
});

it('filters products by branch', function () {
    $branch1 = Branch::create(['name' => 'Sede A', 'is_active' => true]);
    $branch2 = Branch::create(['name' => 'Sede B', 'is_active' => true]);

    $product1 = Product::create([
        'name' => 'Producto A',
        'category' => 'Cat',
        'branch_id' => $branch1->id,
        'stock' => 10,
        'min_stock' => 5,
        'purchase_price' => 10,
        'sale_price' => 20,
    ]);

    $product2 = Product::create([
        'name' => 'Producto B',
        'category' => 'Cat',
        'branch_id' => $branch2->id,
        'stock' => 10,
        'min_stock' => 5,
        'purchase_price' => 10,
        'sale_price' => 20,
    ]);

    $products = Product::where('branch_id', $branch1->id)->get();

    expect($products)->toHaveCount(1);
    expect($products->first()->id)->toBe($product1->id);
});

it('filters sales by branch', function () {
    $branch1 = Branch::create(['name' => 'Sede A', 'is_active' => true]);
    $branch2 = Branch::create(['name' => 'Sede B', 'is_active' => true]);

    $sale1 = Sale::create([
        'customer_name' => 'Cliente A',
        'total_amount' => 100,
        'branch_id' => $branch1->id,
        'sale_date' => now(),
        'payment_method' => 'Efectivo',
        'status' => 'completada',
    ]);

    $sale2 = Sale::create([
        'customer_name' => 'Cliente B',
        'total_amount' => 200,
        'branch_id' => $branch2->id,
        'sale_date' => now(),
        'payment_method' => 'Efectivo',
        'status' => 'completada',
    ]);

    $sales = Sale::where('branch_id', $branch1->id)->get();

    expect($sales)->toHaveCount(1);
    expect($sales->first()->id)->toBe($sale1->id);
});

it('filters service orders by branch', function () {
    $branch1 = Branch::create(['name' => 'Sede A', 'is_active' => true]);
    $branch2 = Branch::create(['name' => 'Sede B', 'is_active' => true]);

    $order1 = ServiceOrder::create([
        'customer_name' => 'Cliente A',
        'vehicle_info' => 'Car A',
        'service_description' => 'Service A',
        'branch_id' => $branch1->id,
        'status' => 'pending',
    ]);

    $order2 = ServiceOrder::create([
        'customer_name' => 'Cliente B',
        'vehicle_info' => 'Car B',
        'service_description' => 'Service B',
        'branch_id' => $branch2->id,
        'status' => 'pending',
    ]);

    $orders = ServiceOrder::where('branch_id', $branch1->id)->get();

    expect($orders)->toHaveCount(1);
    expect($orders->first()->id)->toBe($order1->id);
});

it('filters mechanics by branch', function () {
    $branch1 = Branch::create(['name' => 'Sede A', 'is_active' => true]);
    $branch2 = Branch::create(['name' => 'Sede B', 'is_active' => true]);

    $mechanic1 = Mechanic::create([
        'name' => 'Mecanico A',
        'phone' => '3001112233',
        'hire_date' => now()->subYear(),
        'branch_id' => $branch1->id,
        'is_active' => true,
    ]);

    $mechanic2 = Mechanic::create([
        'name' => 'Mecanico B',
        'phone' => '3004445566',
        'hire_date' => now()->subYear(),
        'branch_id' => $branch2->id,
        'is_active' => true,
    ]);

    $mechanics = Mechanic::where('branch_id', $branch1->id)->get();

    expect($mechanics)->toHaveCount(1);
    expect($mechanics->first()->id)->toBe($mechanic1->id);
});

it('creates branch transfer and completes it', function () {
    $branch1 = Branch::create(['name' => 'Sede A', 'is_active' => true]);
    $branch2 = Branch::create(['name' => 'Sede B', 'is_active' => true]);
    $product = Product::create([
        'name' => 'Producto',
        'category' => 'Cat',
        'branch_id' => $branch1->id,
        'stock' => 100,
        'min_stock' => 5,
        'purchase_price' => 10,
        'sale_price' => 20,
    ]);

    $transfer = BranchTransfer::create([
        'from_branch_id' => $branch1->id,
        'to_branch_id' => $branch2->id,
        'product_id' => $product->id,
        'quantity' => 20,
        'status' => BranchTransfer::STATUS_PENDING,
    ]);

    expect($transfer->status)->toBe(BranchTransfer::STATUS_PENDING);

    $transfer->status = BranchTransfer::STATUS_IN_TRANSIT;
    $transfer->save();

    expect($transfer->canTransitionTo(BranchTransfer::STATUS_COMPLETED))->toBeTrue();

    $transfer->status = BranchTransfer::STATUS_COMPLETED;
    $transfer->save();

    expect($transfer->status)->toBe(BranchTransfer::STATUS_COMPLETED);
});

it('prevents invalid branch transfer status transitions', function () {
    $branch1 = Branch::create(['name' => 'Sede A', 'is_active' => true]);
    $branch2 = Branch::create(['name' => 'Sede B', 'is_active' => true]);
    $product = Product::create([
        'name' => 'Producto',
        'category' => 'Cat',
        'branch_id' => $branch1->id,
        'stock' => 10,
        'min_stock' => 5,
        'purchase_price' => 10,
        'sale_price' => 20,
    ]);

    $transfer = BranchTransfer::create([
        'from_branch_id' => $branch1->id,
        'to_branch_id' => $branch2->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'status' => BranchTransfer::STATUS_PENDING,
    ]);

    expect($transfer->canTransitionTo(BranchTransfer::STATUS_COMPLETED))->toBeFalse();

    expect($transfer->canTransitionTo(BranchTransfer::STATUS_IN_TRANSIT))->toBeTrue();
    $transfer->status = BranchTransfer::STATUS_IN_TRANSIT;
    $transfer->save();

    expect($transfer->canTransitionTo(BranchTransfer::STATUS_COMPLETED))->toBeTrue();
    expect($transfer->canTransitionTo(BranchTransfer::STATUS_PENDING))->toBeFalse();
});
