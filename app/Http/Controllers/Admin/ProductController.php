<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Sku;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        $query = Product::with(['vendor', 'categories', 'skus', 'images']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by vendor
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate($request->get('per_page', 20));

        return response()->json($products);
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:active,draft,archived',
            'category_id' => 'required|exists:categories,id',
            'sku' => 'nullable|string|max:100',
            'images' => 'nullable|array',
            'images.*' => 'image|max:10240', // 10MB max
            'variants' => 'nullable|string', // JSON string
        ]);

        $product = DB::transaction(function () use ($data, $request) {
            // Get authenticated user as vendor
            $vendorId = $request->user()->id;

            // Create product
            $product = Product::create([
                'vendor_id' => $vendorId,
                'title' => $data['name'], // Keep title for backward compatibility
                'name' => $data['name'],
                'slug' => Str::slug($data['name']) . '-' . Str::random(6),
                'description' => $data['description'] ?? null,
                'base_price' => $data['price'], // Keep base_price for backward compatibility
                'price' => $data['price'],
                'status' => $data['status'],
            ]);

            // Attach category (single category)
            $product->categories()->attach($data['category_id']);

            // Handle image uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('products', 'public');
                    $imageUrl = Storage::url($path);

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $path,
                        'image_url' => $imageUrl,
                        'is_primary' => $index === 0,
                        'order' => $index,
                    ]);

                    // Store first image as product image for backward compatibility
                    if ($index === 0) {
                        $product->update(['image' => $imageUrl]);
                    }
                }
            }

            // Handle variants
            $variants = $request->filled('variants') ? json_decode($data['variants'], true) : [];

            if (!empty($variants)) {
                foreach ($variants as $variant) {
                    Sku::create([
                        'product_id' => $product->id,
                        'sku_code' => $variant['sku'] ?? 'SKU-' . strtoupper(Str::random(8)),
                        'price' => $variant['price'] ?? $data['price'],
                        'stock_quantity' => $variant['stock'] ?? 0,
                        'weight' => $variant['weight'] ?? 0,
                        'active' => true,
                        'attributes' => json_encode(['name' => $variant['name'] ?? '']),
                    ]);
                }
            } else {
                // Create default SKU if no variants provided
                Sku::create([
                    'product_id' => $product->id,
                    'sku_code' => $data['sku'] ?? 'SKU-' . strtoupper(Str::random(8)),
                    'price' => $data['price'],
                    'stock_quantity' => 0,
                    'active' => true,
                ]);
            }

            return $product->load(['vendor', 'categories', 'skus']);
        });

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
    }

    /**
     * Display the specified product
     */
    public function show($id)
    {
        $product = Product::with(['vendor', 'categories', 'skus.stocks', 'reviews', 'images'])
            ->findOrFail($id);

        return response()->json($product);
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:active,draft,archived',
            'category_id' => 'sometimes|exists:categories,id',
            'sku' => 'nullable|string|max:100',
            'images' => 'nullable|array',
            'images.*' => 'image|max:10240',
            'variants' => 'nullable|string',
        ]);

        $product = DB::transaction(function () use ($product, $data, $request) {
            // Update basic fields
            $updateData = [];
            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
                $updateData['title'] = $data['name']; // Keep title in sync
            }
            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }
            if (isset($data['price'])) {
                $updateData['price'] = $data['price'];
                $updateData['base_price'] = $data['price']; // Keep base_price in sync
            }
            if (isset($data['status'])) {
                $updateData['status'] = $data['status'];
            }

            $product->update($updateData);

            // Update category if provided
            if (isset($data['category_id'])) {
                $product->categories()->sync([$data['category_id']]);
            }

            // Handle image uploads
            if ($request->has('keep_image_ids') || $request->hasFile('images')) {
                // Get IDs of images to keep
                $keepImageIds = $request->has('keep_image_ids')
                    ? json_decode($request->input('keep_image_ids'), true)
                    : [];

                // Delete images that are not in the keep list
                foreach ($product->images as $oldImage) {
                    if (!in_array($oldImage->id, $keepImageIds)) {
                        Storage::disk('public')->delete($oldImage->image_path);
                        $oldImage->delete();
                    }
                }

                // Upload new images
                if ($request->hasFile('images')) {
                    $currentImageCount = count($keepImageIds);

                    foreach ($request->file('images') as $index => $image) {
                        $path = $image->store('products', 'public');
                        $imageUrl = Storage::url($path);

                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_path' => $path,
                            'image_url' => $imageUrl,
                            'is_primary' => ($currentImageCount === 0 && $index === 0),
                            'order' => $currentImageCount + $index,
                        ]);

                        // Update first image as product image for backward compatibility
                        if ($currentImageCount === 0 && $index === 0) {
                            $product->update(['image' => $imageUrl]);
                        }
                    }
                }

                // Update primary image if we have kept images
                if (count($keepImageIds) > 0 && !$request->hasFile('images')) {
                    $primaryImage = $product->images()->where('is_primary', true)->first();
                    if ($primaryImage) {
                        $product->update(['image' => $primaryImage->image_url]);
                    }
                }
            }

            // Handle variants update
            if ($request->filled('variants')) {
                $variants = json_decode($data['variants'], true);

                // Delete existing SKUs and create new ones
                $product->skus()->delete();

                if (!empty($variants)) {
                    foreach ($variants as $variant) {
                        Sku::create([
                            'product_id' => $product->id,
                            'sku_code' => $variant['sku'] ?? 'SKU-' . strtoupper(Str::random(8)),
                            'price' => $variant['price'] ?? $data['price'],
                            'stock_quantity' => $variant['stock'] ?? 0,
                            'weight' => $variant['weight'] ?? 0,
                            'active' => true,
                            'attributes' => json_encode(['name' => $variant['name'] ?? '']),
                        ]);
                    }
                }
            }

            return $product->load(['vendor', 'categories', 'skus']);
        });

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product
        ]);
    }

    /**
     * Remove the specified product
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * Bulk update products
     */
    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'action' => 'required|in:activate,deactivate,delete',
        ]);

        DB::transaction(function () use ($data) {
            $products = Product::whereIn('id', $data['product_ids']);

            switch ($data['action']) {
                case 'activate':
                    $products->update(['status' => 'active']);
                    break;
                case 'deactivate':
                    $products->update(['status' => 'draft']);
                    break;
                case 'delete':
                    $products->delete();
                    break;
            }
        });

        return response()->json([
            'message' => 'Products updated successfully'
        ]);
    }
}

