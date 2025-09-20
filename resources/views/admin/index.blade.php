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
                    <button type="button" class="btn-block-option" id="clear-cache" title="Clear All Cache">
                        <i class="si si-trash"></i>
                    </button>
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
<div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="updateModal" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fas fa-download me-2"></i>Raptor Panel Update Available
                    </h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa fa-fw fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content fs-sm">
                    <div id="update-details-content">
                        <!-- Update details will be loaded here -->
                    </div>
                </div>
                <div class="block-content block-content-full text-end bg-body">
                    <button type="button" class="btn btn-sm btn-alt-secondary me-1" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-primary" id="apply-update-btn">
                        <i class="fas fa-download me-1"></i>Update Now
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Changelog Section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="block block-rounded block-themed" id="changelog-block">
            <div class="block-header">
                <h3 class="block-title">
                    <i class="fas fa-clipboard-list me-2"></i>Current Version Changelog
                </h3>
                <div class="block-options">
                    <button type="button" class="btn-block-option" id="show-all-changelogs" title="Show all changelogs">
                        <i class="fas fa-list"></i>
                    </button>
                    <button type="button" class="btn-block-option" id="refresh-changelog" title="Refresh changelog">
                        <i class="si si-refresh"></i>
                    </button>
                    <a href="https://github.com/Owen-C137/Raptor-Panel/blob/main/CHANGELOG.md" target="_blank" class="btn-block-option" title="View full changelog">
                        <i class="fab fa-github"></i>
                    </a>
                </div>
            </div>
            <div class="block-content" id="changelog-content">
                <div class="d-flex justify-content-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading changelog...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Progress Modal -->
<div class="modal fade" id="updateProgressModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fas fa-cogs me-2"></i>Updating Raptor Panel
                    </h3>
                </div>
                <div class="block-content fs-sm">
                    <div class="progress mb-3" style="height: 20px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" id="update-progress-bar" style="width: 0%"></div>
                    </div>
                    <p id="update-status-text" class="mb-0 text-center fw-medium">Preparing update...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-6 col-sm-3 text-center">
        <a href="https://discord.gg/GEH2Fc5sgK" class="btn btn-warning w-100 mb-2">
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
        <a href="https://ko-fi.com/owenc137" class="btn btn-success w-100 mb-2">
            <i class="fas fa-heart me-1"></i> Support Me Here!
        </a>
    </div>
</div>
@endsection

@section('footer-scripts')
@parent
<script>
// Wait for jQuery to be available
function initializeAdminPage() {
    // Wait for OneUI and Bootstrap to be ready
    setTimeout(function() {
        initializeUpdateSystem();
    }, 100);

    function initializeUpdateSystem() {
        const updateBlock = document.getElementById('raptor-panel-update-block');
        const updateContent = document.getElementById('update-content');
        const updateBlockHeader = document.getElementById('update-block-header');
        const refreshBtn = document.getElementById('refresh-update-check');
        
        // Use jQuery for Bootstrap modal initialization to ensure compatibility
        let updateModal, updateProgressModal;
        
        try {
            // Initialize modals using OneUI/Bootstrap
            if (typeof bootstrap !== 'undefined') {
                updateModal = new bootstrap.Modal(document.getElementById('updateModal'));
                updateProgressModal = new bootstrap.Modal(document.getElementById('updateProgressModal'));
            } else if (typeof $ !== 'undefined') {
                // Fallback to jQuery modal
                updateModal = $('#updateModal');
                updateProgressModal = $('#updateProgressModal');
            }
        } catch (e) {
            console.error('Modal initialization failed:', e);
            return;
        }

        // Helper functions for modal operations
        function showModal(modal) {
            if (modal && typeof modal.show === 'function') {
                modal.show();
            } else if (modal && typeof modal.modal === 'function') {
                modal.modal('show');
            }
        }

        function hideModal(modal) {
            if (modal && typeof modal.hide === 'function') {
                modal.hide();
            } else if (modal && typeof modal.modal === 'function') {
                modal.modal('hide');
            }
        }

        // Check for updates on page load
        checkForUpdates();

        // Refresh button click handler
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                refreshBtn.querySelector('i').classList.add('fa-spin');
                checkForUpdates(true);
            });
        }

        // Clear cache button handler
        const clearCacheBtn = document.getElementById('clear-cache');
        if (clearCacheBtn) {
            clearCacheBtn.addEventListener('click', function() {
                const icon = clearCacheBtn.querySelector('i');
                icon.classList.add('fa-spin');
                
                fetch('{{ route('admin.updates.cache.clear') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showStatusAlert(false, 'Cache cleared', data.message, 'success');
                        // Auto refresh update check after cache clear
                        setTimeout(() => {
                            checkForUpdates(true);
                        }, 500);
                    } else {
                        showStatusAlert(false, 'Cache clear failed', data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Cache clear error:', error);
                    showStatusAlert(false, 'Error', 'Failed to clear cache', 'danger');
                })
                .finally(() => {
                    icon.classList.remove('fa-spin');
                });
            });
        }

        function checkForUpdates(force = false) {
            const url = '{{ route('admin.updates.check') }}' + (force ? '?force=true' : '');
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                updateUpdateBlock(data);
                refreshBtn.querySelector('i').classList.remove('fa-spin');
                
                // Show status alert when manually refreshed
                if (force) {
                    showStatusAlert(data.update_available, data.current_version, data.latest_version);
                }
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

    function showStatusAlert(updateAvailable, currentVersion, latestVersion, customType = null) {
        // Remove any existing status alerts
        const existingAlert = document.querySelector('.status-alert');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        const alert = document.createElement('div');
        alert.className = 'alert alert-dismissible fade show status-alert';
        
        // Handle custom alert types (for cache clear, etc.)
        if (customType) {
            alert.classList.add(`alert-${customType}`);
            alert.innerHTML = `
                <i class="fas fa-${customType === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                <strong>${currentVersion}!</strong> ${latestVersion}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
        } else if (updateAvailable) {
            alert.classList.add('alert-warning');
            alert.innerHTML = `
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Update Available!</strong> Version ${latestVersion} is available. You're currently running ${currentVersion}.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
        } else {
            alert.classList.add('alert-success');
            alert.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>
                <strong>Up to Date!</strong> You're running the latest version (${currentVersion}).
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
        }
        
        // Find the content container and first row to insert before
        const contentContainer = document.querySelector('.content.content-full');
        const firstRow = contentContainer ? contentContainer.querySelector('.row') : null;
        
        if (contentContainer && firstRow) {
            contentContainer.insertBefore(alert, firstRow);
        } else {
            // Fallback: append to content container or body
            const target = contentContainer || document.querySelector('.content') || document.body;
            target.insertBefore(alert, target.firstChild);
        }
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (alert && alert.parentNode) {
                alert.remove();
            }
        }, 5000);
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

        // Show modal using compatible method
        showModal(updateModal);

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
                const changedFiles = data.changed_files || [];

                let changelogHtml = '';
                if (changelog.added && changelog.added.length > 0) {
                    changelogHtml += `
                        <h6><i class="fas fa-plus-circle text-primary me-1"></i>New Features</h6>
                        <ul class="list-unstyled mb-3">
                            ${changelog.added.map(feature => `<li class="mb-1"><i class="fas fa-check text-success me-2"></i>${feature}</li>`).join('')}
                        </ul>
                    `;
                }

                if (changelog.fixed && changelog.fixed.length > 0) {
                    changelogHtml += `
                        <h6><i class="fas fa-bug text-danger me-1"></i>Bug Fixes</h6>
                        <ul class="list-unstyled mb-3">
                            ${changelog.fixed.map(fix => `<li class="mb-1"><i class="fas fa-check text-success me-2"></i>${fix}</li>`).join('')}
                        </ul>
                    `;
                }

                if (changelog.changed && changelog.changed.length > 0) {
                    changelogHtml += `
                        <h6><i class="fas fa-edit text-info me-1"></i>Changes</h6>
                        <ul class="list-unstyled mb-3">
                            ${changelog.changed.map(change => `<li class="mb-1"><i class="fas fa-check text-success me-2"></i>${change}</li>`).join('')}
                        </ul>
                    `;
                }

                // Generate file details
                let fileDetailsHtml = '';
                if (changedFiles.length > 0) {
                    const filesByCategory = {};
                    changedFiles.forEach(file => {
                        const category = file.category;
                        if (!filesByCategory[category]) {
                            filesByCategory[category] = [];
                        }
                        filesByCategory[category].push(file);
                    });

                    fileDetailsHtml = `
                        <div class="mt-4">
                            <h6><i class="fas fa-file-code text-info me-1"></i>Files to be Updated (${filesCount})</h6>
                            <div class="accordion" id="fileAccordion">
                    `;

                    Object.keys(filesByCategory).forEach((category, index) => {
                        const files = filesByCategory[category];
                        fileDetailsHtml += `
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading${index}">
                                    <button class="accordion-button ${index === 0 ? '' : 'collapsed'}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${index}">
                                        <strong>${category}</strong>
                                        <span class="badge bg-primary ms-2">${files.length}</span>
                                    </button>
                                </h2>
                                <div id="collapse${index}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" data-bs-parent="#fileAccordion">
                                    <div class="accordion-body">
                                        ${files.map(file => `
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="badge bg-${file.type === 'new' ? 'success' : 'warning'} me-2">
                                                    ${file.type === 'new' ? 'NEW' : 'MOD'}
                                                </span>
                                                <code class="flex-grow-1 fs-sm">${file.path}</code>
                                                ${file.size ? `<small class="text-muted ms-2">${Math.round(file.size / 1024)}KB</small>` : ''}
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    fileDetailsHtml += `</div></div>`;
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
                            <small class="text-muted">${filesCount > 0 ? filesCount + ' files will be updated' : 'System files will be updated as needed'}</small>
                        </div>
                    </div>
                    
                    ${changelogHtml || '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No detailed changelog available for this version.</div>'}
                    
                    ${fileDetailsHtml}
                    
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
        hideModal(updateModal);
        applyUpdate();
    });

    function applyUpdate() {
        showModal(updateProgressModal);
        
        const progressBar = document.getElementById('update-progress-bar');
        const statusText = document.getElementById('update-status-text');
        
        progressBar.style.width = '10%';
        statusText.textContent = 'Creating backup...';

        fetch('{{ route('admin.updates.apply') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="_token"]').content
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
                hideModal(updateProgressModal);
                
                // Show success message with detailed report
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                
                const failureInfo = data.failed_files_count > 0 
                    ? `<br><small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>${data.failed_files_count} files failed to update</small>`
                    : '';
                
                alert.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Update Successful!</strong> Raptor Panel has been updated from ${data.old_version} to ${data.updated_version}.
                    <br><small class="mt-1 d-block">
                        <i class="fas fa-file-check me-1"></i>${data.updated_files_count} files updated successfully
                        ${failureInfo}
                        <br><i class="fas fa-clock me-1"></i>Completed at ${new Date(data.update_timestamp).toLocaleString()}
                    </small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                // Find the content container and first row to insert before
                const contentContainer = document.querySelector('.content.content-full');
                const firstRow = contentContainer ? contentContainer.querySelector('.row') : null;
                
                if (contentContainer && firstRow) {
                    contentContainer.insertBefore(alert, firstRow);
                } else {
                    // Fallback: append to content container or body
                    const target = contentContainer || document.querySelector('.content') || document.body;
                    target.insertBefore(alert, target.firstChild);
                }
                
                // Clear update cache and refresh the update check
                setTimeout(() => {
                    // Force refresh the update check to show new status
                    checkForUpdates(true);
                    
                    // Update the page header version if it exists
                    const versionElements = document.querySelectorAll('[data-version]');
                    versionElements.forEach(el => {
                        if (el.textContent.includes(data.current_version)) {
                            el.textContent = el.textContent.replace(data.current_version, data.updated_version);
                        }
                    });
                    
                    // Show success status alert
                    showStatusAlert(false, data.updated_version, data.updated_version);
                    
                }, 1000);
            }, 2000);
        })
        .catch(error => {
            console.error('Update failed:', error);
            statusText.innerHTML = `
                <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                Update failed: ${error.message}
            `;
            
            setTimeout(() => {
                hideModal(updateProgressModal);
            }, 3000);
        });
    }

    } // end initializeUpdateSystem function

    // Initialize changelog system
    initializeChangelogSystem();

    function initializeChangelogSystem() {
        const changelogContent = document.getElementById('changelog-content');
        const refreshChangelogBtn = document.getElementById('refresh-changelog');
        const showAllBtn = document.getElementById('show-all-changelogs');
        const blockTitle = document.querySelector('#changelog-block .block-title');
        
        let showingAll = false;
        let currentVersionData = null;
        let allVersionsData = null;

        // Load current version changelog on page load
        loadCurrentVersionChangelog();

        // Refresh button click handler
        if (refreshChangelogBtn) {
            refreshChangelogBtn.addEventListener('click', function() {
                refreshChangelogBtn.querySelector('i').classList.add('fa-spin');
                if (showingAll) {
                    loadAllChangelogs();
                } else {
                    loadCurrentVersionChangelog();
                }
            });
        }

        // Show all changelogs button handler
        if (showAllBtn) {
            showAllBtn.addEventListener('click', function() {
                if (showingAll) {
                    // Switch back to current version only
                    showingAll = false;
                    blockTitle.innerHTML = '<i class="fas fa-clipboard-list me-2"></i>Current Version Changelog';
                    showAllBtn.setAttribute('title', 'Show all changelogs');
                    showAllBtn.querySelector('i').className = 'fas fa-list';
                    displayCurrentVersionChangelog(currentVersionData);
                } else {
                    // Show all versions
                    showingAll = true;
                    blockTitle.innerHTML = '<i class="fas fa-clipboard-list me-2"></i>All Changelogs';
                    showAllBtn.setAttribute('title', 'Show current version only');
                    showAllBtn.querySelector('i').className = 'fas fa-eye';
                    showAllBtn.querySelector('i').classList.add('fa-spin');
                    loadAllChangelogs();
                }
            });
        }

        function loadCurrentVersionChangelog() {
            // Fetch changelog for current version only
            fetch('{{ route('admin.updates.changelog') }}')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        showChangelogError(data.error);
                        return;
                    }
                    currentVersionData = data;
                    displayCurrentVersionChangelog(data);
                    if (refreshChangelogBtn) {
                        refreshChangelogBtn.querySelector('i').classList.remove('fa-spin');
                    }
                })
                .catch(error => {
                    console.error('Current version changelog fetch failed:', error);
                    showChangelogError();
                    if (refreshChangelogBtn) {
                        refreshChangelogBtn.querySelector('i').classList.remove('fa-spin');
                    }
                });
        }

        function loadAllChangelogs() {
            // Fetch all versions from changelog
            fetch('{{ route('admin.updates.changelog') }}?all=true')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        showChangelogError(data.error);
                        return;
                    }
                    allVersionsData = data;
                    displayAllVersionsChangelog(data.versions || [], data.current_version);
                    if (refreshChangelogBtn) {
                        refreshChangelogBtn.querySelector('i').classList.remove('fa-spin');
                    }
                    if (showAllBtn) {
                        showAllBtn.querySelector('i').classList.remove('fa-spin');
                    }
                })
                .catch(error => {
                    console.error('All versions changelog fetch failed:', error);
                    showChangelogError();
                    if (refreshChangelogBtn) {
                        refreshChangelogBtn.querySelector('i').classList.remove('fa-spin');
                    }
                    if (showAllBtn) {
                        showAllBtn.querySelector('i').classList.remove('fa-spin');
                    }
                });
        }

        function displayCurrentVersionChangelog(data) {
            if (!data || !data.changelog) {
                showChangelogError('No changelog data found for current version.');
                return;
            }

            const version = data.version;
            const changelog = data.changelog;
            const versionDate = changelog.date || 'No date';
            const rawContent = changelog.raw || '';
            
            // Check if content is empty or just whitespace
            const hasContent = rawContent && rawContent.trim().length > 0;
            
            let changelogHtml = `
                <ul class="timeline timeline-alt">
                    <li class="timeline-event">
                        <div class="timeline-event-icon bg-success">
                            <i class="fas fa-tag"></i>
                        </div>
                        <div class="timeline-event-block block">
                            <div class="block-header block-header-default">
                                <h3 class="block-title">v${version} - Current Version</h3>
                                <div class="block-options">
                                    <div class="timeline-event-time block-options-item fs-sm fw-semibold">
                                        ${versionDate}
                                    </div>
                                </div>
                            </div>
                            <div class="block-content">
                                ${hasContent ? formatChangelogContent(rawContent) : '<div class="alert alert-warning d-flex align-items-center" role="alert"><div class="flex-grow-1"><i class="fas fa-exclamation-triangle me-2"></i>No changelog available for this version.</div></div>'}
                                <div class="mt-3">
                                    <a href="https://github.com/Owen-C137/Raptor-Panel" target="_blank" class="btn btn-sm btn-alt-secondary">
                                        <i class="fab fa-github me-1"></i>View on GitHub
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            `;

            changelogContent.innerHTML = changelogHtml;
        }

        function displayAllVersionsChangelog(versions, currentVersion) {
            if (!versions || versions.length === 0) {
                showChangelogError('No changelog versions found.');
                return;
            }

            let changelogHtml = '<ul class="timeline timeline-alt">';
            
            versions.forEach((version, index) => {
                const isCurrentVersion = version.version === currentVersion;
                const versionDate = version.date || 'No date';
                const versionTitle = version.title || '';
                
                // Determine icon color based on version type
                let iconColor = 'bg-info';
                if (isCurrentVersion) {
                    iconColor = 'bg-success';
                } else if (index === 0) {
                    iconColor = 'bg-warning';
                } else if (version.version.includes('beta') || version.version.includes('alpha')) {
                    iconColor = 'bg-danger';
                } else {
                    iconColor = 'bg-smooth';
                }
                
                changelogHtml += `
                    <li class="timeline-event">
                        <div class="timeline-event-icon ${iconColor}">
                            <i class="fas fa-tag"></i>
                        </div>
                        <div class="timeline-event-block block">
                            <div class="block-header block-header-default">
                                <h3 class="block-title">
                                    v${version.version}${versionTitle ? ' - ' + versionTitle : ''}
                                    ${isCurrentVersion ? '<span class="badge bg-success ms-2">Current</span>' : ''}
                                </h3>
                                <div class="block-options">
                                    <div class="timeline-event-time block-options-item fs-sm fw-semibold">
                                        ${versionDate}
                                    </div>
                                </div>
                            </div>
                            <div class="block-content">
                                ${formatChangelogContent(version.content)}
                                <div class="mt-3">
                                    <a href="https://github.com/Owen-C137/Raptor-Panel" target="_blank" class="btn btn-sm btn-alt-secondary">
                                        <i class="fab fa-github me-1"></i>View on GitHub
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                `;
            });
            
            changelogHtml += '</ul>';

            changelogContent.innerHTML = changelogHtml;
        }

        function formatChangelogContent(content) {
            if (!content || content.trim().length === 0) {
                return '<div class="alert alert-warning d-flex align-items-center" role="alert"><div class="flex-grow-1"><i class="fas fa-exclamation-triangle me-2"></i>No changelog available for this version.</div></div>';
            }
            
            // Parse the content to extract sections and items
            const lines = content.split('\n').filter(line => line.trim().length > 0);
            let formatted = '';
            let currentSection = '';
            let currentItems = [];
            
            for (let line of lines) {
                line = line.trim();
                
                // Check for section headers (###, ##, #)
                if (line.match(/^#{1,3}\s+(.+)/)) {
                    // Save previous section if it exists
                    if (currentSection && currentItems.length > 0) {
                        formatted += formatSection(currentSection, currentItems);
                        currentItems = [];
                    }
                    
                    const headerMatch = line.match(/^#{1,3}\s+(.+)/);
                    currentSection = headerMatch[1];
                }
                // Check for list items (-, *, •)
                else if (line.match(/^[-*•]\s+(.+)/)) {
                    const itemMatch = line.match(/^[-*•]\s+(.+)/);
                    if (itemMatch) {
                        // Clean up the item text
                        let itemText = itemMatch[1]
                            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                            .replace(/\*(.*?)\*/g, '<em>$1</em>')
                            .replace(/💰|🧭|🔧|📱|🎨/g, ''); // Remove emojis
                        currentItems.push(itemText);
                    }
                }
                // Handle version headers like "v1.1.0 - 2025-09-19"
                else if (line.match(/^v?\d+\.\d+\.\d+.*-.*\d{4}-\d{2}-\d{2}/)) {
                    // Skip version headers as they're already shown in the timeline
                    continue;
                }
                // Handle other content
                else if (line.length > 0) {
                    if (!currentSection) {
                        currentSection = 'Changes';
                    }
                    currentItems.push(line);
                }
            }
            
            // Add the last section
            if (currentSection && currentItems.length > 0) {
                formatted += formatSection(currentSection, currentItems);
            }
            
            // If no sections were found, treat all content as a single section
            if (!formatted && content.trim()) {
                const items = content.split('\n')
                    .filter(line => line.trim().length > 0)
                    .map(line => line.replace(/^[-*•]\s*/, '').trim())
                    .filter(line => line.length > 0);
                
                if (items.length > 0) {
                    formatted = formatSection('Changes', items);
                }
            }
            
            return formatted || '<div class="alert alert-info d-flex align-items-center" role="alert"><div class="flex-grow-1"><i class="fas fa-info-circle me-2"></i>No detailed changelog information available.</div></div>';
        }
        
        function formatSection(sectionName, items) {
            if (!items || items.length === 0) return '';
            
            let html = `<div class="mb-3">`;
            
            // Add section header if it's not a generic "Changes" section
            if (sectionName && sectionName !== 'Changes') {
                html += `<h6 class="text-primary mb-2">${sectionName}</h6>`;
            }
            
            // Use OneUI list group for items
            html += `<ul class="list-group push">`;
            
            items.forEach(item => {
                // Clean and format each item
                const cleanItem = item
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.*?)\*/g, '<em>$1</em>')
                    .replace(/💰|🧭|🔧|📱|🎨/g, '') // Remove emojis
                    .trim();
                
                if (cleanItem.length > 0) {
                    html += `<li class="list-group-item">${cleanItem}</li>`;
                }
            });
            
            html += `</ul></div>`;
            
            return html;
        }

        function showChangelogError(message = 'Failed to load changelog. Please try again later.') {
            changelogContent.innerHTML = `
                <div class="text-center py-3">
                    <div class="text-danger mb-2">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                    <p class="text-muted mb-0">${message}</p>
                </div>
            `;
        }
    }
}

// Initialize when DOM is ready and jQuery is available
if (typeof $ !== 'undefined') {
    $(document).ready(initializeAdminPage);
} else {
    // Fallback to vanilla JS
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeAdminPage);
    } else {
        initializeAdminPage();
    }
}
</script>
@endsection
