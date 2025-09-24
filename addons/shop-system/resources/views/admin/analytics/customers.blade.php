@extends('layouts.admin')

@section('title')
    Customer Analytics
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Customer Analytics
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Customer behavior and engagement insights
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
    <!-- Period Selector -->
    <div class="col-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-calendar-alt me-1"></i>Customer Analysis Period
                </h3>
            </div>
            <div class="block-content">
                <form method="GET" action="{{ route('admin.shop.analytics.customers') }}" class="row g-3">
                    <div class="col-auto">
                        <label for="period" class="form-label">Period:</label>
                        <select name="period" id="period" class="form-select" onchange="this.form.submit()">
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
        <div class="block block-rounded text-center">
            <div class="block-content block-content-full">
                <div class="fs-2 fw-bold text-success">
                    <i class="fa fa-user-plus"></i>
                </div>
                <div class="fs-sm fw-semibold text-uppercase text-muted">New Customers</div>
                <div class="fs-3 fw-bold text-dark">{{ number_format($customerData['new_customers'] ?? 0) }}</div>
                <div class="progress mb-2" style="height: 5px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                </div>
                <div class="fs-sm text-muted">Last {{ $period }} days</div>
            </div>
        </div>
    </div>

    <!-- Returning Customers -->
    <div class="col-md-6">
        <div class="block block-rounded text-center">
            <div class="block-content block-content-full">
                <div class="fs-2 fw-bold text-primary">
                    <i class="fa fa-user-check"></i>
                </div>
                <div class="fs-sm fw-semibold text-uppercase text-muted">Returning Customers</div>
                <div class="fs-3 fw-bold text-dark">{{ number_format($customerData['returning_customers'] ?? 0) }}</div>
                <div class="progress mb-2" style="height: 5px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min(100, ($customerData['returning_customers'] ?? 0) / max(1, $customerData['new_customers'] ?? 1) * 100) }}%"></div>
                </div>
                <div class="fs-sm text-muted">Repeat purchases</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Average Order Value -->
    <div class="col-md-6">
        <div class="block block-rounded text-center">
            <div class="block-content block-content-full">
                <div class="fs-2 fw-bold text-warning">
                    <i class="fa fa-dollar-sign"></i>
                </div>
                <div class="fs-sm fw-semibold text-uppercase text-muted">Average Order Value</div>
                <div class="fs-3 fw-bold text-dark">{{ $currencySymbol }}{{ number_format($customerData['average_order_value'] ?? 0, 2) }}</div>
                <div class="progress mb-2" style="height: 5px;">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: 75%"></div>
                </div>
                <div class="fs-sm text-muted">Per customer transaction</div>
            </div>
        </div>
    </div>

    <!-- Total Wallet Balance -->
    <div class="col-md-6">
        <div class="block block-rounded text-center">
            <div class="block-content block-content-full">
                <div class="fs-2 fw-bold text-info">
                    <i class="fa fa-wallet"></i>
                </div>
                <div class="fs-sm fw-semibold text-uppercase text-muted">Total Wallet Balance</div>
                <div class="fs-3 fw-bold text-dark">{{ $currencySymbol }}{{ number_format($customerData['total_wallet_balance'] ?? 0, 2) }}</div>
                <div class="progress mb-2" style="height: 5px;">
                    <div class="progress-bar bg-info" role="progressbar" style="width: 60%"></div>
                </div>
                <div class="fs-sm text-muted">Customer credit balance</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Customer Metrics -->
    <div class="col-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-chart-bar me-1"></i>Customer Engagement Metrics
                </h3>
            </div>
            <div class="block-content">
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="py-3 border-end">
                            <div class="fs-3 fw-bold text-success mb-1">
                                <i class="fa fa-caret-up"></i>
                                {{ number_format((($customerData['returning_customers'] ?? 0) / max(1, ($customerData['new_customers'] ?? 1)) * 100), 1) }}%
                            </div>
                            <div class="fs-1 fw-bold text-dark">{{ number_format($customerData['returning_customers'] ?? 0) }}</div>
                            <div class="fs-sm text-muted text-uppercase fw-semibold">Return Rate</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="py-3 border-end">
                            <div class="fs-3 fw-bold text-primary mb-1">
                                <i class="fa fa-dollar-sign"></i>
                            </div>
                            <div class="fs-1 fw-bold text-dark">{{ $currencySymbol }}{{ number_format($customerData['average_order_value'] ?? 0, 0) }}</div>
                            <div class="fs-sm text-muted text-uppercase fw-semibold">Avg Order</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="py-3 border-end">
                            <div class="fs-3 fw-bold text-warning mb-1">
                                <i class="fa fa-wallet"></i>
                            </div>
                            <div class="fs-1 fw-bold text-dark">{{ $currencySymbol }}{{ number_format($customerData['total_wallet_balance'] ?? 0, 0) }}</div>
                            <div class="fs-sm text-muted text-uppercase fw-semibold">Credit Balance</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="py-3">
                            <div class="fs-3 fw-bold text-danger mb-1">
                                <i class="fa fa-users"></i>
                            </div>
                            <div class="fs-1 fw-bold text-dark">{{ number_format(($customerData['new_customers'] ?? 0) + ($customerData['returning_customers'] ?? 0)) }}</div>
                            <div class="fs-sm text-muted text-uppercase fw-semibold">Total Customers</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Actions -->
    <div class="col-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-bolt me-1"></i>Quick Actions
                </h3>
            </div>
            <div class="block-content">
                <div class="row g-2">
                    <div class="col-auto">
                        <a href="{{ route('admin.shop.analytics.index') }}" class="btn btn-outline-secondary">
                            <i class="fa fa-arrow-left me-1"></i>Back to Analytics Overview
                        </a>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('admin.shop.analytics.revenue') }}" class="btn btn-outline-success">
                            <i class="fa fa-dollar-sign me-1"></i>View Revenue Report
                        </a>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('admin.shop.analytics.orders') }}" class="btn btn-outline-info">
                            <i class="fa fa-shopping-cart me-1"></i>View Orders Report
                        </a>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('admin.users') }}" class="btn btn-outline-primary">
                            <i class="fa fa-users me-1"></i>Manage Users
                        </a>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('admin.shop.analytics.export') }}" class="btn btn-outline-warning">
                            <i class="fa fa-download me-1"></i>Export All Data
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(isset($customerData['top_customers']) && count($customerData['top_customers']) > 0)
<div class="row">
    <div class="col-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-crown me-1"></i>Top Customers by Spending
                </h3>
            </div>
            <div class="block-content block-content-full">
                <div class="table-responsive">
                    <table class="table table-hover table-vcenter">
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
                                <td>
                                    <div class="fw-semibold">{{ $customer['name'] ?? 'Unknown' }}</div>
                                </td>
                                <td>
                                    <div class="fs-sm text-muted">{{ $customer['email'] ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-success fs-sm">{{ $currencySymbol }}{{ number_format($customer['total_spent'] ?? 0, 2) }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ number_format($customer['order_count'] ?? 0) }}</span>
                                </td>
                                <td>
                                    <span class="fs-sm">{{ $currencySymbol }}{{ number_format(($customer['total_spent'] ?? 0) / max(1, $customer['order_count'] ?? 1), 2) }}</span>
                                </td>
                                <td>
                                    <div class="fs-sm">{{ $customer['last_order'] ?? 'N/A' }}</div>
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
@endif
@endsection
