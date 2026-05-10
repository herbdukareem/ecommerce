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
        $window = $this->periodWindow($request);
        $start = $window['start'];
        $previousStart = $window['previous_start'];
        $previousEnd = $window['previous_end'];

        $paidOrders = Order::query()
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $start);

        $previousRevenue = (float) Order::query()
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('total');

        $revenue = (float) (clone $paidOrders)->sum('total');
        $ordersCount = Order::where('created_at', '>=', $start)->count();
        $paidOrdersCount = (clone $paidOrders)->count();
        $costOfGoods = (float) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'paid')
            ->where('orders.created_at', '>=', $start)
            ->sum('order_items.total_cost_at_sale');

        $skuStockBalances = DB::table('skus')
            ->leftJoin('stocks', 'stocks.sku_id', '=', 'skus.id')
            ->where('skus.active', true)
            ->select('skus.id')
            ->selectRaw('COALESCE(SUM(CASE WHEN stocks.on_hand > stocks.reserved THEN stocks.on_hand - stocks.reserved ELSE 0 END), 0) as available_stock')
            ->selectRaw('COALESCE(MAX(skus.low_stock_threshold), 5) as low_stock_threshold')
            ->groupBy('skus.id');

        $lowStockProducts = DB::query()
            ->fromSub($skuStockBalances, 'sku_stock_balances')
            ->whereRaw('available_stock <= low_stock_threshold')
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
        $window = $this->periodWindow($request, 7);
        $period = min($window['days'], 31);
        $start = $window['label'] === 'today' ? now()->startOfDay() : now()->subDays($period);

        $salesData = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $start)
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
        for ($i = $period - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $existing = $salesData->firstWhere('date', $date);
            
            $result[] = [
                'date' => $date,
                'period' => $window['label'] === 'today' ? 'Today' : $date,
                'sales' => $existing ? $existing['sales'] : 0,
                'revenue' => $existing ? $existing['sales'] : 0,
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
        $window = $this->periodWindow($request);
        $start = $window['start'];
        $limit = max(1, (int) $request->integer('limit', 8));

        $soldProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('skus', 'skus.id', '=', 'order_items.sku_id')
            ->where('orders.payment_status', 'paid')
            ->where('orders.created_at', '>=', $start)
            ->selectRaw('COALESCE(order_items.product_id, skus.product_id) as product_id')
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as total_sold')
            ->selectRaw('COALESCE(SUM(COALESCE(order_items.total_price_at_sale, order_items.quantity * order_items.price_snapshot)), 0) as revenue')
            ->groupByRaw('COALESCE(order_items.product_id, skus.product_id)');

        $topProducts = DB::table('products')
            ->leftJoinSub($soldProducts, 'sold_products', function ($join) {
                $join->on('products.id', '=', 'sold_products.product_id');
            })
            ->select(
                'products.id',
                DB::raw('COALESCE(products.name, products.title) as name'),
                DB::raw('COALESCE(products.price, products.base_price, 0) as price'),
                'products.image',
                DB::raw('COALESCE(sold_products.total_sold, 0) as total_sold'),
                DB::raw('COALESCE(sold_products.revenue, 0) as revenue')
            )
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

    public function stockAlerts(Request $request)
    {
        $limit = max(1, min((int) $request->integer('limit', 8), 50));

        $stockBalances = DB::table('skus')
            ->join('products', 'products.id', '=', 'skus.product_id')
            ->leftJoin('stocks', 'stocks.sku_id', '=', 'skus.id')
            ->where('skus.active', true)
            ->where('products.status', 'active')
            ->select(
                'skus.id as sku_id',
                'skus.sku_code',
                'skus.option_label',
                'skus.low_stock_threshold',
                'products.id as product_id',
                DB::raw('COALESCE(products.title, products.name) as product_title')
            )
            ->selectRaw('COALESCE(SUM(CASE WHEN stocks.on_hand > stocks.reserved THEN stocks.on_hand - stocks.reserved ELSE 0 END), 0) as available_stock')
            ->groupBy('skus.id', 'skus.sku_code', 'skus.option_label', 'skus.low_stock_threshold', 'products.id', 'products.title', 'products.name')
            ->havingRaw('available_stock <= COALESCE(skus.low_stock_threshold, 5)');

        $alertCounts = DB::query()
            ->fromSub(clone $stockBalances, 'stock_alerts')
            ->selectRaw('SUM(CASE WHEN available_stock <= 0 THEN 1 ELSE 0 END) as out_of_stock_count')
            ->selectRaw('SUM(CASE WHEN available_stock > 0 THEN 1 ELSE 0 END) as low_stock_count')
            ->first();

        $rows = DB::query()
            ->fromSub(clone $stockBalances, 'stock_alerts')
            ->orderBy('available_stock')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $available = (int) $row->available_stock;

                return [
                    'sku_id' => (int) $row->sku_id,
                    'sku_code' => $row->sku_code,
                    'option_label' => $row->option_label,
                    'product_id' => (int) $row->product_id,
                    'product_title' => $row->product_title,
                    'available_stock' => $available,
                    'low_stock_threshold' => (int) ($row->low_stock_threshold ?? 5),
                    'status' => $available <= 0 ? 'out_of_stock' : 'low_stock',
                ];
            });

        return response()->json([
            'out_of_stock_count' => (int) ($alertCounts->out_of_stock_count ?? 0),
            'low_stock_count' => (int) ($alertCounts->low_stock_count ?? 0),
            'items' => $rows->values(),
        ]);
    }

    private function periodWindow(Request $request, int $defaultDays = 30): array
    {
        $period = $request->input('period', $defaultDays);

        if (is_string($period) && strtolower($period) === 'today') {
            return [
                'label' => 'today',
                'days' => 1,
                'start' => now()->startOfDay(),
                'previous_start' => now()->subDay()->startOfDay(),
                'previous_end' => now()->subDay()->endOfDay(),
            ];
        }

        $days = max(1, (int) $period);
        $start = now()->subDays($days);

        return [
            'label' => (string) $days,
            'days' => $days,
            'start' => $start,
            'previous_start' => now()->subDays($days * 2),
            'previous_end' => $start,
        ];
    }
}

