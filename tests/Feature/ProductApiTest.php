<?php

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariation;

beforeEach(function () {
    $this->artisan('migrate:fresh');
});

it('can list all products', function () {
    Product::factory()->simple()->count(3)->create();
    Product::factory()->variable()->count(2)->create();

    $response = $this->getJson('/api/products');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => ['id', 'name', 'sku', 'type', 'price', 'is_active', 'variations']
            ]
        ])
        ->assertJson(['success' => true]);
});

it('can filter products by type', function () {
    Product::factory()->simple()->count(2)->create();
    Product::factory()->variable()->count(3)->create();

    $response = $this->getJson('/api/products?type=simple');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('can filter products by name', function () {
    Product::factory()->create(['name' => 'Laptop Computer']);
    Product::factory()->create(['name' => 'Wireless Mouse']);

    $response = $this->getJson('/api/products?name=laptop');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

it('can filter products by price range', function () {
    Product::factory()->simple()->create(['price' => 10.00]);
    Product::factory()->simple()->create(['price' => 50.00]);
    Product::factory()->simple()->create(['price' => 100.00]);

    $response = $this->getJson('/api/products?min_price=20&max_price=80');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

it('can create simple product', function () {
    $productData = [
        'name' => 'Test Product',
        'description' => 'Test description',
        'sku' => 'TEST-001',
        'type' => 'simple',
        'price' => 29.99,
        'is_active' => true
    ];

    $response = $this->postJson('/api/products', $productData);

    $response->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Product created successfully'
        ]);

    $this->assertDatabaseHas('products', [
        'name' => 'Test Product',
        'sku' => 'TEST-001',
        'type' => 'simple'
    ]);
});

it('can create variable product with variations', function () {
    ProductAttribute::factory()->create(['name' => 'size']);
    ProductAttribute::factory()->create(['name' => 'color']);

    $productData = [
        'name' => 'T-Shirt',
        'description' => 'Cotton t-shirt',
        'sku' => 'TSHIRT-001',
        'type' => 'variable',
        'is_active' => true,
        'variations' => [
            [
                'sku' => 'TSHIRT-001-S-RED',
                'price' => 25.00,
                'attributes' => [
                    ['name' => 'size', 'value' => 'S'],
                    ['name' => 'color', 'value' => 'Red']
                ]
            ],
            [
                'sku' => 'TSHIRT-001-M-BLUE',
                'price' => 25.00,
                'attributes' => [
                    ['name' => 'size', 'value' => 'M'],
                    ['name' => 'color', 'value' => 'Blue']
                ]
            ]
        ]
    ];

    $response = $this->postJson('/api/products', $productData);

    $response->assertCreated()
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('products', ['name' => 'T-Shirt', 'type' => 'variable']);
    $this->assertDatabaseHas('product_variations', ['sku' => 'TSHIRT-001-S-RED']);
    $this->assertDatabaseHas('product_variations', ['sku' => 'TSHIRT-001-M-BLUE']);
});

it('validates required fields when creating product', function () {
    $response = $this->postJson('/api/products', []);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Validation failed'
        ])
        ->assertJsonValidationErrors(['name', 'sku', 'type']);
});


it('can show single product', function () {
    $product = Product::factory()->simple()->create();

    $response = $this->getJson("/api/products/{$product->id}");

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku
            ]
        ]);
});

it('returns 404 when product not found', function () {
    $response = $this->getJson('/api/products/999');

    $response->assertNotFound()
        ->assertJson([
            'success' => false,
            'message' => 'Resource not found'
        ]);
});

it('can update product', function () {
    $product = Product::factory()->simple()->create();

    $updateData = [
        'name' => 'Updated Product Name',
        'price' => 39.99
    ];

    $response = $this->putJson("/api/products/{$product->id}", $updateData);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Product updated successfully'
        ]);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Updated Product Name',
        'price' => 39.99
    ]);
});

it('can delete product', function () {
    $product = Product::factory()->simple()->create();

    $response = $this->deleteJson("/api/products/{$product->id}");

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});

it('can delete variable product with variations', function () {
    $product = Product::factory()->variable()->create();
    $variation = ProductVariation::factory()->create(['product_id' => $product->id]);

    $response = $this->deleteJson("/api/products/{$product->id}");

    $response->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
    $this->assertDatabaseMissing('product_variations', ['id' => $variation->id]);
});

it('returns 404 when deleting non existent product', function () {
    $response = $this->deleteJson('/api/products/999');

    $response->assertNotFound()
        ->assertJson([
            'success' => false,
            'message' => 'Resource not found'
        ]);
});
