@extends('shop::layout')

@section('shop-title', 'Insufficient Funds')

@section('shop-content')
<div class="error-container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card error-card insufficient-funds">
                <div class="card-body text-center py-5">
                    {{-- Error Icon --}}
                    <div class="error-icon mb-4">
                        <i class="fas fa-wallet fa-4x text-warning"></i>
                        <i class="fas fa-exclamation-triangle fa-2x text-danger error-overlay"></i>
                    </div>
                    
                    {{-- Error Message --}}
                    <h2 class="error-title text-warning mb-3">Insufficient Wallet Balance</h2>
                    <p class="lead text-muted mb-4">
                        You don't have enough credits in your wallet to complete this purchase.
                    </p>
                    
                    {{-- Balance Information --}}
                    <div class="balance-info mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted">Current Balance</h6>
                                        <h4 class="text-primary">
                                            <i class="fas fa-coins"></i>
                                            ${{ number_format(auth()->user()->wallet_balance ?? 0, 2) }}
                                        </h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted">Required Amount</h6>
                                        <h4 class="text-danger">
                                            <i class="fas fa-tag"></i>
                                            ${{ number_format($required_amount ?? 0, 2) }}
                                        </h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        @if(isset($required_amount) && auth()->check())
                        <div class="shortage-alert mt-3">
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle"></i>
                                <strong>You need an additional 
                                    ${{ number_format($required_amount - (auth()->user()->wallet_balance ?? 0), 2) }}
                                </strong> to complete this purchase.
                            </div>
                        </div>
                        @endif
                    </div>
                    
                    {{-- Quick Top-up Options --}}
                    <div class="topup-options mb-5">
                        <h6 class="text-muted mb-3">
                            <i class="fas fa-plus-circle"></i>
                            Quick Top-up Options
                        </h6>
                        <div class="row">
                            @foreach([10, 25, 50, 100] as $amount)
                            <div class="col-6 col-lg-3 mb-3">
                                <a href="{{ route('shop.wallet.topup', ['amount' => $amount]) }}" 
                                   class="btn btn-outline-success w-100 topup-btn"
                                   data-amount="{{ $amount }}">
                                    <div class="topup-amount">+${{ $amount }}</div>
                                    <small class="text-muted">Add Credits</small>
                                </a>
                            </div>
                            @endforeach
                        </div>
                        
                        {{-- Custom Amount --}}
                        <div class="custom-topup mt-3">
                            <form action="{{ route('shop.wallet.topup') }}" method="POST" class="d-inline-block">
                                @csrf
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" 
                                           name="amount" 
                                           class="form-control" 
                                           placeholder="Enter custom amount"
                                           min="5"
                                           max="1000"
                                           step="0.01"
                                           required>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-plus"></i>
                                        Add Credits
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    {{-- Alternative Payment Methods --}}
                    <div class="payment-alternatives mb-4">
                        <h6 class="text-muted mb-3">
                            <i class="fas fa-credit-card"></i>
                            Or pay with alternative methods
                        </h6>
                        <div class="payment-methods">
                            <a href="{{ route('shop.checkout.method', 'stripe') }}" 
                               class="btn btn-outline-primary me-2 mb-2">
                                <i class="fab fa-cc-stripe"></i>
                                Credit Card
                            </a>
                            <a href="{{ route('shop.checkout.method', 'paypal') }}" 
                               class="btn btn-outline-info me-2 mb-2">
                                <i class="fab fa-paypal"></i>
                                PayPal
                            </a>
                            <a href="{{ route('shop.checkout.method', 'crypto') }}" 
                               class="btn btn-outline-warning mb-2">
                                <i class="fab fa-bitcoin"></i>
                                Cryptocurrency
                            </a>
                        </div>
                    </div>
                    
                    {{-- Action Buttons --}}
                    <div class="error-actions">
                        <div class="d-flex flex-column align-items-center gap-3">
                            <a href="{{ route('shop.wallet.index') }}" class="btn btn-success btn-lg">
                                <i class="fas fa-wallet"></i>
                                Manage Wallet
                            </a>
                            
                            <div class="alternative-actions">
                                <a href="{{ route('shop.cart') }}" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-shopping-cart"></i>
                                    View Cart
                                </a>
                                
                                <a href="{{ route('shop.index') }}" class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-store"></i>
                                    Continue Shopping
                                </a>
                                
                                <a href="{{ route('shop.dashboard') }}" class="btn btn-outline-info">
                                    <i class="fas fa-tachometer-alt"></i>
                                    Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Earning Credits Info --}}
                    <div class="earning-info mt-5">
                        <hr>
                        <h6 class="text-muted mb-3">
                            <i class="fas fa-star"></i>
                            Ways to Earn Credits
                        </h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="earning-method">
                                    <i class="fas fa-gift fa-2x text-success mb-2"></i>
                                    <h6>Referral Program</h6>
                                    <p class="text-muted small">Earn $5 for each friend you refer</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="earning-method">
                                    <i class="fas fa-star-half-alt fa-2x text-warning mb-2"></i>
                                    <h6>Loyalty Rewards</h6>
                                    <p class="text-muted small">Get cashback on purchases</p>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="earning-method">
                                    <i class="fas fa-tasks fa-2x text-info mb-2"></i>
                                    <h6>Complete Tasks</h6>
                                    <p class="text-muted small">Earn credits for reviews & surveys</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.error-card.insufficient-funds {
    border: 2px solid #ffc107;
    border-radius: 12px;
}

.error-icon {
    position: relative;
    display: inline-block;
}

.error-overlay {
    position: absolute;
    top: -10px;
    right: -10px;
    background: white;
    border-radius: 50%;
    padding: 2px;
}

.error-title {
    font-weight: 700;
}

.balance-info .card {
    border: 1px solid #e9ecef;
    transition: transform 0.2s;
}

.balance-info .card:hover {
    transform: translateY(-2px);
}

.topup-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 15px 10px;
    transition: all 0.2s;
    border-width: 2px;
}

.topup-btn:hover {
    transform: translateY(-2px);
    border-color: #28a745;
    background-color: #28a745;
    color: white;
}

.topup-amount {
    font-size: 1.25rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.payment-methods {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 10px;
}

.earning-method {
    text-align: center;
    padding: 15px;
    border-radius: 8px;
    background-color: #f8f9fa;
    transition: transform 0.2s;
}

.earning-method:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.error-actions .btn-lg {
    padding: 12px 30px;
    font-weight: 600;
}

.alternative-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
}

@media (max-width: 768px) {
    .error-icon i {
        font-size: 3em !important;
    }
    
    .balance-info .col-md-6 {
        margin-bottom: 15px;
    }
    
    .topup-btn {
        padding: 20px 15px;
    }
    
    .payment-methods {
        flex-direction: column;
        width: 100%;
    }
    
    .payment-methods .btn {
        width: 100%;
    }
    
    .alternative-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .alternative-actions .btn {
        width: 100%;
        margin: 0 0 10px 0 !important;
    }
    
    .custom-topup .input-group {
        flex-direction: column;
    }
    
    .custom-topup .input-group .form-control,
    .custom-topup .input-group .btn {
        border-radius: 0.375rem !important;
        margin-bottom: 10px;
    }
}

@media (min-width: 992px) {
    .topup-btn {
        height: 100px;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-calculate suggested amount based on shortage
    const requiredAmount = {{ $required_amount ?? 0 }};
    const currentBalance = {{ auth()->user()->wallet_balance ?? 0 }};
    const shortage = requiredAmount - currentBalance;
    
    if (shortage > 0) {
        // Suggest next higher standard amount
        const amounts = [10, 25, 50, 100];
        const suggestedAmount = amounts.find(amount => amount >= shortage) || 
                               Math.ceil(shortage / 10) * 10;
        
        // Highlight suggested amount
        const topupBtns = document.querySelectorAll('.topup-btn');
        topupBtns.forEach(btn => {
            const amount = parseInt(btn.dataset.amount);
            if (amount === suggestedAmount) {
                btn.classList.add('btn-success');
                btn.classList.remove('btn-outline-success');
                
                // Add "Recommended" badge
                const badge = document.createElement('small');
                badge.className = 'badge bg-light text-dark mt-1';
                badge.textContent = 'Recommended';
                btn.appendChild(badge);
            }
        });
        
        // Set custom amount input to shortage amount
        const customInput = document.querySelector('input[name="amount"]');
        if (customInput && suggestedAmount > 100) {
            customInput.value = Math.ceil(shortage * 100) / 100;
            customInput.placeholder = `Need $${Math.ceil(shortage * 100) / 100}`;
        }
    }
    
    // Add loading state to top-up buttons
    const topupButtons = document.querySelectorAll('.topup-btn, .custom-topup button');
    topupButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (!this.classList.contains('processing')) {
                this.classList.add('processing');
                const originalContent = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                this.disabled = true;
                
                // Re-enable after 5 seconds if still on page (in case of error)
                setTimeout(() => {
                    if (this.classList.contains('processing')) {
                        this.innerHTML = originalContent;
                        this.disabled = false;
                        this.classList.remove('processing');
                    }
                }, 5000);
            }
        });
    });
    
    // Format custom amount input
    const customAmountInput = document.querySelector('input[name="amount"]');
    if (customAmountInput) {
        customAmountInput.addEventListener('input', function() {
            const value = parseFloat(this.value);
            if (value < 5) {
                this.setCustomValidity('Minimum top-up amount is $5.00');
            } else if (value > 1000) {
                this.setCustomValidity('Maximum top-up amount is $1,000.00');
            } else {
                this.setCustomValidity('');
            }
        });
    }
});
</script>
@endpush
