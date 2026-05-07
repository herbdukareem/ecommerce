@php
    $orderUrl = rtrim(config('app.url'), '/') . '/orders/' . $order->id;
    $deliveryName = $shippingAddress->recipient_name ?? $shippingAddress->full_name ?? $shippingAddress->name ?? $order->user?->name ?? 'Customer';
    $addressLine = $shippingAddress->line1 ?? $shippingAddress->address_line_1 ?? data_get($order->delivery_address_snapshot, 'address_line_1');
    $city = $shippingAddress->city ?? $shippingAddress->city_name ?? $order->city_name ?? data_get($order->delivery_address_snapshot, 'city');
    $area = $order->area_name ?? data_get($order->delivery_address_snapshot, 'area_or_district');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:700px;background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background:#111827;padding:28px 32px;color:#ffffff;">
                            <p style="margin:0 0 6px;font-size:13px;letter-spacing:1.6px;text-transform:uppercase;color:#f97316;font-weight:700;">Order received</p>
                            <h1 style="margin:0;font-size:26px;line-height:1.25;">Thank you for your order</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 32px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Hello {{ $order->user?->name ?? 'Customer' }},</p>
                            <p style="margin:0 0 22px;font-size:15px;line-height:1.7;color:#4b5563;">
                                We have received Order #{{ $order->id }} and will keep you updated as it moves through processing and dispatch.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                                <tr>
                                    <td style="padding:16px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">Order date</td>
                                    <td align="right" style="padding:16px;background:#f9fafb;border-bottom:1px solid #e5e7eb;font-size:14px;font-weight:700;color:#111827;">{{ optional($order->placed_at ?? $order->created_at)->format('M j, Y g:i A') }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:16px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">Payment status</td>
                                    <td align="right" style="padding:16px;border-bottom:1px solid #e5e7eb;font-size:14px;font-weight:700;color:#111827;">{{ \Illuminate\Support\Str::of($order->payment_status)->replace('_', ' ')->title() }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:16px;font-size:13px;color:#6b7280;">Total</td>
                                    <td align="right" style="padding:16px;font-size:16px;font-weight:800;color:#f97316;">{{ $currency->format($order->total) }}</td>
                                </tr>
                            </table>

                            <h2 style="margin:0 0 12px;font-size:16px;color:#111827;">Items ordered</h2>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                                @foreach($items as $item)
                                    <tr>
                                        <td style="padding:14px 16px;border-bottom:1px solid #e5e7eb;">
                                            <p style="margin:0;font-size:14px;font-weight:700;color:#111827;">{{ $item->product_name_snapshot ?: ($item->sku?->product?->title ?? 'Product') }}</p>
                                            <p style="margin:4px 0 0;font-size:12px;color:#6b7280;">{{ $item->option_label_snapshot ?: ($item->productOption?->display_label ?? $item->sku?->display_label ?? 'Standard') }} x {{ $item->quantity }}</p>
                                        </td>
                                        <td align="right" style="padding:14px 16px;border-bottom:1px solid #e5e7eb;font-size:14px;font-weight:700;color:#111827;">
                                            {{ $currency->format(((float) $item->price_snapshot) * ((int) $item->quantity)) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                                <tr>
                                    <td style="padding:14px 16px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px;">Subtotal</td>
                                    <td align="right" style="padding:14px 16px;border-bottom:1px solid #e5e7eb;font-size:14px;font-weight:700;">{{ $currency->format($order->subtotal) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 16px;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px;">Delivery fee</td>
                                    <td align="right" style="padding:14px 16px;border-bottom:1px solid #e5e7eb;font-size:14px;font-weight:700;">{{ $currency->format($order->delivery_fee ?: $order->shipping_cost) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 16px;color:#111827;font-size:14px;font-weight:800;">Grand total</td>
                                    <td align="right" style="padding:14px 16px;font-size:16px;font-weight:800;color:#f97316;">{{ $currency->format($order->total) }}</td>
                                </tr>
                            </table>

                            <div style="margin:0 0 24px;padding:16px;border-radius:12px;background:#f9fafb;border:1px solid #e5e7eb;">
                                <p style="margin:0 0 6px;font-size:13px;font-weight:700;color:#111827;">Delivery details</p>
                                <p style="margin:0;font-size:14px;line-height:1.7;color:#4b5563;">
                                    {{ $deliveryName }}<br>
                                    {{ $addressLine ?: 'Address details will be confirmed during dispatch.' }}<br>
                                    {{ $city ?: 'N/A' }}@if($area), {{ $area }}@endif
                                </p>
                            </div>

                            <div style="text-align:center;margin:28px 0;">
                                <a href="{{ $orderUrl }}" style="display:inline-block;background:#f97316;color:#ffffff;text-decoration:none;font-weight:700;border-radius:10px;padding:13px 22px;">View Order Details</a>
                            </div>
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
