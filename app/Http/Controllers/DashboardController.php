<?php

namespace App\Http\Controllers;

use App\Models\Mechanic;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Services\ReportService;
use App\Support\BackupStatus;
use Carbon\CarbonImmutable;

class DashboardController extends Controller
{
    public function index()
    {
        $branchId = $this->currentBranchId();
        $monthStart = CarbonImmutable::now()->startOfMonth();

        $activeOrders = ServiceOrder::forBranch($branchId)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();

        $pendingOrders = ServiceOrder::forBranch($branchId)
            ->where('status', 'in_progress')
            ->count();

        // Ventas + facturas vigentes: cuadra con «Cobrado hoy» y con la caja (decisión 13).
        $reports = app(ReportService::class);
        $today = CarbonImmutable::today();
        $todayEarnings = $reports->income($branchId, $today, $today);
        $monthEarnings = $reports->income($branchId, $today->startOfMonth(), $today);

        $recentSales = Sale::with(['saleProducts.product' => fn ($query) => $query->withTrashed(), 'user'])
            ->forBranch($branchId)
            ->notCancelled()
            ->orderByDesc('sale_date')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $totalProducts = Product::forBranch($branchId)->count();
        $lowStockProducts = Product::forBranch($branchId)->lowStock()->get();
        $lowStockCount = $lowStockProducts->count();

        $todayByMethod = $reports->paymentMethods($branchId, $today, $today);

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

        // Solo el administrador puede hacer algo con un respaldo atrasado.
        $backupStatus = auth()->user()?->is_admin ? BackupStatus::fromConfig() : null;

        return view('dashboard', compact(
            'backupStatus',
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
