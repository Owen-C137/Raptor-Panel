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
        {{-- Plan Header --}}
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">{{ $plan->name }}</h3>
            </div>
            <div class="block-content">
                @if($plan->description)
                <div class="plan-description mb-4">
                    <p class="text-muted">{!! nl2br(e($plan->description)) !!}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Pricing Information --}}
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="far fa-fw fa-credit-card me-1 text-primary"></i>
                    Pricing Options
                </h3>
            </div>
            <div class="block-content">
                <div class="row">
                    @foreach($plan->billing_cycles as $cycle)
                    <div class="col-md-4 mb-3">
                        <div class="block block-rounded block-link-pop">
                            <div class="block-content text-center">
                                <div class="py-2">
                                    <h5 class="text-primary mb-2">{{ ucfirst(str_replace('_', ' ', $cycle['cycle'])) }}</h5>
                                    <div class="price-display">
                                        <span class="h3 fw-bold text-success">${{ number_format($cycle['price'], 2) }}</span>
                                        @if($cycle['cycle'] !== 'one_time')
                                            <span class="text-muted">/ {{ $cycle['cycle'] }}</span>
                                        @endif
                                    </div>
                                    @if(!empty($cycle['setup_fee']) && $cycle['setup_fee'] > 0)
                                    <div class="setup-fee mt-1">
                                        <small class="text-warning">+ ${{ number_format($cycle['setup_fee'], 2) }} setup fee</small>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Server Resources --}}
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="far fa-fw fa-server me-1 text-info"></i>
                    Server Resources
                </h3>
            </div>
            <div class="block-content">
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <i class="fas fa-microchip fa-2x text-primary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-0">CPU Limit</h5>
                                    <p class="text-muted mb-0">{{ $plan->cpu }}% allocated</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <i class="fas fa-memory fa-2x text-info"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-0">Memory (RAM)</h5>
                                    <p class="text-muted mb-0">{{ number_format($plan->memory) }} MiB</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <i class="fas fa-hdd fa-2x text-success"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-0">Storage Space</h5>
                                    <p class="text-muted mb-0">{{ number_format($plan->storage) }} MB</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <i class="fas fa-database fa-2x text-warning"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-0">Databases</h5>
                                    <p class="text-muted mb-0">{{ $plan->databases }} allowed</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <i class="fas fa-save fa-2x text-secondary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-0">Backups</h5>
                                    <p class="text-muted mb-0">{{ $plan->backups }} included</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <i class="fas fa-network-wired fa-2x text-primary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-0">Network Ports</h5>
                                    <p class="text-muted mb-0">{{ $plan->allocations }} allocations</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Game Configuration --}}
        @if($plan->egg)
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="far fa-fw fa-gamepad me-1 text-danger"></i>
                    Game Configuration
                </h3>
            </div>
            <div class="block-content">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <i class="fas fa-gamepad fa-3x text-danger"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h4 class="mb-1">{{ $plan->egg->name }}</h4>
                        <p class="text-muted mb-0">Pre-configured game server environment</p>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">
        {{-- Order Actions --}}
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="far fa-fw fa-shopping-cart me-1 text-success"></i>
                    Order This Plan
                </h3>
            </div>
            <div class="block-content">
                @if($plan->isAvailable())
                    @auth
                    <div class="mb-4">
                        <label for="quantity" class="form-label fw-medium">Quantity:</label>
                        <input type="number" class="form-control" id="quantity" value="1" min="1" max="10">
                    </div>
                    
                    <div class="mb-4">
                        <label for="billing-cycle" class="form-label fw-medium">Billing Cycle:</label>
                        <select class="form-select" id="billing-cycle">
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
                    
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success btn-lg add-to-cart-btn" 
                                data-plan-id="{{ $plan->id }}" data-plan-name="{{ $plan->name }}">
                            <i class="fas fa-plus me-1"></i>
                            Add to Cart
                        </button>
                        
                        <a href="{{ route('shop.category', $category) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>
                            Back to {{ $category->name }}
                        </a>
                    </div>
                    @else
                    <div class="d-grid">
                        <a href="{{ route('auth.login') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-1"></i>
                            Login to Order
                        </a>
                    </div>
                    @endauth
                @else
                    <div class="alert alert-warning">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-2">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="flex-grow-1">
                                This plan is currently not available for purchase.
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Related Plans --}}
        @if($relatedPlans->count() > 0)
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="far fa-fw fa-list me-1 text-info"></i>
                    Other {{ $category->name }} Plans
                </h3>
            </div>
            <div class="block-content">
                @foreach($relatedPlans as $relatedPlan)
                <div class="d-flex align-items-center py-2 @if(!$loop->last) border-bottom @endif">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">
                            <a href="{{ route('shop.plan', $relatedPlan) }}" class="link-fx">
                                {{ $relatedPlan->name }}
                            </a>
                        </h6>
                        <div class="text-muted small mb-1">
                            @php $cheapest = $relatedPlan->getCheapestCycle(); @endphp
                            @if($cheapest)
                                Starting at <span class="fw-semibold text-success">${{ number_format($cheapest['price'], 2) }}</span>
                                @if($cheapest['cycle'] !== 'one_time') / {{ $cheapest['cycle'] }} @endif
                            @endif
                        </div>
                        <div class="text-muted small">
                            <i class="fas fa-microchip me-1"></i>{{ $relatedPlan->cpu }}% CPU
                            <i class="fas fa-memory ms-2 me-1"></i>{{ number_format($relatedPlan->memory) }}MB RAM
                        </div>
                    </div>
                    <div class="flex-shrink-0 ms-3">
                        <a href="{{ route('shop.plan', $relatedPlan) }}" class="btn btn-sm btn-outline-primary">
                            View
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

@endsection
