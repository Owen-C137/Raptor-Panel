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
          Wallet Management
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
        <div class="col-12">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-wallet me-1"></i>User Wallets
                    </h3>
                    <div class="block-options">
                        <form method="GET" class="d-inline-flex align-items-center">
                            <div class="input-group">
                                <input type="text" name="search" value="{{ request('search') }}" 
                                       placeholder="Search by email or username..." class="form-control" style="width: 300px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-search"></i>
                                </button>
                                @if(request('search'))
                                    <a href="{{ route('admin.shop.wallets.index') }}" class="btn btn-secondary">
                                        <i class="fa fa-times"></i>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                <div class="block-content block-content-full">
                    <div class="table-responsive">
                        <table class="table table-hover table-vcenter">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Balance</th>
                                    <th>Total Spent</th>
                                    <th>Total Deposited</th>
                                    <th>Last Transaction</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($wallets as $wallet)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $wallet->user->name_first }} {{ $wallet->user->name_last }}</div>
                                            <div class="fs-sm text-muted">{{ $wallet->user->username }}</div>
                                        </td>
                                        <td>{{ $wallet->user->email }}</td>
                                        <td>
                                            <span class="badge {{ $wallet->balance > 0 ? 'bg-success' : 'bg-secondary' }}">
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
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.shop.wallets.show', $wallet->user) }}" 
                                                   class="btn btn-sm btn-primary" 
                                                   title="View Wallet Details">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.users.view', $wallet->user) }}" 
                                                   class="btn btn-sm btn-info"
                                                   title="View User Profile">
                                                    <i class="fa fa-user"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fa fa-info-circle me-1"></i>
                                                @if(request('search'))
                                                    No wallets found matching "{{ request('search') }}"
                                                @else
                                                    No user wallets found
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($wallets->hasPages())
                        <div class="d-flex justify-content-center mt-3">
                            {{ $wallets->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row g-3">
        <div class="col-md-3 col-sm-6">
            <div class="block block-rounded text-center bg-primary-light">
                <div class="block-content py-3">
                    <div class="fs-1 fw-bold text-primary">
                        <i class="fa fa-wallet"></i>
                    </div>
                    <div class="fs-sm fw-semibold text-uppercase text-muted mt-1">Total Wallets</div>
                    <div class="fs-3 fw-bold text-dark">{{ $wallets->total() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="block block-rounded text-center bg-success-light">
                <div class="block-content py-3">
                    <div class="fs-1 fw-bold text-success">
                        <i class="fa fa-dollar-sign"></i>
                    </div>
                    <div class="fs-sm fw-semibold text-uppercase text-muted mt-1">Total Balance</div>
                    <div class="fs-3 fw-bold text-dark">${{ number_format($wallets->sum('balance'), 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="block block-rounded text-center bg-warning-light">
                <div class="block-content py-3">
                    <div class="fs-1 fw-bold text-warning">
                        <i class="fa fa-chart-line"></i>
                    </div>
                    <div class="fs-sm fw-semibold text-uppercase text-muted mt-1">Total Spent</div>
                    <div class="fs-3 fw-bold text-dark">${{ number_format($wallets->sum('total_spent'), 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="block block-rounded text-center bg-info-light">
                <div class="block-content py-3">
                    <div class="fs-1 fw-bold text-info">
                        <i class="fa fa-arrow-up"></i>
                    </div>
                    <div class="fs-sm fw-semibold text-uppercase text-muted mt-1">Total Deposited</div>
                    <div class="fs-3 fw-bold text-dark">${{ number_format($wallets->sum('total_deposited'), 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-bolt me-1"></i>Quick Actions
                    </h3>
                </div>
                <div class="block-content">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.shop.index') }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left me-1"></i>Back to Shop Management
                        </a>
                        <a href="{{ route('admin.shop.analytics.index') }}" class="btn btn-info">
                            <i class="fa fa-chart-bar me-1"></i>View Analytics
                        </a>
                        <a href="{{ route('admin.shop.payments.index') }}" class="btn btn-success">
                            <i class="fa fa-list me-1"></i>All Payments
                        </a>
                        <button type="button" class="btn btn-warning" onclick="exportWalletData()">
                            <i class="fa fa-download me-1"></i>Export Wallet Data
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
