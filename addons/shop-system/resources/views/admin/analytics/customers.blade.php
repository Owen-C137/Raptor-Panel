@extends('layouts.admin')

@section('title')
    Customer Analytics
@endsection

@section('content-header')
    <h1>
        Customer Analytics
        <small>Customer behavior and engagement insights</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
        <li><a href="{{ route('admin.shop.analytics.index') }}">Analytics</a></li>
        <li class="active">Customers</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <!-- Period Selector -->
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Customer Analysis Period</h3>
            </div>
            <div class="box-body">
                <form method="GET" action="{{ route('admin.shop.analytics.customers') }}" class="form-inline">
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
    <!-- New Customers -->
    <div class="col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">New Customers</h3>
            </div>
            <div class="box-body">
                <div class="info-box">
                    <span class="info-box-icon bg-green">
                        <i class="fas fa-user-plus"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">New Customers</span>
                        <span class="info-box-number">{{ number_format($customerData['new_customers'] ?? 0) }}</span>
                        <div class="progress">
                            <div class="progress-bar bg-green" style="width: 100%"></div>
                        </div>
                        <span class="progress-description">Last {{ $period }} days</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Returning Customers -->
    <div class="col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Returning Customers</h3>
            </div>
            <div class="box-body">
                <div class="info-box">
                    <span class="info-box-icon bg-blue">
                        <i class="fas fa-user-check"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Returning Customers</span>
                        <span class="info-box-number">{{ number_format($customerData['returning_customers'] ?? 0) }}</span>
                        <div class="progress">
                            <div class="progress-bar bg-blue" style="width: {{ min(100, ($customerData['returning_customers'] ?? 0) / max(1, $customerData['new_customers'] ?? 1) * 100) }}%"></div>
                        </div>
                        <span class="progress-description">Repeat purchases</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Average Order Value -->
    <div class="col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Average Order Value</h3>
            </div>
            <div class="box-body">
                <div class="info-box">
                    <span class="info-box-icon bg-yellow">
                        <i class="fas fa-dollar-sign"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Average Order Value</span>
                        <span class="info-box-number">${{ number_format($customerData['average_order_value'] ?? 0, 2) }}</span>
                        <div class="progress">
                            <div class="progress-bar bg-yellow" style="width: 75%"></div>
                        </div>
                        <span class="progress-description">Per customer transaction</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Wallet Balance -->
    <div class="col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Customer Wallets</h3>
            </div>
            <div class="box-body">
                <div class="info-box">
                    <span class="info-box-icon bg-purple">
                        <i class="fas fa-wallet"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Wallet Balance</span>
                        <span class="info-box-number">${{ number_format($customerData['total_wallet_balance'] ?? 0, 2) }}</span>
                        <div class="progress">
                            <div class="progress-bar bg-purple" style="width: 60%"></div>
                        </div>
                        <span class="progress-description">Customer credit balance</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Customer Metrics -->
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Customer Engagement Metrics</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="description-block border-right">
                            <span class="description-percentage text-green">
                                <i class="fas fa-caret-up"></i>
                                {{ number_format((($customerData['returning_customers'] ?? 0) / max(1, ($customerData['new_customers'] ?? 1)) * 100), 1) }}%
                            </span>
                            <h5 class="description-header">{{ number_format($customerData['returning_customers'] ?? 0) }}</h5>
                            <span class="description-text">Return Rate</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="description-block border-right">
                            <span class="description-percentage text-blue">
                                <i class="fas fa-dollar-sign"></i>
                            </span>
                            <h5 class="description-header">${{ number_format($customerData['average_order_value'] ?? 0, 0) }}</h5>
                            <span class="description-text">Avg Order</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="description-block border-right">
                            <span class="description-percentage text-yellow">
                                <i class="fas fa-wallet"></i>
                            </span>
                            <h5 class="description-header">${{ number_format($customerData['total_wallet_balance'] ?? 0, 0) }}</h5>
                            <span class="description-text">Credit Balance</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="description-block">
                            <span class="description-percentage text-red">
                                <i class="fas fa-users"></i>
                            </span>
                            <h5 class="description-header">{{ number_format(($customerData['new_customers'] ?? 0) + ($customerData['returning_customers'] ?? 0)) }}</h5>
                            <span class="description-text">Total Customers</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
                    <a href="{{ route('admin.shop.analytics.orders') }}" class="btn btn-info">
                        <i class="fas fa-shopping-cart"></i> View Orders Report
                    </a>
                    <a href="{{ route('admin.users') }}" class="btn btn-primary">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                    <a href="{{ route('admin.shop.analytics.export') }}" class="btn btn-warning">
                        <i class="fas fa-download"></i> Export All Data
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@if(isset($customerData['top_customers']) && count($customerData['top_customers']) > 0)
<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Top Customers by Spending</h3>
            </div>
            <div class="box-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Total Spent</th>
                            <th>Orders</th>
                            <th>Average Order</th>
                            <th>Last Order</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customerData['top_customers'] as $customer)
                        <tr>
                            <td>{{ $customer['name'] ?? 'Unknown' }}</td>
                            <td>{{ $customer['email'] ?? 'N/A' }}</td>
                            <td>${{ number_format($customer['total_spent'] ?? 0, 2) }}</td>
                            <td>{{ number_format($customer['order_count'] ?? 0) }}</td>
                            <td>${{ number_format(($customer['total_spent'] ?? 0) / max(1, $customer['order_count'] ?? 1), 2) }}</td>
                            <td>{{ $customer['last_order'] ?? 'N/A' }}</td>
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
