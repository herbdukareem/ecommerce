<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Sku;
use App\Models\SkuImage;
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
        $availableStockSubquery = DB::table('skus')
            ->leftJoin('stocks', 'stocks.sku_id', '=', 'skus.id')
            ->whereColumn('skus.product_id', 'products.id')
            ->where('skus.active', true)
            ->selectRaw('COALESCE(SUM(CASE WHEN stocks.on_hand > stocks.reserved THEN stocks.on_hand - stocks.reserved ELSE 0 END), 0)');

        $activeSkuCountSubquery = DB::table('skus')
            ->whereColumn('skus.product_id', 'products.id')
            ->where('skus.active', true)
            ->selectRaw('COUNT(*)');

        $query = Product::query()
            ->with(['vendor', 'categories', 'skus.images', 'images'])
            ->select('products.*')
            ->selectSub($availableStockSubquery, 'available_stock')
            ->selectSub($activeSkuCountSubquery, 'active_sku_count');

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

        $products->getCollection()->transform(function (Product $product) {
            $product->setAttribute('available_stock', max(0, (int) ($product->available_stock ?? 0)));
            $product->setAttribute('active_sku_count', max(0, (int) ($product->active_sku_count ?? 0)));
            return $product;
        });

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
            'has_options' => 'sometimes|boolean',
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
                'has_options' => (bool) ($data['has_options'] ?? false),
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

            // Handle product options / variants
            $variants = $this->decodeVariants($request->input('variants'));
            $this->assertUniqueVariantLabels($variants);
            if ((bool) $product->has_options && empty($variants)) {
                abort(422, 'At least one product option is required when has_options is enabled.');
            }

            if (!empty($variants)) {
                foreach ($variants as $index => $variant) {
                    $label = (string) ($variant['label'] ?? $variant['name'] ?? 'Option ' . ($index + 1));
                    $skuCode = (string) ($variant['sku'] ?? ('SKU-' . strtoupper(Str::random(8))));

                    Sku::create([
                        'product_id' => $product->id,
                        'sku_code' => $skuCode,
                        'option_label' => $label,
                        'option_code' => $variant['option_code'] ?? null,
                        'price' => $variant['price'] ?? $data['price'],
                        'compare_at_price' => $variant['compare_at_price'] ?? null,
                        'cost' => $variant['cost_price'] ?? 0,
                        'cost_price' => $variant['cost_price'] ?? null,
                        'stock_quantity' => max(0, (int) ($variant['stock_quantity'] ?? $variant['stock'] ?? 0)),
                        'low_stock_threshold' => isset($variant['low_stock_threshold']) ? (int) $variant['low_stock_threshold'] : null,
                        'weight' => $variant['weight'] ?? 0,
                        'unit' => $variant['unit'] ?? null,
                        'image_path' => $variant['image_path'] ?? null,
                        'sort_order' => isset($variant['sort_order']) ? (int) $variant['sort_order'] : $index,
                        'active' => isset($variant['is_active']) ? (bool) $variant['is_active'] : true,
                        'attributes' => ['name' => $label],
                        'metadata' => $variant['metadata'] ?? null,
                        'created_by' => $request->user()->id,
                        'updated_by' => $request->user()->id,
                    ]);
                }
            } else {
                // Create default SKU for simple products.
                Sku::create([
                    'product_id' => $product->id,
                    'sku_code' => $data['sku'] ?? 'SKU-' . strtoupper(Str::random(8)),
                    'option_label' => null,
                    'price' => $data['price'],
                    'stock_quantity' => 0,
                    'sort_order' => 0,
                    'active' => true,
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]);
            }

            return $product->load(['vendor', 'categories', 'skus.images']);
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
        $product = Product::with(['vendor', 'categories', 'skus.stocks', 'skus.images', 'reviews', 'images'])
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
            'has_options' => 'sometimes|boolean',
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
            if (array_key_exists('has_options', $data)) {
                $updateData['has_options'] = (bool) $data['has_options'];
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
                $variants = $this->decodeVariants($data['variants']);
                $this->assertUniqueVariantLabels($variants);

                if ((bool) ($updateData['has_options'] ?? $product->has_options) && empty($variants)) {
                    abort(422, 'At least one product option is required when has_options is enabled.');
                }

                // Delete existing SKUs and create new ones
                $product->skus()->delete();

                if (!empty($variants)) {
                    foreach ($variants as $index => $variant) {
                        $label = (string) ($variant['label'] ?? $variant['name'] ?? 'Option ' . ($index + 1));
                        Sku::create([
                            'product_id' => $product->id,
                            'sku_code' => $variant['sku'] ?? 'SKU-' . strtoupper(Str::random(8)),
                            'option_label' => $label,
                            'option_code' => $variant['option_code'] ?? null,
                            'price' => $variant['price'] ?? $data['price'],
                            'compare_at_price' => $variant['compare_at_price'] ?? null,
                            'cost' => $variant['cost_price'] ?? 0,
                            'cost_price' => $variant['cost_price'] ?? null,
                            'stock_quantity' => max(0, (int) ($variant['stock_quantity'] ?? $variant['stock'] ?? 0)),
                            'low_stock_threshold' => isset($variant['low_stock_threshold']) ? (int) $variant['low_stock_threshold'] : null,
                            'weight' => $variant['weight'] ?? 0,
                            'unit' => $variant['unit'] ?? null,
                            'image_path' => $variant['image_path'] ?? null,
                            'sort_order' => isset($variant['sort_order']) ? (int) $variant['sort_order'] : $index,
                            'active' => isset($variant['is_active']) ? (bool) $variant['is_active'] : true,
                            'attributes' => ['name' => $label],
                            'metadata' => $variant['metadata'] ?? null,
                            'created_by' => $request->user()->id,
                            'updated_by' => $request->user()->id,
                        ]);
                    }
                }
            }

            return $product->load(['vendor', 'categories', 'skus.images']);
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

    public function uploadImages(Request $request, int $id)
    {
        $product = Product::with('images')->findOrFail($id);

        $data = $request->validate([
            'images' => 'required|array|min:1',
            'images.*' => 'required|image|max:10240',
        ]);

        $orderOffset = (int) $product->images()->max('order') + 1;

        foreach ($data['images'] as $index => $image) {
            $path = $image->store('products', 'public');
            $imageUrl = Storage::url($path);

            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'image_url' => $imageUrl,
                'is_primary' => $product->images()->count() === 0 && $index === 0,
                'order' => $orderOffset + $index,
            ]);
        }

        $this->syncProductPrimaryImage($product->fresh('images'));

        return response()->json([
            'message' => 'Product images uploaded successfully.',
            'images' => $product->fresh('images')->images,
        ]);
    }

    public function reorderImages(Request $request, int $id)
    {
        $product = Product::with('images')->findOrFail($id);

        $data = $request->validate([
            'image_ids' => 'required|array|min:1',
            'image_ids.*' => 'required|integer|exists:product_images,id',
        ]);

        $productImageIds = $product->images->pluck('id')->values()->all();
        $incomingIds = collect($data['image_ids'])->map(fn ($value) => (int) $value)->values()->all();

        sort($productImageIds);
        $sortedIncoming = $incomingIds;
        sort($sortedIncoming);

        if ($productImageIds !== $sortedIncoming) {
            return response()->json(['message' => 'Image set does not match this product.'], 422);
        }

        foreach ($incomingIds as $index => $imageId) {
            ProductImage::where('id', $imageId)->update(['order' => $index]);
        }

        return response()->json([
            'message' => 'Product image order updated successfully.',
            'images' => $product->fresh('images')->images,
        ]);
    }

    public function setPrimaryImage(int $id, int $imageId)
    {
        $product = Product::with('images')->findOrFail($id);
        $image = $product->images()->where('id', $imageId)->firstOrFail();

        $product->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        $this->syncProductPrimaryImage($product->fresh('images'));

        return response()->json([
            'message' => 'Primary image updated successfully.',
            'images' => $product->fresh('images')->images,
        ]);
    }

    public function deleteImage(int $id, int $imageId)
    {
        $product = Product::with('images')->findOrFail($id);
        $image = $product->images()->where('id', $imageId)->firstOrFail();

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        $remaining = $product->fresh('images');
        if ($remaining->images->isNotEmpty() && !$remaining->images->contains(fn ($entry) => (bool) $entry->is_primary)) {
            $first = $remaining->images->sortBy('order')->first();
            $first?->update(['is_primary' => true]);
        }

        $this->syncProductPrimaryImage($product->fresh('images'));

        return response()->json([
            'message' => 'Image deleted successfully.',
            'images' => $product->fresh('images')->images,
        ]);
    }

    public function uploadSkuImages(Request $request, int $productId, int $skuId)
    {
        $product = Product::query()->findOrFail($productId);
        $sku = Sku::query()->where('product_id', $product->id)->findOrFail($skuId);

        $data = $request->validate([
            'images' => 'required|array|min:1',
            'images.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
            'alt_text' => 'nullable|string|max:255',
        ]);

        $orderOffset = (int) $sku->images()->max('sort_order') + 1;
        $hasPrimary = $sku->images()->where('is_primary', true)->exists();

        foreach ($data['images'] as $index => $image) {
            $path = $image->store('skus', 'public');

            SkuImage::create([
                'sku_id' => $sku->id,
                'image_path' => $path,
                'alt_text' => $data['alt_text'] ?? null,
                'sort_order' => $orderOffset + $index,
                'is_primary' => !$hasPrimary && $index === 0,
                'created_by' => $request->user()->id,
            ]);
        }

        return response()->json([
            'message' => 'SKU images uploaded successfully.',
            'images' => $sku->fresh('images')->images,
        ]);
    }

    public function setPrimarySkuImage(Request $request, int $productId, int $skuId, int $imageId)
    {
        $product = Product::query()->findOrFail($productId);
        $sku = Sku::query()->where('product_id', $product->id)->findOrFail($skuId);
        $image = $sku->images()->where('id', $imageId)->firstOrFail();

        $sku->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return response()->json([
            'message' => 'Primary SKU image updated successfully.',
            'images' => $sku->fresh('images')->images,
        ]);
    }

    public function deleteSkuImage(Request $request, int $productId, int $skuId, int $imageId)
    {
        $product = Product::query()->findOrFail($productId);
        $sku = Sku::query()->where('product_id', $product->id)->findOrFail($skuId);
        $image = $sku->images()->where('id', $imageId)->firstOrFail();

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        $remaining = $sku->fresh('images');
        if ($remaining->images->isNotEmpty() && !$remaining->images->contains(fn ($entry) => (bool) $entry->is_primary)) {
            $first = $remaining->images->sortBy('sort_order')->first();
            $first?->update(['is_primary' => true]);
        }

        return response()->json([
            'message' => 'SKU image deleted successfully.',
            'images' => $sku->fresh('images')->images,
        ]);
    }

    public function reorderSkuImages(Request $request, int $productId, int $skuId)
    {
        $product = Product::query()->findOrFail($productId);
        $sku = Sku::query()->where('product_id', $product->id)->findOrFail($skuId);

        $data = $request->validate([
            'image_ids' => 'required|array|min:1',
            'image_ids.*' => 'required|integer|exists:sku_images,id',
        ]);

        $currentImageIds = $sku->images->pluck('id')->sort()->values()->all();
        $incoming = collect($data['image_ids'])->map(fn ($id) => (int) $id)->values()->all();
        $incomingSorted = $incoming;
        sort($incomingSorted);

        if ($currentImageIds !== $incomingSorted) {
            return response()->json(['message' => 'Image set does not match this SKU.'], 422);
        }

        foreach ($incoming as $index => $id) {
            SkuImage::query()->where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json([
            'message' => 'SKU image order updated successfully.',
            'images' => $sku->fresh('images')->images,
        ]);
    }

    protected function syncProductPrimaryImage(Product $product): void
    {
        $primary = $product->images->firstWhere('is_primary', true) ?: $product->images->sortBy('order')->first();
        $product->update(['image' => $primary?->image_url]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    protected function decodeVariants($raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        if (is_array($raw)) {
            return $raw;
        }

        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<int,array<string,mixed>> $variants
     */
    protected function assertUniqueVariantLabels(array $variants): void
    {
        $labels = collect($variants)
            ->map(fn ($variant) => strtolower(trim((string) ($variant['label'] ?? $variant['name'] ?? ''))))
            ->filter()
            ->values();

        if ($labels->count() !== $labels->unique()->count()) {
            abort(422, 'Duplicate option labels are not allowed for a product.');
        }
    }
}

