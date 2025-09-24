@extends('shop::layout')

@section('shop-title', 'Server Error')

@section('shop-content')
<div class="error-container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card error-card server-error">
                <div class="card-body text-center py-5">
                    {{-- Error Icon --}}
                    <div class="error-icon mb-4">
                        <i class="fas fa-exclamation-triangle fa-4x text-danger"></i>
                        <div class="error-number">500</div>
                    </div>
                    
                    {{-- Error Message --}}
                    <h2 class="error-title text-danger mb-3">Internal Server Error</h2>
                    <p class="lead text-muted mb-4">
                        We're experiencing technical difficulties. Our team has been notified and is working to resolve the issue.
                    </p>
                    
                    {{-- Status Information --}}
                    <div class="status-info mb-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <div class="status-item">
                                            <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                            <h6 class="text-muted">Detected</h6>
                                            <p class="mb-0 small" id="error-time">{{ now()->format('H:i:s') }}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="status-item">
                                            <i class="fas fa-tools fa-2x text-info mb-2"></i>
                                            <h6 class="text-muted">Status</h6>
                                            <p class="mb-0 small">Investigating</p>
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
                    
                    {{-- What to do next --}}
                    <div class="next-steps mb-4">
                        <div class="card bg-info bg-opacity-10 border-info">
                            <div class="card-body">
                                <h6 class="card-title text-info">
                                    <i class="fas fa-lightbulb"></i>
                                    What you can do:
                                </h6>
                                <ul class="list-unstyled text-left mb-0">
                                    <li class="mb-2"><i class="fas fa-redo text-info me-2"></i> Try refreshing the page in a few moments</li>
                                    <li class="mb-2"><i class="fas fa-arrow-left text-info me-2"></i> Go back to the previous page</li>
                                    <li class="mb-2"><i class="fas fa-home text-info me-2"></i> Return to the shop homepage</li>
                                    <li class="mb-0"><i class="fas fa-headset text-info me-2"></i> Contact support if the issue persists</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Action Buttons --}}
                    <div class="error-actions">
                        <div class="d-flex flex-column align-items-center gap-3">
                            <button onclick="location.reload()" class="btn btn-success btn-lg">
                                <i class="fas fa-redo"></i>
                                <span class="reload-text">Try Again</span>
                            </button>
                            
                            <div class="alternative-actions">
                                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-arrow-left"></i>
                                    Go Back
                                </a>
                                
                                <a href="{{ route('shop.index') }}" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-home"></i>
                                    Shop Home
                                </a>
                                
                                @auth
                                <a href="{{ route('shop.dashboard') }}" class="btn btn-outline-info">
                                    <i class="fas fa-tachometer-alt"></i>
                                    Dashboard
                                </a>
                                @endauth
                            </div>
                        </div>
                    </div>
                    
                    {{-- Error ID (for support) --}}
                    <div class="error-reference mt-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <p class="text-muted mb-2">
                                    <strong>Error Reference ID:</strong> 
                                    <code class="error-id">ERR-{{ strtoupper(substr(md5(now()), 0, 8)) }}</code>
                                </p>
                                <p class="text-muted mb-0 small">
                                    Please provide this ID when contacting support for faster assistance.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Support Information --}}
                    <div class="support-section mt-4">
                        <hr>
                        <h6 class="text-muted mb-3">Need Immediate Help?</h6>
                        <div class="support-buttons">
                            <a href="#" class="btn btn-sm btn-outline-danger me-2">
                                <i class="fas fa-exclamation-circle"></i>
                                Report Issue
                            </a>
                            <a href="#" class="btn btn-sm btn-outline-primary me-2">
                                <i class="fas fa-comments"></i>
                                Live Chat
                            </a>
                            <a href="#" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-chart-line"></i>
                                System Status
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
.error-card.server-error {
    border: 2px solid #dc3545;
    border-radius: 12px;
}

.error-icon {
    position: relative;
    display: inline-block;
}

.error-number {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 2rem;
    font-weight: 900;
    color: #6c757d;
    opacity: 0.3;
}

.error-title {
    font-weight: 700;
}

.status-item {
    padding: 10px;
}

.status-item i {
    opacity: 0.8;
}

.error-actions .btn-lg {
    padding: 12px 30px;
    font-weight: 600;
    position: relative;
}

.reload-spinner {
    display: none;
    width: 20px;
    height: 20px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 8px;
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

.error-id {
    font-family: 'Courier New', monospace;
    font-weight: bold;
    color: #e83e8c;
    cursor: pointer;
}

.error-id:hover {
    background-color: #fff3cd;
    padding: 2px 4px;
    border-radius: 3px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@media (max-width: 768px) {
    .error-icon i {
        font-size: 3em !important;
    }
    
    .error-number {
        font-size: 1.5rem;
    }
    
    .status-info .row {
        text-align: center;
    }
    
    .status-info .col-md-4 {
        margin-bottom: 20px;
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

@push('scripts')
<script>
// Copy error ID to clipboard when clicked
document.addEventListener('DOMContentLoaded', function() {
    const errorId = document.querySelector('.error-id');
    if (errorId) {
        errorId.addEventListener('click', function() {
            const text = this.textContent;
            navigator.clipboard.writeText(text).then(function() {
                // Show tooltip or notification
                const originalText = errorId.textContent;
                errorId.textContent = 'Copied!';
                errorId.style.backgroundColor = '#d4edda';
                
                setTimeout(() => {
                    errorId.textContent = originalText;
                    errorId.style.backgroundColor = '';
                }, 1500);
            });
        });
    }
    
    // Reload button animation
    const reloadBtn = document.querySelector('button[onclick="location.reload()"]');
    if (reloadBtn) {
        reloadBtn.addEventListener('click', function() {
            const spinner = '<span class="reload-spinner"></span>';
            const text = this.querySelector('.reload-text');
            
            this.innerHTML = spinner + 'Refreshing...';
            this.disabled = true;
        });
    }
    
    // Auto-update error time
    function updateErrorTime() {
        const timeElement = document.getElementById('error-time');
        if (timeElement) {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour12: false });
            timeElement.textContent = timeString;
        }
    }
    
    // Update time every second
    setInterval(updateErrorTime, 1000);
});
</script>
@endpush
