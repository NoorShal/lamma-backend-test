<?php

namespace App\Http\Requests;

use App\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'description' => 'nullable|string',
            'sku' => 'required|string|unique:products,sku',
            'type' => ['required', Rule::in(ProductType::values())],
            'price' => 'required_if:type,simple|nullable|numeric|min:0',
            'is_active' => 'boolean',
            'variations' => 'required_if:type,variable|array',
            'variations.*.sku' => 'required_with:variations|string|unique:product_variations,sku',
            'variations.*.price' => 'required_with:variations|numeric|min:0',
            'variations.*.attributes' => 'required_with:variations|array',
            'variations.*.attributes.*.name' => 'required|string',
            'variations.*.attributes.*.value' => 'required|string',
        ];
    }
}
