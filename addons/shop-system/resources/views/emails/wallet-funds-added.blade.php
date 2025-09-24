@extends('shop::emails.layout')

@section('subject', 'Wallet Funds Added - ' . config('shop.currency.symbol', '$') . number_format($transaction->amount, 2))

@section('content')
<h1>💰 Funds Added Successfully!</h1>

<p>Hi {{ $user->name_first ?? $user->username }},</p>

<p>Great news! Your wallet has been successfully credited with {{ config('shop.currency.symbol', '$') }}{{ number_format($transaction->amount, 2) }}.</p>

{{-- Transaction Details Section --}}
<div class="panel success">
    <div class="panel-content">
        <h2 style="color: #28a745; margin-top: 0;">✅ Transaction Confirmed</h2>
        <div class="panel-item">
            <strong>Transaction ID:</strong> {{ $transaction->id }}<br>
            <strong>Date & Time:</strong> {{ $transaction->created_at->format('F j, Y \a\t g:i A') }}<br>
            <strong>Amount Added:</strong> <span style="color: #28a745; font-weight: bold;">{{ config('shop.currency.symbol', '$') }}{{ number_format($transaction->amount, 2) }}</span><br>
            <strong>Payment Method:</strong> 
            @php
                $paymentMethod = $transaction->metadata['payment_method'] ?? 'Unknown';
            @endphp
            @if($paymentMethod === 'stripe')
                Credit/Debit Card (Stripe)
            @elseif($paymentMethod === 'paypal')
                PayPal
            @else
                {{ ucfirst($paymentMethod) }}
            @endif
            <br>
            <strong>Status:</strong> 
            <span style="color: #28a745; font-weight: bold;">
                ✅ Completed
            </span>
            @php
                $paymentReference = $transaction->metadata['payment_reference'] ?? null;
            @endphp
            @if($paymentReference)
            <br>
            <strong>Reference:</strong> {{ $paymentReference }}
            @endif
        </div>
    </div>
</div>

{{-- Updated Wallet Balance Section --}}
<div class="panel info">
    <div class="panel-content">
        <h2 style="color: #007bff; margin-top: 0;">🏦 Updated Wallet Balance</h2>
        <div class="panel-item">
            <strong>Previous Balance:</strong> {{ config('shop.currency.symbol', '$') }}{{ number_format($transaction->balance_before ?? 0, 2) }}<br>
            <strong>Amount Added:</strong> {{ config('shop.currency.symbol', '$') }}{{ number_format($transaction->amount, 2) }}<br>
            <strong>New Balance:</strong> 
            <span style="color: #007bff; font-weight: bold; font-size: 1.2em;">
                {{ config('shop.currency.symbol', '$') }}{{ number_format($wallet->balance, 2) }}
            </span>
        </div>
        
        {{-- Access Wallet Button --}}
        <div style="margin-top: 20px;">
            <a href="{{ rtrim(config('app.url'), '/') }}/shop/wallet" 
               style="background-color: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">
                🏦 View My Wallet
            </a>
        </div>
    </div>
</div>

{{-- Next Steps --}}
<div class="panel">
    <div class="panel-content">
        <h2 style="margin-top: 0;">🚀 What's Next?</h2>
        <div class="panel-item">
            <ol>
                <li><strong>Shop for Services:</strong> Browse our server plans and hosting packages</li>
                <li><strong>Instant Purchases:</strong> Use your wallet balance for immediate order processing</li>
                <li><strong>Auto-Renewal:</strong> Enable automatic renewals for your existing services</li>
                <li><strong>Manage Funds:</strong> View transaction history and add more funds anytime</li>
            </ol>
        </div>
    </div>
</div>

{{-- Security Notice --}}
<div class="panel">
    <div class="panel-content">
        <h2 style="margin-top: 0;">🔒 Security Information</h2>
        <div class="panel-item">
            <p><strong>Transaction Security:</strong> This transaction was processed securely using industry-standard encryption.</p>
            <p><strong>Suspicious Activity:</strong> If you did not authorize this transaction, please contact our support team immediately.</p>
            <ul>
                <li><strong>Support Portal:</strong> <a href="{{ rtrim(config('app.url'), '/') }}/tickets">{{ rtrim(config('app.url'), '/') }}/tickets</a></li>
                <li><strong>Email:</strong> {{ config('mail.from.address') }}</li>
                <li><strong>Reference ID:</strong> {{ $transaction->id }}</li>
            </ul>
        </div>
    </div>
</div>

<p style="margin-top: 30px;">
    Thank you for choosing {{ config('mail.from.name', 'Server Shop') }}! 
    Your wallet is now ready for instant purchases.
</p>

<p>
    Best regards,<br>
    <strong>{{ config('mail.from.name', 'Server Shop') }} Team</strong>
</p>
@endsection
