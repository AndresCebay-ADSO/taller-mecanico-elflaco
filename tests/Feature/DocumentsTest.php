<?php

use App\Models\Invoice;
use App\Models\ServiceOrder;
use App\Models\Setting;
use App\Services\SaleService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function invoicedOrder($branch): Invoice
{
    $order = ServiceOrder::create([
        'customer_name' => 'Sandra Polanía',
        'customer_document' => '1.075.123.456',
        'customer_phone' => '3191234560',
        'vehicle_info' => 'Honda Wave 110S - RFV78R',
        'service_description' => 'Revisión general',
        'status' => 'in_progress',
        'branch_id' => $branch->id,
    ]);

    $job = app(\App\Services\WorkshopJobService::class)->addToOrder($order, [
        'job_type_id' => demoJobType()->id,
        'mechanic_id' => branchMechanic($branch)->id,
        'description' => 'Cambio de aceite',
        'labor_cost' => 125000,
    ]);
    $job->update(['status' => 'completed', 'completed_at' => now()]);

    return app(\App\Services\InvoiceService::class)->generateFor($order->fresh());
}

describe('documentos imprimibles', function () {
    it('prints the service receipt on its own page with the amount in words', function () {
        [$user, $branch] = branchUser();
        $invoice = invoicedOrder($branch);

        $this->actingAs($user)->withSession(['current_branch_id' => $branch->id])
            ->get(route('documents.invoice', $invoice))
            ->assertOk()
            ->assertSee('Comprobante de servicio')
            ->assertSee($invoice->invoice_number)
            ->assertSee('C.C./NIT 1.075.123.456')
            ->assertSee('Ciento veinticinco mil pesos m/cte.')
            ->assertSee('No es factura electrónica de venta')
            ->assertDontSee('id="app-sidebar"', false);
    });

    it('marks cancelled receipts as void', function () {
        [$user, $branch] = branchUser(admin: true);
        $invoice = invoicedOrder($branch);
        app(\App\Services\InvoiceService::class)->cancel($invoice, $user->id);

        $this->actingAs($user)->withSession(['current_branch_id' => $branch->id])
            ->get(route('documents.invoice', $invoice))
            ->assertOk()
            ->assertSee('ANULADO')
            ->assertSee('No tiene validez como comprobante');
    });

    it('prints the counter sale receipt and the intake order', function () {
        [$user, $branch] = branchUser();
        $product = stockedProduct($branch, 5);
        $sale = app(SaleService::class)->create($branch->id, $user->id, 'Mostrador', 'Nequi', [['id' => $product->id, 'quantity' => 2]])['sale'];
        $order = invoicedOrder($branch)->serviceOrder;

        $this->actingAs($user)->withSession(['current_branch_id' => $branch->id])
            ->get(route('documents.sale', $sale))
            ->assertOk()
            ->assertSee('Comprobante de venta')
            ->assertSee('Cuarenta mil pesos m/cte.');

        $this->actingAs($user)->withSession(['current_branch_id' => $branch->id])
            ->get(route('documents.intake', $order))
            ->assertOk()
            ->assertSee('Orden de ingreso')
            ->assertSee('Revisión general')
            ->assertSee('Kilometraje');
    });

    it('hides documents from another branch', function () {
        [$user, $branch] = branchUser();
        [, $otherBranch] = branchUser();
        $invoice = invoicedOrder($otherBranch);

        $this->actingAs($user)->withSession(['current_branch_id' => $branch->id])
            ->get(route('documents.invoice', $invoice))
            ->assertNotFound();

        $this->actingAs($user)->withSession(['current_branch_id' => $branch->id])
            ->get(route('documents.intake', $invoice->serviceOrder))
            ->assertNotFound();
    });

    it('stores the workshop logo outside public and serves it, then removes it', function () {
        Storage::fake('local');
        [$admin, $branch] = branchUser(admin: true);

        $this->actingAs($admin)->withSession(['current_branch_id' => $branch->id])
            ->put(route('settings.update'), ['workshop_name' => 'El Flaco', 'workshop_logo' => UploadedFile::fake()->image('logo.png', 300, 120)])
            ->assertSessionHasNoErrors();

        $path = Setting::where('key', 'workshop_logo')->value('value');
        Storage::disk('local')->assertExists($path);

        $this->actingAs($admin)->get(route('branding.logo'))->assertOk();

        $this->actingAs($admin)->withSession(['current_branch_id' => $branch->id])
            ->put(route('settings.update'), ['workshop_name' => 'El Flaco', 'remove_logo' => 1]);

        Storage::disk('local')->assertMissing($path);
        $this->actingAs($admin)->get(route('branding.logo'))->assertNotFound();
    });

    it('rejects a logo that is not an image', function () {
        [$admin, $branch] = branchUser(admin: true);

        $this->actingAs($admin)->withSession(['current_branch_id' => $branch->id])
            ->put(route('settings.update'), ['workshop_logo' => UploadedFile::fake()->create('virus.exe', 10)])
            ->assertSessionHasErrors('workshop_logo');
    });
});
