@extends('layouts.admin')

@section('title')
    Coupon Details: {{ $coupon->code }}
@endsection

@section('content-header')
    <h1>
        Coupon Details
        <small>{{ $coupon->code }}</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.index') }}">Shop</a></li>
        <li><a href="{{ route('admin.shop.coupons.index') }}">Coupons</a></li>
        <li class="active">{{ $coupon->code }}</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Coupon Information</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th width="35%">Code</th>
                                <td>
                                    <code>{{ $coupon->code }}</code>
                                    <button class="btn btn-xs btn-default ml-2" onclick="copyToClipboard('{{ $coupon->code }}')" title="Copy Code">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <th>Name</th>
                                <td>{{ $coupon->name ?: 'No name' }}</td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td>{{ $coupon->description ?: 'No description' }}</td>
                            </tr>
                            <tr>
                                <th>Type</th>
                                <td>
                                    @if($coupon->type === 'percentage')
                                        <span class="label label-info">Percentage</span>
                                    @else
                                        <span class="label label-primary">Fixed Amount</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Value</th>
                                <td>
                                    @if($coupon->type === 'percentage')
                                        {{ $coupon->value }}%
                                    @else
                                        ${{ number_format($coupon->value, 2) }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    @php
                                        $status = $coupon->getStatus();
                                    @endphp
                                    
                                    @switch($status)
                                        @case('active')
                                            <span class="label label-success">Active</span>
                                            @break
                                        @case('expired')
                                            <span class="label label-warning">Expired</span>
                                            @break
                                        @case('inactive')
                                            <span class="label label-danger">Inactive</span>
                                            @break
                                        @case('used_up')
                                            <span class="label label-default">Used Up</span>
                                            @break
                                        @default
                                            <span class="label label-default">{{ ucfirst($status) }}</span>
                                    @endswitch
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th width="35%">Usage Limit</th>
                                <td>{{ $coupon->usage_limit ?? 'Unlimited' }}</td>
                            </tr>
                            <tr>
                                <th>Per User Limit</th>
                                <td>{{ $coupon->usage_limit_per_user ?? 'Unlimited' }}</td>
                            </tr>
                            <tr>
                                <th>Times Used</th>
                                <td>
                                    {{ $coupon->used_count }}
                                    @if($coupon->usage_limit)
                                        / {{ $coupon->usage_limit }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Minimum Amount</th>
                                <td>{{ $coupon->minimum_amount ? '$' . number_format($coupon->minimum_amount, 2) : 'No minimum' }}</td>
                            </tr>
                            <tr>
                                <th>First Order Only</th>
                                <td>
                                    @if($coupon->first_order_only)
                                        <span class="label label-info">Yes</span>
                                    @else
                                        <span class="label label-default">No</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Valid From</th>
                                <td>{{ $coupon->valid_from ? $coupon->valid_from->format('M d, Y g:i A') : 'No start date' }}</td>
                            </tr>
                            <tr>
                                <th>Valid Until</th>
                                <td>{{ $coupon->valid_until ? $coupon->valid_until->format('M d, Y g:i A') : 'No expiration' }}</td>
                            </tr>
                            <tr>
                                <th>Created</th>
                                <td>{{ $coupon->created_at->format('M d, Y g:i A') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="box-footer">
                <a href="{{ route('admin.shop.coupons.edit', $coupon) }}" class="btn btn-warning">
                    <i class="fa fa-pencil"></i> Edit Coupon
                </a>
                <a href="{{ route('admin.shop.coupons.duplicate', $coupon) }}" class="btn btn-info">
                    <i class="fa fa-copy"></i> Duplicate
                </a>
                <a href="{{ route('admin.shop.coupons.index') }}" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">Usage Statistics</h3>
            </div>
            <div class="box-body">
                <div class="info-box">
                    <span class="info-box-icon bg-aqua"><i class="fa fa-shopping-cart"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Uses</span>
                        <span class="info-box-number">{{ $coupon->used_count }}</span>
                    </div>
                </div>
                
                @if($coupon->usage_limit)
                <div class="info-box">
                    <span class="info-box-icon bg-green"><i class="fa fa-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Remaining Uses</span>
                        <span class="info-box-number">{{ max(0, $coupon->usage_limit - $coupon->used_count) }}</span>
                    </div>
                </div>
                @endif
                
                <div class="info-box">
                    <span class="info-box-icon bg-yellow"><i class="fa fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Unique Users</span>
                        <span class="info-box-number">{{ $coupon->usages->unique('user_id')->count() }}</span>
                    </div>
                </div>
            </div>
        </div>
        
        @if($coupon->usages->count() > 0)
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">Recent Usage</h3>
            </div>
            <div class="box-body">
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-condensed">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Date</th>
                                <th>Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($coupon->usages->take(10) as $usage)
                            <tr>
                                <td>
                                    @if($usage->user)
                                        {{ $usage->user->email }}
                                    @else
                                        <em>Unknown User</em>
                                    @endif
                                </td>
                                <td>{{ $usage->created_at->format('M d, Y') }}</td>
                                <td>
                                    @if($usage->order)
                                        <a href="{{ route('admin.shop.orders.show', $usage->order) }}" class="text-primary">
                                            Order #{{ $usage->order->id }}
                                        </a>
                                    @else
                                        <em>No order</em>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($coupon->usages->count() > 10)
                <div class="text-center">
                    <small class="text-muted">Showing 10 of {{ $coupon->usages->count() }} uses</small>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        toastr.success('Coupon code copied to clipboard!');
    }, function(err) {
        toastr.error('Failed to copy code');
    });
}
</script>
@endsection
