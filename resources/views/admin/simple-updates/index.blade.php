@extends('layouts.admin')

@section('title')
    Panel Updates
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Panel Updates
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Keep your panel up to date with the latest features and security improvements.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.index') }}">Admin</a>
          </li>
          <li class="breadcrumb-item" aria-current="page">
            Updates
          </li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Update Information</h3>
            </div>
            <div class="block-content">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center bg-body-light rounded p-3 mb-3">
                            <div class="flex-shrink-0">
                                <i class="fas fa-code-branch fa-2x text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fs-sm text-muted">Current Version</div>
                                <div class="h4 fw-bold mb-0">{{ $current_version }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center bg-body-light rounded p-3 mb-3">
                            <div class="flex-shrink-0">
                                <i class="fas fa-{{ $update_info['available'] ? 'arrow-up text-success' : 'check text-muted' }} fa-2x"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fs-sm text-muted">Latest Version</div>
                                <div class="h4 fw-bold mb-0">{{ $update_info['latest_version'] ?? 'Unknown' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($update_info['available'] ?? false)
                    <div class="alert alert-info d-flex" role="alert">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="alert-heading">Update Available!</h5>
                            A new version of the panel is available. Please review the release notes below before updating.
                        </div>
                    </div>

                    @if(!empty($update_info['release_notes']))
                        <div class="mt-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="mb-0">
                                    <i class="fas fa-scroll me-2 text-primary"></i>Release Notes
                                </h5>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="toggle-release-notes">
                                    <i class="fas fa-eye me-1"></i>Show Release Notes
                                </button>
                            </div>
                            
                            <div id="release-notes-content" class="github-document" style="display: none;">
                                <div class="github-document-header">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file-alt me-2 text-muted"></i>
                                            <span class="fw-medium">CHANGELOG.md</span>
                                        </div>
                                        <div class="text-muted small">
                                            <i class="fas fa-code-branch me-1"></i>{{ $update_info['latest_version'] ?? 'Latest' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="github-document-content">
                                    <div class="markdown-content">
                                        {!! nl2br(e($update_info['release_notes'])) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="alert alert-success d-flex" role="alert">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="alert-heading">Up to Date!</h5>
                            Your panel is running the latest version.
                        </div>
                    </div>
                @endif

                @if(isset($update_info['error']))
                    <div class="alert alert-danger d-flex" role="alert">
                        <div class="flex-shrink-0">
                            <i class="fas fa-ban"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="alert-heading">Error!</h5>
                            Failed to check for updates: {{ $update_info['error'] }}
                        </div>
                    </div>
                @endif

                <!-- Permission Notification Area -->
                <div id="permission-notice" class="alert alert-info d-none" role="alert">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="alert-heading">Permission Management</h5>
                            <p class="mb-2">The update system will automatically fix file permissions if needed during the update process.</p>
                            <p class="mb-0"><strong>Note:</strong> New files (like VersionService.php) will be created with proper permissions automatically.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="block-content block-content-full text-end bg-body-light">
                <button type="button" class="btn btn-sm btn-alt-secondary me-1" id="check-updates">
                    <i class="fas fa-sync-alt me-1"></i>Check for Updates
                </button>
                @if($update_info['available'] ?? false)
                    <button type="button" class="btn btn-sm btn-success" id="perform-update" data-version="{{ $update_info['latest_version'] }}">
                        <i class="fas fa-download me-1"></i>Update to {{ $update_info['latest_version'] }}
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Update Process</h3>
            </div>
            <div class="block-content">
                <p class="text-muted mb-3">The update process will:</p>
                <ol class="mb-3">
                    <li>Create a backup of critical files</li>
                    <li>Download the latest version</li>
                    <li>Replace application files</li>
                    <li>Update dependencies</li>
                    <li>Run database migrations</li>
                    <li>Clear and rebuild cache</li>
                </ol>
                
                <div class="alert alert-warning">
                    <h6 class="alert-heading mb-1"><i class="fas fa-exclamation-triangle me-1"></i>Important</h6>
                    The panel will be briefly unavailable during the update process.
                </div>
            </div>
        </div>

        <!-- Update Progress Section (Outside Block) -->
        <div id="update-log" style="display: none;" class="mt-3">
            <!-- Progress Bar -->
            <div class="progress mb-3" style="height: 1.5rem;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="update-progress" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                    <span class="fw-semibold">0% Complete</span>
                </div>
            </div>
            
            <!-- Terminal Console (Hidden - will be shown in modal) -->
            <div class="terminal-console d-none" id="terminal-preview">
                <div class="terminal-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-circle text-danger" style="font-size: 0.5rem;"></i>
                            <i class="fas fa-circle text-warning" style="font-size: 0.5rem;"></i>
                            <i class="fas fa-circle text-success" style="font-size: 0.5rem;"></i>
                            <span class="terminal-path ms-2">Terminal will open during update</span>
                        </div>
                    </div>
                </div>
                <div class="terminal-body">
                    <div class="terminal-line">
                        <span class="terminal-prompt">user@raptorpanel:~$</span>
                        <span class="terminal-text">Click "Install Update" to begin...</span>
                    </div>
                </div>
            </div>
        </div>
        
        </div>
    </div>
</div>



<!-- Update Confirmation Modal -->
<div class="modal fade" id="confirm-update-modal" tabindex="-1" role="dialog" aria-labelledby="confirm-update-modal" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fas fa-download me-2 text-primary"></i>Confirm Panel Update
                    </h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa fa-fw fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content fs-sm">
                    <div class="alert alert-info d-flex mb-4" role="alert">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="alert-heading">Update to version <strong id="update-version-confirm" class="text-primary"></strong></h6>
                            You are about to update your Raptor Panel installation to the latest version.
                        </div>
                    </div>
                    
                    <h6 class="fw-semibold mb-2">The update process will:</h6>
                    <ul class="mb-3">
                        <li><i class="fas fa-shield-alt me-2 text-success"></i>Create a backup of your current installation</li>
                        <li><i class="fas fa-cloud-download-alt me-2 text-info"></i>Download and verify the latest version</li>
                        <li><i class="fas fa-sync-alt me-2 text-warning"></i>Update application files and dependencies</li>
                        <li><i class="fas fa-database me-2 text-primary"></i>Run any required database migrations</li>
                        <li><i class="fas fa-broom me-2 text-secondary"></i>Clear and rebuild system cache</li>
                    </ul>
                    
                    <div class="alert alert-warning d-flex" role="alert">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="alert-heading mb-1">Important Notice</h6>
                            <p class="mb-1">• The panel will be briefly unavailable during the update</p>
                            <p class="mb-0">• Ensure you have a recent backup before proceeding</p>
                        </div>
                    </div>
                </div>
                <div class="block-content block-content-full text-end bg-body-light">
                    <button type="button" class="btn btn-sm btn-alt-secondary me-2" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-sm btn-success" id="confirm-update">
                        <i class="fas fa-download me-1"></i>Yes, Update Now
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- END Update Confirmation Modal -->

<!-- Full-Screen Terminal Modal -->
<div class="modal fade" id="terminal-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-dark">
            <!-- Blurred Background Overlay -->
            <div class="position-fixed w-100 h-100 top-0 start-0" style="backdrop-filter: blur(10px); background: rgba(0,0,0,0.8); z-index: -1;"></div>
            
            <!-- Terminal Header -->
            <div class="modal-header bg-dark text-light border-secondary">
                <div class="d-flex align-items-center w-100 justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-circle text-danger" style="font-size: 0.8rem;"></i>
                        <i class="fas fa-circle text-warning" style="font-size: 0.8rem;"></i>
                        <i class="fas fa-circle text-success" style="font-size: 0.8rem;"></i>
                        <h5 class="modal-title ms-3 mb-0">
                            <i class="fas fa-terminal me-2"></i>
                            Raptor Panel Update Console
                        </h5>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status" id="update-spinner">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <span class="text-muted" id="update-status">Update in Progress</span>
                        </div>
                        <div class="text-muted small" id="update-progress">0%</div>
                        <!-- Close button (disabled during update) -->
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="terminal-close" disabled>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Terminal Body -->
            <div class="modal-body bg-black text-light p-0" style="font-family: 'Courier New', monospace; overflow-y: auto; max-height: calc(100vh - 120px);">
                <div class="p-4" id="terminal-output">
                    <div class="terminal-line mb-2">
                        <span class="text-success">user@raptorpanel</span>
                        <span class="text-white">:</span>
                        <span class="text-primary">~</span>
                        <span class="text-white">$ </span>
                        <span class="text-warning" id="terminal-command">Starting update process...</span>
                    </div>
                </div>
            </div>
            
            <!-- Terminal Footer -->
            <div class="modal-footer bg-dark text-light border-secondary justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Update cannot be cancelled once started
                    </small>
                    <small class="text-muted" id="update-timer">
                        <i class="fas fa-clock me-1"></i>
                        <span id="elapsed-time">00:00</span>
                    </small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <small class="text-muted">Version:</small>
                    <span class="badge bg-primary" id="target-version">1.3.23</span>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- END Full-Screen Terminal Modal -->

<style>
    /* Full-Screen Terminal Modal Styles */
    #terminal-modal .modal-fullscreen {
        width: 100vw;
        height: 100vh;
        max-width: none;
        margin: 0;
    }
    
    #terminal-modal .modal-content {
        height: 100vh;
        border: none;
        border-radius: 0;
    }
    
    #terminal-output {
        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Courier New', monospace;
        font-size: 14px;
        line-height: 1.4;
        background: #000;
        color: #00ff00;
        padding: 20px;
        overflow-y: auto;
        max-height: calc(100vh - 200px);
        scrollbar-width: thin;
        scrollbar-color: #333 #000;
    }
    
    #terminal-output::-webkit-scrollbar {
        width: 8px;
    }
    
    #terminal-output::-webkit-scrollbar-track {
        background: #000;
    }
    
    #terminal-output::-webkit-scrollbar-thumb {
        background: #333;
        border-radius: 4px;
    }
    
    #terminal-output::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    
    .terminal-line {
        margin-bottom: 4px;
        word-wrap: break-word;
        animation: terminalTypewriter 0.1s ease-out;
    }
    
    @keyframes terminalTypewriter {
        from { opacity: 0; transform: translateY(2px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .text-success { color: #28a745 !important; }
    .text-danger { color: #dc3545 !important; }
    .text-warning { color: #ffc107 !important; }
    .text-info { color: #17a2b8 !important; }
    .text-muted { color: #666 !important; }
    
    /* Blinking cursor effect */
    .terminal-cursor {
        display: inline-block;
        width: 8px;
        height: 14px;
        background: #00ff00;
        animation: blink 1s infinite;
        margin-left: 2px;
    }
    
    @keyframes blink {
        0%, 50% { opacity: 1; }
        51%, 100% { opacity: 0; }
    }
    
    /* Modal backdrop enhancement */
    #terminal-modal.modal.show {
        backdrop-filter: blur(10px);
    }
    
    #terminal-modal .modal-backdrop {
        background: rgba(0, 0, 0, 0.9);
    }
    
    /* Terminal header enhancements */
    #terminal-modal .modal-header {
        border-bottom: 2px solid #333;
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d30 100%);
    }
    
    #terminal-modal .modal-footer {
        border-top: 2px solid #333;
        background: linear-gradient(135deg, #2d2d30 0%, #1a1a1a 100%);
    }
    
    /* Spinner customization */
    #update-spinner {
        border-color: #007bff transparent transparent transparent;
    }
    
    /* Progress indicator */
    #update-progress {
        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
        font-weight: bold;
        min-width: 40px;
        text-align: center;
    }
</style>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
            console.log('Update page JavaScript loaded');
            console.log('jQuery version:', $.fn.jquery);
            console.log('Update button exists:', $('#perform-update').length);
            console.log('Check button exists:', $('#check-updates').length);
            
            let updateInProgress = false;

            // Release Notes Toggle Functionality
            console.log('Setting up release notes toggle...');
            console.log('Toggle button exists:', $('#toggle-release-notes').length);
            console.log('Release notes content exists:', $('#release-notes-content').length);
            
            $('#toggle-release-notes').click(function() {
                console.log('Release notes toggle clicked!');
                const $button = $(this);
                const $content = $('#release-notes-content');
                
                console.log('Button element:', $button);
                console.log('Content element:', $content);
                console.log('Content is visible:', $content.is(':visible'));
                
                // Add loading state
                $button.prop('disabled', true);
                
                if ($content.is(':visible')) {
                    // Hide release notes
                    $content.slideUp(300, function() {
                        $button.html('<i class="fas fa-eye me-1"></i>Show Release Notes');
                        $button.removeClass('btn-outline-secondary').addClass('btn-outline-primary');
                        $button.prop('disabled', false);
                    });
                } else {
                    // Show release notes
                    $button.html('<i class="fas fa-spinner fa-spin me-1"></i>Loading...');
                    
                    // Simulate loading then show content
                    setTimeout(function() {
                        $button.html('<i class="fas fa-eye-slash me-1"></i>Hide Release Notes');
                        $button.removeClass('btn-outline-primary').addClass('btn-outline-secondary');
                        $content.slideDown(300, function() {
                            $button.prop('disabled', false);
                        });
                    }, 200);
                }
            });

            $('#check-updates').click(function() {
                console.log('Check updates clicked');
                const btn = $(this);
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Checking...');
                
                $.get('{{ route("admin.simple-updates.check") }}')
                    .done(function(data) {
                        // Show permission notice if update is available
                        if (data.available) {
                            $('#permission-notice').removeClass('d-none').hide().slideDown(300);
                        }
                        
                        // Reload to show updated information
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    })
                    .fail(function() {
                        alert('Failed to check for updates');
                    })
                    .always(function() {
                        btn.prop('disabled', false).html('<i class="fas fa-sync-alt me-1"></i>Check for Updates');
                    });
            });

            $('#perform-update').click(function() {
                console.log('🔧 Update button clicked!');
                console.log('📊 Update in progress:', updateInProgress);
                
                if (updateInProgress) {
                    console.log('❌ Update already in progress, ignoring click');
                    return;
                }
                
                const version = $(this).data('version');
                console.log('🎯 Version to update to:', version);
                
                // Show the proper modal instead of browser confirm
                $('#update-version-confirm').text(version);
                $('#confirm-update-modal').modal('show');
            });

            // Handle the actual update confirmation
            $('#confirm-update').click(function() {
                console.log('✅ User confirmed update via modal, proceeding...');
                
                const version = $('#update-version-confirm').text();
                console.log('🔑 CSRF token:', '{{ csrf_token() }}');
                console.log('🌐 Update URL:', '{{ route("admin.simple-updates.perform") }}');
                
                // Hide the confirmation modal
                $('#confirm-update-modal').modal('hide');
                
                // Show the full-screen terminal modal
                showTerminalModal(version);
            });

            // Full-screen terminal modal functionality
            function showTerminalModal(version) {
                // Initialize modal elements
                const $modal = $('#terminal-modal');
                const $output = $('#terminal-output');
                const $command = $('#terminal-command');
                const $status = $('#update-status');
                const $progress = $('#update-progress');
                const $targetVersion = $('#target-version');
                const $closeBtn = $('#terminal-close');
                const $spinner = $('#update-spinner');
                
                // Set up initial state
                $targetVersion.text(version);
                $command.text(`raptor-panel update --version=${version}`);
                $status.text('Update in Progress');
                $progress.text('0%');
                $closeBtn.prop('disabled', true);
                $spinner.show();
                
                // Clear terminal output and add initial command
                $output.html(`
                    <div class="terminal-line mb-2">
                        <span class="text-success">user@raptorpanel</span>
                        <span class="text-white">:</span>
                        <span class="text-primary">~</span>
                        <span class="text-white">$ </span>
                        <span class="text-warning">raptor-panel update --version=${version}</span>
                    </div>
                `);
                
                // Start timer
                let startTime = Date.now();
                const timerInterval = setInterval(() => {
                    const elapsed = Math.floor((Date.now() - startTime) / 1000);
                    const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
                    const seconds = (elapsed % 60).toString().padStart(2, '0');
                    $('#elapsed-time').text(`${minutes}:${seconds}`);
                }, 1000);
                
                // Show modal
                $modal.modal('show');
                
                // Start update process
                updateInProgress = true;
                
                // Add initial message
                addTerminalLine('🚀 Initializing update process...', 'info');
                
                // Simulate progress updates
                let progress = 0;
                const progressSteps = [
                    { msg: '🔧 Checking and fixing file permissions...', percent: 10 },
                    { msg: '📦 Downloading update package...', percent: 20 },
                    { msg: '🔍 Verifying download integrity...', percent: 30 },
                    { msg: '💾 Creating backup of current files...', percent: 40 },
                    { msg: '📂 Extracting update files...', percent: 55 },
                    { msg: '� Applying file updates...', percent: 70 },
                    { msg: '🗄️ Running database migrations...', percent: 85 },
                    { msg: '🧹 Cleaning up temporary files...', percent: 92 },
                    { msg: '✨ Finalizing update process...', percent: 95 }
                ];
                let stepIndex = 0;
                
                const progressInterval = setInterval(function() {
                    progress += Math.random() * 8 + 2;
                    if (progress > 90) progress = 90;
                    
                    $progress.text(Math.round(progress) + '%');
                    
                    // Show progress steps
                    if (stepIndex < progressSteps.length && progress >= progressSteps[stepIndex].percent - 5) {
                        addTerminalLine(progressSteps[stepIndex].msg, 'info');
                        stepIndex++;
                    }
                }, 1500);
                
                // Start actual update
                addTerminalLine('📡 Connecting to update server...', 'info');
                
                $.ajax({
                    url: '{{ route("admin.simple-updates.perform") }}',
                    type: 'POST',
                    data: {
                        version: version,
                        _token: '{{ csrf_token() }}'
                    },
                    timeout: 600000 // 10 minutes timeout (600,000ms)
                })
                .done(function(data) {
                    console.log('✅ Update request successful:', data);
                    clearInterval(progressInterval);
                    clearInterval(timerInterval);
                    
                    $progress.text('100%');
                    
                    if (data.success) {
                        addTerminalLine('✅ Update completed successfully!', 'success');
                        if (data.backup_path) {
                            addTerminalLine('💾 Backup created at: ' + data.backup_path, 'info');
                        }
                        addTerminalLine(`🎉 Successfully updated to version ${version}!`, 'success');
                        addTerminalLine('🔄 Reloading panel in 3 seconds...', 'warning');
                        
                        // Update status
                        $status.text('Update Complete');
                        $spinner.hide();
                        
                        // Show success notification and auto-close
                        setTimeout(function() {
                            $modal.modal('hide');
                            
                            // Show success alert
                            const $alert = $(`
                                <div class="alert alert-success alert-dismissible fade show position-fixed" 
                                     style="top: 20px; right: 20px; z-index: 9999; min-width: 350px;">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle fa-2x text-success me-3"></i>
                                        <div>
                                            <h6 class="alert-heading mb-1">Update Complete!</h6>
                                            <small>Successfully updated to version ${version}</small>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            `);
                            
                            $('body').append($alert);
                            
                            // Auto-dismiss alert after 5 seconds
                            setTimeout(() => $alert.alert('close'), 5000);
                            
                            // Reload page
                            setTimeout(() => location.reload(), 1000);
                        }, 3000);
                    } else {
                        console.error('❌ Update failed:', data.error);
                        addTerminalLine('❌ Update failed: ' + data.error, 'danger');
                        $status.text('Update Failed');
                        $spinner.hide();
                        $closeBtn.prop('disabled', false);
                    }
                })
                .fail(function(xhr, textStatus, errorThrown) {
                    console.error('❌ AJAX request failed:', xhr, textStatus, errorThrown);
                    clearInterval(progressInterval);
                    clearInterval(timerInterval);
                    
                    let errorMsg = 'Unknown error';
                    
                    // Handle timeout specifically
                    if (textStatus === 'timeout') {
                        errorMsg = 'Request timed out - Update may still be running in background. Please check the version after a few minutes.';
                        addTerminalLine('⚠️ Request timed out after 10 minutes', 'warning');
                        addTerminalLine('💡 The update process may still be running in the background', 'info');
                        addTerminalLine('🔄 Checking if update completed...', 'info');
                        
                        // Auto-check if update completed after timeout
                        setTimeout(function() {
                            location.reload(); // Refresh to check if version changed
                        }, 5000);
                    } else if (xhr.responseJSON?.error) {
                        errorMsg = xhr.responseJSON.error;
                    } else if (xhr.responseJSON?.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.status === 0) {
                        errorMsg = 'Network error - check console for details';
                    } else if (xhr.status === 504) {
                        errorMsg = 'Gateway timeout - Update may have completed in background';
                        addTerminalLine('⚠️ Gateway timeout detected', 'warning');
                        addTerminalLine('💡 Update may have completed successfully in the background', 'info');
                        addTerminalLine('🔄 Auto-refreshing to check version...', 'info');
                        
                        // Auto-refresh after 504 timeout
                        setTimeout(function() {
                            location.reload();
                        }, 3000);
                    } else {
                        errorMsg = `HTTP ${xhr.status}: ${xhr.statusText}`;
                    }
                    
                    addTerminalLine('❌ Update failed: ' + errorMsg, 'danger');
                    $status.text('Update Failed');
                    $progress.text('Error');
                    $spinner.hide();
                    $closeBtn.prop('disabled', false);
                })
                .always(function() {
                    console.log('🏁 Update request completed');
                    updateInProgress = false;
                });
            }

            // Helper function to add terminal lines
            function addTerminalLine(message, type = 'info') {
                const $output = $('#terminal-output');
                let textColor = 'text-light';
                let icon = '';
                
                switch(type) {
                    case 'success':
                        textColor = 'text-success';
                        icon = '✅ ';
                        break;
                    case 'danger':
                    case 'error':
                        textColor = 'text-danger';
                        icon = '❌ ';
                        break;
                    case 'warning':
                        textColor = 'text-warning';
                        icon = '⚠️ ';
                        break;
                    case 'info':
                    default:
                        textColor = 'text-info';
                        icon = '📄 ';
                        break;
                }
                
                const timestamp = new Date().toLocaleTimeString();
                const $line = $(`
                    <div class="terminal-line mb-1 ${textColor}">
                        <span class="text-muted small">[${timestamp}]</span>
                        <span class="ms-2">${icon}${message}</span>
                    </div>
                `);
                
                $output.append($line);
                
                // Auto-scroll to bottom
                const outputEl = $output[0];
                outputEl.scrollTop = outputEl.scrollHeight;
            }
        });
    </script>
@endsection