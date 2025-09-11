@extends('layouts.admin')

@section('title')
    Shop Reports
@endsection

@section('content-header')
    <h1>
        Shop Reports
        <small>Analytics and detailed reporting</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
        <li class="active">Reports</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Available Reports</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6 col-sm-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-blue">
                                <i class="fas fa-chart-line"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Revenue Reports</span>
                                <span class="info-box-number">Daily & Monthly Analytics</span>
                                <div class="info-box-more">
                                    <a href="{{ route('admin.shop.reports.revenue') }}" class="btn btn-sm btn-primary">
                                        View Revenue Report
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-sm-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-green">
                                <i class="fas fa-shopping-cart"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Order Reports</span>
                                <span class="info-box-number">Order Analytics</span>
                                <div class="info-box-more">
                                    <a href="{{ route('admin.shop.reports.orders') }}" class="btn btn-sm btn-success">
                                        View Order Report
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-sm-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-yellow">
                                <i class="fas fa-users"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Customer Reports</span>
                                <span class="info-box-number">Customer Analytics</span>
                                <div class="info-box-more">
                                    <a href="{{ route('admin.shop.reports.customers') }}" class="btn btn-sm btn-warning">
                                        View Customer Report
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-sm-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-red">
                                <i class="fas fa-download"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Export Data</span>
                                <span class="info-box-number">CSV & Excel Reports</span>
                                <div class="info-box-more">
                                    <div class="btn-group">
                                        <a href="{{ route('admin.shop.reports.export', 'orders') }}" class="btn btn-sm btn-danger">
                                            Export Orders
                                        </a>
                                        <a href="{{ route('admin.shop.reports.export', 'customers') }}" class="btn btn-sm btn-danger">
                                            Export Customers
                                        </a>
                                        <a href="{{ route('admin.shop.reports.export', 'revenue') }}" class="btn btn-sm btn-danger">
                                            Export Revenue
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Quick Analytics</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-aqua">
                                <i class="fas fa-dollar-sign"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">This Month</span>
                                <span class="info-box-number">Revenue</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-green">
                                <i class="fas fa-shopping-bag"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">This Month</span>
                                <span class="info-box-number">Orders</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-yellow">
                                <i class="fas fa-user-plus"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">New</span>
                                <span class="info-box-number">Customers</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-red">
                                <i class="fas fa-chart-bar"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Average</span>
                                <span class="info-box-number">Order Value</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <style>
        .info-box-more {
            margin-top: 10px;
        }
        .info-box-more .btn-group .btn {
            margin-right: 5px;
        }
    </style>
@endsection
