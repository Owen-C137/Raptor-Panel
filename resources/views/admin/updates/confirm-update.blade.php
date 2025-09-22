@extends('layouts.admin')
@include('partials/admin.updates.nav', ['activeTab' => 'manage'])

@section('title')
    System Update
@endsection

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
            <div class="flex-grow-1">
                <h1 class="h3 fw-bold mb-1">
                    System Update
                </h1>
                <h2 class="fs-base lh-base fw-medium text-muted mb-0">
                    Updating from {{ $currentVersion }} to {{ $version }}
                </h2>
            </div>
            <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item">
                        <a class="link-fx" href="{{ route('admin.index') }}">Admin</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a class="link-fx" href="{{ route('admin.updates.dashboard') }}">Updates</a>
                    </li>
                    <li class="breadcrumb-item" aria-current="page">
                        Update System
                    </li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    <!-- Update Progress Card -->
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="block block-rounded" id="update-card">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fas fa-download me-2 text-primary"></i>
                        System Update: {{ $version }}
                    </h3>
                    <div class="block-options">
                        <div class="badge bg-info fs-sm" id="update-status">Ready to Update</div>
                    </div>
                </div>
                <div class="block-content">
                    <!-- Version Information -->
                    <div class="row push">
                        <div class="col-lg-4">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fa fa-2x fa-info-circle text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="fs-sm fw-medium text-muted text-uppercase">Current Version</div>
                                    <div class="fw-semibold">{{ $currentVersion }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fa fa-2x fa-arrow-up text-success"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="fs-sm fw-medium text-muted text-uppercase">Target Version</div>
                                    <div class="fw-semibold text-success">{{ $version }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fa fa-2x fa-calendar text-info"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="fs-sm fw-medium text-muted text-uppercase">Release Date</div>
                                    <div class="fw-semibold">{{ isset($updateDetails['published_at']) ? \Carbon\Carbon::parse($updateDetails['published_at'])->format('M d, Y') : 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar (Initially Hidden) -->
                    <div id="progress-section" class="push" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-semibold">Update Progress</div>
                            <div class="fw-semibold text-primary" id="progress-percentage">0%</div>
                        </div>
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="fs-sm text-muted" id="current-step">Initializing update process...</div>
                    </div>

                    <!-- System Health Check -->
                    @if(isset($systemHealth) && is_array($systemHealth))
                    <div class="push">
                        <h4 class="h5 mb-3">
                            <i class="fas fa-heartbeat text-danger me-2"></i>System Health Check
                        </h4>
                        <div class="row g-3">
                            @php
                                $totalChecks = count($systemHealth);
                                $passedChecks = 0;
                                $warningChecks = 0;
                                $failedChecks = 0;
                                
                                foreach ($systemHealth as $checkName => $checkResult) {
                                    if (isset($checkResult['status'])) {
                                        if ($checkResult['status'] === 'pass') {
                                            $passedChecks++;
                                        } elseif ($checkResult['status'] === 'warning') {
                                            $warningChecks++;
                                        } else {
                                            $failedChecks++;
                                        }
                                    }
                                }
                                
                                $overallStatus = $failedChecks > 0 ? 'danger' : ($warningChecks > 0 ? 'warning' : 'success');
                                $statusText = $failedChecks > 0 ? 'Issues Found' : ($warningChecks > 0 ? 'Warnings' : 'All Clear');
                            @endphp
                            
                            <div class="col-12">
                                <div class="alert alert-{{ $overallStatus }} d-flex align-items-center" role="alert">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-{{ $overallStatus === 'success' ? 'check-circle' : ($overallStatus === 'warning' ? 'exclamation-triangle' : 'times-circle') }} fa-2x"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-bold">{{ $statusText }}</div>
                                        <div class="fs-sm">{{ $passedChecks }} passed, {{ $warningChecks }} warnings, {{ $failedChecks }} failed</div>
                                    </div>
                                </div>
                            </div>

                            @if($warningChecks > 0 || $failedChecks > 0)
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table class="table table-sm table-vcenter">
                                        <thead>
                                            <tr>
                                                <th>Health Check</th>
                                                <th>Status</th>
                                                <th>Message</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($systemHealth as $checkName => $checkResult)
                                                @if(isset($checkResult['status']) && in_array($checkResult['status'], ['warning', 'fail']))
                                                <tr>
                                                    <td class="fw-semibold">{{ ucfirst(str_replace('_', ' ', $checkName)) }}</td>
                                                    <td>
                                                        <span class="badge bg-{{ $checkResult['status'] === 'warning' ? 'warning' : 'danger' }}">
                                                            {{ ucfirst($checkResult['status']) }}
                                                        </span>
                                                    </td>
                                                    <td class="fs-sm">{{ $checkResult['message'] ?? 'Check failed' }}</td>
                                                </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Release Notes -->
                    @if(isset($updateDetails['body']) && !empty($updateDetails['body']))
                    <div class="push">
                        <h4 class="h5 mb-3">
                            <i class="fas fa-file-alt text-warning me-2"></i>Release Notes
                        </h4>
                        <div class="bg-body-light p-3 rounded">
                            <div style="max-height: 300px; overflow-y: auto;">
                                {!! Str::markdown($updateDetails['body']) !!}
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Update Process Information -->
                    <div class="push">
                        <h4 class="h5 mb-3">
                            <i class="fas fa-cogs text-primary me-2"></i>Update Process
                        </h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="bg-body-light p-3 rounded">
                                    <h5 class="h6 fw-bold mb-3">Update Steps:</h5>
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <span class="fw-medium">System Health Check</span>
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <span class="fw-medium">Create System Backup</span>
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <span class="fw-medium">Download Update Files</span>
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <span class="fw-medium">Apply File Updates</span>
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <span class="fw-medium">Run Database Migrations</span>
                                        </li>
                                        <li class="mb-0">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <span class="fw-medium">Clear System Cache</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="bg-body-light p-3 rounded">
                                    <h5 class="h6 fw-bold mb-3">Configuration:</h5>
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <i class="fas fa-{{ $configuration['backup_enabled'] ?? true ? 'check' : 'times' }} text-{{ $configuration['backup_enabled'] ?? true ? 'success' : 'danger' }} me-2"></i>
                                            <span class="fw-medium">Backup: {{ ($configuration['backup_enabled'] ?? true) ? 'Enabled' : 'Disabled' }}</span>
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-{{ $configuration['maintenance_mode'] ?? true ? 'check' : 'times' }} text-{{ $configuration['maintenance_mode'] ?? true ? 'success' : 'danger' }} me-2"></i>
                                            <span class="fw-medium">Maintenance Mode: {{ ($configuration['maintenance_mode'] ?? true) ? 'Enabled' : 'Disabled' }}</span>
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-clock text-info me-2"></i>
                                            <span class="fw-medium">Estimated Time: 5-10 minutes</span>
                                        </li>
                                        <li class="mb-0">
                                            <i class="fas fa-shield-alt text-primary me-2"></i>
                                            <span class="fw-medium">Safe Mode: Active</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Warning Notice -->
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="fw-bold">Important Notice</div>
                            <div>The panel will be temporarily unavailable during the update process. Please ensure no critical operations are running and inform users about the maintenance.</div>
                        </div>
                    </div>
                </div>
                <div class="block-content block-content-full block-content-sm bg-body-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('admin.updates.manage') }}" class="btn btn-alt-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Updates
                        </a>
                        <button type="button" id="confirm-update-btn" class="btn btn-success btn-lg" data-version="{{ $version }}">
                            <i class="fas fa-play me-2"></i>Start Update Now
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Console Output (Initially Hidden) -->
    <div class="row" id="console-section" style="display: none;">
        <div class="col-lg-8 mx-auto">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fas fa-terminal me-2 text-success"></i>
                        Live Console Output
                    </h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option" id="console-scroll-toggle" title="Auto-scroll">
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <button type="button" class="btn-block-option" id="console-clear" title="Clear console">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content p-0">
                    <div id="console-output" class="bg-dark text-light p-3" style="height: 400px; overflow-y: auto; font-family: 'Monaco', 'Menlo', 'Consolas', monospace; font-size: 12px; line-height: 1.4;">
                        <div class="console-line text-success">[INFO] Update system initialized</div>
                        <div class="console-line text-info">[INFO] Session ID: <span id="session-id-display">Preparing...</span></div>
                        <div class="console-line text-muted">[INFO] Waiting for user confirmation...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
            let updateInProgress = false;
            let sessionId = null;
            let progressInterval = null;
            let autoScroll = true;

            // Console functions
            function addConsoleLog(message, type = 'info') {
                const timestamp = new Date().toLocaleTimeString();
                const typeClass = {
                    'info': 'text-info',
                    'success': 'text-success',
                    'warning': 'text-warning',
                    'error': 'text-danger',
                    'default': 'text-light'
                };
                
                const logClass = typeClass[type] || typeClass['default'];
                const logLine = `<div class="console-line ${logClass}">[${timestamp}] ${message}</div>`;
                
                $('#console-output').append(logLine);
                
                if (autoScroll) {
                    $('#console-output').scrollTop($('#console-output')[0].scrollHeight);
                }
            }

            // Console controls
            $('#console-scroll-toggle').click(function() {
                autoScroll = !autoScroll;
                $(this).find('i').toggleClass('fa-arrow-down fa-pause');
                $(this).attr('title', autoScroll ? 'Auto-scroll' : 'Manual scroll');
            });

            $('#console-clear').click(function() {
                $('#console-output').html('<div class="console-line text-success">[INFO] Console cleared</div>');
            });

            // Update progress function
            function updateProgress(percentage, step) {
                $('#progress-percentage').text(percentage + '%');
                $('#progress-bar').css('width', percentage + '%').attr('aria-valuenow', percentage);
                $('#current-step').text(step);
                
                if (percentage >= 100) {
                    $('#progress-bar').removeClass('progress-bar-animated');
                    $('#update-status').removeClass('bg-info').addClass('bg-success').text('Update Complete');
                }
            }

            // Poll for progress updates
            function startProgressPolling() {
                if (!sessionId) return;
                
                progressInterval = setInterval(function() {
                    $.ajax({
                        url: `{{ route('admin.updates.api.progress', '') }}/${sessionId}`,
                        type: 'GET',
                        success: function(response) {
                            if (response.success && response.data) {
                                const data = response.data;
                                updateProgress(data.percentage || 0, data.current_step || 'Processing...');
                                
                                // Add recent logs to console
                                if (data.recent_logs && data.recent_logs.length > 0) {
                                    data.recent_logs.forEach(function(log) {
                                        addConsoleLog(log.message || log, log.level || 'info');
                                    });
                                }
                                
                                // Check if update is complete
                                if (data.status === 'completed') {
                                    addConsoleLog('Update completed successfully!', 'success');
                                    updateProgress(100, 'Update Complete');
                                    clearInterval(progressInterval);
                                    
                                    // Redirect after a few seconds
                                    setTimeout(function() {
                                        addConsoleLog('Redirecting to dashboard...', 'info');
                                        window.location.href = '{{ route("admin.updates.dashboard") }}';
                                    }, 3000);
                                } else if (data.status === 'failed') {
                                    addConsoleLog('Update failed: ' + (data.error_message || 'Unknown error'), 'error');
                                    $('#update-status').removeClass('bg-info').addClass('bg-danger').text('Update Failed');
                                    clearInterval(progressInterval);
                                }
                            }
                        },
                        error: function(xhr) {
                            addConsoleLog('Error fetching progress: ' + xhr.statusText, 'error');
                        }
                    });
                }, 2000); // Poll every 2 seconds
            }

            // Start update button click handler
            $('#confirm-update-btn').click(function() {
                if (updateInProgress) return;
                
                var version = $(this).data('version');
                
                // Show final confirmation
                if (!confirm("Are you absolutely sure you want to proceed with the update?\n\nThis action cannot be undone and will temporarily make the panel unavailable.")) {
                    return;
                }

                updateInProgress = true;
                
                // Update UI to show progress mode
                $(this).html('<i class="fas fa-spinner fa-spin me-2"></i>Starting Update...').prop('disabled', true);
                $('#update-status').removeClass('bg-info').addClass('bg-warning').text('Starting Update');
                
                // Show progress and console sections
                $('#progress-section').slideDown();
                $('#console-section').slideDown();
                
                addConsoleLog('User confirmed update to version ' + version, 'info');
                addConsoleLog('Preparing update process...', 'info');

                // Prepare form data
                var options = {
                    target_version: version,
                    create_backup: true,
                    force: false,
                    web_request: true,
                    _token: "{{ csrf_token() }}"
                };

                // Start the update
                $.ajax({
                    url: "{{ route("admin.updates.initiate") }}",
                    type: 'POST',
                    data: options,
                    success: function(response) {
                        if (response.success && response.session_id) {
                            sessionId = response.session_id;
                            $('#session-id-display').text(sessionId);
                            addConsoleLog('Update session created: ' + sessionId, 'success');
                            addConsoleLog('Starting progress monitoring...', 'info');
                            startProgressPolling();
                        } else {
                            addConsoleLog('Failed to start update: ' + (response.error || 'Unknown error'), 'error');
                            $('#update-status').removeClass('bg-warning').addClass('bg-danger').text('Failed to Start');
                        }
                    },
                    error: function(xhr) {
                        addConsoleLog('Error starting update: ' + xhr.statusText, 'error');
                        $('#update-status').removeClass('bg-warning').addClass('bg-danger').text('Error');
                        updateInProgress = false;
                        $('#confirm-update-btn').html('<i class="fas fa-play me-2"></i>Retry Update').prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endsection