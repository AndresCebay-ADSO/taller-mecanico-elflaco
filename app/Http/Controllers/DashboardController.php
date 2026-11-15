<?php

namespace App\Http\Controllers;

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

        // Las mismas comisiones del mes que muestra Reportes; solo las ve el administrador (decisión 14).
        $mechanics = auth()->user()?->can('admin') ? $reports->commissions($branchId, $today->startOfMonth(), $today) : collect();

        // Solo el administrador puede hacer algo con un respaldo atrasado.
        $backupAlerts = auth()->user()?->is_admin ? BackupStatus::alerts() : [];
        // Datos de ejemplo del taller que saldrían impresos en los comprobantes.
        $exampleSettings = auth()->user()?->is_admin ? \App\Models\Setting::examplesInUse() : [];

        return view('dashboard', compact(
            'backupAlerts',
            'exampleSettings',
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
