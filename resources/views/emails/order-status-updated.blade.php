@php
    $humanize = fn ($value) => \Illuminate\Support\Str::of((string) ($value ?: 'N/A'))->replace('_', ' ')->title();
    $orderUrl = rtrim(config('app.url'), '/') . '/orders/' . $order->id;
    $deliveryLabel = $humanize($order->delivery_status ?: 'pending_assignment');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Update</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:700px;background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background:#111827;padding:28px 32px;color:#ffffff;">
                            <p style="margin:0 0 6px;font-size:13px;letter-spacing:1.6px;text-transform:uppercase;color:#f97316;font-weight:700;">Order update</p>
                            <h1 style="margin:0;font-size:26px;line-height:1.25;">Order #{{ $order->id }} has been updated</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 32px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Hello {{ $order->user?->name ?? 'Customer' }},</p>
                            <p style="margin:0 0 22px;font-size:15px;line-height:1.7;color:#4b5563;">
                                There is a new update on your order. The latest details are shown below.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 22px;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                                <tr>
                                    <td style="padding:16px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">Current order status</td>
                                    <td align="right" style="padding:16px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-size:14px;font-weight:700;color:#111827;">{{ $humanize($order->status) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:16px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">Payment status</td>
                                    <td align="right" style="padding:16px;border-bottom:1px solid #e5e7eb;font-size:14px;font-weight:700;color:#111827;">{{ $humanize($order->payment_status) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:16px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">Delivery status</td>
                                    <td align="right" style="padding:16px;border-bottom:1px solid #e5e7eb;font-size:14px;font-weight:700;color:#111827;">{{ $deliveryLabel }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:16px;font-size:13px;color:#6b7280;">Order total</td>
                                    <td align="right" style="padding:16px;font-size:16px;font-weight:800;color:#f97316;">{{ $currency->format($order->total) }}</td>
                                </tr>
                            </table>

                            <h2 style="margin:0 0 12px;font-size:16px;color:#111827;">What changed</h2>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 22px;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                                @foreach($changes as $change)
                                    <tr>
                                        <td style="padding:14px 16px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">{{ $change['type'] ?? 'Status' }}</td>
                                        <td align="right" style="padding:14px 16px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#111827;">
                                            <span style="color:#6b7280;">{{ $humanize($change['old'] ?? 'N/A') }}</span>
                                            <span style="padding:0 6px;color:#9ca3af;">to</span>
                                            <strong>{{ $humanize($change['new'] ?? 'N/A') }}</strong>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            @if($note)
                                <div style="margin:0 0 22px;padding:16px;border-radius:12px;background:#fff7ed;border:1px solid #fed7aa;">
                                    <p style="margin:0 0 6px;font-size:13px;font-weight:700;color:#9a3412;">Update note</p>
                                    <p style="margin:0;font-size:14px;line-height:1.7;color:#4b5563;">{{ $note }}</p>
                                </div>
                            @endif

                            <h2 style="margin:0 0 12px;font-size:16px;color:#111827;">Items in this order</h2>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                                @forelse($items as $item)
                                    <tr>
                                        <td style="padding:14px 16px;border-bottom:1px solid #e5e7eb;">
                                            <p style="margin:0;font-size:14px;font-weight:700;color:#111827;">{{ $item->product_name_snapshot ?: ($item->sku?->product?->title ?? 'Product') }}</p>
                                            <p style="margin:4px 0 0;font-size:12px;color:#6b7280;">{{ $item->option_label_snapshot ?: ($item->productOption?->display_label ?? $item->sku?->display_label ?? 'Standard') }} x {{ $item->quantity }}</p>
                                        </td>
                                        <td align="right" style="padding:14px 16px;border-bottom:1px solid #e5e7eb;font-size:14px;font-weight:700;color:#111827;">
                                            {{ $currency->format(((float) $item->price_snapshot) * ((int) $item->quantity)) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td style="padding:16px;color:#6b7280;font-size:14px;">No order items are currently available.</td>
                                    </tr>
                                @endforelse
                            </table>

                            <div style="text-align:center;margin:28px 0;">
                                <a href="{{ $orderUrl }}" style="display:inline-block;background:#f97316;color:#ffffff;text-decoration:none;font-weight:700;border-radius:10px;padding:13px 22px;">View Order Details</a>
                            </div>

                            <p style="margin:0;font-size:14px;line-height:1.7;color:#6b7280;">
                                We will continue to keep you informed as your order progresses.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 32px;background:#f9fafb;border-top:1px solid #e5e7eb;color:#6b7280;font-size:12px;line-height:1.6;">
                            Questions? Contact us at {{ config('mail.from.address') }}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
