<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Mail\ShippingUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Manage orders for customers and vendors/admin.
 */
class OrderController extends Controller
{
    /**
     * List orders for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Order::with(['items.sku.product', 'shippingAddress', 'payments'])
            ->where('user_id', $user->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $orders = $query->latest('placed_at')->paginate(20);

        return response()->json($orders);
    }

    /**
     * Show a single order.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::with([
            'items.sku.product',
            'shippingAddress',
            'payments',
            'fulfillments.warehouse'
        ])
        ->where('user_id', $user->id)
        ->findOrFail($id);

        return response()->json($order);
    }

    /**
     * Cancel an order (only if status is pending).
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::where('user_id', $user->id)->findOrFail($id);

        if (!in_array($order->status, ['pending', 'processing'])) {
            return response()->json([
                'message' => 'Order cannot be cancelled at this stage',
            ], 422);
        }

        DB::transaction(function () use ($order) {
            // Release reserved inventory
            foreach ($order->items as $item) {
                $stock = $item->sku->stocks()->lockForUpdate()->first();
                if ($stock && $stock->reserved >= $item->quantity) {
                    $stock->reserved -= $item->quantity;
                    $stock->save();
                }
            }

            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
            ]);
        });

        return response()->json([
            'message' => 'Order cancelled successfully',
            'order' => $order->fresh(),
        ]);
    }

    /**
     * List all orders for vendor (their products only).
     */
    public function vendorOrders(Request $request)
    {
        $user = $request->user();

        // Get orders that contain items from this vendor's products
        $query = Order::with(['items.sku.product', 'shippingAddress', 'user'])
            ->whereHas('items.sku.product', function ($q) use ($user) {
                $q->where('vendor_id', $user->id);
            });

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest('placed_at')->paginate(20);

        return response()->json($orders);
    }

    /**
     * Update order status (vendor/admin).
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,shipped,delivered,cancelled',
        ]);

        $user = $request->user();

        // Verify vendor owns products in this order
        $order = Order::whereHas('items.sku.product', function ($q) use ($user) {
            $q->where('vendor_id', $user->id);
        })->findOrFail($id);

        $order->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order->fresh(),
        ]);
    }

    /**
     * Create fulfillment for an order.
     */
    public function fulfill(Request $request, $id)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'tracking_no' => 'nullable|string',
            'shipment_provider' => 'nullable|string',
        ]);

        $user = $request->user();

        $order = Order::whereHas('items.sku.product', function ($q) use ($user) {
            $q->where('vendor_id', $user->id);
        })->findOrFail($id);

        $fulfillment = OrderFulfillment::create([
            'order_id' => $order->id,
            'warehouse_id' => $request->warehouse_id,
            'tracking_no' => $request->tracking_no,
            'shipment_provider' => $request->shipment_provider,
            'status' => 'shipped',
        ]);

        // Update order status
        $order->update(['status' => 'shipped']);

        // Send shipping update email
        $order->load(['user', 'shippingAddress']);
        Mail::to($order->user->email)->send(new ShippingUpdate($order, $fulfillment));

        return response()->json([
            'message' => 'Order fulfilled successfully',
            'fulfillment' => $fulfillment,
        ], 201);
    }
}