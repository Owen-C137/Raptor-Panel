@extends('layouts.admin')

@section('title')
    Wallet Details - {{ $user->name_first }} {{ $user->name_last }}
@endsection

@section('content-header')
    <h1>Wallet Details <small>{{ $user->username }}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.index') }}">Shop Management</a></li>
        <li><a href="{{ route('admin.shop.wallets.index') }}">Wallet Management</a></li>
        <li class="active">{{ $user->username }}</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <!-- User Information -->
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">User Information</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label>Full Name:</label>
                        <p>{{ $user->name_first }} {{ $user->name_last }}</p>
                    </div>
                    <div class="form-group">
                        <label>Username:</label>
                        <p>{{ $user->username }}</p>
                    </div>
                    <div class="form-group">
                        <label>Email:</label>
                        <p>{{ $user->email }}</p>
                    </div>
                    <div class="form-group">
                        <label>Member Since:</label>
                        <p>{{ $user->created_at->format('M d, Y') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wallet Summary -->
        <div class="col-md-8">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">Wallet Summary</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-success" onclick="addFunds()">
                            <i class="fas fa-plus"></i> Add Funds
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeFunds()">
                            <i class="fas fa-minus"></i> Remove Funds
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="description-block border-right">
                                <span class="description-percentage text-green">
                                    <i class="fas fa-dollar-sign"></i>
                                </span>
                                <h5 class="description-header">${{ number_format($wallet->balance, 2) }}</h5>
                                <span class="description-text">CURRENT BALANCE</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="description-block border-right">
                                <span class="description-percentage text-blue">
                                    <i class="fas fa-arrow-up"></i>
                                </span>
                                <h5 class="description-header">${{ number_format($wallet->total_deposited, 2) }}</h5>
                                <span class="description-text">TOTAL DEPOSITED</span>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="description-block">
                                <span class="description-percentage text-red">
                                    <i class="fas fa-arrow-down"></i>
                                </span>
                                <h5 class="description-header">${{ number_format($wallet->total_spent, 2) }}</h5>
                                <span class="description-text">TOTAL SPENT</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction History -->
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Transaction History</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-sm btn-primary" onclick="exportTransactions()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
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
                                            <span class="label label-success">
                                                <i class="fas fa-plus"></i> Deposit
                                            </span>
                                        @elseif($transaction->type === 'withdrawal')
                                            <span class="label label-warning">
                                                <i class="fas fa-minus"></i> Withdrawal
                                            </span>
                                        @elseif($transaction->type === 'purchase')
                                            <span class="label label-primary">
                                                <i class="fas fa-shopping-cart"></i> Purchase
                                            </span>
                                        @elseif($transaction->type === 'refund')
                                            <span class="label label-info">
                                                <i class="fas fa-undo"></i> Refund
                                            </span>
                                        @else
                                            <span class="label label-default">{{ ucfirst($transaction->type) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($transaction->type === 'deposit' || $transaction->type === 'refund')
                                            <span class="text-green">+${{ number_format($transaction->amount, 2) }}</span>
                                        @else
                                            <span class="text-red">-${{ number_format($transaction->amount, 2) }}</span>
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
                                            <span class="label label-success">Completed</span>
                                        @elseif($transaction->status === 'pending')
                                            <span class="label label-warning">Pending</span>
                                        @elseif($transaction->status === 'failed')
                                            <span class="label label-danger">Failed</span>
                                        @else
                                            <span class="label label-default">{{ ucfirst($transaction->status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No transactions found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($transactions->hasPages())
                    <div class="box-footer">
                        {{ $transactions->links() }}
                    </div>
                @endif
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
                        <a href="{{ route('admin.shop.wallets.index') }}" class="btn btn-default">
                            <i class="fas fa-arrow-left"></i> Back to Wallet Management
                        </a>
                        <a href="{{ route('admin.users.view', $user) }}" class="btn btn-info">
                            <i class="fas fa-user"></i> View User Profile
                        </a>
                        <a href="{{ route('admin.shop.payments.index') }}" class="btn btn-success">
                            <i class="fas fa-list"></i> All Payments
                        </a>
                        <button type="button" class="btn btn-warning" onclick="exportTransactions()">
                            <i class="fas fa-download"></i> Export Transactions
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
