/**
 * RaptorPanel Update System JavaScript Client
 * 
 * Provides frontend functionality for the update management system.
 * Handles UI interactions, API calls, and real-time updates.
 */
class UpdateClient {
    constructor(options = {}) {
        this.options = {
            baseUrl: options.baseUrl || '/admin/updates',
            csrfToken: options.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            ...options
        };
        
        this.currentSession = null;
        this.isUpdating = false;
        
        // Initialize WebSocket if available
        this.ws = window.RaptorPanel?.WebSocket?.getClient() || null;
        this.updateMonitor = window.RaptorPanel?.WebSocket?.updateMonitor || null;
        
        // Bind methods
        this.initializeEventHandlers();
    }

    /**
     * Initialize event handlers for the UI
     */
    initializeEventHandlers() {
        // Check for updates button
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="check-updates"]')) {
                e.preventDefault();
                this.checkForUpdates();
            }
        });

        // Start update button
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="start-update"]')) {
                e.preventDefault();
                const version = e.target.dataset.version;
                this.startUpdate(version);
            }
        });

        // Cancel update button
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="cancel-update"]')) {
                e.preventDefault();
                this.cancelUpdate();
            }
        });

        // Rollback button
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="rollback"]')) {
                e.preventDefault();
                const sessionId = e.target.dataset.sessionId;
                this.rollbackUpdate(sessionId);
            }
        });

        // Emergency stop button
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="emergency-stop"]')) {
                e.preventDefault();
                this.emergencyStop();
            }
        });

        // Refresh health status
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="refresh-health"]')) {
                e.preventDefault();
                this.refreshHealthStatus();
            }
        });

        // Configuration form submission
        document.addEventListener('submit', (e) => {
            if (e.target.matches('#update-settings-form')) {
                e.preventDefault();
                this.saveConfiguration(new FormData(e.target));
            }
        });
    }

    /**
     * Make API request with CSRF protection
     */
    async makeRequest(endpoint, options = {}) {
        const url = `${this.options.baseUrl}${endpoint}`;
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.options.csrfToken
            }
        };

        // Merge options
        const requestOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        };

        try {
            const response = await fetch(url, requestOptions);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || `HTTP ${response.status}`);
            }

            return data;
        } catch (error) {
            console.error('API request failed:', error);
            this.showError('Request failed: ' + error.message);
            throw error;
        }
    }

    /**
     * Check for available updates
     */
    async checkForUpdates() {
        this.showLoading('Checking for updates...');

        try {
            const result = await this.makeRequest('/api/check');
            
            if (result.success) {
                this.displayUpdateResults(result.data);
                this.showSuccess('Update check completed');
            } else {
                this.showError(result.error || 'Failed to check for updates');
            }
        } catch (error) {
            this.showError('Failed to check for updates');
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Start update process
     */
    async startUpdate(version, options = {}) {
        if (this.isUpdating) {
            this.showWarning('Update already in progress');
            return;
        }

        // Show confirmation dialog
        if (!await this.confirmUpdate(version)) {
            return;
        }

        this.isUpdating = true;
        this.showLoading('Starting update...');

        try {
            const result = await this.makeRequest('/api/start', {
                method: 'POST',
                body: JSON.stringify({
                    target_version: version,
                    create_backup: options.createBackup !== false,
                    auto_rollback: options.autoRollback !== false,
                    skip_maintenance: options.skipMaintenance || false
                })
            });

            if (result.success) {
                this.currentSession = result.session_id;
                this.startUpdateMonitoring(result.session_id);
                this.showUpdateProgress();
                this.showSuccess('Update started successfully');
            } else {
                this.showError(result.error || 'Failed to start update');
                this.isUpdating = false;
            }
        } catch (error) {
            this.showError('Failed to start update');
            this.isUpdating = false;
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Cancel current update
     */
    async cancelUpdate() {
        if (!this.currentSession) {
            this.showWarning('No active update to cancel');
            return;
        }

        if (!await this.confirmAction('Are you sure you want to cancel the update?')) {
            return;
        }

        this.showLoading('Cancelling update...');

        try {
            const result = await this.makeRequest(`/api/cancel/${this.currentSession}`, {
                method: 'POST'
            });

            if (result.success) {
                this.showSuccess('Update cancelled successfully');
                this.stopUpdateMonitoring();
                this.hideUpdateProgress();
                this.isUpdating = false;
                this.currentSession = null;
            } else {
                this.showError(result.error || 'Failed to cancel update');
            }
        } catch (error) {
            this.showError('Failed to cancel update');
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Rollback an update
     */
    async rollbackUpdate(sessionId) {
        if (!await this.confirmAction('Are you sure you want to rollback this update? This will restore the previous version.')) {
            return;
        }

        this.showLoading('Rolling back update...');

        try {
            const result = await this.makeRequest(`/api/rollback/${sessionId}`, {
                method: 'POST'
            });

            if (result.success) {
                this.showSuccess('Rollback completed successfully');
                this.refreshUpdateHistory();
            } else {
                this.showError(result.error || 'Failed to rollback update');
            }
        } catch (error) {
            this.showError('Failed to rollback update');
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Emergency stop all updates
     */
    async emergencyStop() {
        if (!await this.confirmAction('EMERGENCY STOP: This will immediately halt all update operations. Are you sure?')) {
            return;
        }

        this.showLoading('Emergency stop in progress...');

        try {
            const result = await this.makeRequest('/api/emergency-stop', {
                method: 'POST'
            });

            if (result.success) {
                this.showWarning('Emergency stop executed');
                this.stopUpdateMonitoring();
                this.hideUpdateProgress();
                this.isUpdating = false;
                this.currentSession = null;
            } else {
                this.showError(result.error || 'Failed to execute emergency stop');
            }
        } catch (error) {
            this.showError('Failed to execute emergency stop');
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Refresh health status
     */
    async refreshHealthStatus() {
        this.showLoading('Refreshing health status...');

        try {
            const result = await this.makeRequest('/api/health');

            if (result.success) {
                this.updateHealthDisplay(result.data);
                this.showSuccess('Health status updated');
            } else {
                this.showError(result.error || 'Failed to get health status');
            }
        } catch (error) {
            this.showError('Failed to refresh health status');
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Save configuration
     */
    async saveConfiguration(formData) {
        this.showLoading('Saving configuration...');

        try {
            const result = await this.makeRequest('/api/config', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': this.options.csrfToken
                    // Don't set Content-Type for FormData
                }
            });

            if (result.success) {
                this.showSuccess('Configuration saved successfully');
            } else {
                this.showError(result.error || 'Failed to save configuration');
            }
        } catch (error) {
            this.showError('Failed to save configuration');
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Start monitoring update progress via WebSocket
     */
    startUpdateMonitoring(sessionId) {
        if (!this.updateMonitor) {
            console.warn('WebSocket update monitor not available');
            return;
        }

        this.updateMonitor.startMonitoring(sessionId, {
            onProgress: (data) => this.handleUpdateProgress(data),
            onStatus: (data) => this.handleUpdateStatus(data),
            onError: (data) => this.handleUpdateError(data),
            onCompleted: (data) => this.handleUpdateCompleted(data)
        });
    }

    /**
     * Stop monitoring update progress
     */
    stopUpdateMonitoring() {
        if (this.updateMonitor) {
            this.updateMonitor.stopMonitoring();
        }
    }

    /**
     * Handle update progress updates
     */
    handleUpdateProgress(data) {
        const progressBar = document.querySelector('#update-progress-bar');
        const progressText = document.querySelector('#update-progress-text');
        const currentStep = document.querySelector('#update-current-step');

        if (progressBar) {
            progressBar.style.width = `${data.progress_percentage || 0}%`;
            progressBar.setAttribute('aria-valuenow', data.progress_percentage || 0);
        }

        if (progressText) {
            progressText.textContent = `${data.progress_percentage || 0}%`;
        }

        if (currentStep && data.current_step) {
            currentStep.textContent = data.current_step;
        }
    }

    /**
     * Handle update status changes
     */
    handleUpdateStatus(data) {
        const statusElement = document.querySelector('#update-status');
        
        if (statusElement) {
            statusElement.textContent = data.status;
            statusElement.className = `badge badge-${this.getStatusClass(data.status)}`;
        }

        if (data.status === 'completed') {
            this.handleUpdateCompleted(data);
        } else if (data.status === 'failed') {
            this.handleUpdateError(data);
        }
    }

    /**
     * Handle update errors
     */
    handleUpdateError(data) {
        this.showError(`Update failed: ${data.error_message || 'Unknown error'}`);
        this.isUpdating = false;
        this.currentSession = null;
        this.hideUpdateProgress();
    }

    /**
     * Handle update completion
     */
    handleUpdateCompleted(data) {
        this.showSuccess('Update completed successfully!');
        this.isUpdating = false;
        this.currentSession = null;
        this.hideUpdateProgress();
        this.refreshUpdateHistory();
    }

    /**
     * Display update check results
     */
    displayUpdateResults(data) {
        const container = document.querySelector('#update-results');
        if (!container) return;

        if (data.updates_available && data.latest_release) {
            const release = data.latest_release;
            container.innerHTML = `
                <div class="alert alert-info">
                    <h5><i class="fas fa-download"></i> Update Available</h5>
                    <p><strong>Version:</strong> ${release.tag_name}</p>
                    <p><strong>Released:</strong> ${new Date(release.published_at).toLocaleDateString()}</p>
                    <p><strong>Size:</strong> ${this.formatFileSize(release.assets[0]?.size || 0)}</p>
                    <div class="mt-3">
                        <button class="btn btn-primary" data-action="start-update" data-version="${release.tag_name}">
                            <i class="fas fa-play"></i> Start Update
                        </button>
                    </div>
                </div>
            `;
        } else {
            container.innerHTML = `
                <div class="alert alert-success">
                    <h5><i class="fas fa-check"></i> System Up to Date</h5>
                    <p>You are running the latest version of Pterodactyl.</p>
                    <p><strong>Current Version:</strong> ${data.current_version}</p>
                </div>
            `;
        }
    }

    /**
     * Update health display
     */
    updateHealthDisplay(healthData) {
        const container = document.querySelector('#health-status');
        if (!container) return;

        // Update overall health status
        const overallStatus = document.querySelector('#overall-health-status');
        if (overallStatus) {
            overallStatus.textContent = healthData.overall_status;
            overallStatus.className = `badge badge-${this.getHealthStatusClass(healthData.overall_status)}`;
        }

        // Update individual health metrics
        Object.entries(healthData.checks || {}).forEach(([key, check]) => {
            const element = document.querySelector(`[data-health-check="${key}"]`);
            if (element) {
                element.textContent = check.status;
                element.className = `badge badge-${this.getHealthStatusClass(check.status)}`;
            }
        });

        // Update last check time
        const lastCheck = document.querySelector('#last-health-check');
        if (lastCheck && healthData.checked_at) {
            lastCheck.textContent = new Date(healthData.checked_at).toLocaleString();
        }
    }

    /**
     * Show/hide update progress
     */
    showUpdateProgress() {
        const progressContainer = document.querySelector('#update-progress-container');
        if (progressContainer) {
            progressContainer.style.display = 'block';
        }
    }

    hideUpdateProgress() {
        const progressContainer = document.querySelector('#update-progress-container');
        if (progressContainer) {
            progressContainer.style.display = 'none';
        }
    }

    /**
     * Refresh update history
     */
    async refreshUpdateHistory() {
        try {
            const result = await this.makeRequest('/api/history');
            if (result.success) {
                this.updateHistoryDisplay(result.data);
            }
        } catch (error) {
            console.error('Failed to refresh update history:', error);
        }
    }

    /**
     * Update history display
     */
    updateHistoryDisplay(historyData) {
        const container = document.querySelector('#update-history');
        if (!container) return;

        const historyHtml = historyData.map(session => `
            <tr>
                <td>${session.id}</td>
                <td>${session.target_version || 'N/A'}</td>
                <td>
                    <span class="badge badge-${this.getStatusClass(session.status)}">${session.status}</span>
                </td>
                <td>${session.progress_percentage || 0}%</td>
                <td>${new Date(session.created_at).toLocaleString()}</td>
                <td>
                    ${session.status === 'completed' && session.backup_id ? 
                        `<button class="btn btn-sm btn-warning" data-action="rollback" data-session-id="${session.id}">
                            <i class="fas fa-undo"></i> Rollback
                        </button>` : 
                        '<span class="text-muted">No backup</span>'
                    }
                </td>
            </tr>
        `).join('');

        const tbody = container.querySelector('tbody');
        if (tbody) {
            tbody.innerHTML = historyHtml;
        }
    }

    /**
     * Utility functions
     */
    getStatusClass(status) {
        const statusClasses = {
            pending: 'info',
            running: 'warning',
            completed: 'success',
            failed: 'danger',
            cancelled: 'secondary',
            rolled_back: 'warning'
        };
        return statusClasses[status] || 'secondary';
    }

    getHealthStatusClass(status) {
        const statusClasses = {
            healthy: 'success',
            warning: 'warning',
            critical: 'danger',
            unknown: 'secondary'
        };
        return statusClasses[status] || 'secondary';
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * UI feedback methods
     */
    showLoading(message = 'Loading...') {
        // Show loading spinner or message
        const loading = document.querySelector('#loading-overlay');
        if (loading) {
            loading.querySelector('.loading-message').textContent = message;
            loading.style.display = 'flex';
        }
    }

    hideLoading() {
        const loading = document.querySelector('#loading-overlay');
        if (loading) {
            loading.style.display = 'none';
        }
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showWarning(message) {
        this.showNotification(message, 'warning');
    }

    showNotification(message, type = 'info') {
        // Create and show notification toast
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        `;

        document.body.appendChild(toast);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 5000);
    }

    /**
     * Confirmation dialogs
     */
    async confirmUpdate(version) {
        return new Promise((resolve) => {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Update</h5>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to update to version <strong>${version}</strong>?</p>
                            <div class="alert alert-warning">
                                <strong>Warning:</strong> This will:
                                <ul class="mb-0">
                                    <li>Create a backup of the current system</li>
                                    <li>Download and install new files</li>
                                    <li>Run database migrations if needed</li>
                                    <li>Temporarily put the system in maintenance mode</li>
                                </ul>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirm-update">Start Update</button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);
            $(modal).modal('show');

            modal.querySelector('#confirm-update').addEventListener('click', () => {
                $(modal).modal('hide');
                resolve(true);
            });

            $(modal).on('hidden.bs.modal', () => {
                document.body.removeChild(modal);
                resolve(false);
            });
        });
    }

    async confirmAction(message) {
        return confirm(message);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Create global instance
    window.RaptorPanel = window.RaptorPanel || {};
    window.RaptorPanel.UpdateClient = new UpdateClient({
        baseUrl: '/admin/updates'
    });

    console.log('Update client initialized');
});