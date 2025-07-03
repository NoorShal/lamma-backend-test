<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends ApiController
{
    public function __construct(private ProductService $productService)
    {
    }

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['type', 'name', 'min_price', 'max_price', 'per_page']);
            $products = $this->productService->getFilteredProducts($filters);

            return $this->successResponse(ProductResource::collection($products));
        } catch (\Exception $e) {
            return $this->errorResponse('Error fetching products', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $product = $this->productService->createProduct($request->validated());

            return $this->successResponse(
                new ProductResource($product),
                'Product created successfully',
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Error creating product', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function show(Product $product): JsonResponse
    {
        try {
            $product->load('variations.attributes');

            return $this->successResponse(new ProductResource($product));
        } catch (\Exception $e) {
            return $this->errorResponse('Error fetching product', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        try {
            $updatedProduct = $this->productService->updateProduct($product, $request->validated());

            return $this->successResponse(
                new ProductResource($updatedProduct),
                'Product updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Error updating product', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function destroy(Product $product): JsonResponse
    {
        try {
            $this->productService->deleteProduct($product);

            return $this->successResponse(null, 'Product deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Error deleting product', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
}
