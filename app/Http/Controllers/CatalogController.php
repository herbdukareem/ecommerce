<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Handle public catalog queries and Reach Filter.
 */
class CatalogController extends Controller
{
    /**
     * List products with filters and facets.
     */
    public function index(Request $request)
    {
        // TODO: implement ReachFilterQueryBuilder to handle filters.
        // For now return paginated products.
        $products = Product::with('skus')->paginate(20);

        return response()->json($products);
    }

    /**
     * Show a single product by slug.
     */
    public function show($slug)
    {
        $product = Product::where('slug', $slug)->with(['skus', 'attributes', 'categories'])->firstOrFail();
        return response()->json($product);
    }

    /**
     * List all categories for filter UI.
     */
    public function categories()
    {
        return response()->json(\App\Models\Category::all());
    }

    /**
     * List all attributes for filter UI.
     */
    public function attributes()
    {
        return response()->json(\App\Models\Attribute::with('values')->get());
    }
}