<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\AdjustInventoryJob;
use App\Jobs\ExportOrdersJob;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Services\Logistics\LogisticsManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(private readonly LogisticsManager $logisticsManager)
    {
    }

    /**
     * Display a listing of orders
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.sku.product', 'shippingAddress', 'deliveryPartner', 'shippingMethod', 'shippingZone']);

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
            'payments',
            'deliveryPartner',
            'shippingMethod',
            'shippingZone',
        ])->findOrFail($id);

        return response()->json($order);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $id)
    {
        $order = Order::with('items.sku')->findOrFail($id);

        $data = $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded',
        ]);

        $order->update($data);

        if ($data['status'] === 'delivered') {
            AdjustInventoryJob::dispatch($order->id, 'commit');
        }

        if ($data['status'] === 'cancelled') {
            AdjustInventoryJob::dispatch($order->id, 'release');
        }

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

    public function assignDeliveryPartner(Request $request, int $id)
    {
        $order = Order::with(['deliveryPartner', 'shippingMethod', 'shippingZone'])->findOrFail($id);

        $data = $request->validate([
            'delivery_partner_id' => 'nullable|exists:delivery_partners,id',
            'delivery_tracking_code' => 'nullable|string|max:120',
            'dispatch_note' => 'nullable|string|max:1000',
        ]);

        if (!empty($data['delivery_partner_id'])) {
            $partner = DeliveryPartner::findOrFail($data['delivery_partner_id']);
            if ($partner->status !== 'active') {
                throw ValidationException::withMessages([
                    'delivery_partner_id' => ['Only active delivery partners can be assigned.'],
                ]);
            }
        }

        $order->update([
            'delivery_partner_id' => $data['delivery_partner_id'] ?? null,
            'delivery_tracking_code' => $data['delivery_tracking_code'] ?? $order->delivery_tracking_code,
            'dispatch_note' => $data['dispatch_note'] ?? $order->dispatch_note,
            'delivery_status' => !empty($data['delivery_partner_id']) ? 'assigned' : 'pending_assignment',
            'assigned_at' => !empty($data['delivery_partner_id']) ? now() : null,
        ]);

        $dispatchResult = null;
        if ($order->delivery_partner_id) {
            $dispatchOrder = $order->fresh(['deliveryPartner']);
            $dispatchProvider = $this->resolveDispatchProvider($dispatchOrder);

            $dispatchResult = $this->logisticsManager->dispatch($dispatchOrder, $dispatchProvider, [
                'tracking_code' => $order->delivery_tracking_code,
            ]);
        }

        return response()->json([
            'message' => 'Delivery assignment updated successfully',
            'order' => $order->fresh(['deliveryPartner', 'shippingMethod', 'shippingZone']),
            'dispatch' => $dispatchResult,
        ]);
    }

    protected function resolveDispatchProvider(Order $order): string
    {
        $partnerName = strtolower(trim((string) data_get($order, 'deliveryPartner.name', '')));
        $companyName = strtolower(trim((string) data_get($order, 'deliveryPartner.company_name', '')));
        $providerLabel = trim($partnerName . ' ' . $companyName);

        $providers = ['dhl', 'gig', 'kwik', 'sendbox'];
        foreach ($providers as $provider) {
            if ($providerLabel !== '' && str_contains($providerLabel, $provider)) {
                return $provider;
            }
        }

        if (config('services.dhl.api_key') && config('services.dhl.api_secret')) {
            return 'dhl';
        }

        return 'manual_local_partner';
    }

    public function updateDeliveryStatus(Request $request, int $id)
    {
        $order = Order::findOrFail($id);

        $data = $request->validate([
            'delivery_status' => 'required|string|in:pending_assignment,assigned,packed,shipped,in_transit,delivered,delivery_failed,returned,cancelled',
            'dispatch_note' => 'nullable|string|max:1000',
            'delivery_tracking_code' => 'nullable|string|max:120',
        ]);

        $nextStatus = $data['delivery_status'];
        if (($order->delivery_status ?? 'pending_assignment') !== $nextStatus && !$order->canTransitionDeliveryStatusTo($nextStatus)) {
            throw ValidationException::withMessages([
                'delivery_status' => ['Invalid delivery status transition requested.'],
            ]);
        }

        $payload = [
            'delivery_status' => $nextStatus,
            'dispatch_note' => $data['dispatch_note'] ?? $order->dispatch_note,
            'delivery_tracking_code' => $data['delivery_tracking_code'] ?? $order->delivery_tracking_code,
        ];

        if ($nextStatus === 'shipped' && !$order->shipped_at) {
            $payload['shipped_at'] = now();
        }

        if ($nextStatus === 'delivered' && !$order->delivered_at) {
            $payload['delivered_at'] = now();
        }

        $order->update($payload);

        return response()->json([
            'message' => 'Delivery status updated successfully',
            'order' => $order->fresh(['deliveryPartner', 'shippingMethod', 'shippingZone']),
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
        $exportName = 'orders-export-' . now()->format('YmdHis') . '.csv';

        ExportOrdersJob::dispatch([
            'status' => $request->status,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ], $exportName);

        return response()->json([
            'message' => 'Orders export queued successfully',
            'file' => 'exports/' . $exportName,
            'download_hint' => 'Retrieve from storage/app/exports/' . $exportName,
        ]);
    }
}

