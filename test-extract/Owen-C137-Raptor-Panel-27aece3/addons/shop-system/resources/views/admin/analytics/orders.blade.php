@extends('layouts.admin')

@section('title')
    Orders Analytics
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Orders Analytics
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Detailed order insights and patterns
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
            Orders
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
                    <i class="fa fa-calendar-alt me-1"></i>Orders Period
                </h3>
            </div>
            <div class="block-content">
                <form method="GET" action="{{ route('admin.shop.analytics.orders') }}" class="row g-3">
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
    <!-- Total Orders -->
    <div class="col-md-6">
        <div class="block block-rounded text-center">
            <div class="block-content block-content-full">
                <div class="fs-2 fw-bold text-primary">
                    <i class="fa fa-shopping-cart"></i>
                </div>
                <div class="fs-sm fw-semibold text-uppercase text-muted">Total Orders</div>
                <div class="fs-3 fw-bold text-dark">{{ number_format($orderData['total'] ?? 0) }}</div>
                <div class="progress mb-2" style="height: 5px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
                </div>
                <div class="fs-sm text-muted">Last {{ $period }} days</div>
            </div>
        </div>
    </div>

    <!-- Daily Average -->
    <div class="col-md-6">
        <div class="block block-rounded text-center">
            <div class="block-content block-content-full">
                <div class="fs-2 fw-bold text-success">
                    <i class="fa fa-calendar-day"></i>
                </div>
                <div class="fs-sm fw-semibold text-uppercase text-muted">Average Daily Orders</div>
                <div class="fs-3 fw-bold text-dark">{{ number_format($orderData['average_daily'] ?? 0, 1) }}</div>
                <div class="progress mb-2" style="height: 5px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 85%"></div>
                </div>
                <div class="fs-sm text-muted">Per day over {{ $period }} days</div>
            </div>
        </div>
    </div>
</div>

@if(isset($orderData['status_breakdown']) && count($orderData['status_breakdown']) > 0)
<div class="row">
    <div class="col-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-chart-pie me-1"></i>Orders by Status
                </h3>
            </div>
            <div class="block-content">
                <div class="row g-3">
                    @foreach($orderData['status_breakdown'] as $status => $count)
                    <div class="col-md-3">
                        <div class="block block-rounded text-center py-3
                            @if($status == 'active') bg-success-light
                            @elseif($status == 'pending') bg-warning-light
                            @elseif($status == 'suspended') bg-danger-light
                            @elseif($status == 'terminated') bg-dark-light
                            @else bg-secondary-light
                            @endif
                        ">
                            <div class="fs-1 fw-bold 
                                @if($status == 'active') text-success
                                @elseif($status == 'pending') text-warning
                                @elseif($status == 'suspended') text-danger
                                @elseif($status == 'terminated') text-dark
                                @else text-secondary
                                @endif
                            ">
                                <i class="fa fa-
                                    @if($status == 'active') check-circle
                                    @elseif($status == 'pending') clock
                                    @elseif($status == 'suspended') pause-circle
                                    @elseif($status == 'terminated') times-circle
                                    @else question-circle
                                    @endif
                                "></i>
                            </div>
                            <div class="fs-sm fw-semibold text-uppercase text-muted mt-2">{{ ucfirst($status) }}</div>
                            <div class="fs-3 fw-bold text-dark">{{ number_format($count) }}</div>
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
                        <a href="{{ route('admin.shop.analytics.customers') }}" class="btn btn-outline-info">
                            <i class="fa fa-users me-1"></i>View Customers Report
                        </a>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('admin.shop.orders.index') }}" class="btn btn-outline-primary">
                            <i class="fa fa-list me-1"></i>Manage Orders
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

@if(isset($orderData['recent_orders']) && count($orderData['recent_orders']) > 0)
<div class="row">
    <div class="col-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-list me-1"></i>Recent Orders
                </h3>
            </div>
            <div class="block-content block-content-full">
                <div class="table-responsive">
                    <table class="table table-hover table-vcenter">
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
                                <td>
                                    <div class="fw-semibold"># {{ $order['id'] ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $order['customer'] ?? 'Unknown' }}</div>
                                </td>
                                <td>
                                    <div>{{ $order['plan'] ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    <span class="fw-semibold text-success">{{ $currencySymbol }}{{ number_format($order['amount'] ?? 0, 2) }}</span>
                                </td>
                                <td>
                                    <span class="badge 
                                        @if($order['status'] == 'active') bg-success
                                        @elseif($order['status'] == 'pending') bg-warning
                                        @elseif($order['status'] == 'suspended') bg-danger
                                        @else bg-secondary
                                        @endif
                                    ">
                                        {{ ucfirst($order['status'] ?? 'unknown') }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fs-sm">{{ $order['date'] ?? 'N/A' }}</div>
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
