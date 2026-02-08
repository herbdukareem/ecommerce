<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Update</title>
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
            background-color: #10B981;
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
        .shipping-details {
            background-color: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            border: 1px solid #e5e7eb;
        }
        .tracking-box {
            background-color: #EFF6FF;
            border: 2px solid #3B82F6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
        }
        .tracking-number {
            font-size: 24px;
            font-weight: bold;
            color: #1E40AF;
            margin: 10px 0;
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
            background-color: #10B981;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            background-color: #10B981;
            color: white;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 Your Order Has Shipped!</h1>
    </div>
    
    <div class="content">
        <p>Hi {{ $order->user->name }},</p>
        
        <p>Great news! Your order has been shipped and is on its way to you.</p>
        
        <div class="shipping-details">
            <h2>Order #{{ $order->id }}</h2>
            <p><strong>Shipping Status:</strong> <span class="status-badge">{{ ucfirst($fulfillment->status) }}</span></p>
            <p><strong>Carrier:</strong> {{ ucfirst($fulfillment->shipment_provider) }}</p>
            
            @if($fulfillment->tracking_no)
            <div class="tracking-box">
                <p style="margin: 0; font-size: 14px; color: #6b7280;">Tracking Number</p>
                <div class="tracking-number">{{ $fulfillment->tracking_no }}</div>
                
                @if($trackingUrl)
                <a href="{{ $trackingUrl }}" class="button">Track Your Package</a>
                @endif
            </div>
            @endif
            
            <h3>Shipping Address:</h3>
            <p>
                {{ $order->shippingAddress->recipient_name }}<br>
                {{ $order->shippingAddress->line1 }}<br>
                @if($order->shippingAddress->line2)
                    {{ $order->shippingAddress->line2 }}<br>
                @endif
                {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state }} {{ $order->shippingAddress->postal_code }}<br>
                {{ $order->shippingAddress->country }}
            </p>
        </div>
        
        <center>
            <a href="{{ config('app.url') }}/orders/{{ $order->id }}" class="button">View Order Details</a>
        </center>
        
        <p>Your package should arrive within the estimated delivery time. If you have any questions, please don't hesitate to contact us.</p>
    </div>
    
    <div class="footer">
        <p>Questions? Contact us at {{ config('mail.from.address') }}</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>

