<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderFulfillment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of orders
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.sku.product', 'shippingAddress']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $orders = $query->paginate($request->get('per_page', 20));

        return response()->json($orders);
    }

    /**
     * Display the specified order
     */
    public function show($id)
    {
        $order = Order::with([
            'user',
            'items.sku.product',
            'shippingAddress',
            'fulfillments',
            'payments'
        ])->findOrFail($id);

        return response()->json($order);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $data = $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded',
        ]);

        $order->update($data);

        // Create fulfillment record if status is shipped or delivered
        if (in_array($data['status'], ['shipped', 'delivered'])) {
            OrderFulfillment::create([
                'order_id' => $order->id,
                'status' => $data['status'],
                'notes' => $request->notes ?? null,
                'fulfilled_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order->load('fulfillments')
        ]);
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $data = $request->validate([
            'payment_status' => 'required|in:pending,paid,failed,refunded',
        ]);

        $order->update($data);

        return response()->json([
            'message' => 'Payment status updated successfully',
            'order' => $order
        ]);
    }

    /**
     * Get order statistics
     */
    public function statistics(Request $request)
    {
        $period = $request->get('period', 30); // days

        $stats = [
            'total_orders' => Order::where('created_at', '>=', now()->subDays($period))->count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'processing_orders' => Order::where('status', 'processing')->count(),
            'shipped_orders' => Order::where('status', 'shipped')->count(),
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
            'total_revenue' => Order::where('payment_status', 'paid')
                ->where('created_at', '>=', now()->subDays($period))
                ->sum('total'),
            'average_order_value' => Order::where('payment_status', 'paid')
                ->where('created_at', '>=', now()->subDays($period))
                ->avg('total'),
        ];

        return response()->json($stats);
    }

    /**
     * Export orders
     */
    public function export(Request $request)
    {
        $query = Order::with(['user', 'items.sku.product']);

        // Apply same filters as index
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $orders = $query->get();

        // Return CSV data
        return response()->json([
            'data' => $orders,
            'message' => 'Orders exported successfully'
        ]);
    }
}

