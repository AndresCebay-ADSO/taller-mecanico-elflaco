<?php

namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Mechanic;
use App\Models\ServiceOrder;
use App\Models\WorkshopJob;
use App\Models\Sale;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $todayStart = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();

        // 1. Órdenes Activas y Pendientes de entrega
        $activeOrders = ServiceOrder::whereNotIn('status', ['completed', 'cancelled'])->count();
        $pendingOrders = ServiceOrder::where('status', 'ready')->count();

        // 2. Ganancias de Hoy (Ventas + Órdenes completadas)
        $todaySales = Sale::where('status', 'completed')->whereDate('created_at', $todayStart)->sum('total_amount');
        $todayServiceOrdersEarnings = WorkshopJob::whereHas('serviceOrder', function($q) use ($todayStart) {
            $q->where('status', 'completed')->whereDate('completed_at', $todayStart);
        })->sum('total_amount');
        $todayEarnings = $todaySales + $todayServiceOrdersEarnings;

        // 3. Ganancias del Mes
        $monthSales = Sale::where('status', 'completed')->where('created_at', '>=', $monthStart)->sum('total_amount');
        $monthServiceOrdersEarnings = WorkshopJob::whereHas('serviceOrder', function($q) use ($monthStart) {
            $q->where('status', 'completed')->where('completed_at', '>=', $monthStart);
        })->sum('total_amount');
        $monthEarnings = $monthSales + $monthServiceOrdersEarnings;

        // 4. Últimas Ventas
        $recentSales = Sale::with(['saleProducts.product', 'user'])->latest()->limit(5)->get();

        // 5. Productos con Stock Bajo (No toma en cuenta is_active porque usa SoftDeletes)
        $totalProducts = Product::count(); // Optional but we have it on layout
        $lowStockCount = Product::whereRaw('stock <= min_stock')->count();
        $lowStockProducts = Product::whereRaw('stock <= min_stock')->get();

        // 6. Ventas por método de pago hoy
        $todayByMethod = Sale::where('status', 'completed')
            ->whereDate('created_at', $todayStart)
            ->selectRaw('payment_method, sum(total_amount) as total')
            ->groupBy('payment_method')
            ->get();

        // 7. Mecánicos activos con trabajos y ganancias del mes
        $mechanics = Mechanic::where('is_active', true)->get()->map(function($mechanic) use ($monthStart) {
            $mechanic->total_jobs = $mechanic->workshopJobs()->where('status', 'completed')->where('completed_at', '>=', $monthStart)->count();
            $mechanic->monthly_earnings = $mechanic->workshopJobs()
                ->where('status', 'completed')
                ->where('completed_at', '>=', $monthStart)
                ->sum('mechanic_cost');
            return $mechanic;
        });

        return view('dashboard', compact(
            'activeOrders', 'pendingOrders',
            'todayEarnings', 'monthEarnings',
            'recentSales', 'lowStockCount', 'lowStockProducts', 'totalProducts',
            'todayByMethod', 'mechanics'
        ));
    }
}
