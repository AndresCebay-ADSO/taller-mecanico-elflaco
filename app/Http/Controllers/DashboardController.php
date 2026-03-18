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
        // 1. Stats de Productos
        $totalProducts = Product::count();
        $lowStockCount = Product::whereRaw('stock <= min_stock')->count();
        $lowStockProducts = Product::whereRaw('stock <= min_stock')->limit(3)->get();

        // 2. Trabajos Activos (Service Orders que no estén completadas)
        $activeJobsCount = ServiceOrder::whereNotIn('status', ['completed', 'cancelled'])->count();

        // 3. Ganancias de Hoy
        // Incluye el total_amount de Sales de hoy + el labor_cost de WorkshopJobs completados hoy
        $todayStart = Carbon::today();
        $todaySales = Sale::where('status', 'completed')->whereDate('created_at', $todayStart)->sum('total_amount');
        $todayJobsLabor = WorkshopJob::where('status', 'completed')->whereDate('completed_at', $todayStart)->sum('labor_cost');
        $todayEarnings = $todaySales + $todayJobsLabor;

        // 4. Ganancias del Mes
        $monthStart = Carbon::now()->startOfMonth();
        $monthSales = Sale::where('status', 'completed')->where('created_at', '>=', $monthStart)->sum('total_amount');
        $monthJobsLabor = WorkshopJob::where('status', 'completed')->where('completed_at', '>=', $monthStart)->sum('labor_cost');
        $monthEarnings = $monthSales + $monthJobsLabor;
        $workshopEarningsMonth = $monthJobsLabor; // O una parte proporcional si se define luego

        // 5. Mecánicos Activos (con stats básicas)
        $activeMechanics = Mechanic::where('is_active', true)->limit(5)->get()->map(function($mechanic) use ($monthStart) {
            $mechanic->total_jobs = $mechanic->workshopJobs()->where('status', 'completed')->count();
            $mechanic->monthly_earnings = $mechanic->workshopJobs()
                ->where('status', 'completed')
                ->where('completed_at', '>=', $monthStart)
                ->sum('mechanic_cost');
            return $mechanic;
        });

        // 6. Actividad Reciente (Últimas órdenes de servicio o trabajos)
        $recentJobs = ServiceOrder::latest()->limit(5)->get();

        return view('dashboard', compact(
            'totalProducts', 'lowStockCount', 'lowStockProducts', 
            'activeJobsCount', 'todayEarnings', 'monthEarnings', 
            'workshopEarningsMonth', 'activeMechanics', 'recentJobs'
        ));
    }
}
