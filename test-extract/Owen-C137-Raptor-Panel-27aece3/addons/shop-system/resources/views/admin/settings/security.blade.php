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
    <div class="col-lg-8">
        <form method="POST" action="{{ route('admin.shop.settings.security.update') }}">
            @csrf
            @method('PUT')
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-shield-alt me-1"></i>reCAPTCHA Protection
                    </h3>
                </div>
                
                <div class="block-content">
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="hidden" name="recaptcha_enabled" value="0">
                                <input type="checkbox" name="recaptcha_enabled" id="recaptcha_enabled" value="1" 
                                       class="form-check-input" {{ ($settings['recaptcha_enabled'] ?? false) ? 'checked' : '' }}>
                                <label for="recaptcha_enabled" class="form-check-label fw-semibold">Enable reCAPTCHA</label>
                            </div>
                            <div class="form-text">Protect forms from spam and automated attacks</div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="recaptcha_site_key" class="form-label">reCAPTCHA Site Key</label>
                            <input type="text" name="recaptcha_site_key" id="recaptcha_site_key" class="form-control" 
                                   value="{{ $settings['recaptcha_site_key'] ?? '' }}" placeholder="6Lc...">
                            <div class="form-text">Get your keys from <a href="https://www.google.com/recaptcha/" target="_blank">Google reCAPTCHA</a></div>
                            @error('recaptcha_site_key')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="recaptcha_secret_key" class="form-label">reCAPTCHA Secret Key</label>
                            <input type="password" name="recaptcha_secret_key" id="recaptcha_secret_key" class="form-control" 
                                   value="{{ $settings['recaptcha_secret_key'] ?? '' }}" placeholder="6Lc...">
                            <div class="form-text">Keep this secret and secure</div>
                            @error('recaptcha_secret_key')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-lock me-1"></i>General Security
                    </h3>
                </div>
                
                <div class="block-content">
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="hidden" name="csrf_protection" value="0">
                                <input type="checkbox" name="csrf_protection" id="csrf_protection" value="1" 
                                       class="form-check-input" {{ ($settings['csrf_protection'] ?? true) ? 'checked' : '' }}>
                                <label for="csrf_protection" class="form-check-label fw-semibold">CSRF Protection</label>
                            </div>
                            <div class="form-text">Protect against Cross-Site Request Forgery attacks</div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="hidden" name="rate_limiting" value="0">
                                <input type="checkbox" name="rate_limiting" id="rate_limiting" value="1" 
                                       class="form-check-input" {{ ($settings['rate_limiting'] ?? true) ? 'checked' : '' }}>
                                <label for="rate_limiting" class="form-check-label fw-semibold">Rate Limiting</label>
                            </div>
                            <div class="form-text">Limit the number of requests per user to prevent abuse</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-key me-1"></i>Login Security
                    </h3>
                </div>
                
                <div class="block-content">
                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="max_login_attempts" class="form-label">Maximum Login Attempts</label>
                            <input type="number" name="max_login_attempts" id="max_login_attempts" class="form-control" 
                                   value="{{ $settings['max_login_attempts'] ?? '5' }}" min="1" max="20">
                            <div class="form-text">Maximum failed login attempts before temporary lockout</div>
                            @error('max_login_attempts')
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
                                <i class="fa fa-save me-1"></i>Update Security Settings
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
