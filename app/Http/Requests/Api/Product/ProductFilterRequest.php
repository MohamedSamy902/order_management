<?php

namespace App\Http\Requests\Api\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'in_stock' => 'nullable|in:true,false',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'search.string'    => __('Search must be a string'),
            'search.max'       => __('Search must be at most 255 characters'),
            'in_stock.boolean' => __('In stock must be a boolean'),
            'per_page.integer' => __('Per page must be an integer'),
            'per_page.min'     => __('Per page must be at least 1'),
            'per_page.max'     => __('Per page must be at most 100'),
        ];
    }

    public function attributes(): array
    {
        return [
            'search' => __('Search'),
            'in_stock' => __('In stock'),
            'per_page' => __('Per page'),
        ];
    }
}
