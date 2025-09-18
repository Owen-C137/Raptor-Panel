@extends('layouts.admin')

@section('title')
    Shop Reports
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Shop Reports
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Analytics and detailed reporting.
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
            Reports
          </li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-chart-bar me-1"></i>Available Reports
                </h3>
            </div>
            <div class="block-content">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="block block-rounded border border-primary bg-primary-light">
                            <div class="block-content text-center py-4">
                                <div class="fs-1 fw-bold text-primary mb-2">
                                    <i class="fa fa-chart-line"></i>
                                </div>
                                <div class="fs-5 fw-semibold">Revenue Reports</div>
                                <div class="text-muted mb-3">Daily & Monthly Analytics</div>
                                <a href="{{ route('admin.shop.reports.revenue') }}" class="btn btn-primary">
                                    <i class="fa fa-eye me-1"></i>View Revenue Report
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="block block-rounded border border-success bg-success-light">
                            <div class="block-content text-center py-4">
                                <div class="fs-1 fw-bold text-success mb-2">
                                    <i class="fa fa-shopping-cart"></i>
                                </div>
                                <div class="fs-5 fw-semibold">Order Reports</div>
                                <div class="text-muted mb-3">Order Analytics</div>
                                <a href="{{ route('admin.shop.reports.orders') }}" class="btn btn-success">
                                    <i class="fa fa-eye me-1"></i>View Order Report
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="block block-rounded border border-warning bg-warning-light">
                            <div class="block-content text-center py-4">
                                <div class="fs-1 fw-bold text-warning mb-2">
                                    <i class="fa fa-users"></i>
                                </div>
                                <div class="fs-5 fw-semibold">Customer Reports</div>
                                <div class="text-muted mb-3">Customer Analytics</div>
                                <a href="{{ route('admin.shop.reports.customers') }}" class="btn btn-warning">
                                    <i class="fa fa-eye me-1"></i>View Customer Report
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="block block-rounded border border-danger bg-danger-light">
                            <div class="block-content text-center py-4">
                                <div class="fs-1 fw-bold text-danger mb-2">
                                    <i class="fa fa-download"></i>
                                </div>
                                <div class="fs-5 fw-semibold">Export Data</div>
                                <div class="text-muted mb-3">CSV & Excel Reports</div>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.shop.reports.export', 'orders') }}" class="btn btn-sm btn-outline-danger">
                                        Orders
                                    </a>
                                    <a href="{{ route('admin.shop.reports.export', 'customers') }}" class="btn btn-sm btn-outline-danger">
                                        Customers
                                    </a>
                                    <a href="{{ route('admin.shop.reports.export', 'revenue') }}" class="btn btn-sm btn-outline-danger">
                                        Revenue
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-tachometer-alt me-1"></i>Quick Analytics
                </h3>
            </div>
            <div class="block-content">
                <div class="row g-3">
                    <div class="col-md-3 col-sm-6">
                        <div class="block block-rounded text-center bg-info-light">
                            <div class="block-content py-3">
                                <div class="fs-2 fw-bold text-info">
                                    <i class="fa fa-dollar-sign"></i>
                                </div>
                                <div class="fs-sm fw-semibold text-uppercase text-muted mt-1">This Month</div>
                                <div class="fs-6 fw-semibold">Revenue</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-6">
                        <div class="block block-rounded text-center bg-success-light">
                            <div class="block-content py-3">
                                <div class="fs-2 fw-bold text-success">
                                    <i class="fa fa-shopping-bag"></i>
                                </div>
                                <div class="fs-sm fw-semibold text-uppercase text-muted mt-1">This Month</div>
                                <div class="fs-6 fw-semibold">Orders</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-6">
                        <div class="block block-rounded text-center bg-warning-light">
                            <div class="block-content py-3">
                                <div class="fs-2 fw-bold text-warning">
                                    <i class="fa fa-user-plus"></i>
                                </div>
                                <div class="fs-sm fw-semibold text-uppercase text-muted mt-1">New</div>
                                <div class="fs-6 fw-semibold">Customers</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-6">
                        <div class="block block-rounded text-center bg-danger-light">
                            <div class="block-content py-3">
                                <div class="fs-2 fw-bold text-danger">
                                    <i class="fa fa-chart-bar"></i>
                                </div>
                                <div class="fs-sm fw-semibold text-uppercase text-muted mt-1">Average</div>
                                <div class="fs-6 fw-semibold">Order Value</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
