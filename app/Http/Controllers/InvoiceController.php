<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessRuleException;
use App\Models\Invoice;
use App\Models\ServiceOrder;
use App\Models\Setting;
use App\Services\CashService;
use App\Services\InvoiceService;
use App\Support\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $branchId = $this->currentBranchId();

        $invoices = Invoice::with(['serviceOrder', 'cancelledBy'])
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
            'cancelledBy',
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
    public function generateFromServiceOrder(Request $request, ServiceOrder $serviceOrder, InvoiceService $invoiceService, CashService $cashService)
    {
        $this->ensureCurrentBranch($serviceOrder->branch_id);

        $validated = $request->validate(
            ['payment_method' => 'required|string|' . PaymentMethod::rule()],
            [
                'payment_method.required' => 'Elige cómo pagó el cliente.',
                'payment_method.in' => 'Elige un método de pago de la lista.',
            ],
        );

        try {
            $cashService->ensureCashAccepted($serviceOrder->branch_id, $validated['payment_method']);
            $invoice = $invoiceService->generateFor($serviceOrder, $validated['payment_method']);
        } catch (BusinessRuleException $exception) {
            return back()->with('error', $exception->getMessage())->withInput();
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Factura consolidada generada con éxito.');
    }

    /**
     * Anula la factura sin borrarla (solo administradores).
     */
    public function cancel(Request $request, Invoice $invoice, InvoiceService $invoiceService, CashService $cashService)
    {
        $branchId = $invoice->serviceOrder?->branch_id;
        $this->ensureCurrentBranch($branchId);

        // Efectivo de un día anterior: se pregunta si se devolvió la plata (decisión 2).
        $asksForRefund = $invoice->asksForRefund();
        if ($asksForRefund) {
            $request->validate(['refunded' => 'required|boolean'], [
                'refunded.required' => 'Indica si le devolviste la plata al cliente.',
            ]);
        }
        $refunded = $asksForRefund && $request->boolean('refunded');

        try {
            $cancelled = DB::transaction(function () use ($invoice, $invoiceService, $cashService, $refunded, $branchId) {
                if (!$invoiceService->cancel($invoice, auth()->id())) {
                    return false;
                }
                if ($refunded) {
                    $cashService->recordRefund($branchId, $invoice, auth()->id());
                }

                return true;
            });
        } catch (BusinessRuleException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if (!$cancelled) {
            return back()->with('error', "La factura {$invoice->invoice_number} ya estaba anulada.");
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "Factura {$invoice->invoice_number} anulada. La orden puede facturarse de nuevo."
                . ($refunded ? ' La devolución de ' . money($invoice->amount) . ' salió de la caja de hoy.' : ''))
            ->with('warning', $cashService->closedDayNotice($branchId, $invoice->invoice_date, $invoice->payment_method));
    }
}
