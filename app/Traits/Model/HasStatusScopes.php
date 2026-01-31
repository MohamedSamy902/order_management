<?php

namespace App\Traits\Model;

trait HasStatusScopes
{
    // Scope to filter by active status
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Scope to filter by inactive status
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    // Scope to filter by custom status
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
