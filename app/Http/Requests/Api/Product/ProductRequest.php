<?php

namespace App\Http\Requests\Api\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH') || $this->isMethod('POST') && $this->route('product');

        return [
            'name' => [$isUpdate ? 'sometimes' : 'required', 'array'],
            'name.en' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'name.ar' => ['nullable', 'string', 'max:255'],

            'description' => [$isUpdate ? 'sometimes' : 'required', 'array'],
            'description.en' => [$isUpdate ? 'sometimes' : 'required', 'string'],
            'description.ar' => ['nullable', 'string'],

            'price' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'stock' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:active,inactive,out_of_stock'],

            'images' => ['sometimes', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],

            'deleted_images' => ['sometimes', 'array'],
            'deleted_images.*' => ['integer', 'exists:image_products,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Product name is required'),
            'name.array' => __('Product name must be an object with translations'),
            'name.en.required' => __('Product name in English is required'),
            'name.en.max' => __('Product name in English must not exceed 255 characters'),

            'description.required' => __('Product description is required'),
            'description.array' => __('Product description must be an object with translations'),
            'description.en.required' => __('Product description in English is required'),

            'price.required' => __('Product price is required'),
            'price.numeric' => __('Product price must be a number'),
            'price.min' => __('Product price must be at least 0'),
            'price.regex' => __('Product price must have at most 2 decimal places'),

            'stock.required' => __('Product stock is required'),
            'stock.integer' => __('Product stock must be an integer'),
            'stock.min' => __('Product stock must be at least 0'),

            'status.in' => __('Product status must be active, inactive, or out_of_stock'),

            'images.array' => __('Images must be an array'),
            'images.max' => __('You can upload at most 5 images'),
            'images.*.image' => __('Each file must be an image'),
            'images.*.mimes' => __('Images must be jpeg, png, jpg, or webp'),
            'images.*.max' => __('Each image must not exceed 2MB'),

            'deleted_images.array' => __('Deleted images must be an array'),
            'deleted_images.*.integer' => __('Each deleted image ID must be an integer'),
            'deleted_images.*.exists' => __('One or more image IDs do not exist'),
        ];
    }
}
