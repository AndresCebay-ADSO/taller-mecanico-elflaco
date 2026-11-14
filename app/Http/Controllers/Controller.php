<?php

namespace App\Http\Controllers;

use App\Services\BranchService;

abstract class Controller
{
    protected function currentBranchId(): ?int
    {
        return app(BranchService::class)->getCurrentBranchId();
    }

    /**
     * Sucursal activa obligatoria para operaciones que crean registros.
     */
    protected function requireCurrentBranchId(): int
    {
        $branchId = $this->currentBranchId();

        abort_if($branchId === null, 403, 'No tienes una sucursal activa asignada. Contacta al administrador.');

        return $branchId;
    }

    /**
     * Responde 404 si el registro pertenece a una sucursal distinta de la activa.
     */
    protected function ensureCurrentBranch(?int $branchId): void
    {
        abort_if($branchId === null || $branchId !== $this->currentBranchId(), 404);
    }
}
