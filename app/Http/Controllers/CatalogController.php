<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\BasketProductService;

/**
 * Handle public catalog queries and Reach Filter.
 */
class CatalogController extends Controller
{
    public function __construct(private readonly BasketProductService $basketProductService)
    {
    }

    /**
     * List products with advanced filters and facets.
     */
    public function index(Request $request)
    {
        $query = Product::query()
            ->with(['skus.stocks', 'skus.images', 'categories', 'images', 'basketComponents.componentSku.product', 'basketComponents.componentSku.stocks', 'basketComponents.unit'])
            ->withCount(['reviews as review_count' => function ($q) {
                $q->where('is_approved', true);
            }])
            ->withAvg(['reviews as average_rating' => function ($q) {
                $q->where('is_approved', true);
            }], 'rating')
            ->where('status', 'active');

        // Search query
        if ($request->filled('q')) {
            $this->applySearchQuery($query, (string) $request->q);
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        // Price range filter
        if ($request->filled('price_min')) {
            $query->where('base_price', '>=', $request->price_min);
        }
        if ($request->filled('price_max')) {
            $query->where('base_price', '<=', $request->price_max);
        }

        // In stock filter
        if ($request->boolean('in_stock')) {
            $query->whereHas('skus.stocks', function ($q) {
                $q->whereRaw('on_hand - reserved > 0');
            });
        }

        // Vendor filter
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Attribute filters (e.g., attributes[color]=red,blue)
        if ($request->filled('attributes')) {
            foreach ($request->attributes as $attributeName => $values) {
                $valueArray = is_array($values) ? $values : explode(',', $values);
                $query->whereHas('skus.attributeValues', function ($q) use ($attributeName, $valueArray) {
                    $q->whereHas('attribute', function ($aq) use ($attributeName) {
                        $aq->where('name', $attributeName);
                    })->whereIn('value', $valueArray);
                });
            }
        }

        // Sorting
        $sortBy = $request->get('sort', 'newest');
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('base_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('base_price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('title', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        // Pagination
        $perPage = min($request->get('per_page', 24), 100);
        $products = $query->paginate($perPage);
        $products->getCollection()->transform(function ($product) {
            return $this->attachPurchaseMeta($product);
        });

        // Build facets for filtering UI
        $facets = $this->buildFacets($request);

        return response()->json([
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
            'facets' => $facets,
        ]);
    }

    /**
     * Build facets for the current query.
     */
    protected function buildFacets(Request $request)
    {
        // Cache facets for 5 minutes
        $cacheKey = 'facets_' . md5(json_encode($request->all()));

        return Cache::remember($cacheKey, 300, function () {
            return [
                'categories' => Category::withCount('products')->get(),
                'price_range' => [
                    'min' => Product::where('status', 'active')->min('base_price') ?? 0,
                    'max' => Product::where('status', 'active')->max('base_price') ?? 0,
                ],
            ];
        });
    }

    /**
     * Show a single product by slug.
     */
    public function show($slug)
    {
        $product = Product::where('slug', $slug)
            ->with([
                'skus.stocks.warehouse',
                'skus.images',
                'skus.attributeValues.attribute',
                'attributes.values',
                'categories',
                'images',
                'basketComponents.componentSku.product',
                'basketComponents.componentSku.stocks',
                'basketComponents.unit',
                'vendor'
            ])
            ->firstOrFail();

        $product = $this->attachPurchaseMeta($product);

        // Calculate total available stock
        $product->total_stock = $product->skus->sum(function ($sku) {
            return $sku->stocks->sum(function ($stock) {
                return $stock->on_hand - $stock->reserved;
            });
        });

        return response()->json($product);
    }

    /**
     * List all categories for filter UI.
     */
    public function categories()
    {
        // $categories = Cache::remember('categories_tree', 3600, function () {
            $categories = Category::with('children')
                ->whereNull('parent_id')
                ->withCount('products')
                ->get();
        // });

        return response()->json($categories);
    }

    /**
     * List all attributes for filter UI.
     */
    public function attributes()
    {
        $attributes = Cache::remember('attributes_with_values', 3600, function () {
            return Attribute::with('values')->get();
        });

        return response()->json($attributes);
    }

    /**
     * Search products with autocomplete.
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $results = Product::with(['skus.stocks', 'skus.images'])
            ->where('status', 'active')
            ->where(function ($query) use ($request) {
                $this->applySearchQuery($query, (string) $request->q);
            })
            ->limit(10)
            ->get()
            ->map(function ($product) {
                return $this->attachPurchaseMeta($product);
            });

        return response()->json($results);
    }

    protected function applySearchQuery($query, string $searchTerm): void
    {
        $like = '%' . $searchTerm . '%';

        $query->where(function ($q) use ($like) {
            $q->where('title', 'like', $like)
                ->orWhere('name', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhereHas('categories', function ($categoryQuery) use ($like) {
                    $categoryQuery->where('name', 'like', $like);
                })
                ->orWhereHas('vendor', function ($vendorQuery) use ($like) {
                    $vendorQuery->where('name', 'like', $like);
                })
                ->orWhereHas('skus', function ($skuQuery) use ($like) {
                    $skuQuery->where('sku_code', 'like', $like)
                        ->orWhere('option_label', 'like', $like)
                        ->orWhere('option_code', 'like', $like);
                });
        });
    }

    /**
     * Attach purchase metadata used by card-level add-to-cart logic.
     */
    protected function attachPurchaseMeta(Product $product)
    {
        $activeSkus = $product->skus->filter(function ($sku) {
            return (bool) ($sku->active ?? true);
        })->sortBy('sort_order')->values();

        $isBasket = $this->basketProductService->isBasketProduct($product);

        $inStockSkus = $activeSkus->filter(function ($sku) {
            $stockFromWarehouses = $sku->stocks->sum(function ($stock) {
                return (int) $stock->on_hand - (int) $stock->reserved;
            });

            return $stockFromWarehouses > 0;
        })->values();

        $minOptionPrice = $activeSkus->min('price');
        $maxOptionPrice = $activeSkus->max('price');
        $hasOptions = (bool) ($product->has_options ?? false);

        $basketAvailableStock = $isBasket ? $this->basketProductService->availableQuantity($product) : null;

        $product->setAttribute('product_type', $product->product_type ?? Product::TYPE_SIMPLE);
        $product->setAttribute('default_sku_id', $hasOptions ? null : optional($activeSkus->first())->id);
        $product->setAttribute('has_variants', $hasOptions);
        $product->setAttribute('has_options', $hasOptions);
        $product->setAttribute('requires_option_selection', $hasOptions);
        $product->setAttribute('option_count', $activeSkus->count());
        $product->setAttribute('min_option_price', $minOptionPrice !== null ? (float) $minOptionPrice : null);
        $product->setAttribute('max_option_price', $maxOptionPrice !== null ? (float) $maxOptionPrice : null);
        $product->setAttribute('display_price', $hasOptions ? ($minOptionPrice ?? $product->base_price) : $product->base_price);
        $product->setAttribute('basket_available_stock', $basketAvailableStock);
        $product->setAttribute('estimated_component_cost', $isBasket ? $this->basketProductService->estimatedComponentCost($product) : null);
        $product->setAttribute('is_in_stock', $isBasket ? $basketAvailableStock > 0 : $inStockSkus->isNotEmpty());
        $product->setAttribute('basket_components', $isBasket ? $product->basketComponents->map(fn ($component) => [
            'id' => $component->id,
            'component_sku_id' => $component->component_sku_id,
            'product_title' => $component->componentSku?->product?->title,
            'sku_code' => $component->componentSku?->sku_code,
            'quantity' => (float) $component->quantity,
            'available_stock' => $this->basketProductService->availableSkuStock($component->componentSku),
            'unit_name' => $component->unit?->name ?: $component->componentSku?->unit,
            'sort_order' => $component->sort_order,
            'is_required' => (bool) $component->is_required,
        ])->values() : []);

        $product->setRelation('skus', $product->skus->map(function ($sku) use ($isBasket, $basketAvailableStock) {
            $availableStock = $isBasket
                ? $basketAvailableStock
                : $sku->stocks->sum(function ($stock) {
                    return (int) $stock->on_hand - (int) $stock->reserved;
                });

            $sku->setAttribute('label', $sku->display_label);
            $sku->setAttribute('available_stock', max(0, (int) $availableStock));
            $sku->setAttribute('in_stock', $availableStock > 0);
            $sku->setAttribute('is_active', (bool) ($sku->active ?? true));

            return $sku;
        })->values());

        return $product;
    }
}
