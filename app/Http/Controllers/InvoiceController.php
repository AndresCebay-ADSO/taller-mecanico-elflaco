<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\ServiceOrder;
use App\Services\BranchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(BranchService $branchService)
    {
        $branchId = $branchService->getCurrentBranch()?->id;
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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('invoices.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_number' => 'required|string|unique:invoices,invoice_number',
            'amount' => 'required|numeric|min:0',
            'invoice_date' => 'required|date',
            'service_order_id' => 'required|exists:service_orders,id',
        ]);

        Invoice::create($validated);

        return redirect()->route('invoices.index')->with('success', 'Factura generada.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        return view('invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice)
    {
        return view('invoices.edit', compact('invoice'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $invoice->update($validated);

        return redirect()->route('invoices.index')->with('success', 'Factura actualizada.');
    }

    /**
     * Generate an invoice from a Service Order.
     */
    public function generateFromServiceOrder(ServiceOrder $serviceOrder)
    {
        $invoice = DB::transaction(function () use ($serviceOrder) {
            $lockedServiceOrder = ServiceOrder::whereKey($serviceOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedServiceOrder->invoices()->exists()) {
                return null;
            }

            $lockedServiceOrder->load('workshopJobs.jobProducts');

            $totalLabor = $lockedServiceOrder->workshopJobs->sum('labor_cost');
            $totalProducts = $lockedServiceOrder->workshopJobs->sum(
                fn ($job) => $job->jobProducts->sum('total_price')
            );

            $invoice = Invoice::create([
                'invoice_number' => 'FAC-' . strtoupper(bin2hex(random_bytes(8))),
                'service_order_id' => $lockedServiceOrder->id,
                'amount' => $totalLabor + $totalProducts,
                'invoice_date' => now(),
            ]);

            $lockedServiceOrder->update([
                'status' => 'completed',
                'completed_at' => $lockedServiceOrder->completed_at ?? now(),
            ]);

            return $invoice;
        });

        if (!$invoice) {
            return back()->with('error', 'Esta orden ya tiene una factura vinculada.');
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Factura consolidada generada con éxito.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Factura eliminada.');
    }
}
