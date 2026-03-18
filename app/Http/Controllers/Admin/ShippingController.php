<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingZone;
use App\Models\ShippingZoneRule;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShippingController extends Controller
{
    /**
     * Get all shipping zones
     */
    public function zones(Request $request)
    {
        $zones = ShippingZone::with('rules')
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json($zones);
    }

    /**
     * Store a new shipping zone
     */
    public function storeZone(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'region' => 'required|string|max:255',
            'description' => 'nullable|string',
            'coverage_states' => 'nullable|array',
            'coverage_cities' => 'nullable|array',
            'coverage_areas' => 'nullable|array',
            'default_fee' => 'nullable|numeric|min:0',
            'is_fallback' => 'sometimes|boolean',
            'active' => 'sometimes|boolean',
        ]);

        $zone = ShippingZone::create($data);

        return response()->json([
            'message' => 'Shipping zone created successfully',
            'zone' => $zone
        ], 201);
    }

    /**
     * Update a shipping zone
     */
    public function updateZone(Request $request, $id)
    {
        $zone = ShippingZone::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'region' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'coverage_states' => 'nullable|array',
            'coverage_cities' => 'nullable|array',
            'coverage_areas' => 'nullable|array',
            'default_fee' => 'nullable|numeric|min:0',
            'is_fallback' => 'sometimes|boolean',
            'active' => 'sometimes|boolean',
        ]);

        $zone->update($data);

        return response()->json([
            'message' => 'Shipping zone updated successfully',
            'zone' => $zone
        ]);
    }

    /**
     * Delete a shipping zone
     */
    public function destroyZone($id)
    {
        $zone = ShippingZone::findOrFail($id);
        $zone->delete();

        return response()->json([
            'message' => 'Shipping zone deleted successfully'
        ]);
    }

    /**
     * Get rules for a specific zone
     */
    public function zoneRules($zoneId)
    {
        $rules = ShippingZoneRule::where('shipping_zone_id', $zoneId)->get();
        return response()->json($rules);
    }

    /**
     * Store a new shipping rule
     */
    public function storeRule(Request $request)
    {
        $data = $request->validate([
            'shipping_zone_id' => 'required|exists:shipping_zones,id',
            'shipping_method_id' => 'nullable|exists:shipping_methods,id',
            'rule_type' => 'required|in:flat,weight_based,price_based,free',
            'config' => 'required|array',
            'priority' => 'nullable|integer|min:1|max:1000',
            'active' => 'sometimes|boolean',
        ]);

        $rule = ShippingZoneRule::create($data);

        return response()->json([
            'message' => 'Shipping rule created successfully',
            'rule' => $rule
        ], 201);
    }

    /**
     * Update a shipping rule
     */
    public function updateRule(Request $request, $id)
    {
        $rule = ShippingZoneRule::findOrFail($id);

        $data = $request->validate([
            'shipping_method_id' => 'nullable|exists:shipping_methods,id',
            'rule_type' => 'sometimes|in:flat,weight_based,price_based,free',
            'config' => 'sometimes|array',
            'priority' => 'nullable|integer|min:1|max:1000',
            'active' => 'sometimes|boolean',
        ]);

        $rule->update($data);

        return response()->json([
            'message' => 'Shipping rule updated successfully',
            'rule' => $rule
        ]);
    }

    /**
     * Delete a shipping rule
     */
    public function destroyRule($id)
    {
        $rule = ShippingZoneRule::findOrFail($id);
        $rule->delete();

        return response()->json([
            'message' => 'Shipping rule deleted successfully'
        ]);
    }

    /**
     * Get all shipping methods
     */
    public function methods()
    {
        $methods = ShippingMethod::all();
        return response()->json($methods);
    }

    /**
     * Store a new shipping method
     */
    public function storeMethod(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:shipping_methods',
            'code' => 'nullable|string|max:100|unique:shipping_methods,code',
            'description' => 'nullable|string',
            'base_fee' => 'nullable|numeric|min:0',
            'per_kg_surcharge' => 'nullable|numeric|min:0',
            'express_surcharge' => 'nullable|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'supports_cod' => 'sometimes|boolean',
            'is_pickup' => 'sometimes|boolean',
            'active' => 'boolean',
        ]);

        if (empty($data['code'])) {
            $data['code'] = \Illuminate\Support\Str::slug($data['name'], '_');
        }

        $method = ShippingMethod::create($data);

        return response()->json([
            'message' => 'Shipping method created successfully',
            'method' => $method
        ], 201);
    }
}

