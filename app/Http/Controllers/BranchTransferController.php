<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\InsufficientStockException;
use App\Models\Branch;
use App\Models\BranchTransfer;
use App\Models\Product;
use App\Services\BranchTransferService;
use Illuminate\Http\Request;

class BranchTransferController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'status' => 'nullable|string|in:pending,in_transit,completed,cancelled',
            'from_branch_id' => 'nullable|exists:branches,id',
            'to_branch_id' => 'nullable|exists:branches,id',
        ]);

        $query = BranchTransfer::with(['fromBranch', 'toBranch', 'product', 'requestedBy', 'completedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('from_branch_id')) {
            $query->where('from_branch_id', $request->input('from_branch_id'));
        }

        if ($request->filled('to_branch_id')) {
            $query->where('to_branch_id', $request->input('to_branch_id'));
        }

        $transfers = $query->latest()->paginate(10)->appends($request->all());

        $branches = Branch::active()->orderBy('name')->get();

        return view('branch-transfers.index', compact('transfers', 'branches'));
    }

    public function create()
    {
        $branches = Branch::active()->orderBy('name')->get();

        $products = Product::orderBy('name')->get();

        return view('branch-transfers.create', compact('branches', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_branch_id' => 'required|exists:branches,id',
            'to_branch_id' => 'required|exists:branches,id|different:from_branch_id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:100000',
            'unit_price' => 'nullable|numeric|min:0|max:99999999',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ], [
            'to_branch_id.different' => 'La sucursal de destino debe ser diferente a la de origen.',
            'quantity.min' => 'La cantidad minima es 1.',
        ]);

        $product = Product::where('id', $validated['product_id'])
            ->where('branch_id', $validated['from_branch_id'])
            ->first();

        if (!$product) {
            return back()->withErrors([
                'product_id' => 'El producto seleccionado no pertenece a la sucursal de origen.',
            ])->withInput();
        }

        if ($product->stock < $validated['quantity']) {
            return back()->withErrors([
                'quantity' => "Stock insuficiente. Disponible: {$product->stock} unidades.",
            ])->withInput();
        }

        $validated['status'] = BranchTransfer::STATUS_PENDING;
        $validated['requested_by'] = auth()->id();
        $validated['unit_price'] = $validated['unit_price'] ?? $product->purchase_price;

        BranchTransfer::create($validated);

        return redirect()->route('branch-transfers.index')->with('success', 'Transferencia creada exitosamente.');
    }

    public function updateStatus(Request $request, BranchTransfer $transfer, BranchTransferService $transferService)
    {
        $validated = $request->validate([
            'status' => 'required|in:in_transit,completed,cancelled',
        ]);

        if (!$transfer->canTransitionTo($validated['status'])) {
            return back()->withErrors([
                'status' => 'No se puede cambiar el estado de la transferencia a "' . $validated['status'] . '".',
            ]);
        }

        if ($validated['status'] === BranchTransfer::STATUS_COMPLETED) {
            try {
                $transferService->complete($transfer, auth()->id());
            } catch (InsufficientStockException|BusinessRuleException $exception) {
                return back()->withErrors(['status' => $exception->getMessage()]);
            }

            return redirect()->route('branch-transfers.index')->with('success', 'Transferencia completada e inventario actualizado.');
        }

        $transfer->update([
            'status' => $validated['status'],
        ]);

        $message = match ($validated['status']) {
            BranchTransfer::STATUS_IN_TRANSIT => 'Transferencia marcada en transito.',
            BranchTransfer::STATUS_CANCELLED => 'Transferencia cancelada.',
            default => 'Estado actualizado.',
        };

        return redirect()->route('branch-transfers.index')->with('success', $message);
    }
}
