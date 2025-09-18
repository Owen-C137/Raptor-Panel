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
          Create Coupon Add a new discount coupon
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
    <div class="col-md-8">
        <form method="POST" action="{{ route('admin.shop.coupons.store') }}">
            @csrf
            
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Basic Information</h3>
                </div>
                
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="code">Coupon Code <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="code" id="code" class="form-control" 
                                           value="{{ old('code') }}" required placeholder="Enter coupon code">
                                    <span class="input-group-btn">
                                        <button type="button" class="btn btn-default" onclick="generateCode()">
                                            <i class="fa fa-refresh"></i> Generate
                                        </button>
                                    </span>
                                </div>
                                @error('code')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Display Name</label>
                                <input type="text" name="name" id="name" class="form-control" 
                                       value="{{ old('name') }}" placeholder="Friendly name for internal use">
                                @error('name')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3">{{ old('description') }}</textarea>
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
                                    <option value="percentage" {{ old('type') === 'percentage' ? 'selected' : '' }}>
                                        Percentage (%)
                                    </option>
                                    <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>
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
                                    <span class="input-group-addon" id="discount-symbol">%</span>
                                    <input type="number" name="value" id="value" class="form-control" 
                                           value="{{ old('value') }}" step="0.01" min="0" required>
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
                                           value="{{ old('minimum_amount', 0) }}" step="0.01" min="0">
                                </div>
                                <small class="form-text text-muted">
                                    Minimum order amount required to use this coupon (0 = no minimum).
                                </small>
                                @error('minimum_amount')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6" id="maximum-discount-group">
                            <div class="form-group">
                                <label for="maximum_discount">Maximum Discount</label>
                                <div class="input-group">
                                    <span class="input-group-addon">$</span>
                                    <input type="number" name="maximum_discount" id="maximum_discount" class="form-control" 
                                           value="{{ old('maximum_discount', 0) }}" step="0.01" min="0">
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
                                       value="{{ old('usage_limit') }}" min="1" placeholder="Unlimited">
                                <small class="form-text text-muted">
                                    How many times this coupon can be used in total (blank = unlimited).
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
                                       value="{{ old('usage_limit_per_user') }}" min="1" placeholder="Unlimited">
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
                                       value="{{ old('valid_from') }}">
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
                                       value="{{ old('valid_until') }}">
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
                    <h3 class="box-title">Product Restrictions</h3>
                </div>
                
                <div class="box-body">
                    <div class="form-group">
                        <label>Applicable Products</label>
                        <div class="radio">
                            <label>
                                <input type="radio" name="product_restriction" value="all" 
                                       {{ old('product_restriction', 'all') === 'all' ? 'checked' : '' }}>
                                All products
                            </label>
                        </div>
                        <div class="radio">
                            <label>
                                <input type="radio" name="product_restriction" value="specific" 
                                       {{ old('product_restriction') === 'specific' ? 'checked' : '' }}>
                                Specific products only
                            </label>
                        </div>
                        <div class="radio">
                            <label>
                                <input type="radio" name="product_restriction" value="categories" 
                                       {{ old('product_restriction') === 'categories' ? 'checked' : '' }}>
                                Specific categories only
                            </label>
                        </div>
                    </div>
                    
                    <div id="specific-products" style="display: none;">
                        <div class="form-group">
                            <label for="applicable_products">Select Plans</label>
                            <select name="applicable_products[]" id="applicable_products" class="form-control" multiple>
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
                        <div class="form-group">
                            <label for="applicable_categories">Select Categories</label>
                            <select name="applicable_categories[]" id="applicable_categories" class="form-control" multiple>
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
            
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Status</h3>
                </div>
                
                <div class="box-body">
                    <div class="form-group">
                        <label for="active">Coupon Status</label>
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input type="checkbox" name="active" id="active" value="1" 
                                   {{ old('active', true) ? 'checked' : '' }}>
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
                        <i class="fa fa-save"></i> Create Coupon
                    </button>
                    <a href="{{ route('admin.shop.coupons.index') }}" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="col-md-4">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Coupon Preview</h3>
            </div>
            
            <div class="box-body">
                <div id="coupon-preview" class="well">
                    <h4 id="preview-code">COUPON-CODE</h4>
                    <p id="preview-description">Coupon description will appear here.</p>
                    <div id="preview-discount">
                        <strong id="discount-text">0% off</strong>
                    </div>
                    <div id="preview-restrictions" class="text-muted">
                        <small>No restrictions</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Coupon Tips</h3>
            </div>
            
            <div class="box-body">
                <div class="callout callout-info">
                    <h4><i class="fa fa-lightbulb-o"></i> Tips!</h4>
                    <ul class="list-unstyled">
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
                
                updatePreview();
            });
            
            // Show/hide product restriction fields
            $('input[name="product_restriction"]').on('change', function() {
                $('#specific-products, #specific-categories').hide();
                
                if ($(this).val() === 'specific') {
                    $('#specific-products').show();
                } else if ($(this).val() === 'categories') {
                    $('#specific-categories').show();
                }
                
                updatePreview();
            });
            
            // Update preview in real-time
            $('#code, #description, #value, #type, #minimum_amount').on('input change', updatePreview);
            
            // Initialize
            $('#type').trigger('change');
            updatePreview();
        });
        
        function generateCode() {
            var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            var code = '';
            for (var i = 0; i < 8; i++) {
                code += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            $('#code').val(code);
            updatePreview();
        }
        
        function updatePreview() {
            var code = $('#code').val() || 'COUPON-CODE';
            var description = $('#description').val() || 'Coupon description will appear here.';
            var type = $('#type').val();
            var value = $('#value').val() || '0';
            var minAmount = $('#minimum_amount').val();
            
            $('#preview-code').text(code);
            $('#preview-description').text(description);
            
            var discountText = type === 'percentage' ? value + '% off' : '$' + value + ' off';
            $('#discount-text').text(discountText);
            
            var restrictions = [];
            if (minAmount && minAmount > 0) {
                restrictions.push('Minimum order: $' + minAmount);
            }
            
            var restrictionType = $('input[name="product_restriction"]:checked').val();
            if (restrictionType === 'specific') {
                restrictions.push('Applies to selected products only');
            } else if (restrictionType === 'categories') {
                restrictions.push('Applies to selected categories only');
            }
            
            $('#preview-restrictions').html(
                restrictions.length > 0 
                    ? '<small>' + restrictions.join('<br>') + '</small>'
                    : '<small>No restrictions</small>'
            );
        }
    </script>
@endsection
