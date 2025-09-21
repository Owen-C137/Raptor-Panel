@extends('layouts.admin')

@section('title')
    Create Coupon
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Create Coupon
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Add a new discount coupon
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.coupons.index') }}">Coupons</a></li>
          <li class="breadcrumb-item" aria-current="page">Create</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('admin.shop.coupons.store') }}">
            @csrf
            
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
                                <div class="input-group">
                                    <input type="text" name="code" id="code" class="form-control" 
                                           value="{{ old('code') }}" required placeholder="Enter coupon code">
                                    <button type="button" class="btn btn-outline-secondary" onclick="generateCode()">
                                        <i class="fa fa-refresh me-1"></i> Generate
                                    </button>
                                </div>
                                @error('code')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Display Name</label>
                                <input type="text" name="name" id="name" class="form-control" 
                                       value="{{ old('name') }}" placeholder="Friendly name for internal use">
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3">{{ old('description') }}</textarea>
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
                                    <option value="percentage" {{ old('type') === 'percentage' ? 'selected' : '' }}>
                                        Percentage (%)
                                    </option>
                                    <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>
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
                                    <span class="input-group-text" id="discount-symbol">%</span>
                                    <input type="number" name="value" id="value" class="form-control" 
                                           value="{{ old('value') }}" step="0.01" min="0" required>
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
                                           value="{{ old('minimum_amount', 0) }}" step="0.01" min="0">
                                </div>
                                <small class="text-muted">
                                    Minimum order amount required to use this coupon (0 = no minimum).
                                </small>
                                @error('minimum_amount')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6" id="maximum-discount-group">
                            <div class="mb-3">
                                <label for="maximum_discount" class="form-label">Maximum Discount</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="maximum_discount" id="maximum_discount" class="form-control" 
                                           value="{{ old('maximum_discount', 0) }}" step="0.01" min="0">
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
                                       value="{{ old('usage_limit') }}" min="1" placeholder="Unlimited">
                                <small class="text-muted">
                                    How many times this coupon can be used in total (blank = unlimited).
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
                                       value="{{ old('usage_limit_per_user') }}" min="1" placeholder="Unlimited">
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
                                       value="{{ old('valid_from') }}">
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
                                       value="{{ old('valid_until') }}">
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
                        <i class="fa fa-shopping-bag me-1"></i>Product Restrictions
                    </h3>
                </div>
                
                <div class="block-content">
                    <div class="mb-3">
                        <label class="form-label">Applicable Products</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="product_restriction" id="restriction_all" value="all" 
                                   {{ old('product_restriction', 'all') === 'all' ? 'checked' : '' }}>
                            <label class="form-check-label" for="restriction_all">
                                All products
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="product_restriction" id="restriction_specific" value="specific" 
                                   {{ old('product_restriction') === 'specific' ? 'checked' : '' }}>
                            <label class="form-check-label" for="restriction_specific">
                                Specific products only
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="product_restriction" id="restriction_categories" value="categories" 
                                   {{ old('product_restriction') === 'categories' ? 'checked' : '' }}>
                            <label class="form-check-label" for="restriction_categories">
                                Specific categories only
                            </label>
                        </div>
                    </div>
                    
                    <div id="specific-products" style="display: none;">
                        <div class="mb-3">
                            <label for="applicable_products" class="form-label">Select Plans</label>
                            <select name="applicable_products[]" id="applicable_products" class="form-select" multiple>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}" 
                                            {{ in_array($plan->id, old('applicable_products', [])) ? 'selected' : '' }}>
                                        {{ $plan->name }} ({{ $plan->category->name ?? 'No Category' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div id="specific-categories" style="display: none;">
                        <div class="mb-3">
                            <label for="applicable_categories" class="form-label">Select Categories</label>
                            <select name="applicable_categories[]" id="applicable_categories" class="form-select" multiple>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" 
                                            {{ in_array($category->id, old('applicable_categories', [])) ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
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
                                   {{ old('active', true) ? 'checked' : '' }}>
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
                        <i class="fa fa-save me-1"></i> Create Coupon
                    </button>
                    <a href="{{ route('admin.shop.coupons.index') }}" class="btn btn-secondary ms-2">
                        <i class="fa fa-arrow-left me-1"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="col-lg-4">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-eye me-1"></i>Coupon Preview
                </h3>
            </div>
            
            <div class="block-content">
                <div id="coupon-preview" class="p-3 bg-light rounded">
                    <h4 id="preview-code" class="mb-2">COUPON-CODE</h4>
                    <p id="preview-description" class="text-muted mb-2">Coupon description will appear here.</p>
                    <div id="preview-discount" class="mb-2">
                        <span id="discount-text" class="badge bg-primary">0% off</span>
                    </div>
                    <div id="preview-restrictions" class="text-muted">
                        <small>No restrictions</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-lightbulb me-1"></i>Coupon Tips
                </h3>
            </div>
            
            <div class="block-content">
                <div class="alert alert-info">
                    <h4 class="alert-heading"><i class="fa fa-lightbulb me-1"></i> Tips!</h4>
                    <ul class="list-unstyled mb-0">
                        <li>• Use memorable coupon codes</li>
                        <li>• Set reasonable expiration dates</li>
                        <li>• Consider usage limits to control costs</li>
                        <li>• Test coupons before going live</li>
                    </ul>
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
            const productRestrictionInputs = document.querySelectorAll('input[name="product_restriction"]');
            const specificProducts = document.getElementById('specific-products');
            const specificCategories = document.getElementById('specific-categories');
            
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
                
                updatePreview();
            });
            
            // Show/hide product restriction fields
            productRestrictionInputs.forEach(function(input) {
                input.addEventListener('change', function() {
                    specificProducts.style.display = 'none';
                    specificCategories.style.display = 'none';
                    
                    if (this.value === 'specific') {
                        specificProducts.style.display = 'block';
                    } else if (this.value === 'categories') {
                        specificCategories.style.display = 'block';
                    }
                    
                    updatePreview();
                });
            });
            
            // Update preview in real-time
            const previewInputs = ['code', 'description', 'value', 'type', 'minimum_amount'];
            previewInputs.forEach(function(inputId) {
                const element = document.getElementById(inputId);
                if (element) {
                    element.addEventListener('input', updatePreview);
                    element.addEventListener('change', updatePreview);
                }
            });
            
            // Initialize
            typeSelect.dispatchEvent(new Event('change'));
            updatePreview();
        });
        
        function generateCode() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let code = '';
            for (let i = 0; i < 8; i++) {
                code += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('code').value = code;
            updatePreview();
        }
        
        function updatePreview() {
            const code = document.getElementById('code').value || 'COUPON-CODE';
            const description = document.getElementById('description').value || 'Coupon description will appear here.';
            const type = document.getElementById('type').value;
            const value = document.getElementById('value').value || '0';
            const minAmount = document.getElementById('minimum_amount').value;
            
            document.getElementById('preview-code').textContent = code;
            document.getElementById('preview-description').textContent = description;
            
            const discountText = type === 'percentage' ? value + '% off' : '$' + value + ' off';
            document.getElementById('discount-text').textContent = discountText;
            
            const restrictions = [];
            if (minAmount && minAmount > 0) {
                restrictions.push('Minimum order: $' + minAmount);
            }
            
            const restrictionType = document.querySelector('input[name="product_restriction"]:checked')?.value;
            if (restrictionType === 'specific') {
                restrictions.push('Applies to selected products only');
            } else if (restrictionType === 'categories') {
                restrictions.push('Applies to selected categories only');
            }
            
            document.getElementById('preview-restrictions').innerHTML = 
                restrictions.length > 0 
                    ? '<small>' + restrictions.join('<br>') + '</small>'
                    : '<small>No restrictions</small>';
        }
    </script>
@endsection
