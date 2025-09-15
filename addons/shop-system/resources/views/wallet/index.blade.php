@extends('shop::layout')

@section('shop-title', 'My Wallet')

@section('shop-content')
<div class="wallet-container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h1>
                    <i class="fas fa-wallet"></i>
                    My Wallet
                </h1>
                
                <div class="wallet-actions">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addFundsModal">
                        <i class="fas fa-plus"></i>
                        Add Funds
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Wallet Overview --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card wallet-balance-card">
                <div class="card-body text-center">
                    <div class="balance-icon mb-3">
                        <i class="fas fa-coins fa-3x text-success"></i>
                    </div>
                    <h3 class="balance-amount">
                        {{ $currencySymbol }}{{ number_format($wallet->balance, 2) }}
                    </h3>
                    <p class="text-muted mb-0">Available Balance</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="stat-icon mb-3">
                        <i class="fas fa-chart-line fa-2x text-primary"></i>
                    </div>
                    <h4>{{ $currencySymbol }}{{ number_format($monthlySpending, 2) }}</h4>
                    <p class="text-muted mb-0">This Month's Spending</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="stat-icon mb-3">
                        <i class="fas fa-history fa-2x text-info"></i>
                    </div>
                    <h4>{{ $transactions->count() }}</h4>
                    <p class="text-muted mb-0">Total Transactions</p>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Quick Actions --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt"></i>
                        Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-success btn-lg w-100 quick-action-btn" 
                                    data-bs-toggle="modal" data-bs-target="#addFundsModal">
                                <i class="fas fa-plus-circle fa-2x mb-2"></i>
                                <div>Add Funds</div>
                                <small class="text-muted">Deposit money</small>
                            </button>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-primary btn-lg w-100 quick-action-btn" 
                                    data-bs-toggle="modal" data-bs-target="#autoTopUpModal">
                                <i class="fas fa-sync-alt fa-2x mb-2"></i>
                                <div>Auto Top-Up</div>
                                <small class="text-muted">Automatic deposits</small>
                            </button>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <button class="btn btn-outline-info btn-lg w-100 quick-action-btn" 
                                    onclick="Shop.exportTransactions()">
                                <i class="fas fa-download fa-2x mb-2"></i>
                                <div>Export</div>
                                <small class="text-muted">Download history</small>
                            </button>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('shop.index') }}" class="btn btn-outline-warning btn-lg w-100 quick-action-btn">
                                <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                                <div>Shop Now</div>
                                <small class="text-muted">Browse products</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Transaction History --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i>
                            Transaction History
                        </h5>
                        
                        <div class="transaction-filters">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary active" data-filter="all">
                                    All
                                </button>
                                <button type="button" class="btn btn-outline-success" data-filter="credit">
                                    Credits
                                </button>
                                <button type="button" class="btn btn-outline-danger" data-filter="debit">
                                    Debits
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($transactions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="transactions-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Balance</th>
                                    <th>Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $transaction)
                                <tr class="transaction-row" data-type="{{ $transaction->type }}">
                                    <td>
                                        <div class="transaction-date">
                                            <div>{{ $transaction->created_at->format('M d, Y') }}</div>
                                            <small class="text-muted">{{ $transaction->created_at->format('g:i A') }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="transaction-description">
                                            <div class="fw-bold">{{ $transaction->description }}</div>
                                            @if($transaction->metadata && isset($transaction->metadata['order_number']))
                                                <small class="text-muted">
                                                    Order #{{ $transaction->metadata['order_number'] }}
                                                </small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $transaction->type === 'credit' ? 'success' : 'danger' }}">
                                            {{ ucfirst($transaction->type) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="amount {{ $transaction->type === 'credit' ? 'text-success' : 'text-danger' }}">
                                            {{ $transaction->type === 'credit' ? '+' : '-' }}{{ $currencySymbol }}{{ number_format(abs($transaction->amount), 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="balance-after">
                                            {{ $currencySymbol }}{{ number_format($transaction->balance_after, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($transaction->reference_id)
                                            <code class="small">{{ Str::limit($transaction->reference_id, 20) }}</code>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    @if($transactions->hasPages())
                    <div class="card-footer">
                        <div class="d-flex justify-content-center">
                            {{ $transactions->links() }}
                        </div>
                    </div>
                    @endif
                    @else
                    <div class="empty-transactions text-center py-5">
                        <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                        <h5>No Transactions Yet</h5>
                        <p class="text-muted">Your transaction history will appear here once you start using your wallet.</p>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addFundsModal">
                            <i class="fas fa-plus"></i>
                            Add Your First Deposit
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add Funds Modal --}}
<div class="modal fade" id="addFundsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle text-success"></i>
                    Add Funds to Wallet
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="add-funds-form">
                    <div class="row">
                        <div class="col-md-8">
                            {{-- Amount Selection --}}
                            <div class="mb-4">
                                <label class="form-label">Select Amount</label>
                                <div class="amount-buttons mb-3">
                                    <div class="row g-2">
                                        <div class="col-4">
                                            <button type="button" class="btn btn-outline-primary w-100 amount-btn" data-amount="10">
                                                {{ $currencySymbol }}10
                                            </button>
                                        </div>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-outline-primary w-100 amount-btn" data-amount="25">
                                                {{ $currencySymbol }}25
                                            </button>
                                        </div>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-outline-primary w-100 amount-btn" data-amount="50">
                                                {{ $currencySymbol }}50
                                            </button>
                                        </div>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-outline-primary w-100 amount-btn" data-amount="100">
                                                {{ $currencySymbol }}100
                                            </button>
                                        </div>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-outline-primary w-100 amount-btn" data-amount="250">
                                                {{ $currencySymbol }}250
                                            </button>
                                        </div>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-outline-primary w-100 amount-btn" data-amount="500">
                                                {{ $currencySymbol }}500
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="input-group">
                                    <span class="input-group-text">{{ $currencySymbol }}</span>
                                    <input type="number" class="form-control" id="customAmount" 
                                           placeholder="Enter custom amount" min="5" max="1000" step="0.01">
                                </div>
                                <div class="form-text">
                                    Minimum: {{ $currencySymbol }}5.00 | Maximum: {{ $currencySymbol }}1,000.00
                                </div>
                            </div>
                            
                            {{-- Payment Method --}}
                            <div class="mb-4">
                                <label class="form-label">Payment Method</label>
                                <div class="payment-methods">
                                    <div class="form-check payment-option">
                                        <input class="form-check-input" type="radio" name="payment_method" 
                                               id="stripe-deposit" value="stripe" checked>
                                        <label class="form-check-label" for="stripe-deposit">
                                            <div class="d-flex align-items-center">
                                                <i class="fab fa-cc-stripe fa-2x text-primary me-3"></i>
                                                <div>
                                                    <div class="fw-bold">Credit/Debit Card</div>
                                                    <small class="text-muted">Visa, Mastercard, American Express</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <div class="form-check payment-option">
                                        <input class="form-check-input" type="radio" name="payment_method" 
                                               id="paypal-deposit" value="paypal">
                                        <label class="form-check-label" for="paypal-deposit">
                                            <div class="d-flex align-items-center">
                                                <i class="fab fa-paypal fa-2x text-primary me-3"></i>
                                                <div>
                                                    <div class="fw-bold">PayPal</div>
                                                    <small class="text-muted">Pay with your PayPal account</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            {{-- Deposit Summary --}}
                            <div class="deposit-summary">
                                <div class="card bg-light">
                                    <div class="card-header">
                                        <h6 class="mb-0">Deposit Summary</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="summary-line">
                                            <span>Current Balance:</span>
                                            <span>{{ $currencySymbol }}{{ number_format($wallet->balance, 2) }}</span>
                                        </div>
                                        <div class="summary-line">
                                            <span>Deposit Amount:</span>
                                            <span id="depositAmount">{{ $currencySymbol }}0.00</span>
                                        </div>
                                        <div class="summary-line" id="processing-fee-line" style="display: none;">
                                            <span>Processing Fee:</span>
                                            <span id="processing-fee-display">{{ $currencySymbol }}0.00</span>
                                        </div>
                                        <hr>
                                        <div class="summary-line total">
                                            <strong>
                                                <span>New Balance:</span>
                                                <span id="new-balance-display">{{ $currencySymbol }}{{ number_format($wallet->balance, 2) }}</span>
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="deposit-benefits mt-3">
                                    <h6 class="text-muted">Benefits</h6>
                                    <ul class="list-unstyled small">
                                        <li><i class="fas fa-check text-success me-1"></i> Instant availability</li>
                                        <li><i class="fas fa-check text-success me-1"></i> Secure transactions</li>
                                        <li><i class="fas fa-check text-success me-1"></i> No expiration</li>
                                        <li><i class="fas fa-check text-success me-1"></i> Auto-renewal support</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-success" id="addFundsBtn">
                    <i class="fas fa-plus"></i>
                    Add Funds
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Auto Top-Up Modal --}}
<div class="modal fade" id="autoTopUpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-sync-alt text-primary"></i>
                    Auto Top-Up Settings
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="auto-topup-form">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="enable-auto-topup" 
                                   {{ $wallet->auto_topup_enabled ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable-auto-topup">
                                Enable Auto Top-Up
                            </label>
                        </div>
                        <div class="form-text">
                            Automatically add funds when your balance drops below the minimum threshold.
                        </div>
                    </div>
                    
                    <div id="auto-topup-settings" style="{{ $wallet->auto_topup_enabled ? '' : 'display: none;' }}">
                        <div class="mb-3">
                            <label for="topup-threshold" class="form-label">Minimum Balance Threshold</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ $currencySymbol }}</span>
                                <input type="number" class="form-control" id="topup-threshold" 
                                       value="{{ $wallet->auto_topup_threshold ?? 10 }}" 
                                       min="5" max="100" step="0.01">
                            </div>
                            <div class="form-text">
                                Top-up will trigger when balance falls below this amount.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="topup-amount" class="form-label">Top-Up Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ $currencySymbol }}</span>
                                <input type="number" class="form-control" id="topup-amount" 
                                       value="{{ $wallet->auto_topup_amount ?? 25 }}" 
                                       min="10" max="500" step="0.01">
                            </div>
                            <div class="form-text">
                                Amount to add to your wallet when threshold is reached.
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> Auto top-up requires a saved payment method. 
                            You'll be prompted to add one when saving these settings.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-primary" id="save-auto-topup-btn">
                    <i class="fas fa-save"></i>
                    Save Settings
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set currency symbol globally for JavaScript use
    window.currencySymbol = '{{ $currencySymbol }}';
    
    let selectedAmount = 0;
    
    // Amount button selection
    document.querySelectorAll('.amount-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Amount button clicked:', this.dataset.amount);
            const amount = parseFloat(this.dataset.amount);
            selectAmount(amount);
            
            // Update UI
            document.querySelectorAll('.amount-btn').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('customAmount').value = '';
        });
    });
    
    // Custom amount input
    document.getElementById('customAmount').addEventListener('input', function() {
        console.log('Custom amount input:', this.value);
        const amount = parseFloat(this.value) || 0;
        selectAmount(amount);
        
        // Remove active class from preset buttons
        document.querySelectorAll('.amount-btn').forEach(btn => btn.classList.remove('active'));
    });
    
    // Amount selection logic
    function selectAmount(amount) {
        console.log('Selecting amount:', amount);
        selectedAmount = amount;
        updateDepositSummary();
    }
    
    // Update deposit summary
    function updateDepositSummary() {
        console.log('Updating deposit summary with amount:', selectedAmount);
        const currentBalance = {{ $wallet->balance }};
        const processingFee = 0; // Calculate based on payment method if needed
        const newBalance = currentBalance + selectedAmount;
        
        const depositAmountElement = document.getElementById('depositAmount');
        const newBalanceElement = document.getElementById('new-balance-display');
        
        if (depositAmountElement) {
            depositAmountElement.textContent = window.currencySymbol + selectedAmount.toFixed(2);
        }
        
        if (newBalanceElement) {
            newBalanceElement.textContent = window.currencySymbol + newBalance.toFixed(2);
        }
        
        // Enable/disable deposit button
        const depositBtn = document.getElementById('addFundsBtn');
        if (depositBtn) {
            if (selectedAmount >= 5) {
                depositBtn.disabled = false;
                depositBtn.innerHTML = '<i class="fas fa-credit-card"></i> Add $' + selectedAmount.toFixed(2);
            } else {
                depositBtn.disabled = true;
                depositBtn.innerHTML = '<i class="fas fa-credit-card"></i> Add Funds';
            }
        }
    }
    
    // Process deposit
    document.getElementById('addFundsBtn').addEventListener('click', function() {
        console.log('Process deposit button clicked, selected amount:', selectedAmount);
        
        if (selectedAmount < 5) {
            Shop.showNotification(`Minimum deposit amount is ${window.currencySymbol}5.00`, 'error');
            return;
        }
        
        const paymentMethodElement = document.querySelector('input[name="payment_method"]:checked');
        if (!paymentMethodElement) {
            Shop.showNotification('Please select a payment method', 'error');
            return;
        }
        
        const paymentMethod = paymentMethodElement.value;
        console.log('Payment method:', paymentMethod);
        
        const formData = new FormData();
        formData.append('amount', selectedAmount);
        formData.append('payment_method', paymentMethod);
        
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        fetch('{{ route("shop.wallet.add-funds.process") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('Response:', data);
            if (data.success) {
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    Shop.showNotification('Funds added successfully!', 'success');
                    location.reload();
                }
            } else {
                Shop.showNotification(data.message || 'Payment processing failed', 'error');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-credit-card"></i> Add Funds';
            }
        })
        .catch(error => {
            console.error('Deposit error:', error);
            Shop.showNotification('Failed to process deposit.', 'error');
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-credit-card"></i> Add Funds';
        });
    });
    
    // Transaction filtering
    document.querySelectorAll('[data-filter]').forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.dataset.filter;
            
            // Update active button
            document.querySelectorAll('[data-filter]').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            // Filter transactions
            document.querySelectorAll('.transaction-row').forEach(row => {
                const type = row.dataset.type;
                const show = filter === 'all' || type === filter;
                row.style.display = show ? '' : 'none';
            });
        });
    });
    
    // Auto top-up toggle
    document.getElementById('enable-auto-topup').addEventListener('change', function() {
        const settings = document.getElementById('auto-topup-settings');
        settings.style.display = this.checked ? 'block' : 'none';
    });
    
    // Save auto top-up settings
    document.getElementById('save-auto-topup-btn').addEventListener('click', function() {
        const enabled = document.getElementById('enable-auto-topup').checked;
        const threshold = parseFloat(document.getElementById('topup-threshold').value);
        const amount = parseFloat(document.getElementById('topup-amount').value);
        
        const formData = new FormData();
        formData.append('enabled', enabled);
        formData.append('threshold', threshold);
        formData.append('amount', amount);
        
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        fetch('{{ route("shop.wallet.auto-topup") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Shop.showNotification('success', 'Auto top-up settings saved successfully!');
                bootstrap.Modal.getInstance(document.getElementById('autoTopUpModal')).hide();
            } else {
                Shop.showNotification('error', data.message);
            }
        })
        .catch(error => {
            console.error('Auto top-up error:', error);
            Shop.showNotification('error', 'Failed to save settings.');
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-save"></i> Save Settings';
        });
    });
    
    // Initialize summary
    updateDepositSummary();
});

// Initialize Shop object if not exists
if (typeof Shop === 'undefined') {
    window.Shop = {
        showNotification: function(message, type = 'info') {
            // Create a simple notification
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '9999';
            notification.style.maxWidth = '300px';
            
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
    };
}

// Export transactions function
Shop.exportTransactions = function() {
    window.open('{{ route("shop.wallet.export") }}', '_blank');
};
</script>
@endpush

@push('styles')
<style>
.wallet-balance-card {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border: none;
}

.wallet-balance-card .balance-icon {
    opacity: 0.2;
}

.balance-amount {
    color: white;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.quick-action-btn {
    text-align: center;
    padding: 20px 15px;
    height: 120px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    transition: all 0.3s ease;
}

.quick-action-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}

.quick-action-btn i {
    display: block;
    margin-bottom: 8px;
}

.transaction-row {
    transition: all 0.2s ease;
}

.transaction-row:hover {
    background-color: #f8f9fa;
}

.amount.text-success {
    font-weight: 600;
}

.amount.text-danger {
    font-weight: 600;
}

.transaction-date {
    white-space: nowrap;
}

.payment-option {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    transition: all 0.3s;
}

.payment-option:hover {
    border-color: #007bff;
}

.payment-option .form-check-input:checked ~ .form-check-label {
    color: #007bff;
}

.amount-btn {
    padding: 12px;
    margin-bottom: 8px;
    transition: all 0.3s;
}

.amount-btn.active {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

.deposit-summary .summary-line {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.deposit-summary .summary-line.total {
    font-size: 1.1em;
    margin-top: 10px;
}

.empty-transactions {
    padding: 60px 30px;
}

.stat-icon {
    opacity: 0.7;
}

@media (max-width: 768px) {
    .wallet-actions {
        width: 100%;
        margin-top: 15px;
    }
    
    .wallet-actions .btn {
        width: 100%;
    }
    
    .quick-action-btn {
        height: 100px;
        padding: 15px 10px;
        font-size: 0.9em;
    }
    
    .quick-action-btn i {
        font-size: 1.5em;
    }
    
    .transaction-filters {
        width: 100%;
        margin-top: 10px;
    }
    
    .btn-group {
        width: 100%;
        display: flex;
    }
    
    .btn-group .btn {
        flex: 1;
    }
}
</style>
@endpush
