<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariation;
use App\ProductType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{
    private const DEFAULT_PER_PAGE = 15;
    private const MAX_PER_PAGE = 100;
    public function getFilteredProducts(array $filters): LengthAwarePaginator
    {
        $perPage = min($filters['per_page'] ?? self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE);

        return Product::query()
            ->with(['variations.attributes'])
            ->when($filters['type'] ?? null, fn(Builder $q, $type) => $q->where('type', $type))
            ->when($filters['name'] ?? null, fn(Builder $q, $name) => $q->where('name', 'like', "%{$name}%"))
            ->when(
                isset($filters['min_price']) || isset($filters['max_price']),
                fn(Builder $q) => $this->applyPriceFilter($q, $filters)
            )
            ->latest()
            ->paginate($perPage);
    }

    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            try {
                $product = Product::create($data);

                if ($this->shouldCreateVariations($product, $data)) {
                    $this->createVariations($product, $data['variations']);
                }

                return $product->load('variations.attributes');
            } catch (\Exception $e) {
                Log::error('Failed to create product', [
                    'data' => $data,
                    'error' => $e->getMessage()
                ]);
                throw new \Exception('Failed to create product: ');
            }
        });
    }

    public function updateProduct(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            try {
                $product->update($data);

                if (isset($data['variations'])) {
                    $this->syncVariations($product, $data['variations']);
                }

                return $product->fresh('variations.attributes');
            } catch (\Exception $e) {
                Log::error('Failed to update product', [
                    'product_id' => $product->id,
                    'data' => $data,
                    'error' => $e->getMessage()
                ]);
                throw new \Exception('Failed to update product: ');
            }
        });
    }

    public function deleteProduct(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            try {
                return $product->delete();
            } catch (\Exception $e) {
                Log::error('Failed to delete product', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage()
                ]);
                throw new \Exception('Failed to delete product: ');
            }
        });
    }


    private function applyPriceFilter(Builder $query, array $filters): void
    {
        $query->where(function (Builder $q) use ($filters) {
            $q->when($filters['min_price'] ?? null, fn($q, $min) => $q->where('price', '>=', $min))
                ->when($filters['max_price'] ?? null, fn($q, $max) => $q->where('price', '<=', $max));
        });
    }


    private function shouldCreateVariations(Product $product, array $data): bool
    {
        return $product->type === ProductType::VARIABLE && !empty($data['variations']);
    }

    private function createVariations(Product $product, array $variations): void
    {
        collect($variations)->each(fn($variationData) => $this->createVariation($product, $variationData));
    }

    private function syncVariations(Product $product, array $variations): void
    {
        $existingVariations = $product->variations()->get()->keyBy('sku');
        $newSkus = collect($variations)->pluck('sku');

        // Delete variations not in new data
        $product->variations()->whereNotIn('sku', $newSkus)->delete();

        // Update or create variations
        collect($variations)->each(function ($variationData) use ($product, $existingVariations) {
            $existing = $existingVariations->get($variationData['sku']);
            if ($existing) {
                $this->updateVariation($existing, $variationData);
            } else {
                $this->createVariation($product, $variationData);
            }
        });
    }

    private function createVariation(Product $product, array $variationData): ProductVariation
    {
        $variation = $product->variations()->create([
            'sku' => trim($variationData['sku']),
            'price' => $variationData['price'] ?? null,
        ]);

        $this->attachAttributes($variation, $variationData['attributes'] ?? []);

        return $variation;
    }

    private function updateVariation(ProductVariation $variation, array $variationData): ProductVariation
    {
        $variation->update([
            'sku' => trim($variationData['sku']),
            'price' => $variationData['price'] ?? $variation->price,
        ]);

        $this->attachAttributes($variation, $variationData['attributes'] ?? []);

        return $variation;
    }

    private function attachAttributes(ProductVariation $variation, array $attributes): void
    {
        if (empty($attributes)) {
            $variation->attributes()->sync([]);
            return;
        }

        // Build pivot data for attribute synce
        $attributeIds = collect($attributes)
            ->map(fn($attr) => $this->findOrCreateAttribute($attr))
            ->filter() // Remove null entries,
            ->mapWithKeys(fn($attr) => [$attr['id'] => ['value' => $attr['value']]]);

        $variation->attributes()->sync($attributeIds);
    }

    private function findOrCreateAttribute(array $attributeData): ?array
    {
        if (empty($attributeData['name']) || empty($attributeData['value'])) {
            return null;
        }

        // Normalize attribute name for consistency
        $name = strtolower(trim($attributeData['name']));

        try {
            $attribute = ProductAttribute::firstOrCreate([
                'name' => $name
            ]);

            return [
                'id' => $attribute->id,
                'value' => trim($attributeData['value']),
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to create attribute', [
                'attribute_name' => $name,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
