<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function index(Request $request)
    {
        $period = max(1, (int) $request->integer('period', 30));
        $start = now()->subDays($period);
        $previousStart = now()->subDays($period * 2);

        $paidOrders = Order::query()
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $start);

        $previousRevenue = (float) Order::query()
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$previousStart, $start])
            ->sum('total');

        $revenue = (float) (clone $paidOrders)->sum('total');
        $ordersCount = Order::where('created_at', '>=', $start)->count();
        $paidOrdersCount = (clone $paidOrders)->count();
        $costOfGoods = (float) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'paid')
            ->where('orders.created_at', '>=', $start)
            ->sum('order_items.total_cost_at_sale');

        $lowStockProducts = DB::table('skus')
            ->leftJoin('stocks', 'stocks.sku_id', '=', 'skus.id')
            ->where('skus.active', true)
            ->groupBy('skus.id')
            ->havingRaw('COALESCE(SUM(stocks.on_hand - stocks.reserved), 0) <= COALESCE(MAX(skus.low_stock_threshold), 5)')
            ->get()
            ->count();

        $stats = [
            'total_products' => Product::count(),
            'total_revenue' => $revenue,
            'revenue_growth_percent' => $previousRevenue > 0 ? round((($revenue - $previousRevenue) / $previousRevenue) * 100, 1) : 0,
            'total_orders' => $ordersCount,
            'pending_orders' => Order::where('status', 'pending')->count(),
            'processing_orders' => Order::where('status', 'processing')->count(),
            'completed_orders' => Order::where('status', 'delivered')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
            'total_customers' => User::whereHas('roles', function ($query) {
                $query->where('name', 'Customer');
            })->count(),
            'average_order_value' => $paidOrdersCount > 0 ? round($revenue / $paidOrdersCount, 2) : 0,
            'gross_profit' => $revenue - $costOfGoods,
            'gross_margin_percent' => $revenue > 0 ? round((($revenue - $costOfGoods) / $revenue) * 100, 1) : 0,
            'cost_of_goods_sold' => $costOfGoods,
            'low_stock_products' => $lowStockProducts,
            'pending_reviews' => class_exists(Review::class) ? Review::where('is_approved', false)->count() : 0,
            'dispatch_pending' => Order::whereIn('delivery_status', ['pending_assignment', 'rejected', 'delivery_failed'])->count(),
            'payment_success_count' => Order::where('payment_status', 'paid')->where('created_at', '>=', $start)->count(),
            'payment_failed_count' => Order::where('payment_status', 'failed')->where('created_at', '>=', $start)->count(),
            'order_status_breakdown' => Order::query()
                ->select('status', DB::raw('COUNT(*) as count'))
                ->where('created_at', '>=', $start)
                ->groupBy('status')
                ->pluck('count', 'status'),
            'dispatch_status_breakdown' => Order::query()
                ->select('delivery_status', DB::raw('COUNT(*) as count'))
                ->where('created_at', '>=', $start)
                ->groupBy('delivery_status')
                ->pluck('count', 'delivery_status'),
        ];

        return response()->json($stats);
    }

    /**
     * Get sales data for chart (last 7 days)
     */
    public function salesData(Request $request)
    {
        $period = max(1, (int) $request->integer('period', 7));
        $salesData = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays($period))
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
        for ($i = min($period - 1, 30); $i >= 0; $i--) {
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
    public function topProducts(Request $request)
    {
        $period = max(1, (int) $request->integer('period', 30));
        $limit = max(1, (int) $request->integer('limit', 8));

        $topProducts = DB::table('products')
            ->leftJoin('skus', 'products.id', '=', 'skus.product_id')
            ->leftJoin('order_items', 'skus.id', '=', 'order_items.sku_id')
            ->leftJoin('orders', function ($join) use ($period) {
                $join->on('order_items.order_id', '=', 'orders.id')
                    ->where('orders.payment_status', '=', 'paid')
                    ->where('orders.created_at', '>=', now()->subDays($period));
            })
            ->select(
                'products.id',
                DB::raw('COALESCE(products.name, products.title) as name'),
                DB::raw('COALESCE(products.price, products.base_price, 0) as price'),
                'products.image',
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_sold'),
                DB::raw('COALESCE(SUM(order_items.quantity * order_items.price_snapshot), 0) as revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.title', 'products.price', 'products.base_price', 'products.image')
            ->orderBy('total_sold', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image' => $product->image,
                    'total_sold' => (int) $product->total_sold,
                    'revenue' => (float) $product->revenue,
                ];
            });

        return response()->json($topProducts);
    }

    /**
     * Get recent orders
     */
    public function recentOrders(Request $request)
    {
        $limit = max(1, (int) $request->integer('limit', 10));
        $recentOrders = Order::with(['user:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
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

