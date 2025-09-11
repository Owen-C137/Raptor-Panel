@extends('layouts.admin')

@section('title')
    Shop Analytics
@endsection

@section('content-header')
    <h1>
        Shop Analytics
        <small>Performance metrics and insights</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
        <li class="active">Analytics</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <!-- Period Selector -->
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Analytics Period</h3>
            </div>
            <div class="box-body">
                <form method="GET" action="{{ route('admin.shop.analytics.index') }}" class="form-inline">
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
    <!-- Revenue Analytics -->
    <div class="col-md-6 col-sm-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Revenue Analytics</h3>
            </div>
            <div class="box-body">
                @if(isset($analytics['revenue']))
                <div class="info-box">
                    <span class="info-box-icon bg-green">
                        <i class="fas fa-dollar-sign"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Revenue</span>
                        <span class="info-box-number">${{ number_format($analytics['revenue']['total'] ?? 0, 2) }}</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 100%"></div>
                        </div>
                        <span class="progress-description">Last {{ $period }} days</span>
                    </div>
                </div>
                @else
                <p class="text-muted">No revenue data available for the selected period.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Orders Analytics -->
    <div class="col-md-6 col-sm-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Orders Analytics</h3>
            </div>
            <div class="box-body">
                @if(isset($analytics['orders']))
                <div class="info-box">
                    <span class="info-box-icon bg-blue">
                        <i class="fas fa-shopping-cart"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Orders</span>
                        <span class="info-box-number">{{ number_format($analytics['orders']['count'] ?? 0) }}</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 100%"></div>
                        </div>
                        <span class="progress-description">Last {{ $period }} days</span>
                    </div>
                </div>
                @else
                <p class="text-muted">No order data available for the selected period.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Category Analytics -->
    <div class="col-md-6 col-sm-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Category Analytics</h3>
            </div>
            <div class="box-body">
                @if(isset($analytics['categories']))
                <div class="info-box">
                    <span class="info-box-icon bg-yellow">
                        <i class="fas fa-tags"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Active Categories</span>
                        <span class="info-box-number">{{ number_format($analytics['categories']['count'] ?? 0) }}</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 100%"></div>
                        </div>
                        <span class="progress-description">Currently active</span>
                    </div>
                </div>
                @else
                <p class="text-muted">No category data available.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Customer Analytics -->
    <div class="col-md-6 col-sm-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Customer Analytics</h3>
            </div>
            <div class="box-body">
                @if(isset($analytics['customers']))
                <div class="info-box">
                    <span class="info-box-icon bg-red">
                        <i class="fas fa-users"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Customers</span>
                        <span class="info-box-number">{{ number_format($analytics['customers']['count'] ?? 0) }}</span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 100%"></div>
                        </div>
                        <span class="progress-description">All time</span>
                    </div>
                </div>
                @else
                <p class="text-muted">No customer data available.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Actions -->
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Detailed Reports</h3>
            </div>
            <div class="box-body">
                <div class="btn-group" role="group">
                    <a href="{{ route('admin.shop.analytics.revenue') }}" class="btn btn-primary">
                        <i class="fas fa-chart-line"></i> Revenue Report
                    </a>
                    <a href="{{ route('admin.shop.analytics.orders') }}" class="btn btn-info">
                        <i class="fas fa-shopping-cart"></i> Orders Report
                    </a>
                    <a href="{{ route('admin.shop.analytics.customers') }}" class="btn btn-success">
                        <i class="fas fa-users"></i> Customers Report
                    </a>
                    <a href="{{ route('admin.shop.analytics.export') }}" class="btn btn-warning">
                        <i class="fas fa-download"></i> Export Data
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        // Auto-refresh analytics every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000); // 5 minutes
    </script>
@endsection
