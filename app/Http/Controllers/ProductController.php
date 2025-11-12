<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Enums\ProductCategoryEnum;

class ProductController extends Controller
{
    // Show All Product
    public function index()
    {
        // return response()->json(Product::all(), 200);
        $products = Product::all()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category->value,
                'price' => $product->price,
                'stock' => $product->stock,
                'description' => $product->description,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
            ];
        });

        return response()->json($products, 200);
    }

    // Adding New Product
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'category' => 'required|string|in:kopi,non_kopi,makanan',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'description' => 'nullable|string|max:255',
        ]);

        $product = Product::create([
            'name' => $validated['name'],
            'category' => ProductCategoryEnum::from($validated['category']),
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json(
            [
                'message' => 'Product created successfully',
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category->value,
                    'price' => $product->price,
                    'stock' => $product->stock,
                    'description' => $product->description,
                ],
            ],
            201,
        );
    }

    // Show one product by ID
    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found', 404]);
        }

        return response()->json(
            [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category->value,
                'price' => $product->price,
                'stock' => $product->stock,
                'description' => $product->description,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
            ],
            200,
        );
    }

    // Update product
    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found', 404]);
        }
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|string|in:kopi,non_kopi,makanan',
            'price' => 'sometimes|required|numeric',
            'stock' => 'sometimes|required|integer',
            'description' => 'nullable|string|max:255',
        ]);
        if (isset($validated['category'])) {
            $validated['category'] = ProductCategory::from($validated['category']);
        }
        $product->update($validated);
        return response()->json(
            [
                'message' => 'Product update successfully',
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category->value,
                    'price' => $product->price,
                    'stock' => $product->stock,
                    'description' => $product->description,
                ],
            ],
            200,
        );
    }

    // Delete product
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product Not Found', 404]);
        }

        $product->delete();
        return response()->json(['message' => 'Product deleted successfully', 200]);
    }
}
