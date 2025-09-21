@extends('layouts.admin')

@section('title')
    Shop Analytics
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Shop Analytics
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Performance metrics and insights
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
          <li class="breadcrumb-item" aria-current="page">
            Analytics
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
                    <i class="fa fa-calendar-alt me-1"></i>Analytics Period
                </h3>
            </div>
            <div class="block-content">
                <form method="GET" action="{{ route('admin.shop.analytics.index') }}" class="row g-3">
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
    <!-- Revenue Analytics -->
    <div class="col-md-6">
        <div class="block block-rounded text-center">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-chart-line me-1"></i>Revenue Analytics
                </h3>
            </div>
            <div class="block-content block-content-full">
                @if(isset($analytics['revenue']))
                <div class="py-3">
                    <div class="fs-2 fw-bold text-success mb-2">
                        <i class="fa fa-dollar-sign"></i>
                    </div>
                    <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Total Revenue</div>
                    <div class="fs-3 fw-bold text-dark">{{ $currencySymbol }}{{ number_format($analytics['revenue']['total'] ?? 0, 2) }}</div>
                    <div class="progress mt-3 mb-2" style="height: 5px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                    </div>
                    <div class="fs-sm text-muted">Last {{ $period }} days</div>
                </div>
                @else
                <div class="py-4">
                    <div class="text-muted">
                        <i class="fa fa-info-circle fs-2 mb-2"></i>
                        <div>No revenue data available for the selected period.</div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Orders Analytics -->
    <div class="col-md-6">
        <div class="block block-rounded text-center">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-shopping-cart me-1"></i>Orders Analytics
                </h3>
            </div>
            <div class="block-content block-content-full">
                @if(isset($analytics['orders']))
                <div class="py-3">
                    <div class="fs-2 fw-bold text-primary mb-2">
                        <i class="fa fa-shopping-cart"></i>
                    </div>
                    <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Total Orders</div>
                    <div class="fs-3 fw-bold text-dark">{{ number_format($analytics['orders']['count'] ?? 0) }}</div>
                    <div class="progress mt-3 mb-2" style="height: 5px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
                    </div>
                    <div class="fs-sm text-muted">Last {{ $period }} days</div>
                </div>
                @else
                <div class="py-4">
                    <div class="text-muted">
                        <i class="fa fa-info-circle fs-2 mb-2"></i>
                        <div>No order data available for the selected period.</div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Category Analytics -->
    <div class="col-md-6">
        <div class="block block-rounded text-center">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-tags me-1"></i>Category Analytics
                </h3>
            </div>
            <div class="block-content block-content-full">
                @if(isset($analytics['categories']))
                <div class="py-3">
                    <div class="fs-2 fw-bold text-warning mb-2">
                        <i class="fa fa-tags"></i>
                    </div>
                    <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Active Categories</div>
                    <div class="fs-3 fw-bold text-dark">{{ number_format($analytics['categories']['count'] ?? 0) }}</div>
                    <div class="progress mt-3 mb-2" style="height: 5px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 100%"></div>
                    </div>
                    <div class="fs-sm text-muted">Currently active</div>
                </div>
                @else
                <div class="py-4">
                    <div class="text-muted">
                        <i class="fa fa-info-circle fs-2 mb-2"></i>
                        <div>No category data available.</div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Customer Analytics -->
    <div class="col-md-6">
        <div class="block block-rounded text-center">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-users me-1"></i>Customer Analytics
                </h3>
            </div>
            <div class="block-content block-content-full">
                @if(isset($analytics['customers']))
                <div class="py-3">
                    <div class="fs-2 fw-bold text-danger mb-2">
                        <i class="fa fa-users"></i>
                    </div>
                    <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Total Customers</div>
                    <div class="fs-3 fw-bold text-dark">{{ number_format($analytics['customers']['count'] ?? 0) }}</div>
                    <div class="progress mt-3 mb-2" style="height: 5px;">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: 100%"></div>
                    </div>
                    <div class="fs-sm text-muted">All time</div>
                </div>
                @else
                <div class="py-4">
                    <div class="text-muted">
                        <i class="fa fa-info-circle fs-2 mb-2"></i>
                        <div>No customer data available.</div>
                    </div>
                </div>
                @endif
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
                    <i class="fa fa-chart-bar me-1"></i>Detailed Reports
                </h3>
            </div>
            <div class="block-content">
                <div class="row g-3">
                    <div class="col-md-6 col-lg-3">
                        <a href="{{ route('admin.shop.analytics.revenue') }}" class="btn btn-outline-primary w-100 py-3">
                            <div class="fs-3 mb-1"><i class="fa fa-chart-line"></i></div>
                            <div class="fw-semibold">Revenue Report</div>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <a href="{{ route('admin.shop.analytics.orders') }}" class="btn btn-outline-info w-100 py-3">
                            <div class="fs-3 mb-1"><i class="fa fa-shopping-cart"></i></div>
                            <div class="fw-semibold">Orders Report</div>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <a href="{{ route('admin.shop.analytics.customers') }}" class="btn btn-outline-success w-100 py-3">
                            <div class="fs-3 mb-1"><i class="fa fa-users"></i></div>
                            <div class="fw-semibold">Customers Report</div>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <a href="{{ route('admin.shop.analytics.export') }}" class="btn btn-outline-warning w-100 py-3">
                            <div class="fs-3 mb-1"><i class="fa fa-download"></i></div>
                            <div class="fw-semibold">Export Data</div>
                        </a>
                    </div>
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
