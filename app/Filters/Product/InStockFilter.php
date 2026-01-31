<?php

namespace App\Filters\Product;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;

class InStockFilter extends BaseFilter
{
    public function apply(Builder $query): void
    {
        if ($this->value) {
            $query->where('stock', '>', 0);
        }
    }

    public function isEmpty(): bool
    {
        return $this->value === null || $this->value === false;
    }
}
