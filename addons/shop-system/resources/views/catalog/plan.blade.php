@extends('shop::layout')

@section('shop-title', $plan->name . ' - Plan Details')

@section('shop-content')
<div class="row">
    <div class="col-12">
        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('shop.index') }}">
                        <i class="fas fa-home"></i>
                        Shop
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('shop.category', $category) }}">{{ $category->name }}</a>
                </li>
                <li class="breadcrumb-item active">{{ $plan->name }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    {{-- Plan Details --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h1 class="card-title">{{ $plan->name }}</h1>
                
                @if($plan->description)
                <div class="plan-description mb-4">
                    <p class="text-muted">{!! nl2br(e($plan->description)) !!}</p>
                </div>
                @endif

                {{-- Pricing Information --}}
                <div class="row mb-4">
                    <div class="col-12">
                        <h3>Pricing Options</h3>
                        <div class="row">
                            @foreach($plan->billing_cycles as $cycle)
                            <div class="col-md-4 mb-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">{{ ucfirst(str_replace('_', ' ', $cycle['cycle'])) }}</h5>
                                        <div class="price-display">
                                            <span class="price">${{ number_format($cycle['price'], 2) }}</span>
                                            @if($cycle['cycle'] !== 'one_time')
                                                <span class="cycle">/ {{ $cycle['cycle'] }}</span>
                                            @endif
                                        </div>
                                        @if(!empty($cycle['setup_fee']) && $cycle['setup_fee'] > 0)
                                        <div class="setup-fee">
                                            <small class="text-muted">+ ${{ number_format($cycle['setup_fee'], 2) }} setup fee</small>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Server Resources --}}
                <div class="row mb-4">
                    <div class="col-12">
                        <h3>Server Resources</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="resource-list">
                                    <div class="resource-item mb-2">
                                        <i class="fas fa-microchip text-primary"></i>
                                        <strong>CPU:</strong> {{ $plan->cpu }}%
                                    </div>
                                    <div class="resource-item mb-2">
                                        <i class="fas fa-memory text-info"></i>
                                        <strong>RAM:</strong> {{ number_format($plan->memory) }} MiB
                                    </div>
                                    <div class="resource-item mb-2">
                                        <i class="fas fa-hdd text-success"></i>
                                        <strong>Storage:</strong> {{ number_format($plan->storage) }} MB
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="resource-list">
                                    <div class="resource-item mb-2">
                                        <i class="fas fa-database text-warning"></i>
                                        <strong>Databases:</strong> {{ $plan->databases }}
                                    </div>
                                    <div class="resource-item mb-2">
                                        <i class="fas fa-save text-secondary"></i>
                                        <strong>Backups:</strong> {{ $plan->backups }}
                                    </div>
                                    <div class="resource-item mb-2">
                                        <i class="fas fa-network-wired text-purple"></i>
                                        <strong>Allocations:</strong> {{ $plan->allocations }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Additional Information --}}
                @if($plan->egg)
                <div class="mb-4">
                    <h3>Game Configuration</h3>
                    <div class="alert alert-info">
                        <i class="fas fa-gamepad"></i>
                        <strong>Game Type:</strong> {{ $plan->egg->name }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">
        {{-- Order Actions --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Order This Plan</h5>
            </div>
            <div class="card-body">
                @if($plan->isAvailable())
                    @auth
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity:</label>
                        <input type="number" class="form-control" id="quantity" value="1" min="1" max="10">
                    </div>
                    
                    <div class="mb-3">
                        <label for="billing-cycle" class="form-label">Billing Cycle:</label>
                        <select class="form-control" id="billing-cycle">
                            @foreach($plan->billing_cycles as $cycle)
                            <option value="{{ $cycle['cycle'] }}" data-price="{{ $cycle['price'] }}" data-setup="{{ $cycle['setup_fee'] ?? 0 }}">
                                {{ ucfirst(str_replace('_', ' ', $cycle['cycle'])) }} - ${{ number_format($cycle['price'], 2) }}
                                @if($cycle['cycle'] !== 'one_time') / {{ $cycle['cycle'] }} @endif
                                @if(!empty($cycle['setup_fee']) && $cycle['setup_fee'] > 0)
                                    (+ ${{ number_format($cycle['setup_fee'], 2) }} setup)
                                @endif
                            </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <button type="button" class="btn btn-success btn-block add-to-cart-btn mb-2" 
                            data-plan-id="{{ $plan->id }}" data-plan-name="{{ $plan->name }}">
                        <i class="fas fa-plus"></i>
                        Add to Cart
                    </button>
                    
                    <a href="{{ route('shop.category', $category) }}" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-arrow-left"></i>
                        Back to {{ $category->name }}
                    </a>
                    @else
                    <a href="{{ route('auth.login') }}" class="btn btn-primary btn-block">
                        <i class="fas fa-sign-in-alt"></i>
                        Login to Order
                    </a>
                    @endauth
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        This plan is currently not available for purchase.
                    </div>
                @endif
            </div>
        </div>

        {{-- Related Plans --}}
        @if($relatedPlans->count() > 0)
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Other {{ $category->name }} Plans</h5>
            </div>
            <div class="card-body">
                @foreach($relatedPlans as $relatedPlan)
                <div class="related-plan mb-3 pb-3 @if(!$loop->last) border-bottom @endif">
                    <h6>
                        <a href="{{ route('shop.plan', $relatedPlan) }}" class="text-decoration-none">
                            {{ $relatedPlan->name }}
                        </a>
                    </h6>
                    <div class="text-muted small">
                        @php $cheapest = $relatedPlan->getCheapestCycle(); @endphp
                        @if($cheapest)
                            Starting at ${{ number_format($cheapest['price'], 2) }}
                            @if($cheapest['cycle'] !== 'one_time') / {{ $cheapest['cycle'] }} @endif
                        @endif
                    </div>
                    <div class="resource-summary small text-muted">
                        {{ $relatedPlan->cpu }}% CPU, {{ number_format($relatedPlan->memory) }}MB RAM
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<style>
.price-display {
    margin: 10px 0;
}
.price-display .price {
    font-size: 1.5rem;
    font-weight: bold;
    color: #007bff;
}
.price-display .cycle {
    font-size: 0.9rem;
    color: #6c757d;
}
.resource-item i {
    width: 20px;
    text-align: center;
}
.related-plan:hover {
    background-color: #f8f9fa;
    border-radius: 5px;
    padding: 10px !important;
    margin: 0 -10px 15px -10px !important;
}
</style>
@endsection
