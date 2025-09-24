@extends('shop::layout')

@section('shop-title', $isSuccessView ? 'Order Complete' : 'Order #' . $order->id)

@section('shop-content')
@if($isSuccessView)
{{-- ORDER SUCCESS VIEW --}}
<div class="checkout-success-container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            {{-- Success Header --}}
            <div class="block block-rounded success-card mb-4">
                <div class="block-content text-center py-5">
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
                                    <strong class="text-primary">#ORD{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</strong>
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
                                                                        <p class="text-success fs-4 fw-bold mb-0">
                                        {{ $currencySymbol }}{{ number_format($order->total_amount, 2) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Order Details --}}
            <div class="block block-rounded mb-4">
                <div class="block-header">
                    <h3 class="block-title">
                        <i class="fas fa-list"></i>
                        Order Details
                    </h3>
                </div>
                <div class="block-content">
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
                                            <span class="badge bg-secondary me-2">{{ ucfirst(str_replace('_', ' ', $order->billing_cycle)) }}</span>
                                            <span class="badge bg-info">Quantity: 1</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 text-center">
                                    <div class="item-pricing">
                                        <div class="unit-price">
                                            {{ $currencySymbol }}{{ number_format($order->amount, 2) }}
                                        </div>
                                        @if($order->setup_fee > 0)
                                            <small class="text-muted">
                                                +{{ $currencySymbol }}{{ number_format($order->setup_fee, 2) }} setup
                                            </small>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="col-md-3 text-end">
                                    <div class="item-total">
                                        <strong class="text-success">
                                            {{ $currencySymbol }}{{ number_format($order->total_amount, 2) }}
                                        </strong>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Server Information (if available) --}}
                            @if($order->server_id)
                            <div class="server-info mt-3">
                                <div class="alert alert-success d-flex align-items-center">
                                    <i class="fas fa-server me-2"></i>
                                    <div>
                                        <strong>Server Created:</strong> 
                                        Server #{{ $order->server_id }} has been automatically created and is being deployed.
                                        <a href="/server/{{ $order->server->uuidShort ?? $order->server_id }}" class="btn btn-sm btn-outline-primary ms-2">
                                            View Server
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @elseif($order->status === 'processing')
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
                                        <span>{{ $currencySymbol }}{{ number_format($order->amount, 2) }}</span>
                                    </div>
                                    
                                    @if($order->setup_fee > 0)
                                    <div class="total-line">
                                        <span>Setup Fees:</span>
                                        <span>{{ $currencySymbol }}{{ number_format($order->setup_fee, 2) }}</span>
                                    </div>
                                    @endif
                                    
                                    @if($order->discount > 0)
                                    <div class="total-line text-success">
                                        <span>Discount:</span>
                                        <span>-{{ $currencySymbol }}{{ number_format($order->discount, 2) }}</span>
                                    </div>
                                    @endif
                                    
                                    @if($order->tax > 0)
                                    <div class="total-line">
                                        <span>Tax:</span>
                                        <span>{{ $currencySymbol }}{{ number_format($order->tax, 2) }}</span>
                                    </div>
                                    @endif
                                    
                                    <hr>
                                    <div class="total-line total-amount">
                                        <strong>
                                            <span>Total:</span>
                                            <span class="text-success">{{ $currencySymbol }}{{ number_format($order->total_amount, 2) }}</span>
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Payment Information --}}
            <div class="block block-rounded mb-4">
                <div class="block-header">
                    <h3 class="block-title">
                        <i class="fas fa-credit-card"></i>
                        Payment Information
                    </h3>
                </div>
                <div class="block-content">
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
                                @php $paymentStatus = $order->getPaymentStatus(); @endphp
                                <span class="badge badge-lg bg-{{ $paymentStatus === 'completed' ? 'success' : ($paymentStatus === 'pending' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($paymentStatus) }}
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
            
            {{-- Next Steps --}}
            <div class="block block-rounded mb-4">
                <div class="block-header">
                    <h3 class="block-title">
                        <i class="fas fa-tasks"></i>
                        What's Next?
                    </h3>
                </div>
                <div class="block-content">
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
            <div class="block block-rounded">
                <div class="block-content text-center">
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

@else
{{-- REGULAR ORDER VIEW --}}
<div class="row">
    <div class="col-12">
        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('shop.index') }}">Shop</a></li>
                <li class="breadcrumb-item"><a href="{{ route('shop.orders.index') }}">Orders</a></li>
                <li class="breadcrumb-item active">#{{ $order->id }}</li>
            </ol>
        </nav>
        
        {{-- Order Details with OneUI Block Styling --}}
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    #ORD{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}
                </h3>
                <div class="block-options">
                    @php $paymentStatus = $order->getPaymentStatus(); @endphp
                    <span class="badge bg-{{ $paymentStatus === 'completed' ? 'success' : ($paymentStatus === 'pending' ? 'warning' : 'danger') }} me-2">
                        {{ ucfirst($paymentStatus) }}
                    </span>
                    <button type="button" class="btn-block-option" onclick="window.print();">
                        <i class="si si-printer me-1"></i> Print Invoice
                    </button>
                    <a href="{{ route('shop.orders.invoice', $order) }}" class="btn-block-option">
                        <i class="si si-cloud-download me-1"></i> Download PDF
                    </a>
                </div>
            </div>
            <div class="block-content">
                <div class="p-sm-4 p-xl-7">
                    <div class="row mb-4">
                        <div class="col-6 fs-sm">
                            <p class="h3">{{ config('app.name', 'Game Server Hosting') }}</p>
                            <address>
                                Professional Gaming Services<br>
                                Game Server Hosting Platform<br>
                                Worldwide Service Coverage<br>
                                support@{{ request()->getHost() }}
                            </address>
                        </div>
                        <div class="col-6 text-end fs-sm">
                            <p class="h3">Customer</p>
                            <address>
                                @if($order->getCustomerName())
                                    {{ $order->getCustomerName() }}<br>
                                @endif
                                {{ $order->user->email }}<br>
                                @if(!empty($order->billing_details['company']))
                                    {{ $order->billing_details['company'] }}<br>
                                @endif
                                @if($order->getBillingAddress())
                                    {!! nl2br(e($order->getBillingAddress())) !!}
                                @endif
                            </address>
                            <p class="text-muted">
                                <strong>Order Date:</strong> {{ $order->created_at->format('M j, Y') }}<br>
                                <strong>Payment Status:</strong> 
                                <span class="badge bg-{{ $paymentStatus === 'completed' ? 'success' : ($paymentStatus === 'pending' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($paymentStatus) }}
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <div class="table-responsive push">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 60px;">#</th>
                                    <th>Product</th>
                                    <th class="text-center" style="width: 90px;">Qty</th>
                                    <th class="text-end" style="width: 120px;">Unit Price</th>
                                    <th class="text-end" style="width: 120px;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="text-center">1</td>
                                    <td>
                                        <p class="fw-semibold mb-1">{{ $order->plan->name }}</p>
                                        <div class="text-muted">
                                            @if($order->plan->description)
                                                {{ $order->plan->description }}<br>
                                            @endif
                                            Billing Cycle: {{ ucfirst(str_replace('_', ' ', $order->billing_cycle)) }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-primary">1</span>
                                    </td>
                                    <td class="text-end">{{ $currencySymbol }}{{ number_format($order->amount, 2) }}</td>
                                    <td class="text-end">{{ $currencySymbol }}{{ number_format($order->amount, 2) }}</td>
                                </tr>
                                @if($order->setup_fee > 0)
                                <tr>
                                    <td class="text-center">2</td>
                                    <td>
                                        <p class="fw-semibold mb-1">Setup Fee</p>
                                        <div class="text-muted">One-time setup charge</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-primary">1</span>
                                    </td>
                                    <td class="text-end">{{ $currencySymbol }}{{ number_format($order->setup_fee, 2) }}</td>
                                    <td class="text-end">{{ $currencySymbol }}{{ number_format($order->setup_fee, 2) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td colspan="4" class="fw-semibold text-end">Subtotal</td>
                                    <td class="text-end">{{ $currencySymbol }}{{ number_format($order->total_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="fw-semibold text-end">Tax</td>
                                    <td class="text-end">{{ $currencySymbol }}0.00</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="fw-bold text-uppercase text-end bg-body-light">Total Due</td>
                                    <td class="fw-bold text-end bg-body-light">{{ $currencySymbol }}{{ number_format($order->total_amount, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <p class="fs-sm text-muted text-center">
                        Thank you for choosing our game server hosting services. We look forward to providing you with excellent gaming performance!
                    </p>
                </div>
            </div>
        </div>
        
        {{-- Server Variable Input Section --}}
        @if($order->status === 'processing' && $order->requiresVariableInput())
            <div class="block block-rounded mt-4">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="si si-settings me-2"></i>Server Configuration Required
                    </h3>
                </div>
                <div class="block-content">
                    <div class="alert alert-info">
                        <p class="mb-0">Your order requires additional configuration before we can create your server. Please provide the required information below:</p>
                    </div>
                    
                    <form id="server-variables-form" method="POST" action="{{ route('shop.orders.create-server', $order) }}">
                        @csrf
                        
                        @foreach($order->getRequiredVariables() as $variable)
                            <div class="mb-3">
                                <label for="var_{{ $variable['env_variable'] }}" class="form-label">
                                    <strong>{{ $variable['user_friendly_name'] }}</strong>
                                    @if($variable['type'] === 'steam_token')
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>
                                
                                @if($variable['type'] === 'password')
                                    <input type="password" 
                                           class="form-control" 
                                           name="variables[{{ $variable['env_variable'] }}]" 
                                           id="var_{{ $variable['env_variable'] }}"
                                           placeholder="Enter {{ strtolower($variable['user_friendly_name']) }}"
                                           {{ $variable['type'] === 'steam_token' ? 'required' : '' }}>
                                @elseif($variable['type'] === 'boolean')
                                    <select class="form-control" 
                                            name="variables[{{ $variable['env_variable'] }}]" 
                                            id="var_{{ $variable['env_variable'] }}">
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>
                                @elseif($variable['type'] === 'number')
                                    <input type="number" 
                                           class="form-control" 
                                           name="variables[{{ $variable['env_variable'] }}]" 
                                           id="var_{{ $variable['env_variable'] }}"
                                           placeholder="Enter {{ strtolower($variable['user_friendly_name']) }}"
                                           {{ $variable['type'] === 'steam_token' ? 'required' : '' }}>
                                @else
                                    <input type="text" 
                                           class="form-control" 
                                           name="variables[{{ $variable['env_variable'] }}]" 
                                           id="var_{{ $variable['env_variable'] }}"
                                           placeholder="Enter {{ strtolower($variable['user_friendly_name']) }}"
                                           {{ $variable['type'] === 'steam_token' ? 'required' : '' }}>
                                @endif
                                
                                @if($variable['help_text'])
                                    <div class="form-text">{{ $variable['help_text'] }}</div>
                                @endif
                            </div>
                        @endforeach
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-success" id="create-server-btn">
                                <i class="si si-rocket me-1"></i>
                                Create Server
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
        
        {{-- Server Information --}}
        @if($order->server_id)
            <div class="block block-rounded mt-4">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="si si-screen-desktop me-2"></i>Server Information
                    </h3>
                    <div class="block-options">
                        @if($order->server)
                            @php 
                                $status = $order->server->status ?? 'installing';
                            @endphp
                            @if($status === 'installing')
                                <span class="badge bg-warning">Installing</span>
                            @elseif($status === 'install_failed')
                                <span class="badge bg-danger">Install Failed</span>
                            @elseif($status === 'reinstall_failed')
                                <span class="badge bg-danger">Reinstall Failed</span>
                            @elseif($status === 'suspended')
                                <span class="badge bg-danger">Suspended</span>
                            @elseif($status === 'restoring_backup')
                                <span class="badge bg-info">Restoring Backup</span>
                            @else
                                <span class="badge bg-success">Ready</span>
                            @endif
                        @else
                            <span class="badge bg-warning">Creating Server</span>
                        @endif
                    </div>
                </div>
                <div class="block-content">
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                                <tr>
                                    <th>Server ID</th>
                                    <th>Name</th>
                                    <th>Install Status</th>
                                    <th>Connection</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-semibold">
                                        {{ $order->server_id }}
                                    </td>
                                    <td>
                                        @if($order->server)
                                            {{ $order->server->name }}
                                        @else
                                            <span class="text-muted">Loading...</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($order->server)
                                            @php 
                                                $status = $order->server->status ?? 'installing';
                                            @endphp
                                            @if($status === 'installing')
                                                <span class="badge bg-warning">Installing</span>
                                            @elseif($status === 'install_failed')
                                                <span class="badge bg-danger">Install Failed</span>
                                            @elseif($status === 'reinstall_failed')
                                                <span class="badge bg-danger">Reinstall Failed</span>
                                            @elseif($status === 'suspended')
                                                <span class="badge bg-danger">Suspended</span>
                                            @elseif($status === 'restoring_backup')
                                                <span class="badge bg-info">Restoring Backup</span>
                                            @else
                                                <span class="badge bg-success">Ready</span>
                                                <br><small class="text-muted">Use manage button to check power status</small>
                                            @endif
                                        @else
                                            <span class="badge bg-warning">Creating</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($order->server && $order->server->allocation)
                                            <code>{{ $order->server->allocation->ip }}:{{ $order->server->allocation->port }}</code>
                                        @else
                                            <span class="text-muted">Pending</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($order->server)
                                            <a href="/server/{{ $order->server->uuidShort }}" class="btn btn-sm btn-primary">
                                                <i class="si si-control-panel me-1"></i>
                                                Manage
                                            </a>
                                        @else
                                            <span class="text-muted">Creating...</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
        
        {{-- Action Buttons --}}
        <div class="block block-rounded mt-4">
            <div class="block-content text-center">
                <a href="{{ route('shop.orders.index') }}" class="btn btn-secondary">
                    <i class="si si-arrow-left me-1"></i>
                    Back to Orders
                </a>
                @if(isset($paymentMethods) && count($paymentMethods) > 0 && $order->status === 'pending')
                    <button class="btn btn-success ms-2">
                        <i class="si si-credit-card me-1"></i>
                        Pay Now
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
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
