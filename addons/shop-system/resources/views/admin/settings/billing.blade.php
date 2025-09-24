@extends('layouts.admin')

@section('title')
    Billing Settings
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Billing Settings Configure billing and currency options
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Configure billing and currency options
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.settings.index') }}">Settings</a></li>
          <li class="breadcrumb-item" aria-current="page">Billing</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('admin.shop.settings.billing.update') }}">
            @csrf
            @method('PUT')
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-coins me-1"></i>Currency & Pricing
                    </h3>
                </div>
                
                <div class="block-content">
                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="currency" class="form-label">Currency <span class="text-danger">*</span></label>
                            <select name="currency" id="currency" class="form-select" required>
                                <option value="USD" {{ ($settings['currency'] ?? 'USD') === 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                <option value="EUR" {{ ($settings['currency'] ?? '') === 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                <option value="GBP" {{ ($settings['currency'] ?? '') === 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                                <option value="CAD" {{ ($settings['currency'] ?? '') === 'CAD' ? 'selected' : '' }}>CAD - Canadian Dollar</option>
                                <option value="AUD" {{ ($settings['currency'] ?? '') === 'AUD' ? 'selected' : '' }}>AUD - Australian Dollar</option>
                            </select>
                            @error('currency')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                            <input type="number" name="tax_rate" id="tax_rate" class="form-control" 
                                   value="{{ $settings['tax_rate'] ?? '0' }}" min="0" max="100" step="0.01">
                            <div class="form-text">Default tax rate applied to orders (percentage)</div>
                            @error('tax_rate')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="hidden" name="tax_inclusive" value="0">
                                <input type="checkbox" name="tax_inclusive" id="tax_inclusive" value="1" 
                                       class="form-check-input" {{ ($settings['tax_inclusive'] ?? false) ? 'checked' : '' }}>
                                <label for="tax_inclusive" class="form-check-label fw-semibold">Tax Inclusive Pricing</label>
                            </div>
                            <div class="form-text">When enabled, displayed prices include tax</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-file-invoice me-1"></i>Invoicing
                    </h3>
                </div>
                
                <div class="block-content">
                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="invoice_prefix" class="form-label">Invoice Prefix</label>
                            <input type="text" name="invoice_prefix" id="invoice_prefix" class="form-control" 
                                   value="{{ $settings['invoice_prefix'] ?? 'INV' }}" maxlength="10" placeholder="INV">
                            <div class="form-text">Prefix for invoice numbers (e.g., INV-001, SHOP-001)</div>
                            @error('invoice_prefix')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-user-cog me-1"></i>Account Management
                    </h3>
                </div>
                
                <div class="block-content">
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="hidden" name="auto_suspend_overdue" value="0">
                                <input type="checkbox" name="auto_suspend_overdue" id="auto_suspend_overdue" value="1" 
                                       class="form-check-input" {{ ($settings['auto_suspend_overdue'] ?? false) ? 'checked' : '' }}>
                                <label for="auto_suspend_overdue" class="form-check-label fw-semibold">Auto-suspend Overdue Services</label>
                            </div>
                            <div class="form-text">Automatically suspend services when payments are overdue</div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="suspend_after_days" class="form-label">Days Before Suspension</label>
                            <input type="number" name="suspend_after_days" id="suspend_after_days" class="form-control" 
                                   value="{{ $settings['suspend_after_days'] ?? '7' }}" min="1" max="365">
                            <div class="form-text">Number of days after due date before auto-suspension</div>
                            @error('suspend_after_days')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="block block-rounded">
                <div class="block-content">
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-save me-1"></i>Update Billing Settings
                            </button>
                            <a href="{{ route('admin.shop.settings.index') }}" class="btn btn-secondary ms-2">
                                <i class="fa fa-arrow-left me-1"></i>Back to Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
