<?php

namespace App\Http\Requests;

use App\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product');

        return [
            'name' => 'sometimes|required|string',
            'description' => 'nullable|string',
            'sku' => 'sometimes|required|string|unique:products,sku,' . $productId,
            'type' => ['sometimes', 'required', Rule::in(ProductType::values())],
            'price' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'variations' => 'array',
            'variations.*.id' => 'sometimes|exists:product_variations,id',
            'variations.*.sku' => 'required_with:variations|string',
            'variations.*.price' => 'required_with:variations|numeric|min:0',
            'variations.*.attributes' => 'required_with:variations|array',
            'variations.*.attributes.*.name' => 'required|string',
            'variations.*.attributes.*.value' => 'required|string',
        ];
    }
}
