@extends('layouts.admin')

@section('title')
    Orders Report
@endsection

@section('content-header')
    <h1>
        Orders Report
        <small>Detailed order analytics</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
        <li><a href="{{ route('admin.shop.reports.index') }}">Reports</a></li>
        <li class="active">Orders</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Order Analytics</h3>
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
                                <i class="fa fa-shopping-cart"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Orders</span>
                                <span class="info-box-number">{{ $orderStats['total_orders'] }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-green">
                                <i class="fa fa-check-circle"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Completed</span>
                                <span class="info-box-number">{{ $orderStats['completed_orders'] }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-yellow">
                                <i class="fa fa-clock-o"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Pending</span>
                                <span class="info-box-number">{{ $orderStats['pending_orders'] }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-red">
                                <i class="fa fa-times-circle"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Failed</span>
                                <span class="info-box-number">{{ $orderStats['failed_orders'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h3 class="box-title">Order Status Breakdown</h3>
                            </div>
                            <div class="box-body">
                                <canvas id="statusChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h3 class="box-title">Order Trends</h3>
                            </div>
                            <div class="box-body">
                                <canvas id="trendsChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <h4>Order Status Details</h4>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Count</th>
                                        <th>Percentage</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($statusBreakdown as $status => $count)
                                        <tr>
                                            <td>
                                                <span class="label 
                                                    @if($status === 'completed') label-success
                                                    @elseif($status === 'pending') label-warning  
                                                    @elseif($status === 'failed') label-danger
                                                    @else label-default
                                                    @endif
                                                ">
                                                    {{ ucfirst($status) }}
                                                </span>
                                            </td>
                                            <td>{{ $count }}</td>
                                            <td>{{ $orderStats['total_orders'] > 0 ? round(($count / $orderStats['total_orders']) * 100, 1) : 0 }}%</td>
                                            <td>
                                                @if($status === 'completed')
                                                    Orders that have been successfully processed and fulfilled
                                                @elseif($status === 'pending')
                                                    Orders awaiting payment or processing
                                                @elseif($status === 'failed')
                                                    Orders that encountered errors during processing
                                                @elseif($status === 'cancelled')
                                                    Orders that were cancelled by user or admin
                                                @else
                                                    {{ ucfirst(str_replace('_', ' ', $status)) }} orders
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
        // Status breakdown pie chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: @json(array_keys($statusBreakdown)),
                datasets: [{
                    data: @json(array_values($statusBreakdown)),
                    backgroundColor: [
                        '#28a745', // completed - green
                        '#ffc107', // pending - yellow  
                        '#dc3545', // failed - red
                        '#6c757d', // cancelled - gray
                        '#17a2b8', // processing - blue
                        '#fd7e14'  // other - orange
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

        // Sample trend data (would be passed from controller in real implementation)
        const trendsCtx = document.getElementById('trendsChart').getContext('2d');
        const trendsChart = new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Orders',
                    data: [12, 19, 15, 25],
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
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
    </script>
@endsection