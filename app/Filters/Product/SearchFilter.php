<?php

namespace App\Filters\Product;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;

class SearchFilter extends BaseFilter
{
    public function apply(Builder $query): void
    {
        $query->where(function ($q) {
            $q->where('name->en', 'like', "%{$this->value}%")
                ->orWhere('name->ar', 'like', "%{$this->value}%")
                ->orWhere('description->en', 'like', "%{$this->value}%")
                ->orWhere('description->ar', 'like', "%{$this->value}%");
        });
    }
}
