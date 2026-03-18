<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ExportOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $filters = [], public ?string $exportName = null)
    {
    }

    public function handle(): void
    {
        $query = Order::with(['user', 'items.sku.product']);

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['end_date']);
        }

        $orders = $query->get();

        $lines = ['order_id,customer_email,status,payment_status,total,created_at'];
        foreach ($orders as $order) {
            $lines[] = implode(',', [
                $order->id,
                '"' . ($order->user->email ?? '') . '"',
                $order->status,
                $order->payment_status,
                $order->total,
                $order->created_at,
            ]);
        }

        $name = $this->exportName ?: 'orders-export-' . now()->format('YmdHis') . '.csv';
        Storage::disk('local')->put('exports/' . $name, implode("\n", $lines));
    }
}
