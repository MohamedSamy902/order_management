<?php

namespace App\Transformers;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

abstract class BaseTransformer
{
	/**
	 * Method used to transform a collection of items.
	 *
	 * @param Collection $items The items in a collection.
	 *
	 * @return Collection The transformed collection.
	 */
	public function transformCollection(Collection $items  , $col=null) : Collection
	{
		return $items->map(function ($item ) use ($col){
			return $this->transform($item, $col);
		});
	}

	/**
	 * Method used to transform an item.
	 *
	 * @param $item mixed The item to be transformed.
	 *
	 * @return array The transformed item.
	 */
	abstract public function transform( $item ); //: array | object;


    /**
     * Method used to transform a pagination collection of items.
     *
     * @param LengthAwarePaginator $awarePaginator
     * @return array
     */
    public function transformPaginate(LengthAwarePaginator $awarePaginator, $items = null, $col=null)
    {
        $queryParams = request()->query();

        return [
            'count'             => $awarePaginator->count(),
            'currentPage'       => $awarePaginator->currentPage(),
            'hasMorePages'      => $awarePaginator->hasMorePages(),
            'items'             => is_null($items) ? $this->transformCollection(collect($awarePaginator->items()), $col) : $items,
            'total'             => $awarePaginator->total(),
            'links'             => $awarePaginator->appends($queryParams)->links(),

        ];
    }
}
