<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Mechanic;
use App\Models\ServiceOrder;
use App\Models\Sale;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        // Totals (calculados de forma simple para la demo)
        $totalIncome = Sale::sum('total_amount');
        $workshopProfit = $totalIncome * 0.25; // 25% de margen sugerido en la imagen
        $monthlyJobs = ServiceOrder::whereMonth('created_at', now()->month)->count();
        
        // Inventario
        $totalProducts = Product::count();
        $lowStockCount = Product::whereRaw('stock <= min_stock')->count();
        $inventoryCostTotal = Product::sum(\DB::raw('purchase_price * stock'));
        $inventorySaleTotal = Product::sum(\DB::raw('sale_price * stock'));

        // Mecánicos
        $mechanics = Mechanic::where('is_active', true)->get();

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
