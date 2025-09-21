@extends('layouts.admin')

@section('title')
    Customers Report
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Customers Report
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Detailed customer analytics
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.index') }}">Admin</a>
          </li>
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.shop.dashboard') }}">Shop</a>
          </li>
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.shop.reports.index') }}">Reports</a>
          </li>
          <li class="breadcrumb-item" aria-current="page">
            Customers
          </li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-users me-1"></i>Customer Analytics
                </h3>
                <div class="block-options">
                    <form method="GET" class="d-inline-block">
                        <select name="period" onchange="this.form.submit()" class="form-select form-select-sm">
                            <option value="7" {{ $period == 7 ? 'selected' : '' }}>Last 7 Days</option>
                            <option value="30" {{ $period == 30 ? 'selected' : '' }}>Last 30 Days</option>
                            <option value="90" {{ $period == 90 ? 'selected' : '' }}>Last 90 Days</option>
                            <option value="365" {{ $period == 365 ? 'selected' : '' }}>Last Year</option>
                        </select>
                    </form>
                </div>
            </div>
            <div class="block-content">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="block block-rounded text-center bg-primary-light">
                            <div class="block-content py-3">
                                <div class="fs-1 fw-bold text-primary">
                                    <i class="fa fa-users"></i>
                                </div>
                                <div class="fs-sm fw-semibold text-uppercase text-muted mt-1">New Customers</div>
                                <div class="fs-3 fw-bold text-dark">{{ $newCustomers }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="block block-rounded text-center bg-success-light">
                            <div class="block-content py-3">
                                <div class="fs-1 fw-bold text-success">
                                    <i class="fa fa-user-plus"></i>
                                </div>
                                <div class="fs-sm fw-semibold text-uppercase text-muted mt-1">Active Customers</div>
                                <div class="fs-3 fw-bold text-dark">{{ count($topCustomers) }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="block block-rounded text-center bg-warning-light">
                            <div class="block-content py-3">
                                <div class="fs-1 fw-bold text-warning">
                                    <i class="fa fa-dollar-sign"></i>
                                </div>
                                <div class="fs-sm fw-semibold text-uppercase text-muted mt-1">Avg Lifetime Value</div>
                                <div class="fs-3 fw-bold text-dark">${{ number_format($customerLifetimeValue, 2) }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="block block-rounded text-center bg-danger-light">
                            <div class="block-content py-3">
                                <div class="fs-1 fw-bold text-danger">
                                    <i class="fa fa-chart-line"></i>
                                </div>
                                <div class="fs-sm fw-semibold text-uppercase text-muted mt-1">Growth Rate</div>
                                <div class="fs-3 fw-bold text-dark">+{{ round($newCustomers / max(1, $period) * 30, 1) }}%</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="block block-rounded">
                            <div class="block-header block-header-default">
                                <h3 class="block-title">
                                    <i class="fa fa-crown me-1"></i>Top Customers by Revenue
                                </h3>
                            </div>
                            <div class="block-content block-content-full">
                                <div class="table-responsive">
                                    <table class="table table-hover table-vcenter">
                                        <thead>
                                            <tr>
                                                <th>Customer</th>
                                                <th>Email</th>
                                                <th>Total Orders</th>
                                                <th>Total Spent</th>
                                                <th>Average Order Value</th>
                                                <th>Last Order</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($topCustomers as $customer)
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold">{{ $customer->name ?: 'Unknown' }}</div>
                                                        <div class="fs-sm text-muted">ID: {{ $customer->user_id }}</div>
                                                    </td>
                                                    <td>
                                                        <div class="fs-sm">{{ $customer->email ?: 'N/A' }}</div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary">{{ $customer->total_orders }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="fw-semibold text-success">${{ number_format($customer->total_spent, 2) }}</div>
                                                    </td>
                                                    <td>
                                                        <span class="fw-semibold">${{ number_format($customer->total_orders > 0 ? $customer->total_spent / $customer->total_orders : 0, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        @if($customer->last_order_date)
                                                            <div class="fs-sm">{{ \Carbon\Carbon::parse($customer->last_order_date)->format('M d, Y') }}</div>
                                                            <div class="fs-sm text-muted">{{ \Carbon\Carbon::parse($customer->last_order_date)->diffForHumans() }}</div>
                                                        @else
                                                            <span class="text-muted">No orders</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center">No customer data available for this period</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <div class="block block-rounded">
                            <div class="block-header block-header-default">
                                <h3 class="block-title">
                                    <i class="fa fa-chart-line me-1"></i>Customer Acquisition
                                </h3>
                            </div>
                            <div class="block-content">
                                <canvas id="acquisitionChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="block block-rounded">
                            <div class="block-header block-header-default">
                                <h3 class="block-title">
                                    <i class="fa fa-chart-pie me-1"></i>Customer Value Distribution
                                </h3>
                            </div>
                            <div class="block-content">
                                <canvas id="valueChart" height="200"></canvas>
                            </div>
                        </div>
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
        // Customer acquisition chart
        const acquisitionCtx = document.getElementById('acquisitionChart').getContext('2d');
        const acquisitionChart = new Chart(acquisitionCtx, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'New Customers',
                    data: [5, 8, 12, {{ $newCustomers }}],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Customer value distribution
        const valueCtx = document.getElementById('valueChart').getContext('2d');
        const valueChart = new Chart(valueCtx, {
            type: 'doughnut',
            data: {
                labels: ['$0-$50', '$50-$100', '$100-$250', '$250+'],
                datasets: [{
                    data: [
                        {{ $topCustomers->where('total_spent', '<', 50)->count() }},
                        {{ $topCustomers->where('total_spent', '>=', 50)->where('total_spent', '<', 100)->count() }},
                        {{ $topCustomers->where('total_spent', '>=', 100)->where('total_spent', '<', 250)->count() }},
                        {{ $topCustomers->where('total_spent', '>=', 250)->count() }}
                    ],
                    backgroundColor: [
                        '#ff6384',
                        '#36a2eb', 
                        '#cc65fe',
                        '#ffce56'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
@endsection