<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class VendorController extends Controller
{
    /**
     * Display a listing of vendors
     */
    public function index(Request $request)
    {
        $query = User::role('Vendor')
            ->withCount(['products' => function($q) {
                $q->where('status', 'active');
            }]);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        $vendors = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json($vendors);
    }

    /**
     * Store a newly created vendor
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'is_verified' => 'boolean',
            'status' => 'nullable|in:active,inactive,suspended',
        ]);

        $vendor = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'is_vendor' => true,
            'status' => $data['status'] ?? 'active',
            'email_verified_at' => ($data['is_verified'] ?? false) ? now() : null,
        ]);

        $vendor->assignRole('Vendor');

        return response()->json([
            'message' => 'Vendor created successfully',
            'vendor' => $vendor->load('roles')
        ], 201);
    }

    /**
     * Display the specified vendor
     */
    public function show($id)
    {
        $vendor = User::role('Vendor')
            ->with(['products' => function($q) {
                $q->latest()->limit(10);
            }])
            ->withCount('products')
            ->findOrFail($id);

        return response()->json($vendor);
    }

    /**
     * Update the specified vendor
     */
    public function update(Request $request, $id)
    {
        $vendor = User::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'is_verified' => 'sometimes|boolean',
            'status' => 'sometimes|in:active,inactive,suspended',
        ]);

        // Handle password separately
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Handle verification status
        if (isset($data['is_verified'])) {
            $data['email_verified_at'] = $data['is_verified'] ? now() : null;
            unset($data['is_verified']);
        }

        $vendor->update($data);

        return response()->json([
            'message' => 'Vendor updated successfully',
            'vendor' => $vendor
        ]);
    }

    /**
     * Remove the specified vendor
     */
    public function destroy($id)
    {
        $vendor = User::findOrFail($id);
        
        // Check if vendor has products
        if ($vendor->products()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete vendor with existing products'
            ], 422);
        }

        $vendor->delete();

        return response()->json([
            'message' => 'Vendor deleted successfully'
        ]);
    }

    /**
     * Approve/verify a vendor
     */
    public function approve($id)
    {
        $vendor = User::findOrFail($id);
        $vendor->update(['email_verified_at' => now()]);

        return response()->json([
            'message' => 'Vendor approved successfully',
            'vendor' => $vendor
        ]);
    }

    /**
     * Get vendor's products
     */
    public function products($id, Request $request)
    {
        $vendor = User::findOrFail($id);
        
        $products = $vendor->products()
            ->with(['categories', 'skus'])
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json($products);
    }
}

