/**
 * RaptorPanel Update System WebSocket Client
 * 
 * Provides real-time communication for update monitoring and system health.
 * Handles connection management, event broadcasting, and UI updates.
 */
class UpdateWebSocketClient {
    constructor(options = {}) {
        this.options = {
            url: options.url || 'ws://localhost:6001',
            auth: options.auth || {},
            reconnectInterval: options.reconnectInterval || 3000,
            maxReconnectAttempts: options.maxReconnectAttempts || 10,
            heartbeatInterval: options.heartbeatInterval || 30000,
            ...options
        };

        this.socket = null;
        this.connected = false;
        this.reconnectAttempts = 0;
        this.heartbeatTimer = null;
        this.eventListeners = new Map();
        
        // Bind methods
        this.connect = this.connect.bind(this);
        this.disconnect = this.disconnect.bind(this);
        this.onOpen = this.onOpen.bind(this);
        this.onClose = this.onClose.bind(this);
        this.onError = this.onError.bind(this);
        this.onMessage = this.onMessage.bind(this);
        this.reconnect = this.reconnect.bind(this);
        this.sendHeartbeat = this.sendHeartbeat.bind(this);
    }

    /**
     * Connect to WebSocket server
     */
    connect() {
        if (this.socket && this.socket.readyState === WebSocket.OPEN) {
            console.log('WebSocket already connected');
            return;
        }

        try {
            console.log('Connecting to WebSocket:', this.options.url);
            this.socket = new WebSocket(this.options.url);
            
            this.socket.onopen = this.onOpen;
            this.socket.onclose = this.onClose;
            this.socket.onerror = this.onError;
            this.socket.onmessage = this.onMessage;
            
        } catch (error) {
            console.error('WebSocket connection failed:', error);
            this.scheduleReconnect();
        }
    }

    /**
     * Disconnect from WebSocket server
     */
    disconnect() {
        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
            this.heartbeatTimer = null;
        }

        if (this.socket) {
            this.socket.onclose = null; // Prevent reconnect
            this.socket.close();
            this.socket = null;
        }

        this.connected = false;
        this.reconnectAttempts = 0;
        
        this.emit('disconnected');
        console.log('WebSocket disconnected');
    }

    /**
     * Handle WebSocket open event
     */
    onOpen(event) {
        console.log('WebSocket connected');
        this.connected = true;
        this.reconnectAttempts = 0;
        
        // Start heartbeat
        this.startHeartbeat();
        
        // Send authentication if provided
        if (this.options.auth && Object.keys(this.options.auth).length > 0) {
            this.send('auth', this.options.auth);
        }
        
        this.emit('connected', event);
    }

    /**
     * Handle WebSocket close event
     */
    onClose(event) {
        console.log('WebSocket closed:', event.code, event.reason);
        this.connected = false;
        
        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
            this.heartbeatTimer = null;
        }
        
        this.emit('disconnected', event);
        
        // Schedule reconnect if not intentionally closed
        if (event.code !== 1000) {
            this.scheduleReconnect();
        }
    }

    /**
     * Handle WebSocket error event
     */
    onError(error) {
        console.error('WebSocket error:', error);
        this.emit('error', error);
    }

    /**
     * Handle WebSocket message event
     */
    onMessage(event) {
        try {
            const data = JSON.parse(event.data);
            console.log('WebSocket message received:', data.type, data);
            
            // Handle heartbeat response
            if (data.type === 'pong') {
                return;
            }
            
            // Emit the specific event type
            this.emit(data.type, data.payload || data);
            
            // Also emit a generic message event
            this.emit('message', data);
            
        } catch (error) {
            console.error('Failed to parse WebSocket message:', error, event.data);
        }
    }

    /**
     * Send data through WebSocket
     */
    send(type, payload = {}) {
        if (!this.connected || !this.socket) {
            console.warn('WebSocket not connected, cannot send:', type);
            return false;
        }

        try {
            const message = JSON.stringify({ type, payload });
            this.socket.send(message);
            console.log('WebSocket message sent:', type, payload);
            return true;
        } catch (error) {
            console.error('Failed to send WebSocket message:', error);
            return false;
        }
    }

    /**
     * Subscribe to updates for a specific session
     */
    subscribeToSession(sessionId) {
        return this.send('subscribe', {
            channel: `update-session.${sessionId}`
        });
    }

    /**
     * Unsubscribe from session updates
     */
    unsubscribeFromSession(sessionId) {
        return this.send('unsubscribe', {
            channel: `update-session.${sessionId}`
        });
    }

    /**
     * Subscribe to system health updates
     */
    subscribeToHealth() {
        return this.send('subscribe', {
            channel: 'system-health'
        });
    }

    /**
     * Subscribe to general update notifications
     */
    subscribeToUpdates() {
        return this.send('subscribe', {
            channel: 'update-notifications'
        });
    }

    /**
     * Add event listener
     */
    on(event, callback) {
        if (!this.eventListeners.has(event)) {
            this.eventListeners.set(event, []);
        }
        this.eventListeners.get(event).push(callback);
    }

    /**
     * Remove event listener
     */
    off(event, callback) {
        if (!this.eventListeners.has(event)) {
            return;
        }
        
        const listeners = this.eventListeners.get(event);
        const index = listeners.indexOf(callback);
        
        if (index > -1) {
            listeners.splice(index, 1);
        }
    }

    /**
     * Emit event to listeners
     */
    emit(event, data = null) {
        if (!this.eventListeners.has(event)) {
            return;
        }
        
        const listeners = this.eventListeners.get(event);
        listeners.forEach(callback => {
            try {
                callback(data);
            } catch (error) {
                console.error(`Error in event listener for ${event}:`, error);
            }
        });
    }

    /**
     * Schedule reconnection attempt
     */
    scheduleReconnect() {
        if (this.reconnectAttempts >= this.options.maxReconnectAttempts) {
            console.error('Max reconnection attempts reached');
            this.emit('maxReconnectAttemptsReached');
            return;
        }

        this.reconnectAttempts++;
        const delay = this.options.reconnectInterval * Math.pow(1.5, this.reconnectAttempts - 1);
        
        console.log(`Scheduling reconnect attempt ${this.reconnectAttempts} in ${delay}ms`);
        
        setTimeout(() => {
            if (!this.connected) {
                console.log(`Reconnect attempt ${this.reconnectAttempts}`);
                this.emit('reconnecting', { attempt: this.reconnectAttempts });
                this.connect();
            }
        }, delay);
    }

    /**
     * Start heartbeat timer
     */
    startHeartbeat() {
        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
        }
        
        this.heartbeatTimer = setInterval(() => {
            this.sendHeartbeat();
        }, this.options.heartbeatInterval);
    }

    /**
     * Send heartbeat ping
     */
    sendHeartbeat() {
        if (this.connected) {
            this.send('ping');
        }
    }

    /**
     * Get connection status
     */
    getStatus() {
        return {
            connected: this.connected,
            readyState: this.socket ? this.socket.readyState : null,
            reconnectAttempts: this.reconnectAttempts,
            url: this.options.url
        };
    }
}

/**
 * Update Monitor class for managing update session monitoring
 */
class UpdateMonitor {
    constructor(webSocketClient) {
        this.ws = webSocketClient;
        this.currentSessionId = null;
        this.progressCallback = null;
        this.statusCallback = null;
        this.errorCallback = null;
        
        // Bind WebSocket events
        this.ws.on('update-progress', this.handleProgress.bind(this));
        this.ws.on('update-status', this.handleStatus.bind(this));
        this.ws.on('update-error', this.handleError.bind(this));
        this.ws.on('update-completed', this.handleCompleted.bind(this));
    }

    /**
     * Start monitoring an update session
     */
    startMonitoring(sessionId, callbacks = {}) {
        if (this.currentSessionId) {
            this.stopMonitoring();
        }

        this.currentSessionId = sessionId;
        this.progressCallback = callbacks.onProgress || null;
        this.statusCallback = callbacks.onStatus || null;
        this.errorCallback = callbacks.onError || null;
        this.completedCallback = callbacks.onCompleted || null;

        // Subscribe to session updates
        this.ws.subscribeToSession(sessionId);
        
        console.log('Started monitoring update session:', sessionId);
    }

    /**
     * Stop monitoring current session
     */
    stopMonitoring() {
        if (this.currentSessionId) {
            this.ws.unsubscribeFromSession(this.currentSessionId);
            console.log('Stopped monitoring update session:', this.currentSessionId);
        }

        this.currentSessionId = null;
        this.progressCallback = null;
        this.statusCallback = null;
        this.errorCallback = null;
        this.completedCallback = null;
    }

    /**
     * Handle progress updates
     */
    handleProgress(data) {
        if (data.session_id === this.currentSessionId && this.progressCallback) {
            this.progressCallback(data);
        }
    }

    /**
     * Handle status updates
     */
    handleStatus(data) {
        if (data.session_id === this.currentSessionId && this.statusCallback) {
            this.statusCallback(data);
        }
    }

    /**
     * Handle errors
     */
    handleError(data) {
        if (data.session_id === this.currentSessionId && this.errorCallback) {
            this.errorCallback(data);
        }
    }

    /**
     * Handle completion
     */
    handleCompleted(data) {
        if (data.session_id === this.currentSessionId && this.completedCallback) {
            this.completedCallback(data);
        }
    }
}

/**
 * Health Monitor class for system health monitoring
 */
class HealthMonitor {
    constructor(webSocketClient) {
        this.ws = webSocketClient;
        this.healthCallback = null;
        this.alertCallback = null;
        this.isMonitoring = false;
        
        // Bind WebSocket events
        this.ws.on('health-update', this.handleHealthUpdate.bind(this));
        this.ws.on('health-alert', this.handleHealthAlert.bind(this));
    }

    /**
     * Start health monitoring
     */
    startMonitoring(callbacks = {}) {
        if (this.isMonitoring) {
            return;
        }

        this.healthCallback = callbacks.onHealthUpdate || null;
        this.alertCallback = callbacks.onAlert || null;
        this.isMonitoring = true;

        // Subscribe to health updates
        this.ws.subscribeToHealth();
        
        console.log('Started health monitoring');
    }

    /**
     * Stop health monitoring
     */
    stopMonitoring() {
        if (!this.isMonitoring) {
            return;
        }

        this.isMonitoring = false;
        this.healthCallback = null;
        this.alertCallback = null;
        
        console.log('Stopped health monitoring');
    }

    /**
     * Handle health updates
     */
    handleHealthUpdate(data) {
        if (this.isMonitoring && this.healthCallback) {
            this.healthCallback(data);
        }
    }

    /**
     * Handle health alerts
     */
    handleHealthAlert(data) {
        if (this.isMonitoring && this.alertCallback) {
            this.alertCallback(data);
        }
    }
}

/**
 * Main WebSocket client instance and utilities
 */
window.RaptorPanel = window.RaptorPanel || {};
window.RaptorPanel.WebSocket = {
    UpdateWebSocketClient,
    UpdateMonitor,
    HealthMonitor,
    
    // Global client instance
    client: null,
    updateMonitor: null,
    healthMonitor: null,
    
    /**
     * Initialize WebSocket client
     */
    init(options = {}) {
        if (this.client) {
            this.client.disconnect();
        }
        
        this.client = new UpdateWebSocketClient(options);
        this.updateMonitor = new UpdateMonitor(this.client);
        this.healthMonitor = new HealthMonitor(this.client);
        
        // Auto-connect
        this.client.connect();
        
        return this.client;
    },
    
    /**
     * Get or create client instance
     */
    getClient() {
        if (!this.client) {
            this.init();
        }
        return this.client;
    }
};

// Auto-initialize if WebSocket URL is available
document.addEventListener('DOMContentLoaded', function() {
    const wsUrl = document.querySelector('meta[name="websocket-url"]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    
    if (wsUrl && wsUrl.getAttribute('content')) {
        const options = {
            url: wsUrl.getAttribute('content')
        };
        
        if (csrfToken) {
            options.auth = {
                token: csrfToken.getAttribute('content')
            };
        }
        
        window.RaptorPanel.WebSocket.init(options);
        console.log('WebSocket client auto-initialized');
    }
});