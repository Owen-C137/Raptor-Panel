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
                            <h5>Release Notes</h5>
                            <div class="bg-body-extra-light rounded p-3">
                                <pre style="white-space: pre-wrap; font-size: 0.875rem;">{{ $update_info['release_notes'] }}</pre>
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

        <div class="block block-rounded" id="update-log" style="display: none;">
            <div class="block-header block-header-default">
                <h3 class="block-title">Update Progress</h3>
            </div>
            <div class="block-content">
                <div class="progress mb-3" style="height: 1rem;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" id="update-progress" role="progressbar" style="width: 0%">
                        <span class="visually-hidden">0% Complete</span>
                    </div>
                </div>
                <div id="update-messages" style="max-height: 300px; overflow-y: auto; font-family: 'SFMono-Regular', Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; font-size: 0.8125rem; background: var(--bs-gray-100); padding: 0.75rem; border-radius: 0.375rem;">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirm-update-modal" tabindex="-1" aria-labelledby="confirmUpdateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmUpdateModalLabel">Confirm Update</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Are you sure you want to update to version <strong id="update-version-confirm" class="text-primary"></strong>?</p>
                <p class="mb-3">This will:</p>
                <ul class="mb-3">
                    <li>Create a backup of your current installation</li>
                    <li>Download and install the new version</li>
                    <li>Update the database if needed</li>
                    <li>Briefly make the panel unavailable</li>
                </ul>
                <div class="alert alert-warning">
                    <h6 class="alert-heading mb-1"><i class="fas fa-exclamation-triangle me-1"></i>Warning</h6>
                    Make sure you have a recent backup before proceeding.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-alt-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirm-update">
                    <i class="fas fa-download me-1"></i>Yes, Update Now
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
<script>
// Wait for jQuery to be available before running
(function checkJQueryUpdates() {
    if (typeof $ !== 'undefined') {
        $(document).ready(function() {
            console.log('Update page JavaScript loaded');
            console.log('jQuery version:', $.fn.jquery);
            console.log('Update button exists:', $('#perform-update').length);
            console.log('Check button exists:', $('#check-updates').length);
            
            let updateInProgress = false;

            $('#check-updates').click(function() {
                console.log('Check updates clicked');
                const btn = $(this);
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Checking...');
                
                $.get('{{ route("admin.simple-updates.check") }}')
                    .done(function(data) {
                        location.reload();
                    })
                    .fail(function() {
                        alert('Failed to check for updates');
                    })
                    .always(function() {
                        btn.prop('disabled', false).html('<i class="fas fa-sync-alt me-1"></i>Check for Updates');
                    });
            });

            $('#perform-update').click(function() {
                console.log('Update button clicked!');
                if (updateInProgress) return;
                
                const version = $(this).data('version');
                console.log('Version to update to:', version);
                
                // Simple confirmation dialog as backup
                if (!confirm(`Are you sure you want to update to version ${version}?`)) {
                    return;
                }
                
                // Proceed with update directly for now (bypass modal)
                updateInProgress = true;
                $('#update-log').show();
                
                const progressBar = $('#update-progress');
                const messages = $('#update-messages');
                
                // Add initial message
                messages.append('Starting update process...\n');
                
                // Simulate progress updates
                let progress = 0;
                const progressInterval = setInterval(function() {
                    progress += Math.random() * 20;
                    if (progress > 90) progress = 90;
                    
                    progressBar.css('width', progress + '%').attr('aria-valuenow', progress);
                }, 1000);
                
                $.post('{{ route("admin.simple-updates.perform") }}', {
                    version: version,
                    _token: '{{ csrf_token() }}'
                })
                .done(function(data) {
                    clearInterval(progressInterval);
                    progressBar.css('width', '100%').attr('aria-valuenow', 100).removeClass('progress-bar-animated');
                    
                    if (data.success) {
                        messages.append('✓ Update completed successfully!\n');
                        messages.append('Backup created at: ' + data.backup_path + '\n');
                        
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        messages.append('✗ Update failed: ' + data.error + '\n');
                        progressBar.addClass('bg-danger');
                    }
                })
                .fail(function(xhr) {
                    clearInterval(progressInterval);
                    progressBar.css('width', '100%').attr('aria-valuenow', 100).addClass('bg-danger').removeClass('progress-bar-animated');
                    messages.append('✗ Update failed: ' + (xhr.responseJSON?.error || 'Unknown error') + '\n');
                })
                .always(function() {
                    updateInProgress = false;
                });
            });
        });
    } else {
        // Retry in 100ms
        setTimeout(checkJQueryUpdates, 100);
    }
})();
</script>
@endsection