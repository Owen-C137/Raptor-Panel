@extends('shop::layout')

@section('shop-title', 'Add Funds')

@section('shop-content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">
                    <i class="fas fa-plus-circle"></i>
                    Add Funds to Wallet
                </h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('shop.wallet.add-funds.process') }}">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" 
                                   class="form-control" 
                                   id="amount" 
                                   name="amount" 
                                   min="5" 
                                   step="0.01" 
                                   required>
                        </div>
                        <div class="form-text">Minimum amount: $5.00</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="">Select payment method...</option>
                            @if($paymentMethods ?? false)
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method['id'] }}">{{ $method['name'] }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-credit-card"></i>
                            Add Funds
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
