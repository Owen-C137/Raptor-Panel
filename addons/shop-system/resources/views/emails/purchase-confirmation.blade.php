@extends('shop::emails.layout')

@section('subject', 'Purchase Confirmation - Order #' . $order->order_number)

@section('content')
<h1>🎉 Purchase Successful!</h1>

<p>Hi {{ $order->user->name_first ?? $order->user->username }},</p>

<p>Great news! Your purchase has been completed successfully and your server is ready to use.</p>

{{-- Successful Payment Section --}}
<div class="panel success">
    <div class="panel-content">
        <h2 style="color: #28a745; margin-top: 0;">✅ Payment Confirmed</h2>
        <div class="panel-item">
            <strong>Order Number:</strong> #{{ $order->order_number ?? $order->id }}<br>
            <strong>Purchase Date:</strong> {{ $order->created_at->format('F j, Y \a\t g:i A') }}<br>
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
            <span style="color: #28a745; font-weight: bold;">
                ✅ {{ ucfirst($order->payment_status) }}
            </span>
            @if($payment && $payment->transaction_id)
            <br>
            <strong>Transaction ID:</strong> {{ $payment->transaction_id }}
            @endif
        </div>
    </div>
</div>

{{-- Server Creation Section --}}
@if($server)
<div class="panel info">
    <div class="panel-content">
        <h2 style="color: #007bff; margin-top: 0;">🖥️ Server Created</h2>
        <div class="panel-item">
            <strong>Server Name:</strong> {{ $server->name }}<br>
            <strong>Server ID:</strong> {{ $server->uuidShort }}<br>
            <strong>Plan:</strong> {{ $order->plan->name }}<br>
            <strong>Status:</strong> 
            <span style="color: #28a745; font-weight: bold;">
                ✅ Ready to Use
            </span>
        </div>
        
        {{-- Server Resources --}}
        <div style="margin-top: 15px;">
            <strong>Server Resources:</strong><br>
            <div style="margin-left: 20px;">
                • CPU: {{ $order->plan->cpu ?? 'N/A' }}%<br>
                • Memory: {{ isset($order->plan->memory) ? number_format($order->plan->memory) . ' MB' : 'N/A' }}<br>
                • Disk: {{ isset($order->plan->disk) ? number_format($order->plan->disk) . ' MB' : 'N/A' }}<br>
                @if($order->plan->databases)
                • Databases: {{ $order->plan->databases }}<br>
                @endif
                @if($order->plan->allocations)
                • Allocations: {{ $order->plan->allocations }}<br>
                @endif
                @if($order->plan->backups)
                • Backups: {{ $order->plan->backups }}<br>
                @endif
            </div>
        </div>
        
        {{-- Manage Server Button --}}
        <div style="margin-top: 20px;">
            <a href="{{ rtrim(config('app.url'), '/') }}/server/{{ $server->uuidShort }}" 
               style="background-color: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">
                🎮 Manage Your Server
            </a>
        </div>
    </div>
</div>
@else
<div class="panel warning">
    <div class="panel-content">
        <h2 style="color: #ffc107; margin-top: 0;">⏳ Server Setup in Progress</h2>
        <div class="panel-item">
            <p>Your server is currently being created. You'll receive another email once it's ready, or you can check your server list in the panel.</p>
        </div>
    </div>
</div>
@endif

{{-- Invoice/Order Details Section --}}
<div class="panel">
    <div class="panel-content">
        <h2 style="margin-top: 0;">📄 Invoice Details</h2>
        
        {{-- Billing Information --}}
        <div class="panel-item">
            <strong>Billing Information:</strong><br>
            {{ $order->billing_details['first_name'] ?? '' }} {{ $order->billing_details['last_name'] ?? '' }}<br>
            {{ $order->billing_details['email'] ?? '' }}<br>
            @if($order->billing_details['company'] ?? false)
            {{ $order->billing_details['company'] }}<br>
            @endif
            {{ $order->billing_details['address'] ?? '' }}<br>
            @if($order->billing_details['address2'] ?? false)
            {{ $order->billing_details['address2'] }}<br>
            @endif
            {{ $order->billing_details['city'] ?? '' }}, {{ $order->billing_details['state'] ?? '' }} {{ $order->billing_details['postal_code'] ?? '' }}<br>
            {{ $order->billing_details['country'] ?? '' }}
        </div>
    </div>
</div>

{{-- Order Items --}}
<h2>📦 Order Summary</h2>

<table class="table">
    <thead>
        <tr>
            <th>Category</th>
            <th>Plan</th>
            <th>Billing Cycle</th>
            <th>Price</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $order->plan->category->name }}</td>
            <td>{{ $order->plan->name }}</td>
            <td>{{ ucfirst(str_replace('_', ' ', $order->billing_cycle)) }}</td>
            <td>{{ config('shop.currency.symbol', '$') }}{{ number_format($order->amount, 2) }}</td>
            <td>{{ config('shop.currency.symbol', '$') }}{{ number_format($order->amount, 2) }}</td>
        </tr>
    </tbody>
</table>

{{-- Order Summary --}}
<table class="table">
    <tr>
        <td><strong>Subtotal:</strong></td>
        <td class="align-right">{{ config('shop.currency.symbol', '$') }}{{ number_format($order->subtotal ?: $order->amount, 2) }}</td>
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
        <td><strong>Total Paid:</strong></td>
        <td class="align-right"><strong>{{ config('shop.currency.symbol', '$') }}{{ number_format($order->total ?: $order->amount, 2) }}</strong></td>
    </tr>
</table>

{{-- Next Steps --}}
<div class="panel info">
    <div class="panel-content">
        <h2 style="color: #007bff; margin-top: 0;">🚀 What's Next?</h2>
        <div class="panel-item">
            <ol>
                @if($server)
                <li><strong>Access Your Server:</strong> Click the "Manage Your Server" button above to access your control panel</li>
                <li><strong>Configure Your Server:</strong> Set up your server settings, install plugins, and customize as needed</li>
                <li><strong>Start Playing:</strong> Your server is ready to use immediately!</li>
                @else
                <li><strong>Server Creation:</strong> Your server is being created and will be ready shortly</li>
                <li><strong>Email Notification:</strong> You'll receive another email when your server is ready</li>
                <li><strong>Check Panel:</strong> You can also check your server status in the panel</li>
                @endif
            </ol>
        </div>
    </div>
</div>

{{-- Support Information --}}
<div class="panel">
    <div class="panel-content">
        <h2 style="margin-top: 0;">💬 Need Help?</h2>
        <div class="panel-item">
            <p>If you have any questions or need assistance, please don't hesitate to contact our support team:</p>
            <ul>
                <li><strong>Support Portal:</strong> <a href="{{ rtrim(config('app.url'), '/') }}/tickets">{{ rtrim(config('app.url'), '/') }}/tickets</a></li>
                <li><strong>Documentation:</strong> Check our knowledge base for guides and tutorials</li>
                <li><strong>Order Reference:</strong> Please include your order number #{{ $order->order_number ?? $order->id }} in any support requests</li>
            </ul>
        </div>
    </div>
</div>

<p style="margin-top: 30px;">
    Thank you for choosing {{ config('mail.from.name', 'Server Shop') }}! 
    We appreciate your business and hope you enjoy your new server.
</p>

<p>
    Best regards,<br>
    <strong>{{ config('mail.from.name', 'Server Shop') }} Team</strong>
</p>
@endsection
