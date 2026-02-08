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
            'rule_type' => 'required|in:flat,weight_based,price_based,free',
            'config' => 'required|array',
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
            'rule_type' => 'sometimes|in:flat,weight_based,price_based,free',
            'config' => 'sometimes|array',
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
            'active' => 'boolean',
        ]);

        $method = ShippingMethod::create($data);

        return response()->json([
            'message' => 'Shipping method created successfully',
            'method' => $method
        ], 201);
    }
}

