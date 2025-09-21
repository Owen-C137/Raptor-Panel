@extends('layouts.admin')

@section('title')
    Export Analytics Data
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Export Analytics Data
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Download comprehensive analytics reports
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
            Export
          </li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row">
    <!-- Export Options -->
    <div class="col-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-cog me-1"></i>Export Options
                </h3>
            </div>
            <div class="block-content">
                <form method="GET" action="{{ route('admin.shop.analytics.export') }}" class="row g-3">
                    <div class="col-md-6">
                        <label for="period" class="form-label">Period</label>
                        <select name="period" id="period" class="form-select">
                            <option value="7" {{ $period == '7' ? 'selected' : '' }}>Last 7 days</option>
                            <option value="30" {{ $period == '30' ? 'selected' : '' }}>Last 30 days</option>
                            <option value="90" {{ $period == '90' ? 'selected' : '' }}>Last 90 days</option>
                            <option value="365" {{ $period == '365' ? 'selected' : '' }}>Last 365 days</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-sync-alt me-1"></i>Update Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Export Actions -->
    <div class="col-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-download me-1"></i>Download Reports
                </h3>
            </div>
            <div class="block-content">
                <div class="alert alert-info d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fa fa-info-circle fs-3"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="fw-semibold">Note:</div>
                        <div>Export functionality will generate downloadable files. This feature can be enhanced to create CSV, PDF, or Excel exports.</div>
                    </div>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-6 col-lg-4">
                        <button class="btn btn-outline-success w-100 py-3" onclick="exportData('revenue')">
                            <div class="fs-2 mb-1"><i class="fa fa-dollar-sign"></i></div>
                            <div class="fw-semibold">Export Revenue Data</div>
                            <div class="fs-sm text-muted">JSON Format</div>
                        </button>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <button class="btn btn-outline-info w-100 py-3" onclick="exportData('orders')">
                            <div class="fs-2 mb-1"><i class="fa fa-shopping-cart"></i></div>
                            <div class="fw-semibold">Export Orders Data</div>
                            <div class="fs-sm text-muted">JSON Format</div>
                        </button>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <button class="btn btn-outline-warning w-100 py-3" onclick="exportData('customers')">
                            <div class="fs-2 mb-1"><i class="fa fa-users"></i></div>
                            <div class="fw-semibold">Export Customer Data</div>
                            <div class="fs-sm text-muted">JSON Format</div>
                        </button>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <button class="btn btn-outline-primary w-100 py-3" onclick="exportData('plans')">
                            <div class="fs-2 mb-1"><i class="fa fa-list"></i></div>
                            <div class="fw-semibold">Export Plans Data</div>
                            <div class="fs-sm text-muted">JSON Format</div>
                        </button>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <button class="btn btn-outline-secondary w-100 py-3" onclick="exportData('all')">
                            <div class="fs-2 mb-1"><i class="fa fa-download"></i></div>
                            <div class="fw-semibold">Export All Data</div>
                            <div class="fs-sm text-muted">JSON Format</div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Data Preview -->
    <div class="col-md-6">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-chart-line me-1"></i>Revenue Summary
                </h3>
            </div>
            <div class="block-content block-content-full">
                <pre class="bg-body-light p-3 rounded" style="max-height: 300px; overflow-y: auto; font-size: 0.875rem;">{{ json_encode($data['revenue'], JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-shopping-cart me-1"></i>Orders Summary
                </h3>
            </div>
            <div class="block-content block-content-full">
                <pre class="bg-body-light p-3 rounded" style="max-height: 300px; overflow-y: auto; font-size: 0.875rem;">{{ json_encode($data['orders'], JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-users me-1"></i>Customer Summary
                </h3>
            </div>
            <div class="block-content block-content-full">
                <pre class="bg-body-light p-3 rounded" style="max-height: 300px; overflow-y: auto; font-size: 0.875rem;">{{ json_encode($data['customers'], JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-list me-1"></i>Plans Summary
                </h3>
            </div>
            <div class="block-content block-content-full">
                <pre class="bg-body-light p-3 rounded" style="max-height: 300px; overflow-y: auto; font-size: 0.875rem;">{{ json_encode($data['plans'], JSON_PRETTY_PRINT) }}</pre>
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
                            <i class="fa fa-dollar-sign me-1"></i>Revenue Report
                        </a>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('admin.shop.analytics.orders') }}" class="btn btn-outline-info">
                            <i class="fa fa-shopping-cart me-1"></i>Orders Report
                        </a>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('admin.shop.analytics.customers') }}" class="btn btn-outline-warning">
                            <i class="fa fa-users me-1"></i>Customers Report
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
        function exportData(type) {
            const data = @json($data);
            let exportData;
            let filename;
            
            switch(type) {
                case 'revenue':
                    exportData = data.revenue;
                    filename = 'shop-revenue-analytics.json';
                    break;
                case 'orders':
                    exportData = data.orders;
                    filename = 'shop-orders-analytics.json';
                    break;
                case 'customers':
                    exportData = data.customers;
                    filename = 'shop-customers-analytics.json';
                    break;
                case 'plans':
                    exportData = data.plans;
                    filename = 'shop-plans-analytics.json';
                    break;
                case 'all':
                default:
                    exportData = data;
                    filename = 'shop-analytics-full.json';
                    break;
            }
            
            // Create and download JSON file
            const jsonStr = JSON.stringify(exportData, null, 2);
            const dataBlob = new Blob([jsonStr], { type: 'application/json' });
            
            const link = document.createElement('a');
            link.href = URL.createObjectURL(dataBlob);
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Show success message with OneUI compatibility
            if (typeof One !== 'undefined' && One.helpers && One.helpers.jqGrowl) {
                One.helpers.jqGrowl('success', 'Analytics data exported successfully!');
            } else if (typeof toastr !== 'undefined') {
                toastr.success('Analytics data exported successfully!');
            } else {
                alert('Analytics data exported successfully!');
            }
        }
    </script>
@endsection
