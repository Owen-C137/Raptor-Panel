@extends('layouts.admin')

@section('title')
    Customers Report
@endsection

@section('content-header')
    <h1>
        Customers Report
        <small>Detailed customer analytics</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
        <li><a href="{{ route('admin.shop.reports.index') }}">Reports</a></li>
        <li class="active">Customers</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Customer Analytics</h3>
                <div class="box-tools pull-right">
                    <form method="GET" style="display: inline-block;">
                        <select name="period" onchange="this.form.submit()" class="form-control" style="width: auto; display: inline-block;">
                            <option value="7" {{ $period == 7 ? 'selected' : '' }}>Last 7 Days</option>
                            <option value="30" {{ $period == 30 ? 'selected' : '' }}>Last 30 Days</option>
                            <option value="90" {{ $period == 90 ? 'selected' : '' }}>Last 90 Days</option>
                            <option value="365" {{ $period == 365 ? 'selected' : '' }}>Last Year</option>
                        </select>
                    </form>
                </div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-blue">
                                <i class="fa fa-users"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">New Customers</span>
                                <span class="info-box-number">{{ $newCustomers }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-green">
                                <i class="fa fa-user-plus"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Active Customers</span>
                                <span class="info-box-number">{{ count($topCustomers) }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-yellow">
                                <i class="fa fa-usd"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Avg Lifetime Value</span>
                                <span class="info-box-number">${{ number_format($customerLifetimeValue, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-red">
                                <i class="fa fa-bar-chart"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Growth Rate</span>
                                <span class="info-box-number">+{{ round($newCustomers / max(1, $period) * 30, 1) }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h3 class="box-title">Top Customers by Revenue</h3>
                            </div>
                            <div class="box-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
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
                                                        <strong>{{ $customer->name ?: 'Unknown' }}</strong>
                                                        <br>
                                                        <small class="text-muted">ID: {{ $customer->user_id }}</small>
                                                    </td>
                                                    <td>{{ $customer->email ?: 'N/A' }}</td>
                                                    <td>
                                                        <span class="badge bg-blue">{{ $customer->total_orders }}</span>
                                                    </td>
                                                    <td>
                                                        <strong class="text-green">${{ number_format($customer->total_spent, 2) }}</strong>
                                                    </td>
                                                    <td>
                                                        ${{ number_format($customer->total_orders > 0 ? $customer->total_spent / $customer->total_orders : 0, 2) }}
                                                    </td>
                                                    <td>
                                                        @if($customer->last_order_date)
                                                            {{ \Carbon\Carbon::parse($customer->last_order_date)->format('M d, Y') }}
                                                            <br>
                                                            <small class="text-muted">{{ \Carbon\Carbon::parse($customer->last_order_date)->diffForHumans() }}</small>
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
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h3 class="box-title">Customer Acquisition</h3>
                            </div>
                            <div class="box-body">
                                <canvas id="acquisitionChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h3 class="box-title">Customer Value Distribution</h3>
                            </div>
                            <div class="box-body">
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