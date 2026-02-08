<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4F46E5;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
        }
        .order-details {
            background-color: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            border: 1px solid #e5e7eb;
        }
        .item {
            padding: 15px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .item:last-child {
            border-bottom: none;
        }
        .total {
            font-size: 18px;
            font-weight: bold;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #4F46E5;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4F46E5;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Thank You for Your Order!</h1>
    </div>
    
    <div class="content">
        <p>Hi {{ $order->user->name }},</p>
        
        <p>We've received your order and it's being processed. Here are the details:</p>
        
        <div class="order-details">
            <h2>Order #{{ $order->id }}</h2>
            <p><strong>Order Date:</strong> {{ $order->placed_at->format('F j, Y g:i A') }}</p>
            <p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>
            
            <h3>Items Ordered:</h3>
            @foreach($items as $item)
            <div class="item">
                <strong>{{ $item->sku->product->name }}</strong>
                @if($item->sku->variant_label)
                    <br><small>{{ $item->sku->variant_label }}</small>
                @endif
                <br>Quantity: {{ $item->quantity }} × ${{ number_format($item->price_snapshot, 2) }}
                <br><strong>${{ number_format($item->quantity * $item->price_snapshot, 2) }}</strong>
            </div>
            @endforeach
            
            <div class="total">
                <p>Subtotal: ${{ number_format($order->subtotal, 2) }}</p>
                <p>Shipping: ${{ number_format($order->shipping_cost, 2) }}</p>
                <p>Tax: ${{ number_format($order->tax, 2) }}</p>
                <p style="font-size: 20px; color: #4F46E5;">Total: ${{ number_format($order->total, 2) }}</p>
            </div>
            
            <h3>Shipping Address:</h3>
            <p>
                {{ $shippingAddress->recipient_name }}<br>
                {{ $shippingAddress->line1 }}<br>
                @if($shippingAddress->line2)
                    {{ $shippingAddress->line2 }}<br>
                @endif
                {{ $shippingAddress->city }}, {{ $shippingAddress->state }} {{ $shippingAddress->postal_code }}<br>
                {{ $shippingAddress->country }}
            </p>
        </div>
        
        <center>
            <a href="{{ config('app.url') }}/orders/{{ $order->id }}" class="button">View Order Details</a>
        </center>
        
        <p>You'll receive another email when your order ships.</p>
    </div>
    
    <div class="footer">
        <p>Questions? Contact us at {{ config('mail.from.address') }}</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>

