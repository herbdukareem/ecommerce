<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Order #{{ $order->id }} Terminal Receipt</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f3f4f6; color: #111827; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; }
        .receipt { width: 320px; margin: 24px auto; background: #fff; padding: 18px 16px 28px; position: relative; box-shadow: 0 12px 30px rgba(15, 23, 42, .12); }
        .receipt:after { content: ""; position: absolute; left: 0; right: 0; bottom: -10px; height: 20px; background: radial-gradient(circle at 10px -2px, transparent 12px, #fff 13px) repeat-x; background-size: 20px 20px; }
        .center { text-align: center; }
        .muted { color: #6b7280; }
        .small { font-size: 11px; }
        .line { border-top: 1px dashed #9ca3af; margin: 12px 0; }
        .row { display: flex; justify-content: space-between; gap: 12px; margin: 5px 0; }
        .row span:last-child { text-align: right; }
        .item { margin: 9px 0; }
        .item-name { font-weight: 700; }
        .total { font-size: 16px; font-weight: 800; }
        @media print {
            body { background: #fff; }
            .receipt { margin: 0 auto; box-shadow: none; }
        }
    </style>
</head>
<body>
    <main class="receipt">
        <section class="center">
            <h1 style="font-size:18px;margin:0 0 4px;">{{ $settings['site_name'] }}</h1>
            <div class="small muted">{{ $settings['site_phone'] }}</div>
            <div class="small muted">{{ $settings['site_email'] }}</div>
        </section>

        <div class="line"></div>
        <div class="small">
            <div class="row"><span>Order</span><span>#{{ $order->id }}</span></div>
            <div class="row"><span>Reference</span><span>{{ $order->payment_reference ?: ('ORD-'.$order->id) }}</span></div>
            <div class="row"><span>Date</span><span>{{ optional($order->placed_at ?: $order->created_at)->format('M j, Y g:i A') }}</span></div>
            <div class="row"><span>Customer</span><span>{{ $order->user?->name ?: 'Walk-in customer' }}</span></div>
            <div class="row"><span>Phone</span><span>{{ $order->shippingAddress?->phone ?: $order->user?->phone ?: 'N/A' }}</span></div>
            <div class="row"><span>Served by</span><span>{{ $order->createdByAdmin?->name ?: 'N/A' }}</span></div>
        </div>

        <div class="line"></div>
        @foreach ($order->items as $item)
            <div class="item small">
                <div class="item-name">{{ $item->sku?->product?->title ?: 'Product' }}</div>
                <div class="row">
                    <span>{{ $item->quantity }} x {{ $currency->format($item->price_snapshot) }}</span>
                    <span>{{ $currency->format((float) $item->price_snapshot * (int) $item->quantity) }}</span>
                </div>
                <div class="muted">{{ $item->sku?->sku_code }}</div>
            </div>
        @endforeach

        <div class="line"></div>
        <div class="small">
            <div class="row"><span>Subtotal</span><span>{{ $currency->format($order->subtotal) }}</span></div>
            <div class="row"><span>Discount</span><span>{{ $currency->format($order->discount ?? $order->discount_amount ?? 0) }}</span></div>
            <div class="row"><span>Delivery</span><span>{{ $currency->format($order->delivery_fee ?: $order->shipping_cost) }}</span></div>
            <div class="row"><span>Tax/VAT</span><span>{{ $currency->format($order->tax) }}</span></div>
            <div class="line"></div>
            <div class="row total"><span>TOTAL</span><span>{{ $currency->format($order->total) }}</span></div>
            <div class="row"><span>Payment</span><span>{{ ucfirst(str_replace('_', ' ', $order->payment_mode ?: 'N/A')) }}</span></div>
            <div class="row"><span>Status</span><span>{{ ucfirst($order->payment_status ?: 'pending') }}</span></div>
        </div>

        <div class="line"></div>
        <section class="center small">
            <strong>Thank you for shopping with us.</strong>
            <div class="muted">Keep this receipt for your records.</div>
        </section>
    </main>
</body>
</html>
