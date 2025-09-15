@extends('layouts.admin')

@section('title')
    Revenue Report
@endsection

@section('content-header')
    <h1>
        Revenue Report
        <small>Detailed revenue analytics</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
        <li><a href="{{ route('admin.shop.reports.index') }}">Reports</a></li>
        <li class="active">Revenue</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Revenue Analytics</h3>
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
                    <div class="col-md-4">
                        <div class="info-box">
                            <span class="info-box-icon bg-green">
                                <i class="fa fa-usd"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Revenue</span>
                                <span class="info-box-number">${{ number_format($totalRevenue, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="info-box">
                            <span class="info-box-icon bg-blue">
                                <i class="fa fa-line-chart"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Average Order Value</span>
                                <span class="info-box-number">${{ number_format($averageOrderValue, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="info-box">
                            <span class="info-box-icon bg-yellow">
                                <i class="fa fa-calendar"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Daily Average</span>
                                <span class="info-box-number">${{ number_format($totalRevenue / $period, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="chart">
                            <canvas id="revenueChart" height="400"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <h4>Revenue Breakdown by Date</h4>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Revenue</th>
                                        <th>Orders</th>
                                        <th>Average Order Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($revenueData as $data)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($data->date)->format('M d, Y') }}</td>
                                            <td>${{ number_format($data->revenue, 2) }}</td>
                                            <td>{{ $data->orders }}</td>
                                            <td>${{ number_format($data->orders > 0 ? $data->revenue / $data->orders : 0, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center">No revenue data available for this period</td>
                                        </tr>
                                    @endforelse
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
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($revenueData->pluck('date')->map(function($date) { return \Carbon\Carbon::parse($date)->format('M d'); })),
                datasets: [{
                    label: 'Revenue',
                    data: @json($revenueData->pluck('revenue')),
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
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                return '$' + value.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    </script>
@endsection