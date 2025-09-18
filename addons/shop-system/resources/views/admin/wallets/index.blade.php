@extends('layouts.admin')

@section('title')
    Wallet Management
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Wallet Management Manage user wallets and transactions
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Manage user wallets and transactions
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.index') }}">Shop Management</a></li>
          <li class="breadcrumb-item" aria-current="page">Wallet Management</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">User Wallets</h3>
                    <div class="box-tools">
                        <form method="GET" class="form-inline">
                            <div class="form-group">
                                <input type="text" name="search" value="{{ request('search') }}" 
                                       placeholder="Search by email or username..." class="form-control" style="width: 300px;">
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-search"></i> Search
                            </button>
                            @if(request('search'))
                                <a href="{{ route('admin.shop.wallets.index') }}" class="btn btn-default">
                                    <i class="fa fa-times"></i> Clear
                                </a>
                            @endif
                        </form>
                    </div>
                </div>

                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Balance</th>
                                <th>Total Spent</th>
                                <th>Total Deposited</th>
                                <th>Last Transaction</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($wallets as $wallet)
                                <tr>
                                    <td>
                                        <strong>{{ $wallet->user->name_first }} {{ $wallet->user->name_last }}</strong><br>
                                        <small class="text-muted">{{ $wallet->user->username }}</small>
                                    </td>
                                    <td>{{ $wallet->user->email }}</td>
                                    <td>
                                        <span class="label {{ $wallet->balance > 0 ? 'label-success' : 'label-default' }}">
                                            ${{ number_format($wallet->balance, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted">${{ number_format($wallet->total_spent, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="text-success">${{ number_format($wallet->total_deposited, 2) }}</span>
                                    </td>
                                    <td>
                                        @if($wallet->transactions->count() > 0)
                                            <small>{{ $wallet->transactions->first()->created_at->diffForHumans() }}</small>
                                        @else
                                            <span class="text-muted">No transactions</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.shop.wallets.show', $wallet->user) }}" 
                                           class="btn btn-xs btn-primary">
                                            <i class="fa fa-eye"></i> View Details
                                        </a>
                                        <a href="{{ route('admin.shop.wallets.manage', $wallet->user) }}" 
                                           class="btn btn-xs btn-warning">
                                            <i class="fa fa-cog"></i> Manage
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        @if(request('search'))
                                            No wallets found matching "{{ request('search') }}"
                                        @else
                                            No user wallets found
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($wallets->hasPages())
                    <div class="box-footer">
                        {{ $wallets->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row">
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-aqua"><i class="fa fa-wallet"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Wallets</span>
                    <span class="info-box-number">{{ $wallets->total() }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-money"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Balance</span>
                    <span class="info-box-number">${{ number_format($wallets->sum('balance'), 2) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fa fa-chart-line"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Spent</span>
                    <span class="info-box-number">${{ number_format($wallets->sum('total_spent'), 2) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-red"><i class="fa fa-arrow-up"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Deposited</span>
                    <span class="info-box-number">${{ number_format($wallets->sum('total_deposited'), 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Quick Actions</h3>
                </div>
                <div class="box-body">
                    <div class="btn-group" role="group">
                        <a href="{{ route('admin.shop.index') }}" class="btn btn-default">
                            <i class="fa fa-arrow-left"></i> Back to Shop Management
                        </a>
                        <a href="{{ route('admin.shop.analytics.index') }}" class="btn btn-info">
                            <i class="fa fa-chart-bar"></i> View Analytics
                        </a>
                        <a href="{{ route('admin.shop.payments.index') }}" class="btn btn-success">
                            <i class="fa fa-list"></i> All Payments
                        </a>
                        <button type="button" class="btn btn-warning" onclick="exportWalletData()">
                            <i class="fa fa-download"></i> Export Wallet Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        function exportWalletData() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            window.location.href = '{{ route("admin.shop.wallets.index") }}?' + params.toString();
        }
    </script>
@endsection
