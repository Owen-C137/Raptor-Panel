@extends('layouts.admin')

@section('title')
    Wallet Details - {{ $user->name_first }} {{ $user->name_last }}
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Wallet Details
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          {{ $user->username }}
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.index') }}">Shop Management</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.wallets.index') }}">Wallet Management</a></li>
          <li class="breadcrumb-item" aria-current="page">{{ $user->username }}</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
    <div class="row">
        <!-- User Information -->
        <div class="col-md-4">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">User Information</h3>
                </div>
                <div class="block-content">
                    <div class="row mb-3">
                        <div class="col-sm-4 fw-semibold">Full Name:</div>
                        <div class="col-sm-8">{{ $user->name_first }} {{ $user->name_last }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 fw-semibold">Username:</div>
                        <div class="col-sm-8">{{ $user->username }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 fw-semibold">Email:</div>
                        <div class="col-sm-8">{{ $user->email }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 fw-semibold">Member Since:</div>
                        <div class="col-sm-8">{{ $user->created_at->format('M d, Y') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wallet Summary -->
        <div class="col-md-8">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Wallet Summary</h3>
                    <div class="block-options">
                        <button type="button" class="btn btn-sm btn-success" onclick="addFunds()">
                            <i class="fa fa-plus"></i> Add Funds
                        </button>
                        <button type="button" class="btn btn-sm btn-danger ms-1" onclick="removeFunds()">
                            <i class="fa fa-minus"></i> Remove Funds
                        </button>
                    </div>
                </div>
                <div class="block-content">
                    <div class="row text-center">
                        <div class="col-sm-4">
                            <div class="py-3">
                                <div class="fs-1 fw-bold text-success">${{ number_format($wallet->balance, 2) }}</div>
                                <div class="fs-sm fw-semibold text-uppercase text-muted">
                                    <i class="fa fa-money me-1"></i>Current Balance
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="py-3">
                                <div class="fs-1 fw-bold text-primary">${{ number_format($wallet->total_deposited, 2) }}</div>
                                <div class="fs-sm fw-semibold text-uppercase text-muted">
                                    <i class="fa fa-arrow-up me-1"></i>Total Deposited
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="py-3">
                                <div class="fs-1 fw-bold text-danger">${{ number_format($wallet->total_spent, 2) }}</div>
                                <div class="fs-sm fw-semibold text-uppercase text-muted">
                                    <i class="fa fa-arrow-down me-1"></i>Total Spent
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction History -->
    <div class="row">
        <div class="col-12">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Transaction History</h3>
                    <div class="block-options">
                        <button type="button" class="btn btn-sm btn-primary" onclick="exportTransactions()">
                            <i class="fa fa-download"></i> Export
                        </button>
                    </div>
                </div>
                <div class="block-content">
                    <div class="table-responsive">
                        <table class="table table-hover table-vcenter">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Description</th>
                                    <th>Balance After</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                    <tr>
                                        <td>
                                            <span class="text-muted">{{ $transaction->created_at->format('M d, Y H:i') }}</span>
                                        </td>
                                        <td>
                                            @if($transaction->type === 'deposit')
                                                <span class="badge bg-success">
                                                    <i class="fa fa-plus"></i> Deposit
                                                </span>
                                            @elseif($transaction->type === 'withdrawal')
                                                <span class="badge bg-warning">
                                                    <i class="fa fa-minus"></i> Withdrawal
                                                </span>
                                            @elseif($transaction->type === 'purchase')
                                                <span class="badge bg-primary">
                                                    <i class="fa fa-shopping-cart"></i> Purchase
                                                </span>
                                            @elseif($transaction->type === 'refund')
                                                <span class="badge bg-info">
                                                    <i class="fa fa-undo"></i> Refund
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($transaction->type) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($transaction->type === 'deposit' || $transaction->type === 'refund')
                                                <span class="text-success fw-semibold">+${{ number_format($transaction->amount, 2) }}</span>
                                            @else
                                                <span class="text-danger fw-semibold">-${{ number_format($transaction->amount, 2) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $transaction->description ?: 'No description' }}</span>
                                        </td>
                                        <td>
                                            <strong>${{ number_format($transaction->balance_after, 2) }}</strong>
                                        </td>
                                        <td>
                                            @if($transaction->status === 'completed')
                                                <span class="badge bg-success">Completed</span>
                                            @elseif($transaction->status === 'pending')
                                                <span class="badge bg-warning">Pending</span>
                                            @elseif($transaction->status === 'failed')
                                                <span class="badge bg-danger">Failed</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($transaction->status) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">No transactions found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($transactions->hasPages())
                    <div class="block-content block-content-full">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Quick Actions</h3>
                </div>
                <div class="block-content">
                    <div class="btn-group" role="group">
                        <a href="{{ route('admin.shop.wallets.index') }}" class="btn btn-outline-secondary">
                            <i class="fa fa-arrow-left"></i> Back to Wallet Management
                        </a>
                        <a href="{{ route('admin.users.view', $user) }}" class="btn btn-outline-info">
                            <i class="fa fa-user"></i> View User Profile
                        </a>
                        <a href="{{ route('admin.shop.payments.index') }}" class="btn btn-outline-success">
                            <i class="fa fa-list"></i> All Payments
                        </a>
                        <button type="button" class="btn btn-outline-warning" onclick="exportTransactions()">
                            <i class="fa fa-download"></i> Export Transactions
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
        function exportTransactions() {
            window.location.href = '{{ route("admin.shop.wallets.show", $user) }}?export=csv';
        }

        function addFunds() {
            const amount = prompt('Enter amount to add to wallet:', '');
            if (amount && parseFloat(amount) > 0) {
                const description = prompt('Enter description (optional):', 'Admin credit adjustment');
                
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("admin.shop.wallets.credit", $user) }}';
                
                const tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = '_token';
                tokenInput.value = '{{ csrf_token() }}';
                form.appendChild(tokenInput);
                
                const amountInput = document.createElement('input');
                amountInput.type = 'hidden';
                amountInput.name = 'amount';
                amountInput.value = amount;
                form.appendChild(amountInput);
                
                const descInput = document.createElement('input');
                descInput.type = 'hidden';
                descInput.name = 'description';
                descInput.value = description;
                form.appendChild(descInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        function removeFunds() {
            const amount = prompt('Enter amount to remove from wallet:', '');
            if (amount && parseFloat(amount) > 0) {
                const description = prompt('Enter description (optional):', 'Admin debit adjustment');
                
                if (confirm('Are you sure you want to remove $' + amount + ' from this wallet?')) {
                    // Create form and submit
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route("admin.shop.wallets.debit", $user) }}';
                    
                    const tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = '_token';
                    tokenInput.value = '{{ csrf_token() }}';
                    form.appendChild(tokenInput);
                    
                    const amountInput = document.createElement('input');
                    amountInput.type = 'hidden';
                    amountInput.name = 'amount';
                    amountInput.value = amount;
                    form.appendChild(amountInput);
                    
                    const descInput = document.createElement('input');
                    descInput.type = 'hidden';
                    descInput.name = 'description';
                    descInput.value = description;
                    form.appendChild(descInput);
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        }
    </script>
@endsection
