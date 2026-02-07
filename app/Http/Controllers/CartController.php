<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Manage the shopping cart stored in the session or database.
 */
class CartController extends Controller
{
    /**
     * Show the current cart.
     */
    public function show(Request $request)
    {
        // TODO: implement persistent carts per user or session.
        return response()->json(['message' => 'Cart functionality not implemented yet']);
    }

    /**
     * Add an item to the cart.
     */
    public function addItem(Request $request)
    {
        $request->validate([
            'sku_id' => 'required|exists:skus,id',
            'quantity' => 'required|integer|min:1',
        ]);
        // TODO: add the item to user's cart and handle quantity merging.
        return response()->json(['message' => 'Item added']);
    }

    /**
     * Update an existing item quantity.
     */
    public function updateItem(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);
        // TODO: update the cart item quantity.
        return response()->json(['message' => 'Item updated']);
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem($id)
    {
        // TODO: remove item from cart.
        return response()->json(['message' => 'Item removed']);
    }
}