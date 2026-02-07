<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Manage orders for customers and vendors/admin.
 */
class OrderController extends Controller
{
    /**
     * List orders (customer) or all orders (vendor/admin).
     */
    public function index(Request $request)
    {
        // TODO: implement order listing with role checks.
        return response()->json(['message' => 'Order listing not implemented']);
    }

    /**
     * Show a single order.
     */
    public function show($id)
    {
        // TODO: load order with items and related data.
        return response()->json(['message' => 'Order detail not implemented']);
    }

    /**
     * Update order status (vendor/admin).
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string',
        ]);
        // TODO: update order status and trigger notifications.
        return response()->json(['message' => 'Order status updated']);
    }

    /**
     * Fulfill an order (vendor/admin) – creates shipments.
     */
    public function fulfill(Request $request, $id)
    {
        // TODO: split order by vendor/warehouse and create fulfillment records.
        return response()->json(['message' => 'Order fulfillment not implemented']);
    }
}