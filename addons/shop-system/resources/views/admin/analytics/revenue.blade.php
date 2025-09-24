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
    <div class="col-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-calendar-alt me-1"></i>Revenue Period
                </h3>
            </div>
            <div class="block-content">
                <form method="GET" action="{{ route('admin.shop.analytics.revenue') }}" class="row g-3">
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
    <!-- Revenue Summary -->
    <div class="col-md-6">
        <div class="block block-rounded text-center">
            <div class="block-content block-content-full">
                <div class="fs-2 fw-bold text-success">
                    <i class="fa fa-dollar-sign"></i>
                </div>
                <div class="fs-sm fw-semibold text-uppercase text-muted">Total Revenue</div>
                <div class="fs-3 fw-bold text-dark">{{ $currencySymbol }}{{ number_format($revenueData['total'] ?? 0, 2) }}</div>
                <div class="progress mb-2" style="height: 5px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                </div>
                <div class="fs-sm text-muted">Last {{ $period }} days</div>
            </div>
        </div>
    </div>

    <!-- Revenue Growth -->
    <div class="col-md-6">
        <div class="block block-rounded text-center">
            <div class="block-content block-content-full">
                <div class="fs-2 fw-bold {{ ($revenueData['growth'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                    <i class="fa fa-{{ ($revenueData['growth'] ?? 0) >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                </div>
                <div class="fs-sm fw-semibold text-uppercase text-muted">Growth Rate</div>
                <div class="fs-3 fw-bold text-dark">{{ number_format($revenueData['growth'] ?? 0, 1) }}%</div>
                <div class="progress mb-2" style="height: 5px;">
                    <div class="progress-bar {{ ($revenueData['growth'] ?? 0) >= 0 ? 'bg-success' : 'bg-danger' }}" role="progressbar" style="width: {{ abs($revenueData['growth'] ?? 0) }}%"></div>
                </div>
                <div class="fs-sm text-muted">Compared to previous period</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Daily Average -->
    <div class="col-md-6">
        <div class="block block-rounded text-center">
            <div class="block-content block-content-full">
                <div class="fs-2 fw-bold text-primary">
                    <i class="fa fa-calendar-day"></i>
                </div>
                <div class="fs-sm fw-semibold text-uppercase text-muted">Average Daily Revenue</div>
                <div class="fs-3 fw-bold text-dark">{{ $currencySymbol }}{{ number_format($revenueData['average_daily'] ?? 0, 2) }}</div>
                <div class="progress mb-2" style="height: 5px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: 70%"></div>
                </div>
                <div class="fs-sm text-muted">Per day over {{ $period }} days</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-md-6">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-bolt me-1"></i>Quick Actions
                </h3>
            </div>
            <div class="block-content">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.shop.analytics.index') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left me-1"></i>Back to Analytics Overview
                    </a>
                    <a href="{{ route('admin.shop.analytics.orders') }}" class="btn btn-outline-info">
                        <i class="fa fa-shopping-cart me-1"></i>View Orders Report
                    </a>
                    <a href="{{ route('admin.shop.analytics.customers') }}" class="btn btn-outline-success">
                        <i class="fa fa-users me-1"></i>View Customers Report
                    </a>
                    <a href="{{ route('admin.shop.analytics.export') }}" class="btn btn-outline-warning">
                        <i class="fa fa-download me-1"></i>Export All Data
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@if(isset($revenueData['breakdown']) && count($revenueData['breakdown']) > 0)
<div class="row">
    <div class="col-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-chart-bar me-1"></i>Revenue Breakdown
                </h3>
            </div>
            <div class="block-content block-content-full">
                <div class="table-responsive">
                    <table class="table table-hover table-vcenter">
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
                                <td>
                                    <div class="fw-semibold">{{ $item['period'] ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    <span class="fw-semibold text-success">{{ $currencySymbol }}{{ number_format($item['revenue'] ?? 0, 2) }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ number_format($item['transactions'] ?? 0) }}</span>
                                </td>
                                <td>
                                    <span class="fs-sm">{{ $currencySymbol }}{{ number_format(($item['revenue'] ?? 0) / max(1, $item['transactions'] ?? 1), 2) }}</span>
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
