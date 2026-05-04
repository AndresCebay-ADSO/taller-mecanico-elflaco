<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Services\BranchService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentBranch
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $branchService = app(BranchService::class);

        if (!$branchService->getCurrentBranch()) {
            $availableBranches = $branchService->getAvailableBranches();

            if ($availableBranches->isNotEmpty()) {
                $branchService->setCurrentBranch($availableBranches->first()->id);
            }
        }

        view()->share('currentBranch', $branchService->getCurrentBranch());

        return $next($request);
    }
}
