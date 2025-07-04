<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariation;
use App\Enums\ProductType;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Create attributes using factory
        $sizeAttribute = ProductAttribute::factory()->create(['name' => 'size']);
        $colorAttribute = ProductAttribute::factory()->create(['name' => 'color']);

        // Create simple products using factory
        Product::factory()->simple()->count(5)->create();

        // Create variable products with variations
        $variableProducts = Product::factory()->variable()->count(3)->create();

        foreach ($variableProducts as $product) {
            // Create 2,4 variations per variable product
            $variationCount = rand(2, 4);

            for ($i = 0; $i < $variationCount; $i++) {
                $variation = ProductVariation::factory()->create([
                    'product_id' => $product->id,
                ]);

                // Attach  attributes size and colors
                $attributes = collect([$sizeAttribute, $colorAttribute]);

                foreach ($attributes as $attribute) {
                    $values = $this->getAttributeValues($attribute->name);
                    $variation->attributes()->attach($attribute->id, [
                        'value' => $values[array_rand($values)]
                    ]);
                }
            }
        }

        // Create some specific samples products
        $this->createDemoProducts($sizeAttribute, $colorAttribute);
    }

    private function createDemoProducts($sizeAttribute, $colorAttribute): void
    {
        // Demo T-shirt
        $tshirt = Product::factory()->variable()->create([
            'name' => 'Cotton T-Shirt',
            'price' => 25.00,
        ]);

        $tshirtVariations = [
            ['price' => 25.00, 'size' => 'S', 'color' => 'Red'],
            ['price' => 25.00, 'size' => 'M', 'color' => 'Red'],
            ['price' => 27.00, 'size' => 'L', 'color' => 'Red'],
            ['price' => 25.00, 'size' => 'S', 'color' => 'Blue'],
            ['price' => 25.00, 'size' => 'M', 'color' => 'Blue'],
        ];

        foreach ($tshirtVariations as $varData) {
            $variation = ProductVariation::factory()->create([
                'product_id' => $tshirt->id,
                'price' => $varData['price'],
            ]);

            $variation->attributes()->attach($sizeAttribute->id, ['value' => $varData['size']]);
            $variation->attributes()->attach($colorAttribute->id, ['value' => $varData['color']]);
        }

        // Demo simple products
        Product::factory()->simple()->create([
            'name' => 'Wireless Mouse',
            'description' => 'High-precision wireless mouse with ergonomic design',
            'price' => 29.99,
        ]);
    }

    private function getAttributeValues(string $attributeName): array
    {
        return match($attributeName) {
            'size' => ['XS', 'S', 'M', 'L'],
            'color' => ['Red', 'Blue', 'Gray', 'Yellow'],
            default => ['Option 1', 'Option 2', 'Option 3'],
        };
    }
}
