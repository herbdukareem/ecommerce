<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminCreateOrderRequest;
use App\Models\Sku;
use App\Models\User;
use App\Services\BasketProductService;
use App\Services\OrderPlacementService;
use Illuminate\Http\Request;

class AdminOrderCreationController extends Controller
{
    public function __construct(
        private readonly OrderPlacementService $orderPlacementService,
        private readonly BasketProductService $basketProductService
    )
    {
    }

    public function customers(Request $request)
    {
        $query = User::query()->whereHas('roles', function ($roleQuery) {
            $roleQuery->where('name', 'Customer');
        });

        if ($request->filled('q')) {
            $term = $request->string('q');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        return response()->json([
            'customers' => $query->orderBy('name')->limit(50)->get(['id', 'name', 'email']),
        ]);
    }

    public function products(Request $request)
    {
        $query = Sku::query()
            ->with(['product:id,title,image,product_type', 'product.basketComponents.componentSku.stocks', 'product.basketComponents.unit', 'stocks:id,sku_id,on_hand,reserved'])
            ->where('active', true)
            ->whereHas('product', function ($productQuery) {
                $productQuery->where('status', 'active');
            });

        if ($request->filled('q')) {
            $term = $request->string('q');
            $query->whereHas('product', function ($productQuery) use ($term) {
                $productQuery->where('title', 'like', "%{$term}%");
            });
        }

        $skus = $query->limit(100)->get()->map(function (Sku $sku) {
            $available = $this->basketProductService->isBasketProduct($sku->product)
                ? $this->basketProductService->availableQuantity($sku->product)
                : $sku->stocks->sum(fn ($stock) => max(0, (int) $stock->on_hand - (int) $stock->reserved));
            return [
                'sku_id' => $sku->id,
                'sku_code' => $sku->sku_code,
                'price' => (float) $sku->price,
                'product_id' => $sku->product_id,
                'product_type' => $sku->product?->product_type,
                'product_title' => $sku->product?->title,
                'product_image' => $sku->product?->image,
                'available_stock' => $available,
            ];
        })->values();

        return response()->json([
            'products' => $skus,
        ]);
    }

    public function store(AdminCreateOrderRequest $request)
    {
        $admin = $request->user();
        $customer = User::query()->findOrFail((int) $request->validated('customer_id'));

        $order = $this->orderPlacementService->placeAdminOrderForCustomer($admin, $customer, $request->validated());

        return response()->json([
            'message' => 'Order created successfully.',
            'order' => $order->load(['items.sku.product', 'city', 'area', 'dispatchTimeSlot', 'user', 'createdByAdmin']),
        ], 201);
    }
}
