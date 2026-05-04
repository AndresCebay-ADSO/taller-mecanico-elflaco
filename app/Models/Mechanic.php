<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mechanic extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'hire_date',
        'branch_id',
        'is_active',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function workshopJobs()
    {
        return $this->hasMany(WorkshopJob::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeForBranch($query, ?int $branchId = null)
    {
        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }
        
        return $query->whereRaw('1 = 0');
    }
}
