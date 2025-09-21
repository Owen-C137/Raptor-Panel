@extends('shop::emails.layout')

@section('subject', 'Renewal Reminder - ' . $order->plan->name)

@section('content')
<h1>Renewal Reminder</h1>

<p>Hi {{ $order->user->name_first ?? $order->user->username }},</p>

<p>This is a friendly reminder that your service is scheduled for renewal in {{ $daysUntilRenewal }} day{{ $daysUntilRenewal !== 1 ? 's' : '' }}.</p>

{{-- Service Details --}}
<div class="panel">
    <div class="panel-content">
        <div class="panel-item">
            <strong>Service:</strong> {{ $order->plan->category->name }} - {{ $order->plan->name }}<br>
            <strong>Order Number:</strong> #{{ $order->id }}<br>
            <strong>Renewal Date:</strong> {{ $order->due_date->format('F j, Y') }}<br>
            <strong>Renewal Amount:</strong> {{ config('shop.currency.symbol', '$') }}{{ number_format($order->amount, 2) }}
        </div>
    </div>
</div>

{{-- Renewal Information --}}
<h2>Renewal Details</h2>

<table class="table">
    <thead>
        <tr>
            <th>Service</th>
            <th>Plan</th>
            <th>Billing Cycle</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $order->plan->category->name }}</td>
            <td>{{ $order->plan->name }}</td>
            <td>{{ ucfirst(str_replace('_', ' ', $order->billing_cycle)) }}</td>
            <td>{{ config('shop.currency.symbol', '$') }}{{ number_format($order->amount, 2) }}</td>
        </tr>
    </tbody>
</table>

{{-- Payment Method Check --}}
@if($walletBalance >= $renewalAmount)
<div class="panel">
    <div class="panel-content">
        <div class="panel-item" style="color: #28a745;">
            <strong>✓ Automatic Renewal Ready</strong><br>
            Your wallet balance ({{ config('shop.currency.symbol', '$') }}{{ number_format($walletBalance, 2) }}) 
            is sufficient for the renewal. The payment will be processed automatically on the renewal date.
        </div>
    </div>
</div>
@else
<div class="panel">
    <div class="panel-content">
        <div class="panel-item" style="color: #dc3545;">
            <strong>⚠ Action Required</strong><br>
            Your current wallet balance ({{ config('shop.currency.symbol', '$') }}{{ number_format($walletBalance, 2) }}) 
            is insufficient for the renewal amount ({{ config('shop.currency.symbol', '$') }}{{ number_format($renewalAmount, 2) }}).
            <br><br>
            Please add funds to your wallet to ensure uninterrupted service.
        </div>
    </div>
</div>
@endif

{{-- Action Buttons --}}
<div class="align-center" style="margin: 30px 0;">
    @if($walletBalance < $renewalAmount)
    <a href="{{ route('shop.wallet.index') }}" class="button button--orange">
        Add Funds to Wallet
    </a>
    @endif
    
    <a href="{{ route('shop.orders.show', $order) }}" class="button" style="margin-left: 10px;">
        View Service Details
    </a>
    
    <a href="{{ route('shop.orders.index') }}" class="button" style="margin-left: 10px;">
        Manage All Services
    </a>
</div>

{{-- Renewal Timeline --}}
<h2>What to Expect</h2>

<div style="margin: 20px 0;">
    <p><strong>{{ $order->due_date->subDays(3)->format('M j') }} - 3 days before:</strong> Final reminder email</p>
    <p><strong>{{ $order->due_date->format('M j') }} - Renewal date:</strong> Automatic payment processing</p>
    <p><strong>If payment fails:</strong> {{ config('shop.grace_period', 3) }}-day grace period before service suspension</p>
</div>

{{-- Important Notes --}}
<h2>Important Information</h2>

<ul>
    <li>Services will continue uninterrupted if sufficient funds are available</li>
    <li>You can cancel auto-renewal anytime from your dashboard</li>
    <li>Services suspended for non-payment may result in data loss</li>
    <li>Contact support if you need assistance with renewals</li>
</ul>

{{-- Support Information --}}
@if($walletBalance < $renewalAmount)
<div class="panel">
    <div class="panel-content">
        <div class="panel-item">
            <strong>Need Help?</strong><br>
            If you have any questions about your renewal or need assistance adding funds, 
            our support team is available 24/7 to help.
        </div>
    </div>
</div>
@endif

<p>
    Thank you for being a valued customer!
</p>

<p>
    Best regards,<br>
    The {{ config('app.name') }} Team
</p>
@endsection

@section('footer-links')
@if($walletBalance < $renewalAmount)
<p>Add funds: <a href="{{ route('shop.wallet.index') }}">{{ route('shop.wallet.index') }}</a></p>
@endif
<p>View service: <a href="{{ route('shop.orders.show', $order) }}">{{ route('shop.orders.show', $order) }}</a></p>
@endsection
