<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/**
 * Documentos imprimibles (tamaño carta, siempre en tema claro). Cada uno es una página aparte, sin el menú
 * de la app, que se abre en otra pestaña y lanza la impresión: lo impreso no depende de la pantalla.
 */
class DocumentController extends Controller
{
    /** Comprobante de servicio de una orden facturada. */
    public function invoice(Invoice $invoice)
    {
        $invoice->load([
            'cancelledBy',
            'serviceOrder.branch',
            'serviceOrder.workshopJobs.jobType',
            'serviceOrder.workshopJobs.mechanic',
            'serviceOrder.workshopJobs.jobProducts.product' => fn ($q) => $q->withTrashed(),
        ]);
        $this->ensureCurrentBranch($invoice->serviceOrder?->branch_id);

        return view('documents.invoice', ['invoice' => $invoice, 'settings' => $this->settings()]);
    }

    /** Comprobante de una venta de mostrador. */
    public function sale(Sale $sale)
    {
        $this->ensureCurrentBranch($sale->branch_id);
        $sale->load(['branch', 'user', 'saleProducts.product' => fn ($q) => $q->withTrashed()]);

        return view('documents.sale', ['sale' => $sale, 'settings' => $this->settings()]);
    }

    /** Orden de ingreso: se imprime al recibir la moto y la firma el cliente. */
    public function serviceOrderIntake(ServiceOrder $serviceOrder)
    {
        $this->ensureCurrentBranch($serviceOrder->branch_id);
        $serviceOrder->load('branch');

        return view('documents.intake', ['order' => $serviceOrder, 'settings' => $this->settings()]);
    }

    /** Logo del taller, guardado fuera de public/ (no necesita storage:link, que en Windows pide permisos). */
    public function logo()
    {
        $path = Setting::where('key', 'workshop_logo')->value('value');

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, max-age=86400']);
    }

    /** @return array<string, string|null> */
    private function settings(): array
    {
        return Setting::pluck('value', 'key')->all();
    }
}
