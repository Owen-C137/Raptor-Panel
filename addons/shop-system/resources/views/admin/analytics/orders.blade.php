@extends('layouts.admin')

@section('title')
    Orders Analytics
@endsection

@section('content-header')
    <h1>
        Orders Analytics
        <small>Detailed order insights and patterns</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
        <li><a href="{{ route('admin.shop.analytics.index') }}">Analytics</a></li>
        <li class="active">Orders</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <!-- Period Selector -->
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Orders Period</h3>
            </div>
            <div class="box-body">
                <form method="GET" action="{{ route('admin.shop.analytics.orders') }}" class="form-inline">
                    <div class="form-group">
                        <label for="period">Period:</label>
                        <select name="period" id="period" class="form-control" onchange="this.form.submit()">
                            <option value="7" {{ $period == '7' ? 'selected' : '' }}>Last 7 days</option>
                            <option value="30" {{ $period == '30' ? 'selected' : '' }}>Last 30 days</option>
                            <option value="90" {{ $period == '90' ? 'selected' : '' }}>Last 90 days</option>
                            <option value="365" {{ $period == '365' ? 'selected' : '' }}>Last 365 days</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Total Orders -->
    <div class="col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Total Orders</h3>
            </div>
            <div class="box-body">
                <div class="info-box">
                    <span class="info-box-icon bg-blue">
                        <i class="fas fa-shopping-cart"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Orders</span>
                        <span class="info-box-number">{{ number_format($orderData['total'] ?? 0) }}</span>
                        <div class="progress">
                            <div class="progress-bar bg-blue" style="width: 100%"></div>
                        </div>
                        <span class="progress-description">Last {{ $period }} days</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Average -->
    <div class="col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Daily Average</h3>
            </div>
            <div class="box-body">
                <div class="info-box">
                    <span class="info-box-icon bg-green">
                        <i class="fas fa-calendar-day"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Average Daily Orders</span>
                        <span class="info-box-number">{{ number_format($orderData['average_daily'] ?? 0, 1) }}</span>
                        <div class="progress">
                            <div class="progress-bar bg-green" style="width: 85%"></div>
                        </div>
                        <span class="progress-description">Per day over {{ $period }} days</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(isset($orderData['status_breakdown']) && count($orderData['status_breakdown']) > 0)
<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Orders by Status</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    @foreach($orderData['status_breakdown'] as $status => $count)
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon 
                                @if($status == 'active') bg-green
                                @elseif($status == 'pending') bg-yellow  
                                @elseif($status == 'suspended') bg-orange
                                @elseif($status == 'terminated') bg-red
                                @else bg-gray
                                @endif
                            ">
                                <i class="fas fa-
                                    @if($status == 'active') check-circle
                                    @elseif($status == 'pending') clock
                                    @elseif($status == 'suspended') pause-circle
                                    @elseif($status == 'terminated') times-circle
                                    @else question-circle
                                    @endif
                                "></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">{{ ucfirst($status) }}</span>
                                <span class="info-box-number">{{ number_format($count) }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row">
    <!-- Quick Actions -->
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Quick Actions</h3>
            </div>
            <div class="box-body">
                <div class="btn-group" role="group">
                    <a href="{{ route('admin.shop.analytics.index') }}" class="btn btn-default">
                        <i class="fas fa-arrow-left"></i> Back to Analytics Overview
                    </a>
                    <a href="{{ route('admin.shop.analytics.revenue') }}" class="btn btn-success">
                        <i class="fas fa-dollar-sign"></i> View Revenue Report
                    </a>
                    <a href="{{ route('admin.shop.analytics.customers') }}" class="btn btn-info">
                        <i class="fas fa-users"></i> View Customers Report
                    </a>
                    <a href="{{ route('admin.shop.orders.index') }}" class="btn btn-primary">
                        <i class="fas fa-list"></i> Manage Orders
                    </a>
                    <a href="{{ route('admin.shop.analytics.export') }}" class="btn btn-warning">
                        <i class="fas fa-download"></i> Export All Data
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@if(isset($orderData['recent_orders']) && count($orderData['recent_orders']) > 0)
<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Recent Orders</h3>
            </div>
            <div class="box-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Plan</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orderData['recent_orders'] as $order)
                        <tr>
                            <td>{{ $order['id'] ?? 'N/A' }}</td>
                            <td>{{ $order['customer'] ?? 'Unknown' }}</td>
                            <td>{{ $order['plan'] ?? 'N/A' }}</td>
                            <td>${{ number_format($order['amount'] ?? 0, 2) }}</td>
                            <td>
                                <span class="label 
                                    @if($order['status'] == 'active') label-success
                                    @elseif($order['status'] == 'pending') label-warning
                                    @elseif($order['status'] == 'suspended') label-danger
                                    @else label-default
                                    @endif
                                ">
                                    {{ ucfirst($order['status'] ?? 'unknown') }}
                                </span>
                            </td>
                            <td>{{ $order['date'] ?? 'N/A' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
