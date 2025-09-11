@extends('layouts.admin')

@section('title')
    Billing Settings
@endsection

@section('content-header')
    <h1>Billing Settings <small>Configure billing and currency options</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
        <li><a href="{{ route('admin.shop.settings.index') }}">Settings</a></li>
        <li class="active">Billing</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <form method="POST" action="{{ route('admin.shop.settings.billing.update') }}">
            @csrf
            @method('PUT')
            
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Currency & Pricing</h3>
                </div>
                
                <div class="box-body">
                    <div class="form-group">
                        <label for="currency">Currency <span class="text-danger">*</span></label>
                        <select name="currency" id="currency" class="form-control" required>
                            <option value="USD" {{ ($settings['currency'] ?? 'USD') === 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                            <option value="EUR" {{ ($settings['currency'] ?? '') === 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                            <option value="GBP" {{ ($settings['currency'] ?? '') === 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                            <option value="CAD" {{ ($settings['currency'] ?? '') === 'CAD' ? 'selected' : '' }}>CAD - Canadian Dollar</option>
                            <option value="AUD" {{ ($settings['currency'] ?? '') === 'AUD' ? 'selected' : '' }}>AUD - Australian Dollar</option>
                        </select>
                        @error('currency')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="tax_rate">Tax Rate (%)</label>
                        <input type="number" name="tax_rate" id="tax_rate" class="form-control" 
                               value="{{ $settings['tax_rate'] ?? '0' }}" min="0" max="100" step="0.01">
                        <p class="help-block">Default tax rate applied to orders (percentage)</p>
                        @error('tax_rate')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                <input type="hidden" name="tax_inclusive" value="0">
                                <input type="checkbox" name="tax_inclusive" value="1" 
                                       {{ ($settings['tax_inclusive'] ?? false) ? 'checked' : '' }}>
                                <strong>Tax Inclusive Pricing</strong>
                            </label>
                        </div>
                        <p class="help-block">When enabled, displayed prices include tax</p>
                    </div>
                </div>
            </div>

            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Invoicing</h3>
                </div>
                
                <div class="box-body">
                    <div class="form-group">
                        <label for="invoice_prefix">Invoice Prefix</label>
                        <input type="text" name="invoice_prefix" id="invoice_prefix" class="form-control" 
                               value="{{ $settings['invoice_prefix'] ?? 'INV' }}" maxlength="10" placeholder="INV">
                        <p class="help-block">Prefix for invoice numbers (e.g., INV-001, SHOP-001)</p>
                        @error('invoice_prefix')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Account Management</h3>
                </div>
                
                <div class="box-body">
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                <input type="hidden" name="auto_suspend_overdue" value="0">
                                <input type="checkbox" name="auto_suspend_overdue" value="1" 
                                       {{ ($settings['auto_suspend_overdue'] ?? false) ? 'checked' : '' }}>
                                <strong>Auto-suspend Overdue Services</strong>
                            </label>
                        </div>
                        <p class="help-block">Automatically suspend services when payments are overdue</p>
                    </div>

                    <div class="form-group">
                        <label for="suspend_after_days">Days Before Suspension</label>
                        <input type="number" name="suspend_after_days" id="suspend_after_days" class="form-control" 
                               value="{{ $settings['suspend_after_days'] ?? '7' }}" min="1" max="365">
                        <p class="help-block">Number of days after due date before auto-suspension</p>
                        @error('suspend_after_days')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="box">
                <div class="box-footer">
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i> Update Billing Settings
                    </button>
                    <a href="{{ route('admin.shop.settings.index') }}" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> Back to Settings
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
