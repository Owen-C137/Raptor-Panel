@extends('shop::layout')

@section('shop-title', 'Payment Failed')

@section('shop-content')
<div class="error-container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card error-card payment-failed">
                <div class="card-body text-center py-5">
                    {{-- Error Icon --}}
                    <div class="error-icon mb-4">
                        <i class="fas fa-credit-card fa-4x text-danger"></i>
                        <i class="fas fa-times-circle fa-2x text-danger error-overlay"></i>
                    </div>
                    
                    {{-- Error Message --}}
                    <h2 class="error-title text-danger mb-3">Payment Failed</h2>
                    <p class="lead text-muted mb-4">
                        We encountered an issue processing your payment. Don't worry, no charges were made to your account.
                    </p>
                    
                    {{-- Error Details --}}
                    @if(isset($error_message))
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Error Details:</strong> {{ $error_message }}
                    </div>
                    @endif
                    
                    {{-- Common Causes --}}
                    <div class="card bg-light mt-4 mb-4">
                        <div class="card-body">
                            <h6 class="card-title text-muted">
                                <i class="fas fa-info-circle"></i>
                                Common causes of payment failures:
                            </h6>
                            <ul class="list-unstyled text-left mb-0">
                                <li><i class="fas fa-check text-muted me-2"></i> Insufficient funds in your account</li>
                                <li><i class="fas fa-check text-muted me-2"></i> Expired or invalid card information</li>
                                <li><i class="fas fa-check text-muted me-2"></i> Card declined by your bank</li>
                                <li><i class="fas fa-check text-muted me-2"></i> Billing address mismatch</li>
                                <li><i class="fas fa-check text-muted me-2"></i> Temporary network or system issues</li>
                            </ul>
                        </div>
                    </div>
                    
                    {{-- Action Buttons --}}
                    <div class="error-actions">
                        <div class="d-flex flex-column align-items-center gap-3">
                            @if(isset($order_id))
                            <a href="{{ route('shop.checkout.retry', $order_id) }}" class="btn btn-success btn-lg">
                                <i class="fas fa-redo"></i>
                                Try Payment Again
                            </a>
                            @else
                            <a href="{{ route('shop.checkout.index') }}" class="btn btn-success btn-lg">
                                <i class="fas fa-redo"></i>
                                Return to Checkout
                            </a>
                            @endif
                            
                            <div class="alternative-actions">
                                <a href="{{ route('shop.cart') }}" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-shopping-cart"></i>
                                    View Cart
                                </a>
                                
                                <a href="{{ route('shop.wallet.index') }}" class="btn btn-outline-success me-2">
                                    <i class="fas fa-wallet"></i>
                                    Use Wallet Credit
                                </a>
                                
                                <a href="{{ route('shop.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-store"></i>
                                    Continue Shopping
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Support Information --}}
                    <div class="support-section mt-5">
                        <hr>
                        <h6 class="text-muted mb-3">Need Help?</h6>
                        <p class="text-muted mb-3">
                            If you continue to experience payment issues, our support team is here to assist you.
                        </p>
                        <div class="support-buttons">
                            <a href="#" class="btn btn-sm btn-outline-primary me-2">
                                <i class="fas fa-comments"></i>
                                Live Chat
                            </a>
                            <a href="#" class="btn btn-sm btn-outline-secondary me-2">
                                <i class="fas fa-ticket-alt"></i>
                                Create Ticket
                            </a>
                            <a href="#" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-question-circle"></i>
                                Payment FAQ
                            </a>
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
.error-card {
    border: 2px solid #dc3545;
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

.support-buttons {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 10px;
}

@media (max-width: 768px) {
    .error-icon i {
        font-size: 3em !important;
    }
    
    .alternative-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .alternative-actions .btn {
        width: 100%;
        margin: 0 0 10px 0 !important;
    }
    
    .support-buttons {
        flex-direction: column;
        width: 100%;
    }
    
    .support-buttons .btn {
        width: 100%;
    }
}
</style>
@endpush
