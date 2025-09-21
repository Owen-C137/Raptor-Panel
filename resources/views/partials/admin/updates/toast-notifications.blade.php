<!-- Toast Container (fixed top-right) -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1100;" id="toast-container">
    <!-- Success Toast Template -->
    <div class="toast align-items-center text-white bg-success border-0 d-none" role="alert" aria-live="assertive" aria-atomic="true" data-toast-type="success">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fa fa-check-circle me-2"></i>
                <span class="toast-message"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>

    <!-- Error Toast Template -->
    <div class="toast align-items-center text-white bg-danger border-0 d-none" role="alert" aria-live="assertive" aria-atomic="true" data-toast-type="error">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fa fa-exclamation-circle me-2"></i>
                <span class="toast-message"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>

    <!-- Warning Toast Template -->
    <div class="toast align-items-center text-white bg-warning border-0 d-none" role="alert" aria-live="assertive" aria-atomic="true" data-toast-type="warning">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fa fa-exclamation-triangle me-2"></i>
                <span class="toast-message"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>

    <!-- Info Toast Template -->
    <div class="toast align-items-center text-white bg-info border-0 d-none" role="alert" aria-live="assertive" aria-atomic="true" data-toast-type="info">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fa fa-info-circle me-2"></i>
                <span class="toast-message"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
// Toast notification system
window.showToast = function(message, type = 'info', duration = 5000) {
    const container = document.getElementById('toast-container');
    const template = container.querySelector(`[data-toast-type="${type}"]`);
    
    if (!template) {
        console.error('Toast type not found:', type);
        return;
    }
    
    // Clone the template
    const toast = template.cloneNode(true);
    toast.classList.remove('d-none');
    
    // Set the message
    const messageElement = toast.querySelector('.toast-message');
    messageElement.textContent = message;
    
    // Add unique ID for tracking
    const toastId = 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    toast.id = toastId;
    
    // Append to container
    container.appendChild(toast);
    
    // Initialize and show the toast
    const bsToast = new bootstrap.Toast(toast, {
        delay: duration,
        autohide: true
    });
    
    bsToast.show();
    
    // Remove from DOM after hiding
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
    
    return bsToast;
};

// Convenience methods
window.showSuccessToast = function(message, duration = 5000) {
    return showToast(message, 'success', duration);
};

window.showErrorToast = function(message, duration = 8000) {
    return showToast(message, 'error', duration);
};

window.showWarningToast = function(message, duration = 6000) {
    return showToast(message, 'warning', duration);
};

window.showInfoToast = function(message, duration = 5000) {
    return showToast(message, 'info', duration);
};

// Auto-show toasts from Laravel flash messages (if they exist)
document.addEventListener('DOMContentLoaded', function() {
    @if(session('success'))
        showSuccessToast('{{ session('success') }}');
    @endif
    
    @if(session('error'))
        showErrorToast('{{ session('error') }}');
    @endif
    
    @if(session('warning'))
        showWarningToast('{{ session('warning') }}');
    @endif
    
    @if(session('info'))
        showInfoToast('{{ session('info') }}');
    @endif
    
    // Handle old-style alerts and convert them (but exclude available updates and current version alerts)
    const oldAlerts = document.querySelectorAll('.alert:not(.d-none):not(#available-updates .alert):not(.current-version-alert)');
    oldAlerts.forEach(function(alert) {
        // Skip alerts that are part of the main content layout
        if (alert.closest('#available-updates') || alert.closest('#update-management')) {
            return;
        }
        
        let type = 'info';
        let message = alert.textContent.trim();
        
        if (alert.classList.contains('alert-success')) type = 'success';
        else if (alert.classList.contains('alert-danger')) type = 'error';
        else if (alert.classList.contains('alert-warning')) type = 'warning';
        else if (alert.classList.contains('alert-info')) type = 'info';
        
        // Show as toast instead
        showToast(message, type);
        
        // Hide the old alert
        alert.style.display = 'none';
    });
});
</script>