@extends('shop::layout')

@section('shop-title', 'Shop Dashboard')

@section('shop-content')
<div class="shop-dashboard">
    <div class="row">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h1>
                    <i class="fas fa-tachometer-alt"></i>
                    Shop Dashboard
                </h1>
                
                <div class="dashboard-actions">
                    <a href="{{ route('shop.index') }}" class="btn btn-primary">
                        <i class="fas fa-store"></i>
                        Browse Shop
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Quick Stats --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card wallet-stat">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="stat-info">
                            <h4 class="stat-value">{{ config('shop.currency.symbol', '$') }}{{ number_format($wallet->balance, 2) }}</h4>
                            <p class="stat-label">Wallet Balance</p>
                        </div>
                    </div>
                    <a href="{{ route('shop.wallet.index') }}" class="btn btn-sm btn-outline-success mt-2">
                        Manage Wallet
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card stat-card orders-stat">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-info">
                            <h4 class="stat-value">{{ $stats['total_orders'] }}</h4>
                            <p class="stat-label">Total Orders</p>
                        </div>
                    </div>
                    <a href="{{ route('shop.orders.index') }}" class="btn btn-sm btn-outline-primary mt-2">
                        View Orders
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card stat-card servers-stat">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon">
                            <i class="fas fa-server"></i>
                        </div>
                        <div class="stat-info">
                            <h4 class="stat-value">{{ $stats['active_servers'] }}</h4>
                            <p class="stat-label">Active Servers</p>
                        </div>
                    </div>
                    <a href="{{ route('index') }}" class="btn btn-sm btn-outline-info mt-2">
                        Manage Servers
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card stat-card spending-stat">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <h4 class="stat-value">{{ config('shop.currency.symbol', '$') }}{{ number_format($stats['monthly_spending'], 2) }}</h4>
                            <p class="stat-label">This Month</p>
                        </div>
                    </div>
                    <small class="text-muted">Monthly Spending</small>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Recent Activity & Quick Actions --}}
    <div class="row mb-4">
        <div class="col-lg-8">
            {{-- Recent Orders --}}
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-clock"></i>
                            Recent Orders
                        </h5>
                        <a href="{{ route('shop.orders.index') }}" class="btn btn-sm btn-outline-primary">
                            View All
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($recentOrders->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <tbody>
                                @foreach($recentOrders as $order)
                                <tr>
                                    <td>
                                        <div class="order-info">
                                            <div class="fw-bold">
                                                <a href="{{ route('shop.orders.show', $order) }}" class="text-decoration-none">
                                                    Order #{{ $order->order_number }}
                                                </a>
                                            </div>
                                            <small class="text-muted">{{ $order->created_at->diffForHumans() }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="order-items">
                                            @foreach($order->items->take(2) as $item)
                                                <div class="small">{{ $item->plan->product->name }}</div>
                                            @endforeach
                                            @if($order->items->count() > 2)
                                                <small class="text-muted">+{{ $order->items->count() - 2 }} more</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $order->getStatusColor() }}">
                                            {{ $order->getStatusLabel() }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-success">
                                            {{ config('shop.currency.symbol', '$') }}{{ number_format($order->total, 2) }}
                                        </strong>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="fas fa-shopping-bag fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No orders yet</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            {{-- Quick Actions --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt"></i>
                        Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('shop.index') }}" class="btn btn-success">
                            <i class="fas fa-store"></i>
                            Browse Products
                        </a>
                        
                        <button class="btn btn-outline-success" onclick="Shop.addFunds()">
                            <i class="fas fa-plus-circle"></i>
                            Add Funds
                        </button>
                        
                        @if($cartCount > 0)
                        <a href="{{ route('shop.cart') }}" class="btn btn-outline-warning">
                            <i class="fas fa-shopping-cart"></i>
                            Complete Cart ({{ $cartCount }})
                        </a>
                        @endif
                        
                        <a href="{{ route('shop.orders.index') }}" class="btn btn-outline-primary">
                            <i class="fas fa-list"></i>
                            View All Orders
                        </a>
                        
                        <a href="{{ route('shop.wallet.index') }}" class="btn btn-outline-info">
                            <i class="fas fa-wallet"></i>
                            Wallet History
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Active Services & Upcoming Renewals --}}
    <div class="row">
        <div class="col-lg-8">
            {{-- Active Services --}}
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-server"></i>
                            Active Services
                        </h5>
                        <a href="{{ route('index') }}" class="btn btn-sm btn-outline-info">
                            Manage All
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($activeServices->count() > 0)
                    <div class="services-list">
                        @foreach($activeServices as $service)
                        <div class="service-item {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="row align-items-center p-3">
                                <div class="col-md-4">
                                    <div class="service-info">
                                        <h6 class="mb-1">{{ $service->plan->product->name }}</h6>
                                        <p class="mb-1 text-primary">{{ $service->plan->name }}</p>
                                        <small class="text-muted">Order #{{ $service->order->order_number }}</small>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 text-center">
                                    @if($service->server_id)
                                        <span class="badge bg-success">
                                            <i class="fas fa-play"></i>
                                            Online
                                        </span>
                                    @elseif($service->status === 'provisioning')
                                        <span class="badge bg-warning">
                                            <i class="fas fa-clock"></i>
                                            Provisioning
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($service->status) }}</span>
                                    @endif
                                </div>
                                
                                <div class="col-md-3 text-center">
                                    @if($service->next_billing_date)
                                        <div class="next-billing">
                                            <small class="text-muted">Next Billing</small>
                                            <div class="fw-bold">{{ $service->next_billing_date->format('M d, Y') }}</div>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="col-md-2 text-end">
                                    @if($service->server_id && $service->server)
                                        <a href="/server/{{ $service->server->uuidShort }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-cog"></i>
                                            Manage
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-4">
                        <i class="fas fa-server fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No active services</p>
                        <a href="{{ route('shop.index') }}" class="btn btn-sm btn-primary mt-2">
                            <i class="fas fa-store"></i>
                            Browse Products
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            {{-- Upcoming Renewals --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt"></i>
                        Upcoming Renewals
                    </h5>
                </div>
                <div class="card-body">
                    @if($upcomingRenewals->count() > 0)
                    <div class="renewals-list">
                        @foreach($upcomingRenewals as $renewal)
                        <div class="renewal-item {{ !$loop->last ? 'border-bottom pb-3 mb-3' : '' }}">
                            <div class="renewal-info">
                                <h6 class="mb-1">{{ $renewal->plan->product->name }}</h6>
                                <small class="text-muted">{{ $renewal->plan->name }}</small>
                            </div>
                            
                            <div class="renewal-date mt-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-{{ $renewal->getDaysUntilRenewal() <= 3 ? 'danger' : 'warning' }}">
                                        {{ $renewal->next_billing_date->format('M d') }}
                                    </span>
                                    <strong class="text-success">
                                        {{ config('shop.currency.symbol', '$') }}{{ number_format($renewal->plan->price, 2) }}
                                    </strong>
                                </div>
                                <small class="text-muted">
                                    {{ $renewal->next_billing_date->diffForHumans() }}
                                </small>
                            </div>
                        </div>
                        @endforeach
                        
                        <div class="text-center mt-3">
                            <a href="{{ route('shop.orders.index') }}" class="btn btn-sm btn-outline-primary">
                                View All Renewals
                            </a>
                        </div>
                    </div>
                    @else
                    <div class="text-center">
                        <i class="fas fa-calendar-check fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No upcoming renewals</p>
                    </div>
                    @endif
                </div>
            </div>
            
            {{-- Account Health --}}
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-heart"></i>
                        Account Health
                    </h5>
                </div>
                <div class="card-body">
                    <div class="health-items">
                        {{-- Wallet Balance Health --}}
                        <div class="health-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-wallet text-{{ $wallet->balance >= 25 ? 'success' : ($wallet->balance >= 10 ? 'warning' : 'danger') }}"></i>
                                    <span>Wallet Balance</span>
                                </div>
                                <span class="badge bg-{{ $wallet->balance >= 25 ? 'success' : ($wallet->balance >= 10 ? 'warning' : 'danger') }}">
                                    @if($wallet->balance >= 25)
                                        Healthy
                                    @elseif($wallet->balance >= 10)
                                        Low
                                    @else
                                        Critical
                                    @endif
                                </span>
                            </div>
                        </div>
                        
                        {{-- Payment Methods --}}
                        <div class="health-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-credit-card text-{{ $hasPaymentMethods ? 'success' : 'warning' }}"></i>
                                    <span>Payment Methods</span>
                                </div>
                                <span class="badge bg-{{ $hasPaymentMethods ? 'success' : 'warning' }}">
                                    {{ $hasPaymentMethods ? 'Set up' : 'Missing' }}
                                </span>
                            </div>
                        </div>
                        
                        {{-- Auto-Renewal Status --}}
                        <div class="health-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-sync-alt text-{{ $wallet->auto_topup_enabled ? 'success' : 'secondary' }}"></i>
                                    <span>Auto Top-Up</span>
                                </div>
                                <span class="badge bg-{{ $wallet->auto_topup_enabled ? 'success' : 'secondary' }}">
                                    {{ $wallet->auto_topup_enabled ? 'Enabled' : 'Disabled' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    @if($wallet->balance < 10 || !$hasPaymentMethods)
                    <div class="alert alert-warning mt-3 mb-0">
                        <small>
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Action Required:</strong>
                            @if($wallet->balance < 10)
                                Your wallet balance is low. 
                                <a href="{{ route('shop.wallet.index') }}" class="alert-link">Add funds</a>
                            @endif
                            @if(!$hasPaymentMethods)
                                Set up payment methods for auto-renewal.
                            @endif
                        </small>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh dashboard data every 30 seconds
    setInterval(function() {
        refreshDashboardStats();
    }, 30000);
    
    function refreshDashboardStats() {
        fetch('{{ route("shop.dashboard.stats") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateStats(data.stats);
                }
            })
            .catch(error => {
                console.log('Stats refresh failed:', error);
            });
    }
    
    function updateStats(stats) {
        // Update wallet balance
        const walletValue = document.querySelector('.wallet-stat .stat-value');
        if (walletValue) {
            walletValue.textContent = '{{ config("shop.currency.symbol", "$") }}' + parseFloat(stats.wallet_balance).toFixed(2);
        }
        
        // Update other stats as needed
        const totalOrders = document.querySelector('.orders-stat .stat-value');
        if (totalOrders) {
            totalOrders.textContent = stats.total_orders;
        }
        
        const activeServers = document.querySelector('.servers-stat .stat-value');
        if (activeServers) {
            activeServers.textContent = stats.active_servers;
        }
        
        const monthlySpending = document.querySelector('.spending-stat .stat-value');
        if (monthlySpending) {
            monthlySpending.textContent = '{{ config("shop.currency.symbol", "$") }}' + parseFloat(stats.monthly_spending).toFixed(2);
        }
    }
});

// Add funds quick action
if (typeof Shop === 'undefined') {
    window.Shop = {};
}

Shop.addFunds = function() {
    window.location.href = '{{ route("shop.wallet.index") }}';
};
</script>
@endpush

@push('styles')
<style>
.stat-card {
    border: none;
    border-radius: 12px;
    transition: all 0.3s ease;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.wallet-stat {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.orders-stat {
    background: linear-gradient(135deg, #007bff 0%, #6f42c1 100%);
    color: white;
}

.servers-stat {
    background: linear-gradient(135deg, #17a2b8 0%, #007bff 100%);
    color: white;
}

.spending-stat {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    color: white;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.stat-icon i {
    font-size: 1.8em;
}

.stat-value {
    font-weight: 700;
    margin-bottom: 5px;
    color: inherit;
}

.stat-label {
    margin: 0;
    opacity: 0.9;
    font-size: 0.9em;
}

.service-item {
    transition: all 0.2s ease;
}

.service-item:hover {
    background-color: #f8f9fa;
}

.service-info h6 {
    color: #495057;
}

.service-info .text-primary {
    font-weight: 500;
}

.renewal-item {
    padding: 8px 0;
}

.renewal-info h6 {
    color: #495057;
    font-size: 0.9em;
}

.health-item {
    padding: 8px 0;
    border-bottom: 1px solid #f1f3f4;
}

.health-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.health-item i {
    width: 20px;
    margin-right: 8px;
}

.order-info .fw-bold a {
    color: #495057;
}

.order-info .fw-bold a:hover {
    color: #007bff;
}

.dashboard-actions {
    display: flex;
    gap: 10px;
}

@media (max-width: 768px) {
    .dashboard-actions {
        width: 100%;
        margin-top: 15px;
    }
    
    .dashboard-actions .btn {
        flex: 1;
    }
    
    .stat-card .card-body {
        padding: 15px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        margin-right: 10px;
    }
    
    .stat-icon i {
        font-size: 1.4em;
    }
    
    .stat-value {
        font-size: 1.2em;
    }
    
    .service-item .row > div {
        margin-bottom: 10px;
        text-align: center;
    }
}
</style>
@endpush
