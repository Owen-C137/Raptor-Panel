@extends('shop::layout')

@section('shop-title', 'Order Complete')

@section('shop-content')
<div class="checkout-success-container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            {{-- Success Header --}}
            <div class="card success-card mb-4">
                <div class="card-body text-center py-5">
                    <div class="success-icon mb-4">
                        <i class="fas fa-check-circle fa-4x text-success"></i>
                    </div>
                    
                    <h2 class="success-title mb-3">Order Completed Successfully!</h2>
                    <p class="lead text-muted mb-4">
                        Thank you for your purchase. Your order has been received and is being processed.
                    </p>
                    
                    <div class="order-info-summary">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="info-item">
                                    <i class="fas fa-receipt fa-2x text-primary mb-2"></i>
                                    <h6>Order Number</h6>
                                    <strong class="text-primary">#{{ $order->order_number }}</strong>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-item">
                                    <i class="fas fa-calendar fa-2x text-info mb-2"></i>
                                    <h6>Order Date</h6>
                                    <strong>{{ $order->created_at->format('M d, Y') }}</strong>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-item">
                                    <i class="fas fa-dollar-sign fa-2x text-success mb-2"></i>
                                    <h6>Total Amount</h6>
                                    <strong class="text-success">
                                        {{ config('shop.currency.symbol', '$') }}{{ number_format($order->total, 2) }}
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Order Details --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i>
                        Order Details
                    </h5>
                </div>
                <div class="card-body">
                    {{-- Order Items --}}
                    <div class="order-items">
                        @if($order->plan)
                        <div class="order-item pb-3 mb-3">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="item-info">
                                        <h6 class="mb-1">{{ $order->plan->category->name ?? 'General' }}</h6>
                                        <p class="mb-1 text-primary fw-bold">{{ $order->plan->name }}</p>
                                        <div class="item-meta">
                                            <span class="badge bg-secondary me-2">{{ $order->billing_cycle }}</span>
                                            @if($item->quantity > 1)
                                                <span class="badge bg-info">Quantity: {{ $item->quantity }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 text-center">
                                    <div class="item-pricing">
                                        <div class="unit-price">
                                            {{ config('shop.currency.symbol', '$') }}{{ number_format($item->unit_price, 2) }}
                                        </div>
                                        @if($item->setup_fee > 0)
                                            <small class="text-muted">
                                                +{{ config('shop.currency.symbol', '$') }}{{ number_format($item->setup_fee, 2) }} setup
                                            </small>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="col-md-3 text-end">
                                    <div class="item-total">
                                        <strong class="text-success">
                                            {{ config('shop.currency.symbol', '$') }}{{ number_format($item->total, 2) }}
                                        </strong>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Server Information (if available) --}}
                            @if($item->server_id)
                            <div class="server-info mt-3">
                                <div class="alert alert-success d-flex align-items-center">
                                    <i class="fas fa-server me-2"></i>
                                    <div>
                                        <strong>Server Created:</strong> 
                                        Server #{{ $item->server_id }} has been automatically created and is being deployed.
                                        <a href="{{ route('index') }}" class="btn btn-sm btn-outline-primary ms-2">
                                            View Server
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @elseif($item->status === 'pending')
                            <div class="server-info mt-3">
                                <div class="alert alert-warning d-flex align-items-center">
                                    <i class="fas fa-clock me-2"></i>
                                    <div>
                                        <strong>Pending Deployment:</strong> 
                                        Your server is being created and will be available shortly.
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        @else
                        <div class="text-muted">No plan associated with this order</div>
                        @endif
                    </div>
                    
                    {{-- Order Totals --}}
                    <div class="order-totals mt-4">
                        <div class="row justify-content-end">
                            <div class="col-md-6">
                                <div class="totals-breakdown">
                                    <div class="total-line">
                                        <span>Subtotal:</span>
                                        <span>{{ config('shop.currency.symbol', '$') }}{{ number_format($order->subtotal, 2) }}</span>
                                    </div>
                                    
                                    @if($order->setup_total > 0)
                                    <div class="total-line">
                                        <span>Setup Fees:</span>
                                        <span>{{ config('shop.currency.symbol', '$') }}{{ number_format($order->setup_total, 2) }}</span>
                                    </div>
                                    @endif
                                    
                                    @if($order->discount > 0)
                                    <div class="total-line text-success">
                                        <span>Discount:</span>
                                        <span>-{{ config('shop.currency.symbol', '$') }}{{ number_format($order->discount, 2) }}</span>
                                    </div>
                                    @endif
                                    
                                    @if($order->tax > 0)
                                    <div class="total-line">
                                        <span>Tax:</span>
                                        <span>{{ config('shop.currency.symbol', '$') }}{{ number_format($order->tax, 2) }}</span>
                                    </div>
                                    @endif
                                    
                                    <hr>
                                    <div class="total-line total-amount">
                                        <strong>
                                            <span>Total:</span>
                                            <span class="text-success">{{ config('shop.currency.symbol', '$') }}{{ number_format($order->total, 2) }}</span>
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Payment Information --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-credit-card"></i>
                        Payment Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="payment-method">
                                <h6>Payment Method</h6>
                                <div class="d-flex align-items-center">
                                    @if($order->payment_method === 'stripe')
                                        <i class="fab fa-cc-stripe fa-2x text-primary me-3"></i>
                                        <div>
                                            <div class="fw-bold">Credit/Debit Card</div>
                                            @if($order->metadata && isset($order->metadata['card_last4']))
                                                <small class="text-muted">**** **** **** {{ $order->metadata['card_last4'] }}</small>
                                            @endif
                                        </div>
                                    @elseif($order->payment_method === 'paypal')
                                        <i class="fab fa-paypal fa-2x text-primary me-3"></i>
                                        <div>
                                            <div class="fw-bold">PayPal</div>
                                            <small class="text-muted">PayPal Account</small>
                                        </div>
                                    @elseif($order->payment_method === 'wallet')
                                        <i class="fas fa-wallet fa-2x text-success me-3"></i>
                                        <div>
                                            <div class="fw-bold">Account Credit</div>
                                            <small class="text-muted">Wallet Payment</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="payment-status">
                                <h6>Payment Status</h6>
                                <span class="badge badge-lg bg-{{ $order->payment_status === 'paid' ? 'success' : ($order->payment_status === 'pending' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($order->payment_status) }}
                                </span>
                                
                                @if($order->payment_id)
                                <div class="mt-2">
                                    <small class="text-muted">
                                        Transaction ID: {{ $order->payment_id }}
                                    </small>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Billing Address --}}
            @if($order->billing_address)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-map-marker-alt"></i>
                        Billing Address
                    </h5>
                </div>
                <div class="card-body">
                    <address class="mb-0">
                        <strong>{{ $order->billing_address['first_name'] }} {{ $order->billing_address['last_name'] }}</strong><br>
                        @if(isset($order->billing_address['company']) && $order->billing_address['company'])
                            {{ $order->billing_address['company'] }}<br>
                        @endif
                        {{ $order->billing_address['address'] }}<br>
                        @if(isset($order->billing_address['address2']) && $order->billing_address['address2'])
                            {{ $order->billing_address['address2'] }}<br>
                        @endif
                        {{ $order->billing_address['city'] }}, {{ $order->billing_address['state'] }} {{ $order->billing_address['postal_code'] }}<br>
                        {{ $order->billing_address['country'] }}
                    </address>
                </div>
            </div>
            @endif
            
            {{-- Next Steps --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-tasks"></i>
                        What's Next?
                    </h5>
                </div>
                <div class="card-body">
                    <div class="next-steps">
                        <div class="step-item">
                            <div class="step-icon">
                                <i class="fas fa-envelope text-info"></i>
                            </div>
                            <div class="step-content">
                                <h6>Email Confirmation</h6>
                                <p class="mb-0">You will receive an email confirmation with your order details and receipt.</p>
                            </div>
                        </div>
                        
                        <div class="step-item">
                            <div class="step-icon">
                                <i class="fas fa-server text-success"></i>
                            </div>
                            <div class="step-content">
                                <h6>Server Deployment</h6>
                                <p class="mb-0">Your servers are being automatically deployed and will be ready within a few minutes.</p>
                            </div>
                        </div>
                        
                        <div class="step-item">
                            <div class="step-icon">
                                <i class="fas fa-tachometer-alt text-primary"></i>
                            </div>
                            <div class="step-content">
                                <h6>Access Your Services</h6>
                                <p class="mb-0">Once deployed, you can access and manage your servers from your dashboard.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Action Buttons --}}
            <div class="card">
                <div class="card-body text-center">
                    <div class="action-buttons">
                        <a href="{{ route('index') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-tachometer-alt"></i>
                            Go to Dashboard
                        </a>
                        
                        <a href="{{ route('shop.orders.show', $order) }}" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-receipt"></i>
                            View Order Details
                        </a>
                        
                        <a href="{{ route('shop.index') }}" class="btn btn-outline-success btn-lg">
                            <i class="fas fa-store"></i>
                            Continue Shopping
                        </a>
                    </div>
                    
                    {{-- Support Information --}}
                    <div class="support-info mt-4">
                        <hr>
                        <h6>Need Help?</h6>
                        <p class="text-muted mb-3">
                            If you have any questions about your order or need assistance, our support team is here to help.
                        </p>
                        <div class="support-buttons">
                            <a href="#" class="btn btn-sm btn-outline-primary me-2">
                                <i class="fas fa-comments"></i>
                                Live Chat
                            </a>
                            <a href="#" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-ticket-alt"></i>
                                Support Ticket
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.success-card {
    border-color: #28a745;
    border-width: 2px;
}

.success-icon {
    animation: success-pulse 2s infinite;
}

@keyframes success-pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.success-title {
    color: #28a745;
}

.order-info-summary .info-item {
    padding: 20px 15px;
}

.order-info-summary .info-item i {
    display: block;
}

.order-item {
    transition: all 0.3s ease;
}

.order-item:hover {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin: -15px -15px 15px -15px;
}

.item-pricing .unit-price {
    font-weight: 600;
    color: #007bff;
}

.server-info .alert {
    border-radius: 8px;
}

.totals-breakdown .total-line {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
}

.totals-breakdown .total-amount {
    font-size: 1.2em;
    padding-top: 10px;
}

.badge-lg {
    padding: 8px 16px;
    font-size: 0.9em;
}

.step-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 20px;
}

.step-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    flex-shrink: 0;
}

.step-icon i {
    font-size: 1.2em;
}

.step-content h6 {
    margin-bottom: 5px;
    color: #495057;
}

.step-content p {
    color: #6c757d;
    font-size: 0.9em;
}

.action-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.support-buttons {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .order-info-summary .row {
        text-align: center;
    }
    
    .order-info-summary .info-item {
        margin-bottom: 20px;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .action-buttons .btn {
        width: 100%;
        max-width: 300px;
    }
    
    .order-item .row > div {
        margin-bottom: 15px;
        text-align: center;
    }
}
</style>
@endpush
