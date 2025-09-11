@extends('layouts.admin')

@section('title')
    Export Analytics Data
@endsection

@section('content-header')
    <h1>
        Export Analytics Data
        <small>Download comprehensive analytics reports</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
        <li><a href="{{ route('admin.shop.analytics.index') }}">Analytics</a></li>
        <li class="active">Export</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <!-- Export Options -->
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Export Options</h3>
            </div>
            <div class="box-body">
                <form method="GET" action="{{ route('admin.shop.analytics.export') }}" class="form-horizontal">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">Period</label>
                        <div class="col-sm-10">
                            <select name="period" class="form-control">
                                <option value="7" {{ $period == '7' ? 'selected' : '' }}>Last 7 days</option>
                                <option value="30" {{ $period == '30' ? 'selected' : '' }}>Last 30 days</option>
                                <option value="90" {{ $period == '90' ? 'selected' : '' }}>Last 90 days</option>
                                <option value="365" {{ $period == '365' ? 'selected' : '' }}>Last 365 days</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-refresh"></i> Update Data
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Export Actions -->
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Download Reports</h3>
            </div>
            <div class="box-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Note:</strong> Export functionality will generate downloadable files. This feature can be enhanced to create CSV, PDF, or Excel exports.
                </div>
                
                <div class="btn-group-vertical btn-group-lg" style="width: 100%">
                    <button class="btn btn-success" onclick="exportData('revenue')">
                        <i class="fas fa-dollar-sign"></i> Export Revenue Data (JSON)
                    </button>
                    <button class="btn btn-info" onclick="exportData('orders')">
                        <i class="fas fa-shopping-cart"></i> Export Orders Data (JSON)
                    </button>
                    <button class="btn btn-warning" onclick="exportData('customers')">
                        <i class="fas fa-users"></i> Export Customer Data (JSON)
                    </button>
                    <button class="btn btn-primary" onclick="exportData('plans')">
                        <i class="fas fa-list"></i> Export Plans Data (JSON)
                    </button>
                    <button class="btn btn-default" onclick="exportData('all')">
                        <i class="fas fa-download"></i> Export All Data (JSON)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Data Preview -->
    <div class="col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Revenue Summary</h3>
            </div>
            <div class="box-body">
                <pre class="bg-gray" style="max-height: 300px; overflow-y: auto;">{{ json_encode($data['revenue'], JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Orders Summary</h3>
            </div>
            <div class="box-body">
                <pre class="bg-gray" style="max-height: 300px; overflow-y: auto;">{{ json_encode($data['orders'], JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Customer Summary</h3>
            </div>
            <div class="box-body">
                <pre class="bg-gray" style="max-height: 300px; overflow-y: auto;">{{ json_encode($data['customers'], JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Plans Summary</h3>
            </div>
            <div class="box-body">
                <pre class="bg-gray" style="max-height: 300px; overflow-y: auto;">{{ json_encode($data['plans'], JSON_PRETTY_PRINT) }}</pre>
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
                        <i class="fas fa-dollar-sign"></i> Revenue Report
                    </a>
                    <a href="{{ route('admin.shop.analytics.orders') }}" class="btn btn-info">
                        <i class="fas fa-shopping-cart"></i> Orders Report
                    </a>
                    <a href="{{ route('admin.shop.analytics.customers') }}" class="btn btn-warning">
                        <i class="fas fa-users"></i> Customers Report
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
            
            // Show success message
            toastr.success('Analytics data exported successfully!');
        }
    </script>
@endsection
