@extends('layouts.admin')

@section('title')
    Security Settings
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Security Settings Configure security and protection options
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Configure security and protection options
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.settings.index') }}">Settings</a></li>
          <li class="breadcrumb-item" aria-current="page">Security</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <form method="POST" action="{{ route('admin.shop.settings.security.update') }}">
            @csrf
            @method('PUT')
            
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">reCAPTCHA Protection</h3>
                </div>
                
                <div class="box-body">
                    <div class="form-group">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input type="hidden" name="recaptcha_enabled" value="0">
                            <input type="checkbox" name="recaptcha_enabled" id="recaptcha_enabled" value="1" 
                                   {{ ($settings['recaptcha_enabled'] ?? false) ? 'checked' : '' }}>
                            <label for="recaptcha_enabled" class="strong">Enable reCAPTCHA</label>
                        </div>
                        <p class="help-block">Protect forms from spam and automated attacks</p>
                    </div>

                    <div class="form-group">
                        <label for="recaptcha_site_key">reCAPTCHA Site Key</label>
                        <input type="text" name="recaptcha_site_key" id="recaptcha_site_key" class="form-control" 
                               value="{{ $settings['recaptcha_site_key'] ?? '' }}" placeholder="6Lc...">
                        <p class="help-block">Get your keys from <a href="https://www.google.com/recaptcha/" target="_blank">Google reCAPTCHA</a></p>
                        @error('recaptcha_site_key')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="recaptcha_secret_key">reCAPTCHA Secret Key</label>
                        <input type="password" name="recaptcha_secret_key" id="recaptcha_secret_key" class="form-control" 
                               value="{{ $settings['recaptcha_secret_key'] ?? '' }}" placeholder="6Lc...">
                        <p class="help-block">Keep this secret and secure</p>
                        @error('recaptcha_secret_key')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">General Security</h3>
                </div>
                
                <div class="box-body">
                    <div class="form-group">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input type="hidden" name="csrf_protection" value="0">
                            <input type="checkbox" name="csrf_protection" id="csrf_protection" value="1" 
                                   {{ ($settings['csrf_protection'] ?? true) ? 'checked' : '' }}>
                            <label for="csrf_protection" class="strong">CSRF Protection</label>
                        </div>
                        <p class="help-block">Protect against Cross-Site Request Forgery attacks</p>
                    </div>

                    <div class="form-group">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input type="hidden" name="rate_limiting" value="0">
                            <input type="checkbox" name="rate_limiting" id="rate_limiting" value="1" 
                                   {{ ($settings['rate_limiting'] ?? true) ? 'checked' : '' }}>
                            <label for="rate_limiting" class="strong">Rate Limiting</label>
                        </div>
                        <p class="help-block">Limit the number of requests per user to prevent abuse</p>
                    </div>
                </div>
            </div>

            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Login Security</h3>
                </div>
                
                <div class="box-body">
                    <div class="form-group">
                        <label for="max_login_attempts">Maximum Login Attempts</label>
                        <input type="number" name="max_login_attempts" id="max_login_attempts" class="form-control" 
                               value="{{ $settings['max_login_attempts'] ?? '5' }}" min="1" max="20">
                        <p class="help-block">Maximum failed login attempts before temporary lockout</p>
                        @error('max_login_attempts')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="box">
                <div class="box-footer">
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i> Update Security Settings
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
