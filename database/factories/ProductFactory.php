<?php

namespace Database\Factories;

use App\ProductType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(ProductType::cases());

        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'sku' => strtoupper($this->faker->unique()->bothify('??-###')),
            'type' => $type,
            'price' => $type === ProductType::SIMPLE ? $this->faker->randomFloat(2, 10, 200) : null,
            'is_active' => $this->faker->boolean(90),
        ];
    }

    public function simple(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::SIMPLE,
            'price' => $this->faker->randomFloat(2, 10, 200),
        ]);
    }

    public function variable(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ProductType::VARIABLE,
            'price' => $this->faker->randomFloat(2, 20, 300),
        ]);
    }
}
