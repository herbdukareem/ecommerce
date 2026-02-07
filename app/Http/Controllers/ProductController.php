<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sku;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Manage products for vendors/admins.
 */
class ProductController extends Controller
{
    /**
     * List all products for the authenticated vendor.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Product::with(['skus.stocks', 'categories'])
            ->where('vendor_id', $user->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $query->where('title', 'like', "%{$request->q}%");
        }

        $products = $query->latest()->paginate(20);

        return response()->json($products);
    }

    /**
     * Store a new product.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'status' => 'sometimes|in:active,inactive,draft',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        $user = $request->user();

        $product = DB::transaction(function () use ($data, $user) {
            $product = Product::create([
                'vendor_id' => $user->id,
                'title' => $data['title'],
                'slug' => Str::slug($data['title']) . '-' . Str::random(6),
                'description' => $data['description'] ?? null,
                'base_price' => $data['base_price'],
                'status' => $data['status'] ?? 'draft',
            ]);

            // Attach categories
            if (!empty($data['category_ids'])) {
                $product->categories()->attach($data['category_ids']);
            }

            // Create default SKU
            $sku = Sku::create([
                'product_id' => $product->id,
                'sku_code' => 'SKU-' . strtoupper(Str::random(8)),
                'price' => $data['base_price'],
                'active' => true,
            ]);

            return $product;
        });

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product->load(['skus', 'categories']),
        ], 201);
    }

    /**
     * Show a single product.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        $product = Product::with(['skus.stocks', 'categories', 'attributes'])
            ->where('vendor_id', $user->id)
            ->findOrFail($id);

        return response()->json($product);
    }

    /**
     * Update a product.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        
        $product = Product::where('vendor_id', $user->id)->findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:active,inactive,draft',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        DB::transaction(function () use ($product, $data) {
            if (isset($data['title'])) {
                $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);
            }

            $product->update($data);

            // Sync categories
            if (isset($data['category_ids'])) {
                $product->categories()->sync($data['category_ids']);
            }
        });

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product->fresh(['skus', 'categories']),
        ]);
    }

    /**
     * Delete a product.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        $product = Product::where('vendor_id', $user->id)->findOrFail($id);
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }
}

