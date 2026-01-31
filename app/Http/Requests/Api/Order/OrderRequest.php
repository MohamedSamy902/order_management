<?php

namespace App\Http\Requests\Api\Order;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],

            'payment_method' => ['required', 'string', 'in:myfatoorah,tabby,tamara'],

            'billing_address' => ['required', 'array'],
            'billing_address.name' => ['required', 'string', 'max:255'],
            'billing_address.phone' => ['required', 'string', 'max:20'],
            'billing_address.email' => ['nullable', 'email'],
            'billing_address.city' => ['required', 'string', 'max:100'],
            'billing_address.address' => ['required', 'string', 'max:500'],
            'billing_address.zip' => ['nullable', 'string', 'max:20'],

            'shipping_address' => ['nullable', 'array'],
            'shipping_address.name' => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.phone' => ['required_with:shipping_address', 'string', 'max:20'],
            'shipping_address.city' => ['required_with:shipping_address', 'string', 'max:100'],
            'shipping_address.address' => ['required_with:shipping_address', 'string', 'max:500'],
            'shipping_address.zip' => ['nullable', 'string', 'max:20'],

            'notes' => ['nullable', 'string', 'max:1000'],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => __('Order items are required'),
            'items.min' => __('Order must contain at least one item'),
            'items.*.product_id.required' => __('Product ID is required for each item'),
            'items.*.product_id.exists' => __('One or more products do not exist'),
            'items.*.quantity.required' => __('Quantity is required for each item'),
            'items.*.quantity.min' => __('Quantity must be at least 1'),

            'payment_method.required' => __('Payment method is required'),
            'payment_method.in' => __('Invalid payment method. Supported methods: myfatoorah, tabby, tamara'),

            'billing_address.required' => __('Billing address is required'),
            'billing_address.name.required' => __('Billing name is required'),
            'billing_address.phone.required' => __('Billing phone is required'),
            'billing_address.city.required' => __('Billing city is required'),
            'billing_address.address.required' => __('Billing address is required'),
        ];
    }
}
