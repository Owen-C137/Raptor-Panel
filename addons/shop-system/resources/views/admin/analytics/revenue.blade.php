@extends('layouts.admin')

@section('title')
    Revenue Analytics
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Revenue Analytics
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Detailed revenue insights and trends
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
            <a class="link-fx" href="{{ route('admin.shop.analytics.index') }}">Analytics</a>
          </li>
          <li class="breadcrumb-item" aria-current="page">
            Revenue
          </li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row">
    <!-- Period Selector -->
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Revenue Period</h3>
            </div>
            <div class="box-body">
                <form method="GET" action="{{ route('admin.shop.analytics.revenue') }}" class="form-inline">
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
    <!-- Revenue Summary -->
    <div class="col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Revenue Summary</h3>
            </div>
            <div class="box-body">
                <div class="info-box">
                    <span class="info-box-icon bg-green">
                        <i class="fa fa-money"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Revenue</span>
                        <span class="info-box-number">{{ $currencySymbol }}{{ number_format($revenueData['total'] ?? 0, 2) }}</span>
                        <div class="progress">
                            <div class="progress-bar bg-green" style="width: 100%"></div>
                        </div>
                        <span class="progress-description">Last {{ $period }} days</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Growth -->
    <div class="col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Revenue Growth</h3>
            </div>
            <div class="box-body">
                <div class="info-box">
                    <span class="info-box-icon {{ ($revenueData['growth'] ?? 0) >= 0 ? 'bg-green' : 'bg-red' }}">
                        <i class="fa fa-{{ ($revenueData['growth'] ?? 0) >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Growth Rate</span>
                        <span class="info-box-number">{{ number_format($revenueData['growth'] ?? 0, 1) }}%</span>
                        <div class="progress">
                            <div class="progress-bar {{ ($revenueData['growth'] ?? 0) >= 0 ? 'bg-green' : 'bg-red' }}" style="width: {{ abs($revenueData['growth'] ?? 0) }}%"></div>
                        </div>
                        <span class="progress-description">Compared to previous period</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Daily Average -->
    <div class="col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Daily Average</h3>
            </div>
            <div class="box-body">
                <div class="info-box">
                    <span class="info-box-icon bg-blue">
                        <i class="fa fa-calendar-day"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Average Daily Revenue</span>
                        <span class="info-box-number">{{ $currencySymbol }}{{ number_format($revenueData['average_daily'] ?? 0, 2) }}</span>
                        <div class="progress">
                            <div class="progress-bar bg-blue" style="width: 70%"></div>
                        </div>
                        <span class="progress-description">Per day over {{ $period }} days</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Quick Actions</h3>
            </div>
            <div class="box-body">
                <div class="btn-group-vertical btn-group-sm" style="width: 100%">
                    <a href="{{ route('admin.shop.analytics.index') }}" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> Back to Analytics Overview
                    </a>
                    <a href="{{ route('admin.shop.analytics.orders') }}" class="btn btn-info">
                        <i class="fa fa-shopping-cart"></i> View Orders Report
                    </a>
                    <a href="{{ route('admin.shop.analytics.customers') }}" class="btn btn-success">
                        <i class="fa fa-users"></i> View Customers Report
                    </a>
                    <a href="{{ route('admin.shop.analytics.export') }}" class="btn btn-warning">
                        <i class="fa fa-download"></i> Export All Data
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@if(isset($revenueData['breakdown']) && count($revenueData['breakdown']) > 0)
<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Revenue Breakdown</h3>
            </div>
            <div class="box-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Revenue</th>
                            <th>Transactions</th>
                            <th>Average</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($revenueData['breakdown'] as $item)
                        <tr>
                            <td>{{ $item['period'] ?? 'N/A' }}</td>
                            <td>{{ $currencySymbol }}{{ number_format($item['revenue'] ?? 0, 2) }}</td>
                            <td>{{ number_format($item['transactions'] ?? 0) }}</td>
                            <td>{{ $currencySymbol }}{{ number_format(($item['revenue'] ?? 0) / max(1, $item['transactions'] ?? 1), 2) }}</td>
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
