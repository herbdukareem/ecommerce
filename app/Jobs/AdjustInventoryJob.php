<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\InventoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AdjustInventoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $orderId,
        public string $action // release|commit
    ) {
    }

    public function handle(InventoryService $inventoryService): void
    {
        $order = Order::with('items.sku')->find($this->orderId);
        if (!$order) {
            return;
        }

        $items = $order->items->map(fn ($item) => [
            'sku' => $item->sku,
            'qty' => $item->quantity,
        ])->toArray();

        if ($this->action === 'release') {
            $inventoryService->release($items);
            return;
        }

        if ($this->action === 'commit') {
            $inventoryService->commit($items);
        }
    }
}
