<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessRuleException;
use App\Models\Invoice;
use App\Models\ServiceOrder;
use App\Models\Setting;
use App\Services\InvoiceService;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $branchId = $this->currentBranchId();

        $invoices = Invoice::with('serviceOrder')
            ->whereHas('serviceOrder', function ($q) use ($branchId) {
                $q->forBranch($branchId);
            })
            ->latest('invoice_date')
            ->latest('id')
            ->paginate(15);

        return view('invoices.index', compact('invoices'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load([
            'serviceOrder.branch',
            'serviceOrder.workshopJobs.jobType',
            'serviceOrder.workshopJobs.mechanic',
            'serviceOrder.workshopJobs.jobProducts.product' => fn ($q) => $q->withTrashed(),
        ]);

        $this->ensureCurrentBranch($invoice->serviceOrder?->branch_id);

        $settings = Setting::pluck('value', 'key')->all();

        return view('invoices.show', compact('invoice', 'settings'));
    }

    /**
     * Generate an invoice from a Service Order.
     */
    public function generateFromServiceOrder(ServiceOrder $serviceOrder, InvoiceService $invoiceService)
    {
        $this->ensureCurrentBranch($serviceOrder->branch_id);

        try {
            $invoice = $invoiceService->generateFor($serviceOrder);
        } catch (BusinessRuleException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Factura consolidada generada con éxito.');
    }

    /**
     * Remove the specified resource from storage (solo administradores).
     */
    public function destroy(Invoice $invoice)
    {
        $this->ensureCurrentBranch($invoice->serviceOrder?->branch_id);

        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Factura anulada.');
    }
}
