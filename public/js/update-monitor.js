/**
 * Real-time Update Monitor Client
 * 
 * Handles WebSocket connections and real-time update monitoring
 * for the Pterodactyl update system dashboard.
 */
class UpdateMonitorClient {
    constructor(options = {}) {
        this.options = {
            wsUrl: options.wsUrl || this.getWebSocketUrl(),
            reconnectInterval: options.reconnectInterval || 5000,
            maxReconnectAttempts: options.maxReconnectAttempts || 10,
            heartbeatInterval: options.heartbeatInterval || 30000,
            ...options
        };

        this.socket = null;
        this.reconnectAttempts = 0;
        this.isConnected = false;
        this.subscriptions = new Set();
        this.listeners = new Map();
        this.heartbeatTimer = null;

        this.init();
    }

    /**
     * Initialize the WebSocket connection.
     */
    init() {
        this.connect();
        this.setupEventHandlers();
    }

    /**
     * Connect to WebSocket server.
     */
    connect() {
        try {
            this.socket = new WebSocket(this.options.wsUrl);
            
            this.socket.onopen = this.onOpen.bind(this);
            this.socket.onmessage = this.onMessage.bind(this);
            this.socket.onclose = this.onClose.bind(this);
            this.socket.onerror = this.onError.bind(this);

            console.log('Attempting to connect to update monitoring service...');
        } catch (error) {
            console.error('Failed to create WebSocket connection:', error);
            this.scheduleReconnect();
        }
    }

    /**
     * Handle WebSocket open event.
     */
    onOpen() {
        console.log('Connected to update monitoring service');
        
        this.isConnected = true;
        this.reconnectAttempts = 0;
        
        // Subscribe to channels
        this.resubscribeToChannels();
        
        // Start heartbeat
        this.startHeartbeat();
        
        // Emit connected event
        this.emit('connected');
        
        // Update UI connection status
        this.updateConnectionStatus(true);
    }

    /**
     * Handle WebSocket message event.
     */
    onMessage(event) {
        try {
            const message = JSON.parse(event.data);
            
            console.log('Received message:', message);
            
            // Handle different message types
            switch (message.event) {
                case 'progress.updated':
                    this.handleProgressUpdate(message.data);
                    break;
                case 'status.changed':
                    this.handleStatusChange(message.data);
                    break;
                case 'error.occurred':
                    this.handleError(message.data);
                    break;
                case 'update.completed':
                    this.handleUpdateCompleted(message.data);
                    break;
                case 'monitoring.started':
                    this.handleMonitoringStarted(message.data);
                    break;
                case 'monitoring.stopped':
                    this.handleMonitoringStopped(message.data);
                    break;
                case 'heartbeat':
                    this.handleHeartbeat(message.data);
                    break;
                default:
                    console.log('Unknown message type:', message.event);
            }
            
            // Emit message event for custom handlers
            this.emit('message', message);
            this.emit(message.event, message.data);
            
        } catch (error) {
            console.error('Failed to parse WebSocket message:', error);
        }
    }

    /**
     * Handle WebSocket close event.
     */
    onClose(event) {
        console.log('WebSocket connection closed:', event.code, event.reason);
        
        this.isConnected = false;
        this.stopHeartbeat();
        
        // Update UI connection status
        this.updateConnectionStatus(false);
        
        // Emit disconnected event
        this.emit('disconnected', {
            code: event.code,
            reason: event.reason
        });
        
        // Schedule reconnect if not intentional close
        if (event.code !== 1000) {
            this.scheduleReconnect();
        }
    }

    /**
     * Handle WebSocket error event.
     */
    onError(error) {
        console.error('WebSocket error:', error);
        this.emit('error', error);
    }

    /**
     * Schedule reconnection attempt.
     */
    scheduleReconnect() {
        if (this.reconnectAttempts >= this.options.maxReconnectAttempts) {
            console.error('Max reconnection attempts reached');
            this.emit('maxReconnectAttemptsReached');
            return;
        }

        this.reconnectAttempts++;
        
        console.log(`Scheduling reconnect attempt ${this.reconnectAttempts} in ${this.options.reconnectInterval}ms`);
        
        setTimeout(() => {
            this.connect();
        }, this.options.reconnectInterval);
    }

    /**
     * Subscribe to update channels.
     */
    subscribe(channels) {
        if (!Array.isArray(channels)) {
            channels = [channels];
        }

        channels.forEach(channel => {
            this.subscriptions.add(channel);
        });

        if (this.isConnected) {
            this.sendMessage('subscribe', { channels });
        }
    }

    /**
     * Unsubscribe from update channels.
     */
    unsubscribe(channels) {
        if (!Array.isArray(channels)) {
            channels = [channels];
        }

        channels.forEach(channel => {
            this.subscriptions.delete(channel);
        });

        if (this.isConnected) {
            this.sendMessage('unsubscribe', { channels });
        }
    }

    /**
     * Resubscribe to all channels after reconnection.
     */
    resubscribeToChannels() {
        if (this.subscriptions.size > 0) {
            const channels = Array.from(this.subscriptions);
            this.sendMessage('subscribe', { channels });
        }
    }

    /**
     * Send message to WebSocket server.
     */
    sendMessage(event, data) {
        if (!this.isConnected || !this.socket) {
            console.warn('Cannot send message: not connected');
            return;
        }

        const message = {
            event,
            data,
            timestamp: new Date().toISOString()
        };

        try {
            this.socket.send(JSON.stringify(message));
        } catch (error) {
            console.error('Failed to send WebSocket message:', error);
        }
    }

    /**
     * Start heartbeat to keep connection alive.
     */
    startHeartbeat() {
        this.heartbeatTimer = setInterval(() => {
            this.sendMessage('heartbeat', { timestamp: Date.now() });
        }, this.options.heartbeatInterval);
    }

    /**
     * Stop heartbeat timer.
     */
    stopHeartbeat() {
        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
            this.heartbeatTimer = null;
        }
    }

    /**
     * Add event listener.
     */
    on(event, callback) {
        if (!this.listeners.has(event)) {
            this.listeners.set(event, []);
        }
        this.listeners.get(event).push(callback);
    }

    /**
     * Remove event listener.
     */
    off(event, callback) {
        if (this.listeners.has(event)) {
            const callbacks = this.listeners.get(event);
            const index = callbacks.indexOf(callback);
            if (index > -1) {
                callbacks.splice(index, 1);
            }
        }
    }

    /**
     * Emit event to listeners.
     */
    emit(event, data) {
        if (this.listeners.has(event)) {
            this.listeners.get(event).forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Error in event listener for ${event}:`, error);
                }
            });
        }
    }

    /**
     * Handle progress update message.
     */
    handleProgressUpdate(data) {
        const { session_id, progress } = data;
        
        // Update progress bar
        const progressBar = document.getElementById('update-progress-bar');
        const progressText = document.getElementById('progress-text');
        const currentStep = document.getElementById('current-step');
        const updateDuration = document.getElementById('update-duration');
        
        if (progressBar && progress.percentage !== undefined) {
            progressBar.style.width = `${progress.percentage}%`;
            progressBar.setAttribute('aria-valuenow', progress.percentage);
        }
        
        if (progressText && progress.percentage !== undefined) {
            progressText.textContent = `${progress.percentage}% Complete`;
        }
        
        if (currentStep && progress.current_step) {
            currentStep.innerHTML = `Current Step: ${progress.current_step}`;
        }
        
        if (updateDuration && progress.duration_formatted) {
            updateDuration.textContent = progress.duration_formatted;
        }

        // Update timeline if present
        this.updateTimeline(progress);
        
        console.log(`Progress update for session ${session_id}:`, progress);
    }

    /**
     * Handle status change message.
     */
    handleStatusChange(data) {
        const { session_id, old_status, new_status } = data;
        
        // Update status displays
        const statusElements = document.querySelectorAll('#system-status, .status-display');
        statusElements.forEach(element => {
            element.textContent = new_status.charAt(0).toUpperCase() + new_status.slice(1);
        });
        
        // Update status icons
        this.updateStatusIcons(new_status);
        
        // Show notification
        this.showNotification(`Update status changed to: ${new_status}`, 'info');
        
        console.log(`Status changed for session ${session_id}: ${old_status} → ${new_status}`);
    }

    /**
     * Handle error message.
     */
    handleError(data) {
        const { session_id, error } = data;
        
        // Show error notification
        this.showNotification(`Update error: ${error.message}`, 'error');
        
        // Log error details
        console.error(`Update error in session ${session_id}:`, error);
        
        // Update UI to show error state
        this.updateErrorState(error);
    }

    /**
     * Handle update completed message.
     */
    handleUpdateCompleted(data) {
        const { session_id, result } = data;
        
        // Show completion notification
        const message = result.success ? 'Update completed successfully!' : 'Update failed!';
        const type = result.success ? 'success' : 'error';
        
        this.showNotification(message, type);
        
        // Redirect to dashboard after delay
        setTimeout(() => {
            window.location.reload();
        }, 3000);
        
        console.log(`Update completed for session ${session_id}:`, result);
    }

    /**
     * Handle monitoring started message.
     */
    handleMonitoringStarted(data) {
        console.log('Monitoring started:', data);
        this.showNotification('Real-time monitoring started', 'info');
    }

    /**
     * Handle monitoring stopped message.
     */
    handleMonitoringStopped(data) {
        console.log('Monitoring stopped:', data);
        this.showNotification('Real-time monitoring stopped', 'info');
    }

    /**
     * Handle heartbeat message.
     */
    handleHeartbeat(data) {
        // Update connection indicator
        this.updateConnectionIndicator();
    }

    /**
     * Update status icons based on status.
     */
    updateStatusIcons(status) {
        const statusIcon = document.getElementById('status-icon');
        if (!statusIcon) return;
        
        // Hide all icons
        statusIcon.querySelectorAll('i').forEach(icon => {
            icon.style.display = 'none';
        });
        
        // Show appropriate icon
        let iconSelector;
        switch (status) {
            case 'running':
                iconSelector = '.fa-circle-o-notch';
                break;
            case 'idle':
                iconSelector = '.fa-check';
                break;
            case 'failed':
                iconSelector = '.fa-times';
                break;
            default:
                iconSelector = '.fa-exclamation-triangle';
        }
        
        const icon = statusIcon.querySelector(iconSelector);
        if (icon) {
            icon.style.display = 'inline';
        }
    }

    /**
     * Update timeline with progress information.
     */
    updateTimeline(progress) {
        const timeline = document.getElementById('update-timeline');
        if (!timeline) return;
        
        // Implementation depends on timeline structure
        console.log('Updating timeline with progress:', progress);
    }

    /**
     * Update error state in UI.
     */
    updateErrorState(error) {
        // Add error styling to relevant elements
        const progressBar = document.getElementById('update-progress-bar');
        if (progressBar) {
            progressBar.classList.add('progress-bar-danger');
        }
        
        // Show error details if available
        if (error.step) {
            const currentStep = document.getElementById('current-step');
            if (currentStep) {
                currentStep.innerHTML = `<span class="text-danger">Error in step: ${error.step}</span>`;
            }
        }
    }

    /**
     * Update connection status indicator.
     */
    updateConnectionStatus(connected) {
        const indicators = document.querySelectorAll('.connection-status');
        indicators.forEach(indicator => {
            indicator.classList.toggle('connected', connected);
            indicator.classList.toggle('disconnected', !connected);
            indicator.title = connected ? 'Connected to monitoring service' : 'Disconnected from monitoring service';
        });
    }

    /**
     * Update connection indicator (for heartbeat).
     */
    updateConnectionIndicator() {
        const indicator = document.querySelector('.connection-indicator');
        if (indicator) {
            indicator.classList.add('pulse');
            setTimeout(() => {
                indicator.classList.remove('pulse');
            }, 500);
        }
    }

    /**
     * Show notification to user.
     */
    showNotification(message, type = 'info') {
        // Use existing alert function if available
        if (typeof showAlert === 'function') {
            showAlert(type, message);
            return;
        }
        
        // Fallback notification
        console.log(`[${type.toUpperCase()}] ${message}`);
        
        // Create temporary notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible`;
        notification.innerHTML = `
            <button type="button" class="close" onclick="this.parentElement.remove()">&times;</button>
            ${message}
        `;
        
        document.body.insertBefore(notification, document.body.firstChild);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    /**
     * Get WebSocket URL based on current page.
     */
    getWebSocketUrl() {
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const host = window.location.host;
        return `${protocol}//${host}/ws/updates`;
    }

    /**
     * Setup additional event handlers.
     */
    setupEventHandlers() {
        // Handle page visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // Page is hidden, reduce update frequency
                this.options.heartbeatInterval = 60000; // 1 minute
            } else {
                // Page is visible, normal update frequency
                this.options.heartbeatInterval = 30000; // 30 seconds
            }
            
            if (this.isConnected) {
                this.stopHeartbeat();
                this.startHeartbeat();
            }
        });

        // Handle window beforeunload
        window.addEventListener('beforeunload', () => {
            this.disconnect();
        });
    }

    /**
     * Disconnect from WebSocket server.
     */
    disconnect() {
        if (this.socket) {
            this.socket.close(1000, 'Client disconnecting');
        }
        this.stopHeartbeat();
        this.isConnected = false;
    }

    /**
     * Get connection status.
     */
    getStatus() {
        return {
            connected: this.isConnected,
            reconnectAttempts: this.reconnectAttempts,
            subscriptions: Array.from(this.subscriptions),
            readyState: this.socket ? this.socket.readyState : null
        };
    }
}

// Auto-initialize on pages with update monitoring
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize on update-related pages
    if (document.getElementById('update-dashboard') || 
        document.getElementById('update-management') ||
        document.querySelector('.update-monitor')) {
        
        window.updateMonitor = new UpdateMonitorClient();
        
        // Subscribe to relevant channels
        window.updateMonitor.subscribe(['all', 'progress', 'status', 'errors']);
        
        console.log('Update monitoring client initialized');
    }
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = UpdateMonitorClient;
}