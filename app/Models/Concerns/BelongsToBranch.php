<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Registros que pertenecen a una sucursal (columna branch_id).
 */
trait BelongsToBranch
{
    /**
     * Solo los de esa sucursal. Sin sucursal no devuelve nada: así nunca se mezclan datos de otra sede.
     */
    public function scopeForBranch(Builder $query, ?int $branchId = null): Builder
    {
        return $branchId
            ? $query->where($query->qualifyColumn('branch_id'), $branchId)
            : $query->whereRaw('1 = 0');
    }
}
