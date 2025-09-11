@extends('shop::emails.layout')

@section('subject', 'Order Confirmation - Order #' . $order->order_number)

@section('content')
<h1>Order Confirmation</h1>

<p>Hi {{ $order->user->name_first ?? $order->user->username }},</p>

<p>Thank you for your order! We're excited to confirm that we've received your order and it's being processed.</p>

{{-- Order Details --}}
<div class="panel">
    <div class="panel-content">
        <div class="panel-item">
            <strong>Order Number:</strong> #{{ $order->order_number }}<br>
            <strong>Order Date:</strong> {{ $order->created_at->format('F j, Y \a\t g:i A') }}<br>
            <strong>Order Total:</strong> {{ config('shop.currency.symbol', '$') }}{{ number_format($order->total, 2) }}
        </div>
    </div>
</div>

{{-- Order Items --}}
<h2>Order Items</h2>

<table class="table">
    <thead>
        <tr>
            <th>Product</th>
            <th>Plan</th>
            <th>Quantity</th>
            <th>Price</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $item)
        <tr>
            <td>{{ $item->plan->product->name }}</td>
            <td>{{ $item->plan->name }}</td>
            <td>{{ $item->quantity }}</td>
            <td>{{ config('shop.currency.symbol', '$') }}{{ number_format($item->unit_price, 2) }}</td>
            <td>{{ config('shop.currency.symbol', '$') }}{{ number_format($item->total, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Order Summary --}}
<table class="table">
    <tr>
        <td><strong>Subtotal:</strong></td>
        <td class="align-right">{{ config('shop.currency.symbol', '$') }}{{ number_format($order->subtotal, 2) }}</td>
    </tr>
    @if($order->setup_total > 0)
    <tr>
        <td><strong>Setup Fees:</strong></td>
        <td class="align-right">{{ config('shop.currency.symbol', '$') }}{{ number_format($order->setup_total, 2) }}</td>
    </tr>
    @endif
    @if($order->discount > 0)
    <tr>
        <td><strong>Discount:</strong></td>
        <td class="align-right">-{{ config('shop.currency.symbol', '$') }}{{ number_format($order->discount, 2) }}</td>
    </tr>
    @endif
    @if($order->tax > 0)
    <tr>
        <td><strong>Tax:</strong></td>
        <td class="align-right">{{ config('shop.currency.symbol', '$') }}{{ number_format($order->tax, 2) }}</td>
    </tr>
    @endif
    <tr>
        <td><strong>Total:</strong></td>
        <td class="align-right"><strong>{{ config('shop.currency.symbol', '$') }}{{ number_format($order->total, 2) }}</strong></td>
    </tr>
</table>

{{-- Payment Information --}}
<h2>Payment Information</h2>

<div class="panel">
    <div class="panel-content">
        <div class="panel-item">
            <strong>Payment Method:</strong> 
            @if($order->payment_method === 'stripe')
                Credit/Debit Card
            @elseif($order->payment_method === 'paypal')
                PayPal
            @elseif($order->payment_method === 'wallet')
                Account Credit
            @else
                {{ ucfirst($order->payment_method) }}
            @endif
            <br>
            
            <strong>Payment Status:</strong> 
            <span style="color: {{ $order->payment_status === 'paid' ? '#28a745' : ($order->payment_status === 'pending' ? '#ffc107' : '#dc3545') }}">
                {{ ucfirst($order->payment_status) }}
            </span>
            <br>
            
            @if($order->payment_id)
            <strong>Transaction ID:</strong> {{ $order->payment_id }}
            @endif
        </div>
    </div>
</div>

{{-- Next Steps --}}
<h2>What's Next?</h2>

<p>Your order is now being processed. Here's what happens next:</p>

<ol>
    <li><strong>Server Deployment:</strong> Your servers are being automatically created and will be ready within a few minutes.</li>
    <li><strong>Access Information:</strong> You'll receive login details once your servers are deployed.</li>
    <li><strong>Support:</strong> If you need any assistance, our support team is here to help.</li>
</ol>

{{-- Action Buttons --}}
<div class="align-center" style="margin: 30px 0;">
    <a href="{{ route('shop.orders.show', $order) }}" class="button">
        View Order Details
    </a>
    
    <a href="{{ route('index') }}" class="button button--green" style="margin-left: 10px;">
        Access Dashboard
    </a>
</div>

{{-- Billing Address --}}
@if($order->billing_address)
<h2>Billing Address</h2>

<div class="panel">
    <div class="panel-content">
        <div class="panel-item">
            {{ $order->billing_address['first_name'] }} {{ $order->billing_address['last_name'] }}<br>
            @if(isset($order->billing_address['company']) && $order->billing_address['company'])
                {{ $order->billing_address['company'] }}<br>
            @endif
            {{ $order->billing_address['address'] }}<br>
            @if(isset($order->billing_address['address2']) && $order->billing_address['address2'])
                {{ $order->billing_address['address2'] }}<br>
            @endif
            {{ $order->billing_address['city'] }}, {{ $order->billing_address['state'] }} {{ $order->billing_address['postal_code'] }}<br>
            {{ $order->billing_address['country'] }}
        </div>
    </div>
</div>
@endif

<p>Thank you for choosing us for your hosting needs!</p>

<p>
    Best regards,<br>
    The {{ config('app.name') }} Team
</p>
@endsection

@section('footer-links')
<p>
    <a href="{{ route('shop.orders.show', $order) }}">{{ route('shop.orders.show', $order) }}</a>
</p>
@endsection
