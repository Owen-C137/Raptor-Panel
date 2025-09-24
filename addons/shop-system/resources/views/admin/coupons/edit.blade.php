@extends('layouts.admin')

@section('title')
    Edit Coupon: {{ $coupon->code }}
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Edit Coupon {{ $coupon->code }}
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          {{ $coupon->code }}
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.coupons.index') }}">Coupons</a></li>
          <li class="breadcrumb-item" aria-current="page">Edit</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('admin.shop.coupons.update', $coupon->id) }}">
            @csrf
            @method('PUT')
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-info-circle me-1"></i>Basic Information
                    </h3>
                </div>
                
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="code" class="form-label">Coupon Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" id="code" class="form-control" 
                                       value="{{ old('code', $coupon->code) }}" required>
                                @error('code')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Display Name</label>
                                <input type="text" name="name" id="name" class="form-control" 
                                       value="{{ old('name', $coupon->name) }}" placeholder="Friendly name for internal use">
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $coupon->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-tag me-1"></i>Discount Settings
                    </h3>
                </div>
                
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="type" class="form-label">Discount Type <span class="text-danger">*</span></label>
                                <select name="type" id="type" class="form-select" required>
                                    <option value="percentage" {{ old('type', $coupon->type) === 'percentage' ? 'selected' : '' }}>
                                        Percentage (%)
                                    </option>
                                    <option value="fixed" {{ old('type', $coupon->type) === 'fixed' ? 'selected' : '' }}>
                                        Fixed Amount ($)
                                    </option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="value" class="form-label">Discount Value <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text" id="discount-symbol">
                                        {{ $coupon->type === 'percentage' ? '%' : '$' }}
                                    </span>
                                    <input type="number" name="value" id="value" class="form-control" 
                                           value="{{ old('value', $coupon->value) }}" step="0.01" min="0" required>
                                </div>
                                @error('value')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="minimum_amount" class="form-label">Minimum Order Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="minimum_amount" id="minimum_amount" class="form-control" 
                                           value="{{ old('minimum_amount', $coupon->minimum_amount ?? 0) }}" step="0.01" min="0">
                                </div>
                                <small class="text-muted">
                                    Minimum order amount required to use this coupon (0 = no minimum).
                                </small>
                                @error('minimum_amount')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6" id="maximum-discount-group" 
                             style="{{ $coupon->type === 'fixed' ? 'display: none;' : '' }}">
                            <div class="mb-3">
                                <label for="maximum_discount" class="form-label">Maximum Discount</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="maximum_discount" id="maximum_discount" class="form-control" 
                                           value="{{ old('maximum_discount', $coupon->maximum_discount ?? 0) }}" step="0.01" min="0">
                                </div>
                                <small class="text-muted">
                                    Maximum discount amount for percentage coupons (0 = no limit).
                                </small>
                                @error('maximum_discount')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-lock me-1"></i>Usage Restrictions
                    </h3>
                </div>
                
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="usage_limit" class="form-label">Total Usage Limit</label>
                                <input type="number" name="usage_limit" id="usage_limit" class="form-control" 
                                       value="{{ old('usage_limit', $coupon->usage_limit) }}" min="1" placeholder="Unlimited">
                                <small class="text-muted">
                                    How many times this coupon can be used in total (blank = unlimited).
                                    <strong>Used: {{ $coupon->used_count ?? 0 }} times</strong>
                                </small>
                                @error('usage_limit')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="usage_limit_per_user" class="form-label">Usage Limit Per User</label>
                                <input type="number" name="usage_limit_per_user" id="usage_limit_per_user" class="form-control" 
                                       value="{{ old('usage_limit_per_user', $coupon->usage_limit_per_user) }}" min="1" placeholder="Unlimited">
                                <small class="text-muted">
                                    How many times each user can use this coupon (blank = unlimited).
                                </small>
                                @error('usage_limit_per_user')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="valid_from" class="form-label">Valid From</label>
                                <input type="datetime-local" name="valid_from" id="valid_from" class="form-control" 
                                       value="{{ old('valid_from', $coupon->valid_from ? $coupon->valid_from->format('Y-m-d\TH:i') : '') }}">
                                <small class="text-muted">
                                    When this coupon becomes active (blank = immediately).
                                </small>
                                @error('valid_from')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="valid_until" class="form-label">Valid Until</label>
                                <input type="datetime-local" name="valid_until" id="valid_until" class="form-control" 
                                       value="{{ old('valid_until', $coupon->valid_until ? $coupon->valid_until->format('Y-m-d\TH:i') : '') }}">
                                <small class="text-muted">
                                    When this coupon expires (blank = never expires).
                                </small>
                                @error('valid_until')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-toggle-on me-1"></i>Status
                    </h3>
                </div>
                
                <div class="block-content">
                    <div class="mb-3">
                        <label for="active" class="form-label">Coupon Status</label>
                        <div class="form-check form-switch">
                            <input type="checkbox" name="active" id="active" value="1" class="form-check-input"
                                   {{ old('active', $coupon->active) ? 'checked' : '' }}>
                            <label for="active" class="form-check-label fw-medium">Active</label>
                        </div>
                        <small class="text-muted">
                            Inactive coupons cannot be used by customers.
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="block block-rounded">
                <div class="block-content text-center">
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save me-1"></i> Update Coupon
                    </button>
                    <a href="{{ route('admin.shop.coupons.show', $coupon->id) }}" class="btn btn-primary ms-2">
                        <i class="fa fa-eye me-1"></i> View Coupon
                    </a>
                    <a href="{{ route('admin.shop.coupons.index') }}" class="btn btn-secondary ms-2">
                        <i class="fa fa-arrow-left me-1"></i> Back to Coupons
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="col-lg-4">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-chart-bar me-1"></i>Coupon Statistics
                </h3>
            </div>
            
            <div class="block-content">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-center">
                            <div class="text-muted small">Status</div>
                            @php
                                $status = $coupon->getStatus();
                            @endphp
                            
                            @switch($status)
                                @case('active')
                                    <span class="badge bg-success">Active</span>
                                    @break
                                @case('expired')
                                    <span class="badge bg-warning">Expired</span>
                                    @break
                                @case('inactive')
                                    <span class="badge bg-danger">Inactive</span>
                                    @break
                                @case('used_up')
                                    <span class="badge bg-secondary">Used Up</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ ucfirst($status) }}</span>
                            @endswitch
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="text-center">
                            <div class="text-muted small">Times Used</div>
                            <div class="fw-medium">{{ $coupon->used_count ?? 0 }}</div>
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="text-center">
                            <div class="text-muted small">Created</div>
                            <div class="fw-medium">{{ $coupon->created_at->format('M d, Y') }}</div>
                            <small class="text-muted">{{ $coupon->created_at->format('g:i A') }}</small>
                        </div>
                    </div>
                    
                    <div class="col-6">
                        <div class="text-center">
                            <div class="text-muted small">Updated</div>
                            <div class="fw-medium">{{ $coupon->updated_at->format('M d, Y') }}</div>
                            <small class="text-muted">{{ $coupon->updated_at->format('g:i A') }}</small>
                        </div>
                    </div>
                    
                    @if($coupon->usage_limit)
                        <div class="col-12">
                            <div class="text-center">
                                <div class="text-muted small mb-1">Usage Progress</div>
                                <div class="progress mb-1" style="height: 8px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: {{ ($coupon->used_count / $coupon->usage_limit) * 100 }}%"></div>
                                </div>
                                <small class="text-muted">{{ $coupon->used_count }} / {{ $coupon->usage_limit }}</small>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        @if($coupon->usages && $coupon->usages->count() > 0)
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-history me-1"></i>Recent Usage
                    </h3>
                </div>
                
                <div class="block-content">
                    @foreach($coupon->usages->take(5) as $usage)
                        <div class="d-flex align-items-center py-2">
                            <div class="flex-fill">
                                <div class="fw-medium">
                                    <a href="{{ route('admin.users.view', $usage->user_id) }}" class="link-fx">
                                        {{ $usage->user->username ?? 'Unknown User' }}
                                    </a>
                                    <small class="text-muted ms-1">{{ $usage->created_at->diffForHumans() }}</small>
                                </div>
                                <div class="small text-muted">
                                    Order: 
                                    <a href="{{ route('admin.shop.orders.show', $usage->order_id) }}" class="link-fx">
                                        #{{ $usage->order_id }}
                                    </a>
                                    | Discount: ${{ number_format($usage->discount_amount, 2) }}
                                </div>
                            </div>
                        </div>
                        @if(!$loop->last)<hr class="my-1">@endif
                    @endforeach
                    
                    @if($coupon->usages->count() > 5)
                        <div class="text-center mt-3">
                            <a href="{{ route('admin.shop.coupons.usage', $coupon->id) }}" class="btn btn-sm btn-outline-primary">
                                View All Usage ({{ $coupon->usages->count() }})
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endif
        
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-bolt me-1"></i>Quick Actions
                </h3>
            </div>
            
            <div class="block-content">
                <div class="d-grid gap-2">
                    @if($coupon->active)
                        <button class="btn btn-warning" onclick="toggleCoupon(false)">
                            <i class="fa fa-pause me-1"></i> Deactivate Coupon
                        </button>
                    @else
                        <button class="btn btn-success" onclick="toggleCoupon(true)">
                            <i class="fa fa-play me-1"></i> Activate Coupon
                        </button>
                    @endif
                    
                    <button class="btn btn-info" onclick="duplicateCoupon()">
                        <i class="fa fa-copy me-1"></i> Duplicate Coupon
                    </button>
                    
                    <hr class="my-2">
                    
                    <button class="btn btn-danger" onclick="deleteCoupon()">
                        <i class="fa fa-trash me-1"></i> Delete Coupon
                    </button>
                    <small class="text-muted">
                        This action cannot be undone.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const typeSelect = document.getElementById('type');
            const discountSymbol = document.getElementById('discount-symbol');
            const maxDiscountGroup = document.getElementById('maximum-discount-group');
            
            // Update discount symbol based on type
            typeSelect.addEventListener('change', function() {
                const symbol = this.value === 'percentage' ? '%' : '$';
                discountSymbol.textContent = symbol;
                
                // Show/hide maximum discount field
                if (this.value === 'percentage') {
                    maxDiscountGroup.style.display = 'block';
                } else {
                    maxDiscountGroup.style.display = 'none';
                }
            });
            
            // Initialize
            typeSelect.dispatchEvent(new Event('change'));
        });
        
        function toggleCoupon(activate) {
            const action = activate ? 'activate' : 'deactivate';
            if (confirm('Are you sure you want to ' + action + ' this coupon?')) {
                fetch('{{ route('admin.shop.coupons.toggle-status', $coupon->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        active: activate
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the coupon.');
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
                fetch('{{ route('admin.shop.coupons.destroy', $coupon->id) }}', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '{{ route('admin.shop.coupons.index') }}';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the coupon.');
                });
            }
        }
    </script>
@endsection
