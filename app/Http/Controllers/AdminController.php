<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin dashboard and management endpoints
 */
class AdminController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function dashboard(Request $request)
    {
        $period = $request->get('period', '30'); // days

        $stats = [
            'total_revenue' => Order::where('payment_status', 'paid')
                ->where('created_at', '>=', now()->subDays($period))
                ->sum('total'),
            
            'total_orders' => Order::where('created_at', '>=', now()->subDays($period))->count(),
            
            'pending_orders' => Order::where('status', 'pending')->count(),
            
            'total_customers' => User::where('created_at', '>=', now()->subDays($period))->count(),
            
            'total_products' => Product::count(),
            
            'low_stock_products' => DB::table('stocks')
                ->select('sku_id')
                ->groupBy('sku_id')
                ->havingRaw('SUM(available) < 10')
                ->count(),
            
            'pending_reviews' => Review::where('is_approved', false)->count(),
            
            'average_order_value' => Order::where('payment_status', 'paid')
                ->where('created_at', '>=', now()->subDays($period))
                ->avg('total'),
        ];

        return response()->json($stats);
    }

    /**
     * Get sales data for charts
     */
    public function salesData(Request $request)
    {
        $period = $request->get('period', '30'); // days
        $groupBy = $request->get('group_by', 'day'); // day, week, month

        $dateFormat = match($groupBy) {
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $sales = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays($period))
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('AVG(total) as avg_order_value')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return response()->json($sales);
    }

    /**
     * Get top selling products
     */
    public function topProducts(Request $request)
    {
        $limit = $request->get('limit', 10);
        $period = $request->get('period', '30');

        $products = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('skus', 'order_items.sku_id', '=', 'skus.id')
            ->join('products', 'skus.product_id', '=', 'products.id')
            ->where('orders.created_at', '>=', now()->subDays($period))
            ->where('orders.payment_status', 'paid')
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.quantity * order_items.price_snapshot) as revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get();

        return response()->json($products);
    }

    /**
     * Get recent orders
     */
    public function recentOrders(Request $request)
    {
        $limit = $request->get('limit', 20);

        $orders = Order::with(['user', 'items.sku.product'])
            ->latest('created_at')
            ->limit($limit)
            ->get();

        return response()->json($orders);
    }

    /**
     * Get all users with pagination
     */
    public function users(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->withCount('orders')
            ->latest()
            ->paginate(20);

        return response()->json($users);
    }

    /**
     * Get all products with pagination (admin view)
     */
    public function products(Request $request)
    {
        $query = Product::with(['category', 'skus']);

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->latest()->paginate(20);

        return response()->json($products);
    }
}

