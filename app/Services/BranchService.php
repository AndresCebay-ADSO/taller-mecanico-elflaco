<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Support\Facades\Session;

class BranchService
{
    public function getCurrentBranch(): ?Branch
    {
        $branchId = Session::get('current_branch_id');

        if (!$branchId) {
            return null;
        }

        return Branch::find($branchId);
    }

    public function setCurrentBranch(int $branchId): void
    {
        $branch = Branch::findOrFail($branchId);

        if (auth()->check() && !auth()->user()->canAccessBranch($branch)) {
            abort(403, 'No tienes permiso para acceder a esta sucursal.');
        }

        Session::put('current_branch_id', $branch->id);
    }

    public function getAvailableBranches()
    {
        if (!auth()->check()) {
            return collect();
        }

        $user = auth()->user();

        if ($user->is_admin) {
            return Branch::active()->orderBy('name')->get();
        }

        return $user->branches()->active()->orderBy('name')->get();
    }

    public function hasMultipleBranches(): bool
    {
        return $this->getAvailableBranches()->count() > 1;
    }
}
