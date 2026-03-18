<?php

namespace App\Http\Controllers;

use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\ShippingZoneRule;
use Illuminate\Http\Request;

/**
 * Manage shipping zones, rules, and methods. Only accessible by admins/vendors.
 */
class LogisticsController extends Controller
{
    public function zones()
    {
        $zones = ShippingZone::with('rules')->orderBy('name')->get();
        return response()->json(['zones' => $zones]);
    }

    public function storeZone(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'region' => 'required|string',
        ]);

        $zone = ShippingZone::create([
            'name' => $request->name,
            'region' => $request->region,
        ]);

        return response()->json([
            'message' => 'Zone created',
            'zone' => $zone,
        ], 201);
    }

    public function storeZoneRule(Request $request, $id)
    {
        $request->validate([
            'rule_type' => 'required|string|in:flat,weight_based,price_based,free',
            'config' => 'required|array',
        ]);

        $zone = ShippingZone::findOrFail($id);
        $rule = ShippingZoneRule::create([
            'shipping_zone_id' => $zone->id,
            'rule_type' => $request->rule_type,
            'config' => $request->config,
        ]);

        return response()->json([
            'message' => 'Rule added',
            'rule' => $rule,
        ], 201);
    }

    public function methods()
    {
        return response()->json([
            'methods' => ShippingMethod::where('active', true)->orderBy('name')->get(),
        ]);
    }
}