<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchTransfer;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ], [
            'to_branch_id.different' => 'La sucursal de destino debe ser diferente a la de origen.',
            'quantity.min' => 'La cantidad minima es 1.',
        ]);

        // Fix M06: Verificar producto pertenezca a la sucursal de origen
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
        $validated['unit_price'] = $validated['unit_price'] ?? $product->sale_price;

        BranchTransfer::create($validated);

        return redirect()->route('branch-transfers.index')->with('success', 'Transferencia creada exitosamente.');
    }

    public function updateStatus(Request $request, BranchTransfer $transfer)
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
            return $this->completeTransfer($transfer);
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

    protected function completeTransfer(BranchTransfer $transfer)
    {
        return DB::transaction(function () use ($transfer) {
            $sourceProduct = Product::where('id', $transfer->product_id)
                ->where('branch_id', $transfer->from_branch_id)
                ->lockForUpdate()
                ->first();

            if (!$sourceProduct || $sourceProduct->stock < $transfer->quantity) {
                throw new \RuntimeException('Stock insuficiente en la sucursal de origen para completar la transferencia.');
            }

            $sourceProduct->decrement('stock', $transfer->quantity);

            InventoryMovement::create([
                'product_id' => $sourceProduct->id,
                'movement_type' => 'transfer_out',
                'quantity' => -$transfer->quantity,
                'unit_price' => $transfer->unit_price,
                'reference' => "Transferencia #{$transfer->id}",
                'notes' => "Transferencia a sucursal {$transfer->toBranch->name}",
                'movement_date' => now(),
            ]);

            $destinationProduct = Product::where('branch_id', $transfer->to_branch_id)
                ->where(function ($query) use ($transfer) {
                    if (!empty($transfer->product->upc)) {
                        $query->where('upc', $transfer->product->upc);
                    } else {
                        $query->where('name', $transfer->product->name)
                              ->where('category', $transfer->product->category);
                    }
                })
                ->lockForUpdate()
                ->first();

            if (!$destinationProduct) {
                $destinationProduct = Product::create([
                    'name' => $transfer->product->name,
                    'category' => $transfer->product->category,
                    'branch_id' => $transfer->to_branch_id,
                    'purchase_price' => $transfer->product->purchase_price,
                    'sale_price' => $transfer->unit_price ?? $transfer->product->sale_price,
                    'stock' => 0,
                    'min_stock' => $transfer->product->min_stock,
                    'upc' => $transfer->product->upc,
                ]);
            }

            $destinationProduct->increment('stock', $transfer->quantity);

            // Bug 4: Create a Batch for the transferred stock to maintain FIFO logic
            $batch = \App\Models\Batch::create([
                'product_id' => $destinationProduct->id,
                'supplier_id' => null,
                'cost_price' => $transfer->unit_price ?? $transfer->product->purchase_price ?? 0,
                'sale_price' => $destinationProduct->sale_price,
                'quantity' => $transfer->quantity,
                'remaining_stock' => $transfer->quantity,
                'purchased_at' => now(),
            ]);

            InventoryMovement::create([
                'product_id' => $destinationProduct->id,
                'batch_id' => $batch->id,
                'movement_type' => 'transfer_in',
                'quantity' => $transfer->quantity,
                'unit_price' => $transfer->unit_price,
                'reference' => "Transferencia #{$transfer->id}",
                'notes' => "Transferencia desde sucursal {$transfer->fromBranch->name}",
                'movement_date' => now(),
            ]);

            $transfer->update([
                'status' => BranchTransfer::STATUS_COMPLETED,
                'completed_at' => now(),
                'completed_by' => auth()->id(),
            ]);

            return redirect()->route('branch-transfers.index')->with('success', 'Transferencia completada e inventario actualizado.');
        });
    }
}
