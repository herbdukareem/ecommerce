<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories (tree structure)
     */
    public function index(Request $request)
    {
        if ($request->get('tree') === 'true') {
            // Return hierarchical tree structure
            $categories = Category::with('children.children')
                ->whereNull('parent_id')
                ->withCount('products')
                ->get();
        } else {
            // Return flat list with pagination
            $query = Category::with('parent')->withCount('products');

            if ($request->filled('search')) {
                $query->where('name', 'like', "%{$request->search}%");
            }

            $categories = $query->latest()->paginate($request->get('per_page', 20));
        }

        return response()->json($categories);
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $category = Category::create($data);

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('categories', 'public');
            $category->update(['image' => $path]);
        }

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category->load('parent')
        ], 201);
    }

    /**
     * Display the specified category
     */
    public function show($id)
    {
        $category = Category::with(['parent', 'children', 'products'])
            ->withCount('products')
            ->findOrFail($id);

        return response()->json($category);
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255|unique:categories,name,' . $id,
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
        ]);

        // Prevent circular reference
        if (isset($data['parent_id']) && $data['parent_id'] == $id) {
            return response()->json([
                'message' => 'Category cannot be its own parent'
            ], 422);
        }

        $category->update($data);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category->load('parent')
        ]);
    }

    /**
     * Remove the specified category
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Check if category has children
        if ($category->children()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with subcategories'
            ], 422);
        }

        // Check if category has products
        if ($category->products()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with products'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }

    /**
     * Reorder categories
     */
    public function reorder(Request $request)
    {
        $data = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.parent_id' => 'nullable|exists:categories,id',
            'categories.*.order' => 'required|integer',
        ]);

        foreach ($data['categories'] as $categoryData) {
            Category::where('id', $categoryData['id'])->update([
                'parent_id' => $categoryData['parent_id'],
                'order' => $categoryData['order'] ?? 0,
            ]);
        }

        return response()->json([
            'message' => 'Categories reordered successfully'
        ]);
    }
}

