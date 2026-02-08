<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function index()
    {
        $stats = [
            'total_products' => Product::count(),
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total'),
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'total_customers' => User::whereHas('roles', function ($query) {
                $query->where('name', 'Customer');
            })->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Get sales data for chart (last 7 days)
     */
    public function salesData()
    {
        $salesData = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(7))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as total'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'sales' => (float) $item->total,
                    'orders' => (int) $item->orders,
                ];
            });

        // Fill in missing dates with zero values
        $result = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $existing = $salesData->firstWhere('date', $date);
            
            $result[] = [
                'date' => $date,
                'sales' => $existing ? $existing['sales'] : 0,
                'orders' => $existing ? $existing['orders'] : 0,
            ];
        }

        return response()->json($result);
    }

    /**
     * Get top selling products
     */
    public function topProducts()
    {
        $topProducts = Product::select('products.*')
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as total_sold')
            ->leftJoin('skus', 'products.id', '=', 'skus.product_id')
            ->leftJoin('order_items', 'skus.id', '=', 'order_items.sku_id')
            ->leftJoin('orders', function ($join) {
                $join->on('order_items.order_id', '=', 'orders.id')
                     ->where('orders.payment_status', '=', 'paid');
            })
            ->groupBy('products.id', 'products.name', 'products.price', 'products.image', 'products.slug', 'products.description', 'products.vendor_id', 'products.created_at', 'products.updated_at')
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image' => $product->image,
                    'total_sold' => (int) $product->total_sold,
                    'revenue' => $product->price * $product->total_sold,
                ];
            });

        return response()->json($topProducts);
    }

    /**
     * Get recent orders
     */
    public function recentOrders()
    {
        $recentOrders = Order::with(['user:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'user' => [
                        'name' => $order->user->name ?? 'Guest',
                        'email' => $order->user->email ?? '',
                    ],
                    'total' => $order->total,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'created_at' => $order->created_at->toISOString(),
                ];
            });

        return response()->json($recentOrders);
    }
}

