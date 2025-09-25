@extends('layouts.admin')

@section('title')
    Administration
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Administrative Overview
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          A quick glance at your system status and key metrics.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.index') }}">Admin</a>
          </li>
          <li class="breadcrumb-item" aria-current="page">
            Overview
          </li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<!-- System Update Test Notice -->
<div class="row">
    <div class="col-12">
        <div class="alert alert-info alert-dismissible" role="alert">
            <h5 class="alert-heading">
                <i class="fas fa-info-circle me-2"></i>System Update Test Notice
            </h5>
            <p class="mb-2">
                <strong>Welcome to Raptor Panel v{{ $appVersion }}!</strong> This version includes enhanced admin dashboard features and improved system notifications. 
                This notice demonstrates the live update system functionality.
            </p>
            <p class="mb-0">
                <small class="text-muted">
                    <i class="fas fa-calendar me-1"></i>Updated: {{ date('F j, Y \a\t g:i A') }}
                    | <i class="fas fa-code-branch me-1"></i>Version: {{ $appVersion }}
                </small>
            </p>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <!-- Enhanced Raptor Panel Information Section -->
        <div class="block block-rounded block-themed">
            <div class="block-header">
                <h3 class="block-title">
                    <i class="fas fa-rocket me-2"></i>Raptor Panel Information
                </h3>
                <div class="block-options">
                    @if(!$version->is_latest)
                        <a href="{{ route('admin.simple-updates.index') }}" class="btn btn-sm btn-primary" title="Update Available">
                            <i class="fas fa-download me-1"></i>Update
                        </a>
                    @endif
                    <a href="{{ route('admin.settings') }}" class="btn-block-option" title="Settings">
                        <i class="si si-settings"></i>
                    </a>
                </div>
            </div>
            <div class="block-content">
                <!-- Version Status Row -->
                <div class="row text-center mb-4">
                    <div class="col-lg-3 col-md-6 py-3">
                        <div class="d-flex flex-column align-items-center">
                            <div class="item item-rounded-lg {{ $version->is_latest ? 'bg-success-light' : 'bg-warning-light' }} mb-2">
                                <i class="fas {{ $version->is_latest ? 'fa-check-circle' : 'fa-exclamation-triangle' }} fs-3 {{ $version->is_latest ? 'text-success' : 'text-warning' }}"></i>
                            </div>
                            <div class="fs-3 fw-bold text-dark">{{ config('app.version') }}</div>
                            <div class="fw-semibold text-muted">Current Version</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 py-3">
                        <div class="d-flex flex-column align-items-center">
                            <div class="item item-rounded-lg {{ $version->is_latest ? 'bg-success-light' : 'bg-warning-light' }} mb-2">
                                <i class="fas {{ $version->is_latest ? 'fa-check-circle' : 'fa-sync-alt' }} fs-3 {{ $version->is_latest ? 'text-success' : 'text-warning' }}"></i>
                            </div>
                            <div class="fs-3 fw-bold {{ $version->is_latest ? 'text-success' : 'text-warning' }}">
                                {{ $version->is_latest ? 'Up-to-date' : 'Update Available' }}
                            </div>
                            <div class="fw-semibold text-muted">Status</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 py-3">
                        <div class="d-flex flex-column align-items-center">
                            <div class="item item-rounded-lg bg-primary-light mb-2">
                                <i class="fas fa-tag fs-3 text-primary"></i>
                            </div>
                            <div class="fs-3 fw-bold text-primary">{{ $version->latest }}</div>
                            <div class="fw-semibold text-muted">Latest Version</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 py-3">
                        <div class="d-flex flex-column align-items-center">
                            <div class="item item-rounded-lg bg-info-light mb-2">
                                <i class="fab fa-php fs-3 text-info"></i>
                            </div>
                            <div class="fs-3 fw-bold text-info">PHP {{ PHP_VERSION }}</div>
                            <div class="fw-semibold text-muted">PHP Version</div>
                        </div>
                    </div>
                </div>

                @if(!$version->is_latest)
                    <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle fs-4"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="alert-heading mb-1">Update Available!</h5>
                            <p class="mb-2">
                                A new version of Raptor Panel is available. Update from 
                                <strong>v{{ config('app.version') }}</strong> to <strong>v{{ $version->latest }}</strong>.
                            </p>
                            <div class="btn-group" role="group">
                                <a href="{{ route('admin.simple-updates.index') }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-download me-1"></i>Go to Updates
                                </a>
                                <a href="https://github.com/Owen-C137/Raptor-Panel/releases/tag/v{{ $version->latest }}" target="_blank" class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-external-link-alt me-1"></i>View Changelog
                                </a>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle fs-4"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="alert-heading mb-1">You're up to date!</h5>
                            <p class="mb-0">
                                You are running the latest version of Raptor Panel 
                                <strong>v{{ config('app.version') }}</strong>.
                            </p>
                        </div>
                    </div>
                @endif

                <!-- System Information Grid -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="block block-rounded border">
                            <div class="block-header block-header-default">
                                <h3 class="block-title fs-sm">
                                    <i class="fas fa-server me-2 text-primary"></i>Server Information
                                </h3>
                            </div>
                            <div class="block-content">
                                <table class="table table-borderless table-sm">
                                    <tbody>
                                        <tr>
                                            <td class="fw-semibold text-muted" style="width: 40%;">
                                                <i class="fas fa-desktop me-2"></i>Operating System
                                            </td>
                                            <td>{{ PHP_OS_FAMILY }} ({{ php_uname('r') }})</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted">
                                                <i class="fas fa-microchip me-2"></i>Architecture
                                            </td>
                                            <td>{{ php_uname('m') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted">
                                                <i class="fas fa-memory me-2"></i>Memory Limit
                                            </td>
                                            <td>{{ ini_get('memory_limit') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted">
                                                <i class="fas fa-clock me-2"></i>Max Execution Time
                                            </td>
                                            <td>{{ ini_get('max_execution_time') }}s</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted">
                                                <i class="fas fa-upload me-2"></i>Max Upload Size
                                            </td>
                                            <td>{{ ini_get('upload_max_filesize') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="block block-rounded border">
                            <div class="block-header block-header-default">
                                <h3 class="block-title fs-sm">
                                    <i class="fas fa-cogs me-2 text-info"></i>Application Information
                                </h3>
                            </div>
                            <div class="block-content">
                                <table class="table table-borderless table-sm">
                                    <tbody>
                                        <tr>
                                            <td class="fw-semibold text-muted" style="width: 40%;">
                                                <i class="fab fa-laravel me-2"></i>Laravel Version
                                            </td>
                                            <td>{{ app()->version() }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted">
                                                <i class="fas fa-layer-group me-2"></i>Environment
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ app()->environment() === 'production' ? 'success' : 'warning' }}">
                                                    {{ ucfirst(app()->environment()) }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted">
                                                <i class="fas fa-bug me-2"></i>Debug Mode
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ config('app.debug') ? 'danger' : 'success' }}">
                                                    {{ config('app.debug') ? 'Enabled' : 'Disabled' }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted">
                                                <i class="fas fa-globe me-2"></i>Timezone
                                            </td>
                                            <td>{{ config('app.timezone') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted">
                                                <i class="fas fa-database me-2"></i>Database Driver
                                            </td>
                                            <td>{{ ucfirst(config('database.default')) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-6 col-sm-3 text-center">
        <a href="https://discord.gg/GEH2Fc5sgK" class="btn btn-warning w-100 mb-2">
            <i class="fab fa-discord me-2"></i>Discord
        </a>
    </div>
    <div class="col-6 col-sm-3 text-center">
        <a href="https://github.com/Owen-C137/Raptor-Panel" class="btn btn-dark w-100 mb-2">
            <i class="fab fa-github me-2"></i>GitHub
        </a>
    </div>
    <div class="col-6 col-sm-3 text-center">
        <a href="https://raptorpanel.live" class="btn btn-primary w-100 mb-2">
            <i class="fas fa-globe me-2"></i>Website
        </a>
    </div>
    <div class="col-6 col-sm-3 text-center">
        <a href="https://ko-fi.com/owenc137" class="btn btn-danger w-100 mb-2">
            <i class="fas fa-heart me-2"></i>Donate
        </a>
    </div>
</div>

<div class="row">
    <div class="col-sm-6 col-xl-3">
        <div class="block block-rounded d-flex flex-column h-100 mb-0">
            <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                <div class="me-3">
                    <p class="fs-3 fw-bold mb-0">
                        {{ $servers }}
                    </p>
                    <p class="text-muted mb-0">
                        Total Servers
                    </p>
                </div>
                <div class="item item-rounded-lg bg-body-light">
                    <i class="far fa-gem fs-3 text-primary"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="block block-rounded d-flex flex-column h-100 mb-0">
            <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                <div class="me-3">
                    <p class="fs-3 fw-bold mb-0">
                        {{ $suspensions }}
                    </p>
                    <p class="text-muted mb-0">
                        Suspended Servers
                    </p>
                </div>
                <div class="item item-rounded-lg bg-body-light">
                    <i class="fas fa-ban fs-3 text-danger"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="block block-rounded d-flex flex-column h-100 mb-0">
            <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                <div class="me-3">
                    <p class="fs-3 fw-bold mb-0">
                        {{ $nodes }}
                    </p>
                    <p class="text-muted mb-0">
                        Total Nodes
                    </p>
                </div>
                <div class="item item-rounded-lg bg-body-light">
                    <i class="fas fa-server fs-3 text-info"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="block block-rounded d-flex flex-column h-100 mb-0">
            <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                <div class="me-3">
                    <p class="fs-3 fw-bold mb-0">
                        {{ $users }}
                    </p>
                    <p class="text-muted mb-0">
                        Total Users
                    </p>
                </div>
                <div class="item item-rounded-lg bg-body-light">
                    <i class="far fa-user fs-3 text-warning"></i>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection