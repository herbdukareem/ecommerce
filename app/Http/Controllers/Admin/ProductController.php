<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Sku;
use App\Models\SkuImage;
use App\Models\Category;
use App\Services\BasketProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function __construct(private readonly BasketProductService $basketProductService)
    {
    }

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
            ->with(['vendor', 'categories', 'skus.images', 'images', 'basketComponents.componentSku.product', 'basketComponents.componentSku.stocks', 'basketComponents.unit'])
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
            if ($product->isBasket()) {
                $product->setAttribute('available_stock', $this->basketProductService->availableQuantity($product));
                $product->setAttribute('estimated_component_cost', $this->basketProductService->estimatedComponentCost($product));
            } else {
                $product->setAttribute('available_stock', max(0, (int) ($product->available_stock ?? 0)));
            }
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
            'product_type' => 'sometimes|in:simple,variant,basket',
            'pay_on_delivery_enabled' => 'sometimes|boolean',
            'has_options' => 'sometimes|boolean',
            'category_id' => 'required|exists:categories,id',
            'sku' => 'nullable|string|max:100',
            'images' => 'nullable|array',
            'images.*' => 'image|max:10240', // 10MB max
            'variants' => 'nullable|string', // JSON string
            'basket_components' => 'nullable',
        ]);

        $product = DB::transaction(function () use ($data, $request) {
            // Get authenticated user as vendor
            $vendorId = $request->user()->id;

            // Create product
            $productType = $data['product_type'] ?? ((bool) ($data['has_options'] ?? false) ? Product::TYPE_VARIANT : Product::TYPE_SIMPLE);
            $hasOptions = $productType === Product::TYPE_VARIANT || (bool) ($data['has_options'] ?? false);
            if ($productType === Product::TYPE_BASKET) {
                $hasOptions = false;
            }

            $product = Product::create([
                'vendor_id' => $vendorId,
                'title' => $data['name'], // Keep title for backward compatibility
                'name' => $data['name'],
                'slug' => Str::slug($data['name']) . '-' . Str::random(6),
                'description' => $data['description'] ?? null,
                'base_price' => $data['price'], // Keep base_price for backward compatibility
                'price' => $data['price'],
                'status' => $data['status'],
                'has_options' => $hasOptions,
                'product_type' => $productType,
                'pay_on_delivery_enabled' => (bool) ($data['pay_on_delivery_enabled'] ?? false),
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

            if ($product->isBasket()) {
                Sku::create([
                    'product_id' => $product->id,
                    'sku_code' => $data['sku'] ?? 'BASKET-' . strtoupper(Str::random(8)),
                    'option_label' => null,
                    'price' => $data['price'],
                    'stock_quantity' => 0,
                    'sort_order' => 0,
                    'active' => true,
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]);

                $this->basketProductService->syncComponents($product, $this->decodeBasketComponents($request->input('basket_components')));
            } elseif (!empty($variants)) {
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
                        'stock_quantity' => 0,
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

            return $product->load(['vendor', 'categories', 'skus.images', 'basketComponents.componentSku.product', 'basketComponents.unit']);
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
        $product = Product::with(['vendor', 'categories', 'skus.stocks', 'skus.images', 'reviews', 'images', 'basketComponents.componentSku.product', 'basketComponents.componentSku.stocks', 'basketComponents.unit'])
            ->findOrFail($id);

        if ($product->isBasket()) {
            $product->setAttribute('available_stock', $this->basketProductService->availableQuantity($product));
            $product->setAttribute('estimated_component_cost', $this->basketProductService->estimatedComponentCost($product));
        }

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
            'product_type' => 'sometimes|in:simple,variant,basket',
            'pay_on_delivery_enabled' => 'sometimes|boolean',
            'has_options' => 'sometimes|boolean',
            'category_id' => 'sometimes|exists:categories,id',
            'sku' => 'nullable|string|max:100',
            'images' => 'nullable|array',
            'images.*' => 'image|max:10240',
            'variants' => 'nullable|string',
            'basket_components' => 'nullable',
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
            if (isset($data['product_type'])) {
                $updateData['product_type'] = $data['product_type'];
                $updateData['has_options'] = $data['product_type'] === Product::TYPE_VARIANT;
            }
            if (array_key_exists('pay_on_delivery_enabled', $data)) {
                $updateData['pay_on_delivery_enabled'] = (bool) $data['pay_on_delivery_enabled'];
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
            if ($product->fresh()->isBasket()) {
                $parentSku = $product->skus()->where('active', true)->orderBy('id')->first();
                if (!$parentSku) {
                    Sku::create([
                        'product_id' => $product->id,
                        'sku_code' => $data['sku'] ?? 'BASKET-' . strtoupper(Str::random(8)),
                        'option_label' => null,
                        'price' => $data['price'] ?? $product->price,
                        'stock_quantity' => 0,
                        'sort_order' => 0,
                        'active' => true,
                        'created_by' => $request->user()->id,
                        'updated_by' => $request->user()->id,
                    ]);
                } else {
                    $parentSku->update([
                        'sku_code' => $data['sku'] ?? $parentSku->sku_code,
                        'price' => $data['price'] ?? $parentSku->price,
                        'updated_by' => $request->user()->id,
                    ]);
                }

                if ($request->has('basket_components')) {
                    $this->basketProductService->syncComponents($product->fresh(), $this->decodeBasketComponents($request->input('basket_components')));
                }
            } else {
                $product->basketComponents()->delete();
            }

            if (!$product->fresh()->isBasket() && $request->filled('variants')) {
                $variants = $this->decodeVariants($data['variants']);
                $this->assertUniqueVariantLabels($variants);

                if ((bool) ($updateData['has_options'] ?? $product->has_options) && empty($variants)) {
                    abort(422, 'At least one product option is required when has_options is enabled.');
                }

                $this->syncVariantSkus($product->fresh(), $variants, $data, $request);
            }

            return $product->load(['vendor', 'categories', 'skus.images', 'basketComponents.componentSku.product', 'basketComponents.unit']);
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

    public function skuSearch(Request $request)
    {
        $query = Sku::query()
            ->with(['product:id,title,vendor_id,status', 'stocks:id,sku_id,on_hand,reserved'])
            ->where('active', true)
            ->whereHas('product', fn ($productQuery) => $productQuery->where('status', 'active'));

        if ($request->filled('q')) {
            $term = (string) $request->q;
            $query->where(function ($q) use ($term) {
                $q->where('sku_code', 'like', "%{$term}%")
                    ->orWhere('option_label', 'like', "%{$term}%")
                    ->orWhereHas('product', fn ($productQuery) => $productQuery->where('title', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('vendor_id')) {
            $query->whereHas('product', fn ($productQuery) => $productQuery->where('vendor_id', $request->integer('vendor_id')));
        }

        $skus = $query->limit(50)->get()->map(function (Sku $sku) {
            return [
                'id' => $sku->id,
                'sku_id' => $sku->id,
                'sku_code' => $sku->sku_code,
                'label' => trim(($sku->product?->title ?? 'Product') . ' - ' . $sku->display_label),
                'product_id' => $sku->product_id,
                'product_title' => $sku->product?->title,
                'vendor_id' => $sku->product?->vendor_id,
                'price' => (float) $sku->price,
                'cost' => (float) ($sku->cost_price ?? $sku->cost ?? 0),
                'unit' => $sku->unit,
                'available_stock' => $sku->stocks->sum(fn ($stock) => max(0, (int) $stock->on_hand - (int) $stock->reserved)),
            ];
        })->values();

        return response()->json(['skus' => $skus]);
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

    protected function syncVariantSkus(Product $product, array $variants, array $data, Request $request): void
    {
        $seenSkuIds = [];

        foreach ($variants as $index => $variant) {
            $label = (string) ($variant['label'] ?? $variant['name'] ?? 'Option ' . ($index + 1));
            $skuCode = trim((string) ($variant['sku'] ?? ''));
            if ($skuCode === '') {
                $skuCode = 'SKU-' . strtoupper(Str::random(8));
            }

            $sku = null;
            if (!empty($variant['id'])) {
                $sku = $product->skus()->whereKey((int) $variant['id'])->first();
            }

            if (!$sku) {
                $sku = $product->skus()->where('sku_code', $skuCode)->first();
            }

            $costPrice = array_key_exists('cost_price', $variant) ? $variant['cost_price'] : $sku?->cost_price;
            $cost = array_key_exists('cost_price', $variant) ? ($variant['cost_price'] ?? 0) : ($sku?->cost ?? 0);

            $payload = [
                'sku_code' => $skuCode,
                'option_label' => $label,
                'option_code' => array_key_exists('option_code', $variant) ? $variant['option_code'] : $sku?->option_code,
                'price' => $variant['price'] ?? $data['price'] ?? $product->price,
                'compare_at_price' => array_key_exists('compare_at_price', $variant) ? $variant['compare_at_price'] : $sku?->compare_at_price,
                'cost' => $cost,
                'cost_price' => $costPrice,
                'stock_quantity' => 0,
                'low_stock_threshold' => array_key_exists('low_stock_threshold', $variant) ? (int) $variant['low_stock_threshold'] : $sku?->low_stock_threshold,
                'weight' => $variant['weight'] ?? $sku?->weight ?? 0,
                'unit' => array_key_exists('unit', $variant) ? $variant['unit'] : $sku?->unit,
                'image_path' => array_key_exists('image_path', $variant) ? $variant['image_path'] : $sku?->image_path,
                'sort_order' => isset($variant['sort_order']) ? (int) $variant['sort_order'] : $index,
                'active' => isset($variant['is_active']) ? (bool) $variant['is_active'] : true,
                'attributes' => ['name' => $label],
                'metadata' => $variant['metadata'] ?? null,
                'updated_by' => $request->user()->id,
            ];

            if ($sku) {
                $sku->update($payload);
            } else {
                $sku = Sku::create(array_merge($payload, [
                    'product_id' => $product->id,
                    'created_by' => $request->user()->id,
                ]));
            }

            $seenSkuIds[] = $sku->id;
        }

        if (!empty($seenSkuIds)) {
            $product->skus()
                ->whereNotIn('id', $seenSkuIds)
                ->update([
                    'active' => false,
                    'stock_quantity' => 0,
                    'updated_by' => $request->user()->id,
                ]);
        }
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

    protected function decodeBasketComponents($raw): array
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

