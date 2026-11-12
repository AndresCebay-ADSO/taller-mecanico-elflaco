<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\User;
use App\Services\BranchService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Una instancia por peticion: evita repetir consultas de sucursal activa.
        $this->app->scoped(BranchService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('admin', fn (User $user) => (bool) $user->is_admin);

        // Paginación con los tokens de la app (resources/views/pagination/app.blade.php).
        Paginator::defaultView('pagination.app');
        Paginator::defaultSimpleView('pagination.app');

        View::composer('components.app-layout', function ($view) {
            try {
                $branchId = app(BranchService::class)->getCurrentBranchId();
                $count = Product::forBranch($branchId)
                    ->whereColumn('stock', '<=', 'min_stock')
                    ->count();
                $view->with('lowStockCount', $count);
            } catch (\Exception $e) {
                $view->with('lowStockCount', 0);
            }
        });
    }
}
