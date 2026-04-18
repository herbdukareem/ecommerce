<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryBatch;
use App\Models\InventoryLedgerEntry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Sku;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function index(Request $request)
    {
        $query = InventoryBatch::query()->with(['product:id,title', 'sku:id,sku_code']);

        if ($request->filled('sku_id')) {
            $query->where('variant_id', (int) $request->input('sku_id'));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', (int) $request->input('product_id'));
        }

        $batches = $query->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expiry_date')
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json($batches);
    }

    public function addStock(Request $request)
    {
        $data = $request->validate([
            'sku_id' => 'required|integer|exists:skus,id',
            'quantity' => 'required|integer|min:1',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'batch_reference' => 'nullable|string|max:120',
            'source_type' => 'nullable|string|max:80',
            'source_id' => 'nullable|integer',
            'note' => 'nullable|string|max:1000',
        ]);

        $batch = $this->inventoryService->addStock($data, $request->user());

        return response()->json([
            'message' => 'Stock added successfully.',
            'batch' => $batch->load(['product:id,title', 'sku:id,sku_code']),
        ], 201);
    }

    public function ledger(Request $request)
    {
        $query = InventoryLedgerEntry::query()->with(['product:id,title', 'sku:id,sku_code,option_label', 'productOption:id,sku_code,option_label']);

        if ($request->filled('product_id')) {
            $query->where('product_id', (int) $request->input('product_id'));
        }

        if ($request->filled('sku_id')) {
            $query->where('variant_id', (int) $request->input('sku_id'));
        }

        if ($request->filled('product_option_id')) {
            $query->where('product_option_id', (int) $request->input('product_option_id'));
        }

        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->string('movement_type'));
        }

        return response()->json($query->latest()->paginate((int) $request->input('per_page', 30)));
    }

    public function expiryAlerts(Request $request)
    {
        $bucket = $request->string('bucket', 'all')->toString();
        $today = Carbon::today();

        $query = InventoryBatch::query()->with(['product:id,title', 'sku:id,sku_code'])
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expiry_date');

        if ($request->filled('product_id')) {
            $query->where('product_id', (int) $request->input('product_id'));
        }

        if ($request->filled('batch_id')) {
            $query->where('id', (int) $request->input('batch_id'));
        }

        match ($bucket) {
            'expired' => $query->whereDate('expiry_date', '<', $today),
            '7_days' => $query->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', $today->copy()->addDays(7)),
            '14_days' => $query->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', $today->copy()->addDays(14)),
            '30_days' => $query->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', $today->copy()->addDays(30)),
            default => null,
        };

        $batches = $query->orderBy('expiry_date')->paginate((int) $request->input('per_page', 20));
        $batches->getCollection()->transform(function (InventoryBatch $batch) use ($today) {
            $days = $today->diffInDays(Carbon::parse($batch->expiry_date), false);
            return [
                'id' => $batch->id,
                'product' => $batch->product,
                'sku' => $batch->sku,
                'batch_reference' => $batch->batch_reference,
                'quantity_remaining' => $batch->quantity_remaining,
                'expiry_date' => $batch->expiry_date,
                'days_to_expiry' => $days,
                'status_bucket' => $days < 0 ? 'expired' : ($days <= 7 ? '7_days' : ($days <= 14 ? '14_days' : ($days <= 30 ? '30_days' : 'safe'))),
            ];
        });

        return response()->json($batches);
    }

    public function updateBatchExpiry(Request $request, int $batchId)
    {
        $data = $request->validate([
            'expiry_date' => 'nullable|date',
        ]);

        $batch = InventoryBatch::query()->findOrFail($batchId);
        $updated = $this->inventoryService->updateBatchExpiry($batch, $data['expiry_date'] ?? null, $request->user());

        return response()->json([
            'message' => 'Batch expiry updated successfully.',
            'batch' => $updated,
        ]);
    }

    public function profitMargins(Request $request)
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $query = Order::query()->whereIn('status', ['processing', 'shipped', 'delivered'])->where('payment_status', '!=', 'failed');

        if (!empty($validated['date'])) {
            $query->whereDate('placed_at', $validated['date']);
        } elseif (!empty($validated['start_date']) && !empty($validated['end_date'])) {
            $query->whereDate('placed_at', '>=', $validated['start_date'])
                ->whereDate('placed_at', '<=', $validated['end_date']);
        }

        $orders = $query->with(['user:id,name,email', 'items.sku.product'])->get();

        $summary = [
            'revenue' => 0,
            'total_cost' => 0,
            'gross_profit' => 0,
            'gross_margin_percent' => 0,
            'orders_count' => $orders->count(),
            'items_sold' => 0,
        ];

        $orderRows = [];
        $productMap = [];

        foreach ($orders as $order) {
            $orderRevenue = (float) $order->total;
            $orderCost = (float) $order->items->sum(fn (OrderItem $item) => (float) ($item->total_cost_at_sale ?? 0));
            $orderProfit = $orderRevenue - $orderCost;

            $summary['revenue'] += $orderRevenue;
            $summary['total_cost'] += $orderCost;
            $summary['gross_profit'] += $orderProfit;
            $summary['items_sold'] += (int) $order->items->sum('quantity');

            $orderRows[] = [
                'order_id' => $order->id,
                'date' => $order->placed_at,
                'customer' => $order->user?->name,
                'revenue' => $orderRevenue,
                'cost' => $orderCost,
                'profit' => $orderProfit,
            ];

            foreach ($order->items as $item) {
                $productId = $item->sku?->product_id;
                if (!$productId) {
                    continue;
                }

                if (!isset($productMap[$productId])) {
                    $productMap[$productId] = [
                        'product_id' => $productId,
                        'product' => $item->sku?->product?->title,
                        'qty_sold' => 0,
                        'revenue' => 0,
                        'cost' => 0,
                        'profit' => 0,
                        'margin_percent' => 0,
                    ];
                }

                $lineRevenue = (float) ($item->total_price_at_sale ?? ((float) $item->price_snapshot * (int) $item->quantity));
                $lineCost = (float) ($item->total_cost_at_sale ?? 0);

                $productMap[$productId]['qty_sold'] += (int) $item->quantity;
                $productMap[$productId]['revenue'] += $lineRevenue;
                $productMap[$productId]['cost'] += $lineCost;
                $productMap[$productId]['profit'] += ($lineRevenue - $lineCost);
            }
        }

        foreach ($productMap as $productId => $row) {
            $productMap[$productId]['margin_percent'] = $row['revenue'] > 0
                ? round(($row['profit'] / $row['revenue']) * 100, 2)
                : 0;
        }

        $summary['gross_margin_percent'] = $summary['revenue'] > 0
            ? round(($summary['gross_profit'] / $summary['revenue']) * 100, 2)
            : 0;

        return response()->json([
            'summary' => $summary,
            'orders' => $orderRows,
            'products' => array_values($productMap),
        ]);
    }

    public function stockSkus(Request $request)
    {
        $query = Sku::query()->with(['product:id,title', 'stocks:id,sku_id,on_hand,reserved']);

        if ($request->filled('q')) {
            $term = $request->string('q');
            $query->where(function ($q) use ($term) {
                $q->where('sku_code', 'like', "%{$term}%")
                    ->orWhereHas('product', function ($productQuery) use ($term) {
                        $productQuery->where('title', 'like', "%{$term}%");
                    });
            });
        }

        $rows = $query->where('active', true)->limit(120)->get()->map(function (Sku $sku) {
            return [
                'sku_id' => $sku->id,
                'sku_code' => $sku->sku_code,
                'product_id' => $sku->product_id,
                'product_title' => $sku->product?->title,
                'option_label' => $sku->display_label,
                'available_stock' => $sku->stocks->sum(fn ($stock) => (int) $stock->on_hand - (int) $stock->reserved),
            ];
        });

        return response()->json(['skus' => $rows]);
    }
}
