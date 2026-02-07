<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Handle public catalog queries and Reach Filter.
 */
class CatalogController extends Controller
{
    /**
     * List products with advanced filters and facets.
     */
    public function index(Request $request)
    {
        $query = Product::query()
            ->with(['skus.stocks', 'categories'])
            ->where('status', 'active');

        // Search query
        if ($request->filled('q')) {
            $searchTerm = $request->q;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
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
                'skus.attributeValues.attribute',
                'attributes.values',
                'categories',
                'vendor'
            ])
            ->firstOrFail();

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
        $categories = Cache::remember('categories_tree', 3600, function () {
            return Category::with('children')
                ->whereNull('parent_id')
                ->withCount('products')
                ->get();
        });

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

        $results = Product::where('status', 'active')
            ->where(function ($query) use ($request) {
                $query->where('title', 'like', "%{$request->q}%")
                      ->orWhere('description', 'like', "%{$request->q}%");
            })
            ->select('id', 'title', 'slug', 'base_price')
            ->limit(10)
            ->get();

        return response()->json($results);
    }
}