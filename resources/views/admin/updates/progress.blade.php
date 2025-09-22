<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>System Update in Progress - {{ config('app.name', 'Raptor Panel') }}</title>
    <link rel="shortcut icon" href="{{ asset('favicons/favicon.ico') }}">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }

        .update-container {
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }

        .update-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .update-header h1 {
            font-size: 2.5rem;
            font-weight: 300;
            margin-bottom: 10px;
            color: #00d4aa;
        }

        .version-info {
            font-size: 1.1rem;
            color: #b8b8b8;
            margin-bottom: 30px;
        }

        .progress-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .progress-bar-container {
            margin-bottom: 30px;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #00d4aa 0%, #00b894 100%);
            border-radius: 4px;
            width: 0%;
            transition: width 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .progress-fill.animated::before {
            content: '';
            position: absolute;
            top: 0;
            left: -50px;
            width: 50px;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: progress-shine 1.5s infinite;
        }

        @keyframes progress-shine {
            0% { left: -50px; }
            100% { left: 100%; }
        }

        .progress-text {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }

        .current-step {
            font-size: 1.1rem;
            color: #00d4aa;
            font-weight: 500;
        }

        .percentage {
            font-size: 1.1rem;
            color: #fff;
            font-weight: 600;
        }

        .console-container {
            background: #1a1a1a;
            border-radius: 8px;
            border: 1px solid #333;
            height: 400px;
            display: flex;
            flex-direction: column;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
        }

        .console-header {
            background: #2d2d2d;
            padding: 12px 20px;
            border-bottom: 1px solid #333;
            display: flex;
            align-items: center;
            border-radius: 8px 8px 0 0;
        }

        .console-dots {
            display: flex;
            gap: 8px;
        }

        .console-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .console-dot.red { background: #ff5f56; }
        .console-dot.yellow { background: #ffbd2e; }
        .console-dot.green { background: #27ca3f; }

        .console-title {
            margin-left: 15px;
            font-size: 0.9rem;
            color: #b8b8b8;
        }

        .console-body {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            line-height: 1.6;
            font-size: 0.9rem;
        }

        .console-line {
            margin-bottom: 4px;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .console-line.info { color: #74b9ff; }
        .console-line.success { color: #00d4aa; }
        .console-line.warning { color: #fdcb6e; }
        .console-line.error { color: #e17055; }
        .console-line.default { color: #ddd; }

        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #00d4aa;
            animation: spin 1s ease-in-out infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .status-indicator {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .status-dot.running { background: #00d4aa; animation: pulse 2s infinite; }
        .status-dot.success { background: #27ca3f; }
        .status-dot.error { background: #e17055; }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .completion-actions {
            text-align: center;
            margin-top: 30px;
            display: none;
        }

        .completion-actions.show {
            display: block;
        }

        .btn {
            background: linear-gradient(135deg, #00d4aa 0%, #00b894 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: linear-gradient(135deg, #00b894 0%, #00a085 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 212, 170, 0.4);
        }

        .error-container {
            background: rgba(225, 112, 85, 0.1);
            border: 1px solid rgba(225, 112, 85, 0.3);
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            display: none;
        }

        .error-container.show {
            display: block;
        }

        .error-title {
            color: #e17055;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .error-message {
            color: #ddd;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="update-container">
        <!-- Header -->
        <div class="update-header">
            <h1><span class="spinner"></span>System Update</h1>
            <div class="version-info">
                Updating from <strong>{{ $fromVersion }}</strong> to <strong>{{ $toVersion }}</strong>
            </div>
        </div>

        <!-- Progress Section -->
        <div class="progress-section">
            <!-- Status Indicator -->
            <div class="status-indicator">
                <div class="status-dot running" id="statusDot"></div>
                <span id="statusText">Initializing update...</span>
            </div>

            <!-- Progress Bar -->
            <div class="progress-bar-container">
                <div class="progress-bar">
                    <div class="progress-fill animated" id="progressFill"></div>
                </div>
                <div class="progress-text">
                    <span class="current-step" id="currentStep">Preparing update...</span>
                    <span class="percentage" id="progressPercentage">0%</span>
                </div>
            </div>

            <!-- Console Output -->
            <div class="console-container">
                <div class="console-header">
                    <div class="console-dots">
                        <div class="console-dot red"></div>
                        <div class="console-dot yellow"></div>
                        <div class="console-dot green"></div>
                    </div>
                    <div class="console-title">Update Console</div>
                </div>
                <div class="console-body" id="consoleOutput">
                    <div class="console-line info">[INFO] Starting Raptor Panel update process...</div>
                    <div class="console-line default">[INFO] Version: {{ $fromVersion }} → {{ $toVersion }}</div>
                    <div class="console-line default">[INFO] Session ID: {{ $sessionId }}</div>
                    <div class="console-line default">━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</div>
                </div>
            </div>
        </div>

        <!-- Error Container -->
        <div class="error-container" id="errorContainer">
            <div class="error-title">Update Failed</div>
            <div class="error-message" id="errorMessage"></div>
        </div>

        <!-- Completion Actions -->
        <div class="completion-actions" id="completionActions">
            <a href="{{ route('admin.index') }}" class="btn">Return to Dashboard</a>
        </div>
    </div>

    <script>
        const sessionId = '{{ $sessionId }}';
        const progressFill = document.getElementById('progressFill');
        const progressPercentage = document.getElementById('progressPercentage');
        const currentStep = document.getElementById('currentStep');
        const consoleOutput = document.getElementById('consoleOutput');
        const statusDot = document.getElementById('statusDot');
        const statusText = document.getElementById('statusText');
        const errorContainer = document.getElementById('errorContainer');
        const errorMessage = document.getElementById('errorMessage');
        const completionActions = document.getElementById('completionActions');

        let updateComplete = false;
        let updateFailed = false;

        // Add console line with timestamp
        function addConsoleLine(message, type = 'default') {
            const timestamp = new Date().toLocaleTimeString();
            const line = document.createElement('div');
            line.className = `console-line ${type}`;
            line.textContent = `[${timestamp}] ${message}`;
            consoleOutput.appendChild(line);
            consoleOutput.scrollTop = consoleOutput.scrollHeight;
        }

        // Update progress
        function updateProgress(percentage, step, status = 'running') {
            progressFill.style.width = `${percentage}%`;
            progressPercentage.textContent = `${percentage}%`;
            currentStep.textContent = step;
            statusText.textContent = step;

            // Update status indicator
            statusDot.className = `status-dot ${status}`;
        }

        // Handle update completion
        function handleUpdateComplete(success, error = null) {
            updateComplete = true;
            
            if (success) {
                updateProgress(100, 'Update completed successfully!', 'success');
                addConsoleLine('Update completed successfully!', 'success');
                addConsoleLine('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━', 'success');
                addConsoleLine('Redirecting to dashboard in 3 seconds...', 'info');
                
                setTimeout(() => {
                    window.location.href = '{{ route('admin.index') }}';
                }, 3000);
                
                completionActions.classList.add('show');
            } else {
                updateFailed = true;
                updateProgress(0, 'Update failed', 'error');
                statusText.textContent = 'Update failed';
                addConsoleLine('Update failed with error: ' + error, 'error');
                
                errorMessage.textContent = error;
                errorContainer.classList.add('show');
                completionActions.classList.add('show');
            }
        }

        // Poll for progress updates
        function pollProgress() {
            if (updateComplete || updateFailed) {
                return;
            }

            fetch(`{{ route('admin.updates.progress', '') }}/${sessionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const progress = data.data;
                        
                        updateProgress(progress.percentage, progress.current_step);
                        
                        // Add any new log messages
                        if (progress.recent_logs) {
                            progress.recent_logs.forEach(log => {
                                addConsoleLine(log.message, log.level || 'default');
                            });
                        }
                        
                        // Check if update is complete
                        if (progress.status === 'completed') {
                            handleUpdateComplete(true);
                            return;
                        } else if (progress.status === 'failed') {
                            handleUpdateComplete(false, progress.error_message || 'Update failed for unknown reason');
                            return;
                        }
                        
                        // Continue polling
                        setTimeout(pollProgress, 1000);
                    } else {
                        handleUpdateComplete(false, data.error || 'Failed to get progress update');
                    }
                })
                .catch(error => {
                    console.error('Progress polling error:', error);
                    addConsoleLine('Connection error while checking progress', 'warning');
                    
                    // Retry after longer delay
                    setTimeout(pollProgress, 3000);
                });
        }

        // Start progress polling
        setTimeout(() => {
            addConsoleLine('Starting progress monitoring...', 'info');
            pollProgress();
        }, 1000);

        // Add some initial progress simulation
        setTimeout(() => addConsoleLine('Validating update requirements...', 'info'), 500);
        setTimeout(() => addConsoleLine('Downloading update package...', 'info'), 1500);
    </script>
</body>
</html>