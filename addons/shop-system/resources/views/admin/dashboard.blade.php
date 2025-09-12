@extends('layouts.admin')

@section('title')
    Shop Dashboard
@endsection

@section('content-header')
    <div class="col-sm-6">
        <h1>Shop Dashboard</h1>
        <p class="small text-muted">Overview of shop performance and key metrics.</p>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Admin</a></li>
            <li class="breadcrumb-item active">Shop Dashboard</li>
        </ol>
    </div>
@endsection

@section('content')
<div class="row">
    {{-- Key Metrics Cards --}}
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ config('shop.currency.symbol', '$') }}{{ number_format($metrics['total_revenue'], 2) }}</h3>
                <p>Total Revenue</p>
            </div>
            <div class="icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <a href="{{ route('admin.shop.orders.index') }}" class="small-box-footer">
                View Orders <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $metrics['total_orders'] }}</h3>
                <p>Total Orders</p>
            </div>
            <div class="icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <a href="{{ route('admin.shop.orders.index') }}" class="small-box-footer">
                Manage Orders <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $metrics['active_subscriptions'] }}</h3>
                <p>Active Subscriptions</p>
            </div>
            <div class="icon">
                <i class="fas fa-sync-alt"></i>
            </div>
            <a href="{{ route('admin.shop.orders.index', ['status' => 'active']) }}" class="small-box-footer">
                View Active <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $metrics['pending_orders'] }}</h3>
                <p>Pending Orders</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
            <a href="{{ route('admin.shop.orders.index', ['status' => 'pending']) }}" class="small-box-footer">
                Review Pending <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    {{-- Revenue Chart --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-line"></i>
                    Revenue Overview
                </h3>
                <div class="card-tools">
                    <div class="btn-group btn-group-sm" data-toggle="btn-toggle">
                        <button type="button" class="btn btn-default active" data-period="7">7 Days</button>
                        <button type="button" class="btn btn-default" data-period="30">30 Days</button>
                        <button type="button" class="btn btn-default" data-period="90">90 Days</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="300"></canvas>
            </div>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-tachometer-alt"></i>
                    Today's Activity
                </h3>
            </div>
            <div class="card-body">
                <div class="info-box mb-3">
                    <span class="info-box-icon bg-success"><i class="fas fa-shopping-bag"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">New Orders Today</span>
                        <span class="info-box-number">{{ $todayStats['orders'] }}</span>
                    </div>
                </div>

                <div class="info-box mb-3">
                    <span class="info-box-icon bg-info"><i class="fas fa-dollar-sign"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Revenue Today</span>
                        <span class="info-box-number">{{ config('shop.currency.symbol', '$') }}{{ number_format($todayStats['revenue'], 2) }}</span>
                    </div>
                </div>

                <div class="info-box mb-3">
                    <span class="info-box-icon bg-warning"><i class="fas fa-server"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Servers Created</span>
                        <span class="info-box-number">{{ $todayStats['servers'] }}</span>
                    </div>
                </div>

                <div class="info-box">
                    <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Failed Payments</span>
                        <span class="info-box-number">{{ $todayStats['failed_payments'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Recent Orders --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i>
                    Recent Orders
                </h3>
                <div class="card-tools">
                    <a href="{{ route('admin.shop.orders.index') }}" class="btn btn-tool">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                @if($recentOrders->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentOrders as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.shop.orders.show', $order) }}" class="text-decoration-none">
                                        #{{ $order->order_number }}
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://www.gravatar.com/avatar/{{ md5($order->user->email) }}?s=32" 
                                             class="img-circle me-2" width="32" height="32">
                                        <div>
                                            <div class="fw-bold">{{ $order->user->username }}</div>
                                            <small class="text-muted">{{ $order->user->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="order-items-summary">
                                        @if($order->plan)
                                            <div class="small">{{ $order->plan->name }} 
                                                @if($order->plan->category)
                                                    ({{ $order->plan->category->name }})
                                                @endif
                                            </div>
                                            <small class="text-muted">{{ ucfirst($order->billing_cycle) }} billing</small>
                                        @else
                                            <div class="small text-muted">No plan associated</div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $order->getStatusColor() }}">
                                        {{ $order->getStatusLabel() }}
                                    </span>
                                </td>
                                <td>
                                    <strong class="text-success">
                                        {{ config('shop.currency.symbol', '$') }}{{ number_format($order->total, 2) }}
                                    </strong>
                                </td>
                                <td>{{ $order->created_at->format('M d, H:i') }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.shop.orders.show', $order) }}" 
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($order->canProcess())
                                        <button class="btn btn-outline-success process-order-btn" 
                                                data-order-id="{{ $order->id }}">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4">
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No recent orders</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Top Categories & System Health --}}
    <div class="col-lg-4">
        {{-- Top Categories --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-star"></i>
                    Top Categories
                </h3>
            </div>
            <div class="card-body">
                @if($topCategories->count() > 0)
                @foreach($topCategories as $category)
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="fw-bold">{{ $category->name }}</div>
                        <small class="text-muted">{{ $category->order_count }} orders</small>
                    </div>
                    <span class="badge badge-primary">
                        {{ config('shop.currency.symbol', '$') }}{{ number_format($category->total_revenue, 0) }}
                    </span>
                </div>
                @endforeach
                @else
                <p class="text-muted text-center">No order data available</p>
                @endif
            </div>
        </div>

        {{-- System Health --}}
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-heartbeat"></i>
                    System Health
                </h3>
            </div>
            <div class="card-body">
                <div class="health-metrics">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Payment Success Rate</span>
                        <span class="badge badge-{{ $systemHealth['payment_success_rate'] >= 95 ? 'success' : 'warning' }}">
                            {{ number_format($systemHealth['payment_success_rate'], 1) }}%
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Server Provision Success</span>
                        <span class="badge badge-{{ $systemHealth['provision_success_rate'] >= 98 ? 'success' : 'warning' }}">
                            {{ number_format($systemHealth['provision_success_rate'], 1) }}%
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Queue Processing</span>
                        <span class="badge badge-{{ $systemHealth['queue_healthy'] ? 'success' : 'danger' }}">
                            {{ $systemHealth['queue_healthy'] ? 'Healthy' : 'Issues' }}
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Renewal Success Rate</span>
                        <span class="badge badge-{{ $systemHealth['renewal_success_rate'] >= 90 ? 'success' : 'warning' }}">
                            {{ number_format($systemHealth['renewal_success_rate'], 1) }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Quick Actions --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bolt"></i>
                    Quick Actions
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="{{ route('admin.shop.categories.create') }}" class="btn btn-success btn-lg btn-block">
                            <i class="fas fa-plus"></i>
                            Add Category
                        </a>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <a href="{{ route('admin.shop.coupons.create') }}" class="btn btn-info btn-lg btn-block">
                            <i class="fas fa-ticket-alt"></i>
                            Create Coupon
                        </a>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <button class="btn btn-warning btn-lg btn-block" onclick="AdminShop.processRenewals()">
                            <i class="fas fa-sync-alt"></i>
                            Process Renewals
                        </button>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <a href="{{ route('admin.shop.reports.index') }}" class="btn btn-secondary btn-lg btn-block">
                            <i class="fas fa-chart-bar"></i>
                            View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
@parent
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const ctx = document.getElementById('revenueChart').getContext('2d');
    let revenueChart;
    
    function initRevenueChart(period = 7) {
        if (revenueChart) {
            revenueChart.destroy();
        }
        
        fetch(`/admin/shop/dashboard/chart-data?period=${period}`)
            .then(response => response.json())
            .then(data => {
                revenueChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Revenue',
                            data: data.revenue,
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.1)',
                            tension: 0.1,
                            fill: true
                        }, {
                            label: 'Orders',
                            data: data.orders,
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.1)',
                            tension: 0.1,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                ticks: {
                                    callback: function(value) {
                                        return '{{ config("shop.currency.symbol", "$") }}' + value;
                                    }
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false,
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        if (context.datasetIndex === 0) {
                                            return 'Revenue: {{ config("shop.currency.symbol", "$") }}' + context.parsed.y;
                                        } else {
                                            return 'Orders: ' + context.parsed.y;
                                        }
                                    }
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error loading chart data:', error);
            });
    }
    
    // Period selection buttons
    document.querySelectorAll('[data-period]').forEach(button => {
        button.addEventListener('click', function() {
            // Update active button
            document.querySelectorAll('[data-period]').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            // Update chart
            const period = parseInt(this.dataset.period);
            initRevenueChart(period);
        });
    });
    
    // Process order buttons
    document.querySelectorAll('.process-order-btn').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            
            if (confirm('Are you sure you want to process this order?')) {
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                
                fetch(`/admin/shop/orders/${orderId}/process`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        swal('Success!', 'Order processed successfully.', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        swal('Error!', data.message, 'error');
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-play"></i>';
                    }
                })
                .catch(error => {
                    console.error('Error processing order:', error);
                    swal('Error!', 'Failed to process order.', 'error');
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-play"></i>';
                });
            }
        });
    });
    
    // Initialize chart
    initRevenueChart();
    
    // Auto-refresh dashboard every 30 seconds
    setInterval(() => {
        location.reload();
    }, 30000);
});

// Admin Shop namespace
window.AdminShop = {
    processRenewals: function() {
        if (confirm('Process all pending renewals now?')) {
            fetch('/admin/shop/renewals/process', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    swal('Success!', `Processed ${data.processed} renewals.`, 'success');
                } else {
                    swal('Error!', data.message, 'error');
                }
            });
        }
    }
};
</script>
@endsection

@section('footer-styles')
<style>
.small-box {
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.info-box {
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.order-items-summary {
    max-width: 200px;
}

.health-metrics {
    font-size: 0.9em;
}

.card {
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}

@media (max-width: 768px) {
    .small-box .inner h3 {
        font-size: 1.5rem;
    }
    
    .info-box-number {
        font-size: 1.2rem;
    }
    
    .table-responsive {
        border: none;
    }
}
</style>
@endsection
