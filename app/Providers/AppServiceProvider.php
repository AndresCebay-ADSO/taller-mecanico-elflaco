<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.app-layout', function ($view) {
            try {
                $count = Product::where('stock', '<=', DB::raw('min_stock'))
                    ->whereNull('deleted_at')
                    ->count();
                $view->with('lowStockCount', $count);
            } catch (\Exception $e) {
                $view->with('lowStockCount', 0);
            }
        });
    }
}
