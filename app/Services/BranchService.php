<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class BranchService
{
    /**
     * Sucursal activa de la sesion, solo si sigue activa y el usuario conserva acceso a ella.
     * El resultado se guarda en los atributos de la peticion para no repetir consultas.
     */
    public function getCurrentBranch(): ?Branch
    {
        $attributes = request()->attributes;
        $branchId = Session::get('current_branch_id');
        $key = $branchId . '|' . auth()->id();

        if ($attributes->get('current_branch_key') === $key) {
            return $attributes->get('current_branch');
        }

        $branch = $branchId ? Branch::active()->find($branchId) : null;
        $user = auth()->user();

        if ($branch && $user && !$user->canAccessBranch($branch)) {
            $branch = null;
        }

        if ($branchId && !$branch) {
            Session::forget('current_branch_id');
            $key = '|' . auth()->id();
        }

        $attributes->set('current_branch_key', $key);
        $attributes->set('current_branch', $branch);

        return $branch;
    }

    public function getCurrentBranchId(): ?int
    {
        return $this->getCurrentBranch()?->id;
    }

    public function setCurrentBranch(int $branchId): void
    {
        $branch = Branch::findOrFail($branchId);

        if (!$branch->is_active) {
            abort(403, 'La sucursal seleccionada esta inactiva.');
        }

        if (auth()->check() && !auth()->user()->canAccessBranch($branch)) {
            abort(403, 'No tienes permiso para acceder a esta sucursal.');
        }

        Session::put('current_branch_id', $branch->id);

        request()->attributes->set('current_branch_key', $branch->id . '|' . auth()->id());
        request()->attributes->set('current_branch', $branch);
    }

    /**
     * Sucursales del usuario en orden de creación: la primera (la sede principal) es la inicial.
     */
    public function getAvailableBranches(): Collection
    {
        if (!auth()->check()) {
            return collect();
        }

        $user = auth()->user();

        if ($user->is_admin) {
            return Branch::active()->orderBy('branches.id')->get();
        }

        return $user->branches()->active()->orderBy('branches.id')->get();
    }

    public function hasMultipleBranches(): bool
    {
        return $this->getAvailableBranches()->count() > 1;
    }
}
