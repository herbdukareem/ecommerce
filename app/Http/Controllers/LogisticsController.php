<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Manage shipping zones, rules, and methods. Only accessible by admins/vendors.
 */
class LogisticsController extends Controller
{
    public function zones()
    {
        // TODO: return configured shipping zones with rules.
        return response()->json(['zones' => []]);
    }

    public function storeZone(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'region' => 'required|string',
        ]);
        // TODO: create shipping zone record.
        return response()->json(['message' => 'Zone created'], 201);
    }

    public function storeZoneRule(Request $request, $id)
    {
        $request->validate([
            'rule_type' => 'required|string',
            'config' => 'required|array',
        ]);
        // TODO: attach rule to zone.
        return response()->json(['message' => 'Rule added'], 201);
    }

    public function methods()
    {
        // TODO: return available shipping methods.
        return response()->json(['methods' => [
            ['name' => 'standard'],
            ['name' => 'express'],
        ]]);
    }
}