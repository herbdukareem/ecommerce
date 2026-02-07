<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Manage checkout flows: shipping quote and placing orders.
 */
class CheckoutController extends Controller
{
    /**
     * Return shipping quotes based on destination and cart contents.
     */
    public function quoteShipping(Request $request)
    {
        $request->validate([
            'destination.zip' => 'required|string',
            'destination.state' => 'nullable|string',
            'destination.country' => 'required|string',
        ]);

        // TODO: retrieve cart and call ShippingRateService to compute quotes.
        return response()->json([
            'quotes' => [
                ['method' => 'standard', 'amount' => 1000],
                ['method' => 'express', 'amount' => 2000],
            ],
        ]);
    }

    /**
     * Place an order (reserve inventory).
     */
    public function placeOrder(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'payment_method' => 'required|string',
        ]);

        // TODO: implement order creation, inventory reservation, payment initiation.
        return response()->json(['message' => 'Order placed'], 201);
    }
}