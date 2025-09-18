@extends('layouts.admin')

@section('title')
    Coupons
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Coupons Manage discount coupons
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Manage discount coupons
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
          <li class="breadcrumb-item" aria-current="page">Coupons</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Coupon Management</h3>
                <div class="box-tools">
                    <a href="{{ route('admin.shop.coupons.create') }}" class="btn btn-success btn-sm">
                        <i class="fa fa-plus"></i> Create Coupon
                    </a>
                </div>
            </div>
            
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            <input type="text" id="searchCoupons" class="form-control" placeholder="Search coupons...">
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            <select id="filterStatus" class="form-control">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="expired">Expired</option>
                                <option value="inactive">Inactive</option>
                                <option value="used_up">Used Up</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            <select id="filterType" class="form-control">
                                <option value="">All Types</option>
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed Amount</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-group">
                            <button class="btn btn-default btn-block" onclick="resetFilters()">
                                <i class="fa fa-refresh"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
                
                <table class="table table-bordered table-hover" id="couponsTable">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Usage</th>
                            <th>Valid Period</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coupons as $coupon)
                            <tr data-status="{{ $coupon->getStatus() }}" data-type="{{ $coupon->type }}">
                                <td>
                                    <strong>{{ $coupon->code }}</strong>
                                    @if($coupon->name)
                                        <br><small class="text-muted">{{ $coupon->name }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($coupon->type === 'percentage')
                                        <span class="label label-info">Percentage</span>
                                    @else
                                        <span class="label label-primary">Fixed Amount</span>
                                    @endif
                                </td>
                                <td>
                                    @if($coupon->type === 'percentage')
                                        {{ $coupon->value }}%
                                    @else
                                        ${{ number_format($coupon->value, 2) }}
                                    @endif
                                    
                                    @if($coupon->minimum_amount > 0)
                                        <br><small class="text-muted">Min: ${{ number_format($coupon->minimum_amount, 2) }}</small>
                                    @endif
                                    
                                    @if($coupon->maximum_discount > 0 && $coupon->type === 'percentage')
                                        <br><small class="text-muted">Max: ${{ number_format($coupon->maximum_discount, 2) }}</small>
                                    @endif
                                </td>
                                <td>
                                    {{ $coupon->used_count ?? 0 }}
                                    @if($coupon->usage_limit)
                                        / {{ $coupon->usage_limit }}
                                    @else
                                        / ∞
                                    @endif
                                    
                                    @if($coupon->usage_limit_per_user)
                                        <br><small class="text-muted">{{ $coupon->usage_limit_per_user }}/user</small>
                                    @endif
                                </td>
                                <td>
                                    @if($coupon->valid_from)
                                        <small>From: {{ $coupon->valid_from->format('M d, Y') }}</small><br>
                                    @endif
                                    @if($coupon->valid_until)
                                        <small>Until: {{ $coupon->valid_until->format('M d, Y') }}</small>
                                    @else
                                        <small>No expiration</small>
                                    @endif
                                </td>
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
                                <td>{{ $coupon->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.shop.coupons.show', $coupon->id) }}" 
                                           class="btn btn-xs btn-primary" title="View">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.shop.coupons.edit', $coupon->id) }}" 
                                           class="btn btn-xs btn-warning" title="Edit">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                        @if($coupon->active)
                                            <button class="btn btn-xs btn-danger" 
                                                    onclick="toggleCoupon({{ $coupon->id }}, false)" title="Deactivate">
                                                <i class="fa fa-pause"></i>
                                            </button>
                                        @else
                                            <button class="btn btn-xs btn-success" 
                                                    onclick="toggleCoupon({{ $coupon->id }}, true)" title="Activate">
                                                <i class="fa fa-play"></i>
                                            </button>
                                        @endif
                                        <button class="btn btn-xs btn-danger" 
                                                onclick="deleteCoupon({{ $coupon->id }})" title="Delete">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">
                                    <p class="text-muted">No coupons found.</p>
                                    <a href="{{ route('admin.shop.coupons.create') }}" class="btn btn-success">
                                        <i class="fa fa-plus"></i> Create First Coupon
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if(method_exists($coupons, 'links'))
                <div class="box-footer">
                    {{ $coupons->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row">
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-aqua">
            <div class="inner">
                <h3>{{ $stats['total_coupons'] ?? $coupons->count() }}</h3>
                <p>Total Coupons</p>
            </div>
            <div class="icon">
                <i class="fa fa-tags"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-green">
            <div class="inner">
                <h3>{{ $stats['active_coupons'] ?? $coupons->where('active', true)->count() }}</h3>
                <p>Active Coupons</p>
            </div>
            <div class="icon">
                <i class="fa fa-check"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-yellow">
            <div class="inner">
                <h3>{{ $stats['used_coupons'] ?? 0 }}</h3>
                <p>Times Used</p>
            </div>
            <div class="icon">
                <i class="fa fa-shopping-cart"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-red">
            <div class="inner">
                <h3>${{ number_format($stats['total_discount'] ?? 0, 2) }}</h3>
                <p>Total Discounts</p>
            </div>
            <div class="icon">
                <i class="fa fa-money"></i>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
            // Search functionality
            $('#searchCoupons').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $("#couponsTable tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
            
            // Status filter
            $('#filterStatus').on('change', function() {
                var status = $(this).val();
                if (status === '') {
                    $("#couponsTable tbody tr").show();
                } else {
                    $("#couponsTable tbody tr").each(function() {
                        var rowStatus = $(this).data('status');
                        if (rowStatus === status) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                }
            });
            
            // Type filter
            $('#filterType').on('change', function() {
                var type = $(this).val();
                if (type === '') {
                    $("#couponsTable tbody tr").show();
                } else {
                    $("#couponsTable tbody tr").each(function() {
                        var rowType = $(this).data('type');
                        if (rowType === type) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                }
            });
        });
        
        function resetFilters() {
            $('#searchCoupons').val('');
            $('#filterStatus').val('');
            $('#filterType').val('');
            $("#couponsTable tbody tr").show();
        }
        
        function toggleCoupon(couponId, activate) {
            var action = activate ? 'activate' : 'deactivate';
            if (confirm('Are you sure you want to ' + action + ' this coupon?')) {
                $.ajax({
                    url: '{{ url('admin/shop/coupons') }}/' + couponId + '/toggle-status',
                    type: 'POST',
                    data: {
                        active: activate,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred while updating the coupon.');
                    }
                });
            }
        }
        
        function deleteCoupon(couponId) {
            if (confirm('Are you sure you want to delete this coupon? This action cannot be undone.')) {
                $.ajax({
                    url: '/admin/shop/coupons/' + couponId,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred while deleting the coupon.');
                    }
                });
            }
        }
    </script>
@endsection
