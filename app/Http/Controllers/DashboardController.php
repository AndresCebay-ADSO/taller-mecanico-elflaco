<?php

namespace App\Http\Controllers;

use App\Models\Mechanic;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\WorkshopJob;
use App\Services\BranchService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(BranchService $branchService)
    {
        $branchId = $branchService->getCurrentBranch()?->id;
        $todayStart = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();

        $activeOrders = ServiceOrder::forBranch($branchId)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();

        $pendingOrders = ServiceOrder::forBranch($branchId)
            ->where('status', 'in_progress')
            ->count();

        $todaySales = Sale::forBranch($branchId)
            ->where('status', '!=', 'anulada')
            ->whereDate('sale_date', $todayStart)
            ->sum('total_amount');

        $todayServiceOrdersEarnings = WorkshopJob::whereHas('serviceOrder', function ($query) use ($todayStart, $branchId) {
            $query
                ->forBranch($branchId)
                ->where('status', 'completed')
                ->whereDate('completed_at', $todayStart);
        })->sum('total_amount');

        $todayEarnings = $todaySales + $todayServiceOrdersEarnings;

        $monthSales = Sale::forBranch($branchId)
            ->where('status', '!=', 'anulada')
            ->whereDate('sale_date', '>=', $monthStart)
            ->sum('total_amount');

        $monthServiceOrdersEarnings = WorkshopJob::whereHas('serviceOrder', function ($query) use ($monthStart, $branchId) {
            $query
                ->forBranch($branchId)
                ->where('status', 'completed')
                ->where('completed_at', '>=', $monthStart);
        })->sum('total_amount');

        $monthEarnings = $monthSales + $monthServiceOrdersEarnings;

        $recentSales = Sale::with(['saleProducts.product', 'user'])
            ->forBranch($branchId)
            ->where('status', '!=', 'anulada')
            ->orderByDesc('sale_date')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $totalProducts = Product::forBranch($branchId)->count();
        $lowStockProducts = Product::forBranch($branchId)->whereRaw('stock <= min_stock')->get();
        $lowStockCount = $lowStockProducts->count();

        $todayByMethod = Sale::forBranch($branchId)
            ->where('status', '!=', 'anulada')
            ->whereDate('sale_date', $todayStart)
            ->selectRaw('payment_method, sum(total_amount) as total')
            ->groupBy('payment_method')
            ->get();

        $mechanics = Mechanic::forBranch($branchId)
            ->where('is_active', true)
            ->withCount([
                'workshopJobs as total_jobs' => function ($query) use ($monthStart) {
                    $query
                        ->where('status', 'completed')
                        ->where('completed_at', '>=', $monthStart);
                },
            ])
            ->withSum([
                'workshopJobs as monthly_earnings' => function ($query) use ($monthStart) {
                    $query
                        ->where('status', 'completed')
                        ->where('completed_at', '>=', $monthStart);
                },
            ], 'mechanic_cost')
            ->get();

        return view('dashboard', compact(
            'activeOrders',
            'pendingOrders',
            'todayEarnings',
            'monthEarnings',
            'recentSales',
            'lowStockCount',
            'lowStockProducts',
            'totalProducts',
            'todayByMethod',
            'mechanics'
        ));
    }
}
