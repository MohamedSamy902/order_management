<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

abstract class BaseFilter
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    abstract protected function apply(Builder $query): void;

    protected function isEmpty(): bool
    {
        return $this->value === null || $this->value === '' || $this->value === [];
    }

    public function handle(Builder $query, \Closure $next): Builder
    {
        if (!$this->isEmpty()) {
            $this->apply($query);
        }

        return $next($query);
    }
}
