<?php

namespace App\Http\Middleware;

use App\Services\BranchService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentBranch
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Un usuario desactivado pierde la sesion en su siguiente peticion.
        if ($user->is_active === false) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Tu usuario esta desactivado. Contacta al administrador.',
            ]);
        }

        $branchService = app(BranchService::class);
        $availableBranches = $branchService->getAvailableBranches();

        if (!$branchService->getCurrentBranch() && $availableBranches->isNotEmpty()) {
            $branchService->setCurrentBranch($availableBranches->first()->id);
        }

        view()->share([
            'currentBranch' => $branchService->getCurrentBranch(),
            'availableBranches' => $availableBranches,
        ]);

        return $next($request);
    }
}
