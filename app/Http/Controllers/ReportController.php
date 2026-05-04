<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Mechanic;
use App\Models\ServiceOrder;
use App\Models\Sale;
use App\Models\WorkshopJob;
use App\Services\BranchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(BranchService $branchService)
    {
        $branchId = $branchService->getCurrentBranchId();

        // Fix M01: Ganancia real = ingresos por ventas de mostrador + ganancias del taller por trabajos
        $totalIncome = Sale::where('status', '!=', 'anulada')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('total_amount');

        // Ganancia real del taller basada en workshop_cost de trabajos completados
        $workshopProfit = WorkshopJob::where('status', WorkshopJob::STATUS_COMPLETED)
            ->when($branchId, function ($q) use ($branchId) {
                $q->whereHas('serviceOrder', fn ($so) => $so->where('branch_id', $branchId));
            })
            ->sum('workshop_cost');

        $monthlyJobs = ServiceOrder::whereMonth('created_at', now()->month)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->count();

        // Inventario — Fix M02: filtrado por sucursal
        $productsQuery = Product::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $totalProducts = (clone $productsQuery)->count();
        $lowStockCount = (clone $productsQuery)->whereRaw('stock <= min_stock')->count();
        $inventoryCostTotal = (clone $productsQuery)->sum(DB::raw('purchase_price * stock'));
        $inventorySaleTotal = (clone $productsQuery)->sum(DB::raw('sale_price * stock'));

        // Mecánicos — filtrado por sucursal
        $mechanics = Mechanic::where('is_active', true)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get();

        return view('reports.index', compact(
            'totalIncome', 
            'workshopProfit', 
            'monthlyJobs', 
            'totalProducts', 
            'lowStockCount', 
            'inventoryCostTotal', 
            'inventorySaleTotal',
            'mechanics'
        ));
    }
}
