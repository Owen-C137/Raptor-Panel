@extends('layouts.admin')

@section('title')
    Edit Coupon: {{ $coupon->code }}
@endsection

@section('content-header')
    <h1>Edit Coupon <small>{{ $coupon->code }}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
        <li><a href="{{ route('admin.shop.coupons.index') }}">Coupons</a></li>
        <li class="active">Edit</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <form method="POST" action="{{ route('admin.shop.coupons.update', $coupon->id) }}">
            @csrf
            @method('PUT')
            
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Basic Information</h3>
                </div>
                
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="code">Coupon Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" id="code" class="form-control" 
                                       value="{{ old('code', $coupon->code) }}" required>
                                @error('code')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Display Name</label>
                                <input type="text" name="name" id="name" class="form-control" 
                                       value="{{ old('name', $coupon->name) }}" placeholder="Friendly name for internal use">
                                @error('name')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $coupon->description) }}</textarea>
                        @error('description')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Discount Settings</h3>
                </div>
                
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="type">Discount Type <span class="text-danger">*</span></label>
                                <select name="type" id="type" class="form-control" required>
                                    <option value="percentage" {{ old('type', $coupon->type) === 'percentage' ? 'selected' : '' }}>
                                        Percentage (%)
                                    </option>
                                    <option value="fixed" {{ old('type', $coupon->type) === 'fixed' ? 'selected' : '' }}>
                                        Fixed Amount ($)
                                    </option>
                                </select>
                                @error('type')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="value">Discount Value <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-addon" id="discount-symbol">
                                        {{ $coupon->type === 'percentage' ? '%' : '$' }}
                                    </span>
                                    <input type="number" name="value" id="value" class="form-control" 
                                           value="{{ old('value', $coupon->value) }}" step="0.01" min="0" required>
                                </div>
                                @error('value')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="minimum_amount">Minimum Order Amount</label>
                                <div class="input-group">
                                    <span class="input-group-addon">$</span>
                                    <input type="number" name="minimum_amount" id="minimum_amount" class="form-control" 
                                           value="{{ old('minimum_amount', $coupon->minimum_amount ?? 0) }}" step="0.01" min="0">
                                </div>
                                <small class="form-text text-muted">
                                    Minimum order amount required to use this coupon (0 = no minimum).
                                </small>
                                @error('minimum_amount')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6" id="maximum-discount-group" 
                             style="{{ $coupon->type === 'fixed' ? 'display: none;' : '' }}">
                            <div class="form-group">
                                <label for="maximum_discount">Maximum Discount</label>
                                <div class="input-group">
                                    <span class="input-group-addon">$</span>
                                    <input type="number" name="maximum_discount" id="maximum_discount" class="form-control" 
                                           value="{{ old('maximum_discount', $coupon->maximum_discount ?? 0) }}" step="0.01" min="0">
                                </div>
                                <small class="form-text text-muted">
                                    Maximum discount amount for percentage coupons (0 = no limit).
                                </small>
                                @error('maximum_discount')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Usage Restrictions</h3>
                </div>
                
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="usage_limit">Total Usage Limit</label>
                                <input type="number" name="usage_limit" id="usage_limit" class="form-control" 
                                       value="{{ old('usage_limit', $coupon->usage_limit) }}" min="1" placeholder="Unlimited">
                                <small class="form-text text-muted">
                                    How many times this coupon can be used in total (blank = unlimited).
                                    <strong>Used: {{ $coupon->used_count ?? 0 }} times</strong>
                                </small>
                                @error('usage_limit')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="usage_limit_per_user">Usage Limit Per User</label>
                                <input type="number" name="usage_limit_per_user" id="usage_limit_per_user" class="form-control" 
                                       value="{{ old('usage_limit_per_user', $coupon->usage_limit_per_user) }}" min="1" placeholder="Unlimited">
                                <small class="form-text text-muted">
                                    How many times each user can use this coupon (blank = unlimited).
                                </small>
                                @error('usage_limit_per_user')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="valid_from">Valid From</label>
                                <input type="datetime-local" name="valid_from" id="valid_from" class="form-control" 
                                       value="{{ old('valid_from', $coupon->valid_from ? $coupon->valid_from->format('Y-m-d\TH:i') : '') }}">
                                <small class="form-text text-muted">
                                    When this coupon becomes active (blank = immediately).
                                </small>
                                @error('valid_from')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="valid_until">Valid Until</label>
                                <input type="datetime-local" name="valid_until" id="valid_until" class="form-control" 
                                       value="{{ old('valid_until', $coupon->valid_until ? $coupon->valid_until->format('Y-m-d\TH:i') : '') }}">
                                <small class="form-text text-muted">
                                    When this coupon expires (blank = never expires).
                                </small>
                                @error('valid_until')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Status</h3>
                </div>
                
                <div class="box-body">
                    <div class="form-group">
                        <label for="active">Coupon Status</label>
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input type="checkbox" name="active" id="active" value="1" 
                                   {{ old('active', $coupon->active) ? 'checked' : '' }}>
                            <label for="active" class="strong">Active</label>
                        </div>
                        <small class="form-text text-muted">
                            Inactive coupons cannot be used by customers.
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="box">
                <div class="box-footer">
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i> Update Coupon
                    </button>
                    <a href="{{ route('admin.shop.coupons.show', $coupon->id) }}" class="btn btn-primary">
                        <i class="fa fa-eye"></i> View Coupon
                    </a>
                    <a href="{{ route('admin.shop.coupons.index') }}" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> Back to Coupons
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="col-md-4">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Coupon Statistics</h3>
            </div>
            
            <div class="box-body">
                <dl class="dl-horizontal">
                    <dt>Status:</dt>
                    <dd>
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
                    </dd>
                    
                    <dt>Times Used:</dt>
                    <dd>{{ $coupon->used_count ?? 0 }}</dd>
                    
                    <dt>Created:</dt>
                    <dd>{{ $coupon->created_at->format('M d, Y g:i A') }}</dd>
                    
                    <dt>Updated:</dt>
                    <dd>{{ $coupon->updated_at->format('M d, Y g:i A') }}</dd>
                    
                    @if($coupon->usage_limit)
                        <dt>Usage Progress:</dt>
                        <dd>
                            <div class="progress progress-sm">
                                <div class="progress-bar" style="width: {{ ($coupon->used_count / $coupon->usage_limit) * 100 }}%"></div>
                            </div>
                            {{ $coupon->used_count }} / {{ $coupon->usage_limit }}
                        </dd>
                    @endif
                </dl>
            </div>
        </div>
        
        @if($coupon->usages && $coupon->usages->count() > 0)
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Recent Usage</h3>
                </div>
                
                <div class="box-body">
                    @foreach($coupon->usages->take(5) as $usage)
                        <div class="media">
                            <div class="media-body">
                                <h5 class="media-heading">
                                    <a href="{{ route('admin.users.view', $usage->user_id) }}">
                                        {{ $usage->user->username ?? 'Unknown User' }}
                                    </a>
                                    <small class="text-muted">{{ $usage->created_at->diffForHumans() }}</small>
                                </h5>
                                <p class="margin-bottom-5">
                                    Order: 
                                    <a href="{{ route('admin.shop.orders.show', $usage->order_id) }}">
                                        #{{ $usage->order_id }}
                                    </a>
                                    <br>
                                    <small class="text-muted">Discount: ${{ number_format($usage->discount_amount, 2) }}</small>
                                </p>
                            </div>
                        </div>
                        @if(!$loop->last)<hr>@endif
                    @endforeach
                    
                    @if($coupon->usages->count() > 5)
                        <div class="text-center">
                            <a href="{{ route('admin.shop.coupons.usage', $coupon->id) }}" class="btn btn-sm btn-default">
                                View All Usage ({{ $coupon->usages->count() }})
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endif
        
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Quick Actions</h3>
            </div>
            
            <div class="box-body">
                @if($coupon->active)
                    <button class="btn btn-warning btn-block" onclick="toggleCoupon(false)">
                        <i class="fa fa-pause"></i> Deactivate Coupon
                    </button>
                @else
                    <button class="btn btn-success btn-block" onclick="toggleCoupon(true)">
                        <i class="fa fa-play"></i> Activate Coupon
                    </button>
                @endif
                
                <button class="btn btn-info btn-block" onclick="duplicateCoupon()">
                    <i class="fa fa-copy"></i> Duplicate Coupon
                </button>
                
                <hr>
                
                <button class="btn btn-danger btn-block" onclick="deleteCoupon()">
                    <i class="fa fa-trash"></i> Delete Coupon
                </button>
                <small class="form-text text-muted">
                    This action cannot be undone.
                </small>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
            // Update discount symbol based on type
            $('#type').on('change', function() {
                var symbol = $(this).val() === 'percentage' ? '%' : '$';
                $('#discount-symbol').text(symbol);
                
                // Show/hide maximum discount field
                if ($(this).val() === 'percentage') {
                    $('#maximum-discount-group').show();
                } else {
                    $('#maximum-discount-group').hide();
                }
            });
            
            // Initialize
            $('#type').trigger('change');
        });
        
        function toggleCoupon(activate) {
            var action = activate ? 'activate' : 'deactivate';
            if (confirm('Are you sure you want to ' + action + ' this coupon?')) {
                $.ajax({
                    url: '{{ route('admin.shop.coupons.toggle-status', $coupon->id) }}',
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
        
        function duplicateCoupon() {
            if (confirm('Create a duplicate of this coupon?')) {
                window.location.href = '{{ route('admin.shop.coupons.duplicate', $coupon->id) }}';
            }
        }
        
        function deleteCoupon() {
            if (confirm('Are you sure you want to delete this coupon? This action cannot be undone.')) {
                $.ajax({
                    url: '{{ route('admin.shop.coupons.destroy', $coupon->id) }}',
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.href = '{{ route('admin.shop.coupons.index') }}';
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
