<?php

namespace App\Filters\Product;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;

class StatusFilter extends BaseFilter
{
    public function apply(Builder $query): void
    {
        $query->where('status', $this->value);
    }
}
