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
            
            <!-- Terminal Console -->
            <div class="terminal-console">
                <div class="terminal-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-circle text-danger" style="font-size: 0.5rem;"></i>
                            <i class="fas fa-circle text-warning" style="font-size: 0.5rem;"></i>
                            <i class="fas fa-circle text-success" style="font-size: 0.5rem;"></i>
                            <span class="terminal-path ms-2">Raptor Panel Update Console</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="terminal-time">Update in Progress</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="update-minimize">
                                <i class="fa fa-minus"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="terminal-body" id="terminal-messages">
                    <div class="terminal-line">
                        <span class="terminal-prompt">user@raptorpanel:~$</span>
                        <span class="terminal-text">Starting update process...</span>
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
                
                // Start the update process
                updateInProgress = true;
                $('#update-log').show();
                
                // Initialize terminal display
                const progressBar = $('#update-progress');
                const messages = $('#terminal-messages');
                
                // Clear any existing content and show progress section
                messages.html('<div class="terminal-line"><span class="terminal-prompt">user@raptorpanel:~$</span><span class="terminal-text">raptor-panel update --version=' + version + '</span></div>');
                
                // Show the update progress section
                $('#update-log').slideDown(300);
                
                // Add initial progress message
                addTerminalMessage('🚀 Initializing update process...', 'info');
                
                // Simulate progress updates with realistic steps
                let progress = 0;
                const progressSteps = [
                    { msg: '�️ Checking and fixing file permissions...', percent: 10 },
                    { msg: '�📦 Downloading update package...', percent: 20 },
                    { msg: '🔍 Verifying download integrity...', percent: 30 },
                    { msg: '💾 Creating backup of current files...', percent: 40 },
                    { msg: '📂 Extracting update files...', percent: 55 },
                    { msg: '🔧 Applying file updates (includes new VersionService.php)...', percent: 70 },
                    { msg: '🗄️ Running database migrations...', percent: 85 },
                    { msg: '🧹 Cleaning up temporary files...', percent: 92 },
                    { msg: '✨ Finalizing update process...', percent: 95 }
                ];
                let stepIndex = 0;
                
                const progressInterval = setInterval(function() {
                    progress += Math.random() * 8 + 2; // 2-10% increments
                    if (progress > 90) progress = 90;
                    
                    progressBar.css('width', progress + '%').attr('aria-valuenow', progress);
                    progressBar.find('span').text(Math.round(progress) + '% Complete');
                    
                    // Show progress steps at appropriate intervals
                    if (stepIndex < progressSteps.length && progress >= progressSteps[stepIndex].percent - 5) {
                        addTerminalMessage(progressSteps[stepIndex].msg, 'info');
                        stepIndex++;
                    }
                }, 1500);
                
                console.log('🚀 Starting AJAX request to perform update...');
                addTerminalMessage('📡 Connecting to update server...', 'info');
                
                $.post('{{ route("admin.simple-updates.perform") }}', {
                    version: version,
                    _token: '{{ csrf_token() }}'
                })
                .done(function(data) {
                    console.log('✅ Update request successful:', data);
                    clearInterval(progressInterval);
                    progressBar.css('width', '100%').attr('aria-valuenow', 100).removeClass('progress-bar-animated');
                    progressBar.find('span').text('100% Complete');
                    
                    if (data.success) {
                        addTerminalMessage('✅ Update completed successfully!', 'success');
                        if (data.backup_path) {
                            addTerminalMessage('💾 Backup created at: ' + data.backup_path, 'info');
                        }
                        addTerminalMessage('🔄 Reloading panel in 3 seconds...', 'warning');
                        
                        setTimeout(function() {
                            console.log('🔄 Reloading page...');
                            location.reload();
                        }, 3000);
                    } else {
                        console.error('❌ Update failed:', data.error);
                        addTerminalMessage('❌ Update failed: ' + data.error, 'danger');
                        progressBar.removeClass('bg-primary').addClass('bg-danger');
                    }
                })
                .fail(function(xhr) {
                    console.error('❌ AJAX request failed:', xhr);
                    console.error('Response status:', xhr.status);
                    console.error('Response text:', xhr.responseText);
                    console.error('Response JSON:', xhr.responseJSON);
                    
                    clearInterval(progressInterval);
                    progressBar.css('width', '100%').attr('aria-valuenow', 100).removeClass('bg-primary').addClass('bg-danger').removeClass('progress-bar-animated');
                    progressBar.find('span').text('Update Failed');
                    
                    let errorMsg = 'Unknown error';
                    if (xhr.responseJSON?.error) {
                        errorMsg = xhr.responseJSON.error;
                    } else if (xhr.responseJSON?.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.status === 0) {
                        errorMsg = 'Network error - check console for details';
                    } else {
                        errorMsg = `HTTP ${xhr.status}: ${xhr.statusText}`;
                    }
                    
                    addTerminalMessage('❌ Update failed: ' + errorMsg, 'danger');
                })
                .always(function() {
                    console.log('🏁 Update request completed');
                    updateInProgress = false;
                });
            });
        });
        
        // Terminal message helper function
        function addTerminalMessage(message, type = 'info') {
            const logTypes = {
                'info': 'log-info',
                'start': 'log-start',
                'progress': 'log-progress',
                'success': 'log-success', 
                'warning': 'log-warning',
                'danger': 'log-error',
                'error': 'log-error',
                'final': 'log-final'
            };
            
            const icons = {
                'info': '🔵',
                'start': '🚀',
                'progress': '⚡',
                'success': '✅', 
                'warning': '⚠️',
                'danger': '❌',
                'error': '❌',
                'final': '🎉'
            };
            
            const logType = logTypes[type] || 'log-info';
            const icon = icons[type] || 'ℹ️';
            const timestamp = new Date().toLocaleTimeString();
            const terminalMessages = $('#terminal-messages');
            
            terminalMessages.append(
                '<div class="terminal-line ' + logType + '">' +
                '<span class="terminal-timestamp">' + timestamp + '</span>' +
                '<span class="terminal-icon">' + icon + '</span>' +
                '<span class="terminal-message">' + message + '</span>' +
                '</div>'
            );
            
            // Auto scroll to bottom
            const terminalBody = terminalMessages;
            terminalBody.scrollTop(terminalBody[0].scrollHeight);
        }
    </script>
@endsection