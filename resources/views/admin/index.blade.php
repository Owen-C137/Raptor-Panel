@extends('layouts.admin')

@section('title')
    Administration
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Administrative Overview
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          A quick glance at your system status and key metrics.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.index') }}">Admin</a>
          </li>
          <li class="breadcrumb-item" aria-current="page">
            Overview
          </li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="block block-rounded block-themed" id="raptor-panel-update-block">
            <div class="block-header" id="update-block-header">
                <h3 class="block-title">
                    <i class="fas fa-rocket me-2"></i>Raptor Panel Information
                </h3>
                <div class="block-options">
                    <button type="button" class="btn-block-option" id="refresh-update-check" title="Check for updates">
                        <i class="si si-refresh"></i>
                    </button>
                    <a href="{{ route('admin.settings') }}" class="btn-block-option" title="Settings">
                        <i class="si si-settings"></i>
                    </a>
                </div>
            </div>
            <div class="block-content" id="update-content">
                <div class="d-flex justify-content-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Checking for updates...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Available Modal -->
<div class="modal fade" id="updateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-download me-2"></i>Raptor Panel Update Available
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="update-details-content">
                    <!-- Update details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="apply-update-btn">
                    <i class="fas fa-download me-1"></i>Update Now
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Update Progress Modal -->
<div class="modal fade" id="updateProgressModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cogs me-2"></i>Updating Raptor Panel
                </h5>
            </div>
            <div class="modal-body">
                <div class="progress mb-3">
                    <div class="progress-bar" id="update-progress-bar" style="width: 0%"></div>
                </div>
                <p id="update-status-text" class="mb-0 text-center">Preparing update...</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-6 col-sm-3 text-center">
        <a href="https://discord.gg/raptorpanel" class="btn btn-warning w-100 mb-2">
            <i class="fab fa-discord me-1"></i> Support <small>(Discord)</small>
        </a>
    </div>
    <div class="col-6 col-sm-3 text-center">
        <a href="https://github.com/Owen-C137/Raptor-Panel/wiki" class="btn btn-primary w-100 mb-2">
            <i class="fas fa-book me-1"></i> Documentation
        </a>
    </div>
    <div class="col-6 col-sm-3 text-center">
        <a href="https://github.com/Owen-C137/Raptor-Panel" class="btn btn-primary w-100 mb-2">
            <i class="fab fa-github me-1"></i> Github
        </a>
    </div>
    <div class="col-6 col-sm-3 text-center">
        <a href="https://github.com/sponsors/Owen-C137" class="btn btn-success w-100 mb-2">
            <i class="fas fa-heart me-1"></i> Sponsor Project
        </a>
    </div>
</div>
@endsection

@section('footer-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const updateBlock = document.getElementById('raptor-panel-update-block');
    const updateContent = document.getElementById('update-content');
    const updateBlockHeader = document.getElementById('update-block-header');
    const refreshBtn = document.getElementById('refresh-update-check');
    const updateModal = new bootstrap.Modal(document.getElementById('updateModal'));
    const updateProgressModal = new bootstrap.Modal(document.getElementById('updateProgressModal'));

    // Check for updates on page load
    checkForUpdates();

    // Refresh button click handler
    refreshBtn.addEventListener('click', function() {
        refreshBtn.querySelector('i').classList.add('fa-spin');
        checkForUpdates(true);
    });

    function checkForUpdates(force = false) {
        const url = '{{ route('admin.updates.check') }}' + (force ? '?force=true' : '');
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                updateUpdateBlock(data);
                refreshBtn.querySelector('i').classList.remove('fa-spin');
            })
            .catch(error => {
                console.error('Update check failed:', error);
                showUpdateError('Failed to check for updates. Please try again later.');
                refreshBtn.querySelector('i').classList.remove('fa-spin');
            });
    }

    function updateUpdateBlock(data) {
        if (data.error) {
            showUpdateError(data.error);
            return;
        }

        const currentVersion = data.current_version;
        const latestVersion = data.latest_version;
        const updateAvailable = data.update_available;

        // Update header color
        updateBlockHeader.className = updateAvailable ? 
            'block-header bg-warning text-dark' : 
            'block-header bg-success';

        // Update content
        if (updateAvailable) {
            const featuresCount = data.features_count || 0;
            const fixesCount = data.fixes_count || 0;
            
            updateContent.innerHTML = `
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h6 class="mb-1">
                            <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                            Update Available!
                        </h6>
                        <p class="mb-0 text-muted">
                            Version ${latestVersion} is now available (you're running ${currentVersion})
                        </p>
                    </div>
                    <button class="btn btn-warning" id="view-update-btn">
                        <i class="fas fa-eye me-1"></i>View Update
                    </button>
                </div>
                <div class="row text-center">
                    <div class="col-4">
                        <div class="fs-sm text-muted">New Features</div>
                        <div class="fs-lg fw-bold text-primary">${featuresCount}</div>
                    </div>
                    <div class="col-4">
                        <div class="fs-sm text-muted">Bug Fixes</div>
                        <div class="fs-lg fw-bold text-success">${fixesCount}</div>
                    </div>
                    <div class="col-4">
                        <div class="fs-sm text-muted">Version</div>
                        <div class="fs-lg fw-bold text-info">${latestVersion}</div>
                    </div>
                </div>
            `;

            // Add click handler for view update button
            document.getElementById('view-update-btn').addEventListener('click', function() {
                showUpdateModal(latestVersion);
            });
        } else {
            updateContent.innerHTML = `
                <div class="text-center">
                    <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                    <h6 class="mb-1">Raptor Panel is Up to Date!</h6>
                    <p class="mb-0 text-muted">
                        You are running version <code>${currentVersion}</code>. 
                        Your panel is up-to-date!
                    </p>
                </div>
            `;
        }
    }

    function showUpdateError(errorMessage) {
        updateBlockHeader.className = 'block-header bg-danger';
        updateContent.innerHTML = `
            <div class="text-center">
                <i class="fas fa-exclamation-triangle text-danger fa-2x mb-2"></i>
                <h6 class="mb-1">Update Check Failed</h6>
                <p class="mb-0 text-muted">${errorMessage}</p>
            </div>
        `;
    }

    function showUpdateModal(version) {
        const detailsContent = document.getElementById('update-details-content');
        
        // Show loading state
        detailsContent.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading update details...</span>
                </div>
            </div>
        `;

        updateModal.show();

        // Load update details
        fetch('{{ route('admin.updates.details') }}?version=' + encodeURIComponent(version))
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    detailsContent.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${data.error}
                        </div>
                    `;
                    return;
                }

                const changelog = data.changelog || {};
                const filesCount = data.files_count || 0;

                let changelogHtml = '';
                if (changelog.features && changelog.features.length > 0) {
                    changelogHtml += `
                        <h6><i class="fas fa-plus-circle text-primary me-1"></i>New Features</h6>
                        <ul class="list-unstyled mb-3">
                            ${changelog.features.map(feature => `<li class="mb-1"><i class="fas fa-check text-success me-2"></i>${feature}</li>`).join('')}
                        </ul>
                    `;
                }

                if (changelog.fixes && changelog.fixes.length > 0) {
                    changelogHtml += `
                        <h6><i class="fas fa-bug text-danger me-1"></i>Bug Fixes</h6>
                        <ul class="list-unstyled mb-3">
                            ${changelog.fixes.map(fix => `<li class="mb-1"><i class="fas fa-check text-success me-2"></i>${fix}</li>`).join('')}
                        </ul>
                    `;
                }

                if (changelog.changes && changelog.changes.length > 0) {
                    changelogHtml += `
                        <h6><i class="fas fa-edit text-info me-1"></i>Changes</h6>
                        <ul class="list-unstyled mb-3">
                            ${changelog.changes.map(change => `<li class="mb-1"><i class="fas fa-check text-success me-2"></i>${change}</li>`).join('')}
                        </ul>
                    `;
                }

                detailsContent.innerHTML = `
                    <div class="mb-4">
                        <h6>Update Summary</h6>
                        <div class="row">
                            <div class="col-6">
                                <div class="text-center p-2 bg-light rounded">
                                    <div class="fs-lg fw-bold text-primary">${data.current_version}</div>
                                    <div class="fs-sm text-muted">Current</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-2 bg-primary text-white rounded">
                                    <div class="fs-lg fw-bold">${data.version}</div>
                                    <div class="fs-sm">Latest</div>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mt-2">
                            <small class="text-muted">${filesCount} files will be updated</small>
                        </div>
                    </div>
                    
                    ${changelogHtml || '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No detailed changelog available for this version.</div>'}
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-shield-alt me-2"></i>
                        <strong>Backup Notice:</strong> A backup will be created automatically before applying the update.
                        You can restore from this backup if needed.
                    </div>
                `;
            })
            .catch(error => {
                console.error('Failed to load update details:', error);
                detailsContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Failed to load update details. Please try again.
                    </div>
                `;
            });
    }

    // Apply update button handler
    document.getElementById('apply-update-btn').addEventListener('click', function() {
        updateModal.hide();
        applyUpdate();
    });

    function applyUpdate() {
        updateProgressModal.show();
        
        const progressBar = document.getElementById('update-progress-bar');
        const statusText = document.getElementById('update-status-text');
        
        progressBar.style.width = '10%';
        statusText.textContent = 'Creating backup...';

        fetch('{{ route('admin.updates.apply') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                backup: true
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }

            progressBar.style.width = '100%';
            statusText.innerHTML = `
                <i class="fas fa-check-circle text-success me-2"></i>
                Update completed successfully!
            `;

            setTimeout(() => {
                updateProgressModal.hide();
                
                // Show success message and reload
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Update Successful!</strong> Raptor Panel has been updated to version ${data.updated_version}.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.content').insertBefore(alert, document.querySelector('.row'));
                
                // Refresh the update check
                setTimeout(() => checkForUpdates(true), 1000);
            }, 2000);
        })
        .catch(error => {
            console.error('Update failed:', error);
            statusText.innerHTML = `
                <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                Update failed: ${error.message}
            `;
            
            setTimeout(() => {
                updateProgressModal.hide();
            }, 3000);
        });
    }
});
</script>
@endsection
