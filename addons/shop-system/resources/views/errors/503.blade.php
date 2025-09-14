@extends('shop::layout')

@section('shop-title', 'Service Unavailable')

@section('shop-content')
<div class="error-container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card error-card service-unavailable">
                <div class="card-body text-center py-5">
                    {{-- Error Icon --}}
                    <div class="error-icon mb-4">
                        <i class="fas fa-exclamation-circle fa-4x text-warning"></i>
                        <div class="error-number">503</div>
                    </div>
                    
                    {{-- Error Message --}}
                    <h2 class="error-title text-warning mb-3">Service Unavailable</h2>
                    <p class="lead text-muted mb-4">
                        The shop is temporarily unavailable. Please try again in a few moments.
                    </p>
                    
                    {{-- Status Information --}}
                    <div class="status-info mb-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <div class="status-item">
                                            <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                            <h6 class="text-muted">Status</h6>
                                            <p class="mb-0 small">Maintenance</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="status-item">
                                            <i class="fas fa-tools fa-2x text-info mb-2"></i>
                                            <h6 class="text-muted">Expected</h6>
                                            <p class="mb-0 small">Back Soon</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="status-item">
                                            <i class="fas fa-shield-alt fa-2x text-success mb-2"></i>
                                            <h6 class="text-muted">Data Safety</h6>
                                            <p class="mb-0 small">Secure</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Action Buttons --}}
                    <div class="action-buttons mb-4">
                        <a href="{{ route('index') }}" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-home me-2"></i>Go to Dashboard
                        </a>
                        <button onclick="window.location.reload()" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-sync-alt me-2"></i>Try Again
                        </button>
                    </div>
                    
                    {{-- Help Text --}}
                    <div class="help-text">
                        <p class="text-muted small mb-2">
                            If this problem persists, please contact support.
                        </p>
                        <div class="contact-info">
                            <span class="text-muted small">
                                <i class="fas fa-envelope me-1"></i>
                                Error Code: SVC-503-{{ now()->timestamp }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.error-container {
    min-height: 60vh;
    display: flex;
    align-items: center;
    padding: 2rem 0;
}

.error-card {
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border-radius: 15px;
}

.error-icon {
    position: relative;
}

.error-number {
    font-size: 1.5rem;
    font-weight: bold;
    color: #ffc107;
    margin-top: 0.5rem;
}

.error-title {
    font-weight: 600;
    font-size: 2rem;
}

.status-item {
    padding: 1rem;
}

.status-item i {
    opacity: 0.8;
}

.action-buttons .btn {
    min-width: 150px;
    border-radius: 25px;
    font-weight: 500;
}

.contact-info {
    background: #f8f9fa;
    padding: 0.75rem 1rem;
    border-radius: 5px;
    display: inline-block;
    margin-top: 0.5rem;
}

.service-unavailable {
    background: linear-gradient(135deg, #fff9c4 0%, #fff 100%);
}

@media (max-width: 768px) {
    .error-title {
        font-size: 1.5rem;
    }
    
    .action-buttons .btn {
        display: block;
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .status-item {
        padding: 0.5rem;
        margin-bottom: 1rem;
    }
}
</style>

<script>
// Auto-refresh every 30 seconds to check if service is back
setTimeout(function() {
    window.location.reload();
}, 30000);

// Show time since error occurred
function updateTime() {
    const errorTime = document.getElementById('error-time');
    if (errorTime) {
        const now = new Date();
        errorTime.textContent = now.toLocaleTimeString();
    }
}

// Update time every second
setInterval(updateTime, 1000);
</script>
@endsection