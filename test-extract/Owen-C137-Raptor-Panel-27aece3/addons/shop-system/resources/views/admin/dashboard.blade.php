@extends('layouts.admin')

@section('title')
    Shop Dashboard
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Shop Dashboard
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Overview of shop performance and key metrics.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.index') }}">Admin</a>
          </li>
          <li class="breadcrumb-item" aria-current="page">
            Shop Dashboard
          </li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row mb-4">
    {{-- Key Metrics Cards --}}
    <div class="col-sm-6 col-xxl-3">
        <div class="block block-rounded d-flex flex-column h-100 mb-3">
            <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                <dl class="mb-0">
                    <dt class="fs-3 fw-bold">{{ $currencySymbol }}{{ number_format($metrics['total_revenue'], 2) }}</dt>
                    <dd class="fs-sm fw-medium text-muted mb-0">Total Revenue</dd>
                </dl>
                <div class="item item-rounded-lg bg-body-light">
                    <i class="fa fa-money fs-3 text-success"></i>
                </div>
            </div>
            <div class="bg-body-light rounded-bottom">
                <a class="block-content block-content-full block-content-sm fs-sm fw-medium d-flex align-items-center justify-content-between" href="{{ route('admin.shop.orders.index') }}">
                    <span>View Orders</span>
                    <i class="fas fa-arrow-alt-circle-right ms-1 opacity-25 fs-base"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xxl-3">
        <div class="block block-rounded d-flex flex-column h-100 mb-3">
            <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                <dl class="mb-0">
                    <dt class="fs-3 fw-bold">{{ $metrics['total_orders'] }}</dt>
                    <dd class="fs-sm fw-medium text-muted mb-0">Total Orders</dd>
                </dl>
                <div class="item item-rounded-lg bg-body-light">
                    <i class="fa fa-shopping-cart fs-3 text-info"></i>
                </div>
            </div>
            <div class="bg-body-light rounded-bottom">
                <a class="block-content block-content-full block-content-sm fs-sm fw-medium d-flex align-items-center justify-content-between" href="{{ route('admin.shop.orders.index') }}">
                    <span>Manage Orders</span>
                    <i class="fas fa-arrow-alt-circle-right ms-1 opacity-25 fs-base"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xxl-3">
        <div class="block block-rounded d-flex flex-column h-100 mb-3">
            <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                <dl class="mb-0">
                    <dt class="fs-3 fw-bold">{{ $metrics['active_subscriptions'] }}</dt>
                    <dd class="fs-sm fw-medium text-muted mb-0">Active Subscriptions</dd>
                </dl>
                <div class="item item-rounded-lg bg-body-light">
                    <i class="fa fa-sync-alt fs-3 text-warning"></i>
                </div>
            </div>
            <div class="bg-body-light rounded-bottom">
                <a class="block-content block-content-full block-content-sm fs-sm fw-medium d-flex align-items-center justify-content-between" href="{{ route('admin.shop.orders.index', ['status' => 'active']) }}">
                    <span>View Active</span>
                    <i class="fas fa-arrow-alt-circle-right ms-1 opacity-25 fs-base"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xxl-3">
        <div class="block block-rounded d-flex flex-column h-100 mb-3">
            <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                <dl class="mb-0">
                    <dt class="fs-3 fw-bold">{{ $metrics['pending_orders'] }}</dt>
                    <dd class="fs-sm fw-medium text-muted mb-0">Pending Orders</dd>
                </dl>
                <div class="item item-rounded-lg bg-body-light">
                    <i class="fa fa-clock fs-3 text-danger"></i>
                </div>
            </div>
            <div class="bg-body-light rounded-bottom">
                <a class="block-content block-content-full block-content-sm fs-sm fw-medium d-flex align-items-center justify-content-between" href="{{ route('admin.shop.orders.index', ['status' => 'pending']) }}">
                    <span>Review Pending</span>
                    <i class="fas fa-arrow-alt-circle-right ms-1 opacity-25 fs-base"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Revenue Chart --}}
    <div class="col-lg-8">
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">
                    <i class="fa fa-chart-line me-1 text-muted"></i>
                    Revenue Overview
                </h3>
                <div class="block-options">
                    <div class="btn-group btn-group-sm" data-toggle="btn-toggle">
                        <button type="button" class="btn btn-outline-secondary active" data-period="7">7 Days</button>
                        <button type="button" class="btn btn-outline-secondary" data-period="30">30 Days</button>
                        <button type="button" class="btn btn-outline-secondary" data-period="90">90 Days</button>
                    </div>
                </div>
            </div>
            <div class="block-content">
                <canvas id="revenueChart" height="300"></canvas>
            </div>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="col-lg-4">
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">
                    <i class="fas fa-tachometer-alt me-1 text-muted"></i>
                    Today's Activity
                </h3>
            </div>
            <div class="block-content">
                <div class="row items-push">
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fa fa-shopping-bag fa-2x text-success"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fs-3 fw-bold">{{ $todayStats['orders'] }}</div>
                                <div class="text-muted fs-sm">New Orders Today</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fa fa-money fa-2x text-info"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fs-3 fw-bold">{{ $currencySymbol }}{{ number_format($todayStats['revenue'], 2) }}</div>
                                <div class="text-muted fs-sm">Revenue Today</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row items-push">
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fa fa-server fa-2x text-warning"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fs-3 fw-bold">{{ $todayStats['servers'] }}</div>
                                <div class="text-muted fs-sm">Servers Created</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fa fa-exclamation-triangle fa-2x text-danger"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fs-3 fw-bold">{{ $todayStats['failed_payments'] }}</div>
                                <div class="text-muted fs-sm">Failed Payments</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Recent Orders --}}
    <div class="col-lg-8">
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">
                    <i class="fa fa-list me-1 text-muted"></i>
                    Recent Orders
                </h3>
                <div class="block-options">
                    <a href="{{ route('admin.shop.orders.index') }}" class="btn-block-option">
                        <i class="fa fa-external-link-alt"></i>
                    </a>
                </div>
            </div>
            <div class="block-content block-content-full">
                @if($recentOrders->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-vcenter">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th>Total</th>
                                <th>Date</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
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
                                    <span class="fs-xs fw-semibold d-inline-block py-1 px-3 rounded-pill bg-{{ $order->getStatusColor() }}-light text-{{ $order->getStatusColor() }}">
                                        {{ $order->getStatusLabel() }}
                                    </span>
                                </td>
                                <td>
                                    <strong class="text-success">
                                        {{ $currencySymbol }}{{ number_format($order->total, 2) }}
                                    </strong>
                                </td>
                                <td>{{ $order->created_at->format('M d, H:i') }}</td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.shop.orders.show', $order) }}" 
                                           class="btn btn-sm btn-alt-secondary">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        @if($order->canProcess())
                                        <button class="btn btn-sm btn-alt-success process-order-btn" 
                                                data-order-id="{{ $order->id }}">
                                            <i class="fa fa-play"></i>
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
                    <i class="fa fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No recent orders</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Top Categories & System Health --}}
    <div class="col-lg-4">
        {{-- Top Categories --}}
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">
                    <i class="fa fa-star me-1 text-muted"></i>
                    Top Categories
                </h3>
            </div>
            <div class="block-content">
                @if($topCategories->count() > 0)
                @foreach($topCategories as $category)
                <div class="d-flex justify-content-between align-items-center py-2">
                    <div>
                        <div class="fw-bold">{{ $category->name }}</div>
                        <small class="text-muted">{{ $category->order_count }} orders</small>
                    </div>
                    <span class="badge bg-primary">
                        {{ $currencySymbol }}{{ number_format($category->total_revenue, 0) }}
                    </span>
                </div>
                @endforeach
                @else
                <p class="text-muted text-center">No order data available</p>
                @endif
            </div>
        </div>

        {{-- System Health --}}
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">
                    <i class="fa fa-heartbeat me-1 text-muted"></i>
                    System Health
                </h3>
            </div>
            <div class="block-content">
                <div class="health-metrics">
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span>Payment Success Rate</span>
                        <span class="badge bg-{{ $systemHealth['payment_success_rate'] >= 95 ? 'success' : 'warning' }}">
                            {{ number_format($systemHealth['payment_success_rate'], 1) }}%
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span>Server Provision Success</span>
                        <span class="badge bg-{{ $systemHealth['provision_success_rate'] >= 98 ? 'success' : 'warning' }}">
                            {{ number_format($systemHealth['provision_success_rate'], 1) }}%
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span>Queue Processing</span>
                        <span class="badge bg-{{ $systemHealth['queue_healthy'] ? 'success' : 'danger' }}">
                            {{ $systemHealth['queue_healthy'] ? 'Healthy' : 'Issues' }}
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span>Renewal Success Rate</span>
                        <span class="badge bg-{{ $systemHealth['renewal_success_rate'] >= 90 ? 'success' : 'warning' }}">
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
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">
                    <i class="fa fa-bolt me-1 text-muted"></i>
                    Quick Actions
                </h3>
            </div>
            <div class="block-content">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="{{ route('admin.shop.categories.create') }}" class="btn btn-success btn-lg w-100">
                            <i class="fa fa-plus me-1"></i>
                            Add Category
                        </a>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <a href="{{ route('admin.shop.coupons.create') }}" class="btn btn-info btn-lg w-100">
                            <i class="fas fa-ticket-alt me-1"></i>
                            Create Coupon
                        </a>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <button class="btn btn-warning btn-lg w-100" onclick="AdminShop.processRenewals()">
                            <i class="fas fa-sync-alt me-1"></i>
                            Process Renewals
                        </button>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <a href="{{ route('admin.shop.reports.index') }}" class="btn btn-outline-primary btn-lg w-100">
                            <i class="fa fa-chart-bar me-1"></i>
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
        
        fetch(`/admin/shop/ajax/dashboard/revenue-chart?period=${period}`)
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
                                        return '{{ $currencySymbol }}' + value;
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
                                            return 'Revenue: {{ $currencySymbol }}' + context.parsed.y;
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
                this.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
                
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
                        this.innerHTML = '<i class="fa fa-play"></i>';
                    }
                })
                .catch(error => {
                    console.error('Error processing order:', error);
                    swal('Error!', 'Failed to process order.', 'error');
                    this.disabled = false;
                    this.innerHTML = '<i class="fa fa-play"></i>';
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
.order-items-summary {
    max-width: 200px;
}

.health-metrics {
    font-size: 0.9em;
}

@media (max-width: 768px) {
    .table-responsive {
        border: none;
    }
    
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        margin-bottom: 2px;
    }
}
</style>
@endsection
