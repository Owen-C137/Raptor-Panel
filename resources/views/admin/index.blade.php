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
<div class="row">
    <div class="col-12">
        <div class="block block-rounded block-themed">
            <div class="block-header">
                <h3 class="block-title">
                    <i class="fas fa-rocket me-2"></i>Raptor Panel Information
                </h3>
                <div class="block-options">
                    <a href="{{ route('admin.settings') }}" class="btn-block-option" title="Settings">
                        <i class="si si-settings"></i>
                    </a>
                </div>
            </div>
            <div class="block-content">
                <div class="text-center py-4">
                    <h5>Raptor Panel {{ $appVersion ?? '1.0.0' }}</h5>
                    <p class="text-muted">Update system will be rebuilt here</p>
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