@extends('layouts.admin')
@include('partials/admin.updates.nav', ['activeTab' => 'manage'])

@section('title')
    Manage Updates
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Manage Updates
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Check for and manage Pterodactyl panel updates.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.index') }}">Admin</a>
          </li>
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.updates.dashboard') }}">Update System</a>
          </li>
          <li class="breadcrumb-item" aria-current="page">
            Manage Updates
          </li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
@yield('updates::nav')
<div class="row" id="update-management">
    <!-- Current Version & Latest Available Update -->
    <div class="col-md-8">
        <div class="block block-rounded block-themed">
            <div class="block-header bg-primary">
                <h3 class="block-title text-white">
                    <i class="fa fa-arrow-up me-1"></i> Latest Available Update
                </h3>
                <div class="block-options">
                    <button class="btn btn-sm btn-outline-light" id="refresh-updates-btn">
                        <i class="fa fa-refresh me-1"></i> Check Updates
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div class="alert alert-info d-flex align-items-center current-version-alert">
                    <div class="flex-shrink-0">
                        <i class="fa fa-info-circle fa-2x"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="alert-heading mb-2">Current Version</h4>
                        <p class="mb-0">
                            <strong>Version:</strong> {{ $configuration['current_version'] ?? 'Unknown' }}
                            <br>
                            <strong>Last Updated:</strong> {{ $configuration['last_updated'] ?? 'Never' }}
                        </p>
                    </div>
                </div>

                <div id="available-updates">
                    @if(count($availableUpdates) > 0)
                        @foreach($availableUpdates as $update)
                        @php 
                            $updateType = $update['type'] ?? 'patch';
                            $typeClass = match($updateType) {
                                'major' => 'warning',
                                'minor' => 'info', 
                                'patch' => 'success',
                                default => 'success'
                            };
                            $badgeClass = match($updateType) {
                                'major' => 'bg-warning',
                                'minor' => 'bg-info',
                                'patch' => 'bg-success', 
                                default => 'bg-success'
                            };
                        @endphp
                        <div class="alert alert-{{ $typeClass }} border-{{ $typeClass }}">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h4 class="alert-heading d-flex align-items-center">
                                        <i class="fa fa-tag me-2"></i>
                                        Version {{ $update['version'] }}
                                        <span class="badge {{ $badgeClass }} ms-2">
                                            {{ ucfirst($updateType) }} Update
                                        </span>
                                    </h4>
                                    <p class="mb-1"><strong>Released:</strong> {{ $update['release_date'] ?? 'Unknown' }}</p>
                                    @if(!empty($update['body']))
                                        <p class="mb-1"><strong>Description:</strong> {{ Str::limit(strip_tags($update['body']), 150) }}</p>
                                    @endif
                                </div>
                            </div>

                            
                            <div class="row">
                                <div class="col-sm-6">
                                    <h6 class="fw-semibold mb-2">Requirements:</h6>
                                    <ul class="list-unstyled">
                                        <li class="mb-1">
                                            <i class="fa fa-database text-primary me-2"></i> 
                                            Database migrations: <span class="fw-medium">{{ ($update['has_migrations'] ?? false) ? 'Yes' : 'No' }}</span>
                                        </li>
                                        <li class="mb-1">
                                            <i class="fa fa-file text-info me-2"></i> 
                                            File changes: <span class="fw-medium">{{ $update['file_changes'] ?? 0 }} files</span>
                                        </li>
                                        <li class="mb-1">
                                            <i class="fa fa-clock-o text-warning me-2"></i> 
                                            Est. duration: <span class="fw-medium">{{ $update['estimated_duration'] ?? 'Unknown' }}</span>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-sm-6 d-flex align-items-end justify-content-end">
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-success update-now-btn" data-version="{{ $update['version'] }}">
                                            <i class="fa fa-play me-1"></i> Update Now
                                        </button>
                                        <button class="btn btn-info schedule-update-btn" data-version="{{ $update['version'] }}">
                                            <i class="fa fa-calendar me-1"></i> Schedule
                                        </button>
                                        <button class="btn btn-outline-secondary preview-update-btn" data-version="{{ $update['version'] }}">
                                            <i class="fa fa-eye me-1"></i> Preview
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="alert alert-success d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fa fa-check-circle fa-2x"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h4 class="alert-heading mb-2">System Up to Date</h4>
                            <p class="mb-0">Your system is running the latest version. Only the newest available release is shown when updates are available.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Update Options & Quick Actions -->
    <div class="col-md-4">
        <div class="block block-rounded block-themed">
            <div class="block-header bg-info">
                <h3 class="block-title text-white">
                    <i class="fa fa-cogs me-1"></i> Update Options
                </h3>
            </div>
            <div class="block-content">
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="force-update">
                        <label class="form-check-label fw-medium" for="force-update">
                            Force Update
                        </label>
                    </div>
                    <small class="text-muted">Skip version compatibility checks</small>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="skip-backup">
                        <label class="form-check-label fw-medium" for="skip-backup">
                            Skip Backup
                        </label>
                    </div>
                    <small class="text-muted">Skip creating system backup (faster but risky)</small>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="skip-maintenance">
                        <label class="form-check-label fw-medium" for="skip-maintenance">
                            Skip Maintenance Mode
                        </label>
                    </div>
                    <small class="text-muted">Don't enable maintenance mode during update</small>
                </div>
                
                <div class="mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="dry-run">
                        <label class="form-check-label fw-medium" for="dry-run">
                            Dry Run
                        </label>
                    </div>
                    <small class="text-muted">Test update without applying changes</small>
                </div>

                <hr>

                <div class="d-grid gap-2" role="group">
                    <button class="btn btn-warning" id="test-update-system">
                        <i class="fa fa-stethoscope me-2"></i> Test Update System
                    </button>
                    <button class="btn btn-info" id="check-system-health">
                        <i class="fa fa-heartbeat me-2"></i> Check System Health
                    </button>
                    <button class="btn btn-outline-secondary" id="view-update-logs">
                        <i class="fa fa-file-text me-2"></i> View Update Logs
                    </button>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="block block-rounded block-themed">
            <div class="block-header bg-success">
                <h3 class="block-title text-white">
                    <i class="fa fa-info-circle me-1"></i> System Status
                </h3>
            </div>
            <div class="block-content">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="d-flex align-items-center p-3 bg-body-light rounded">
                            <div class="flex-shrink-0">
                                <i class="fa fa-server fa-2x text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fw-semibold">Update System</div>
                                <div class="fs-sm" id="update-system-status">
                                    <span class="badge bg-{{ $configuration['enabled'] ? 'success' : 'danger' }}">
                                        {{ $configuration['enabled'] ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="d-flex align-items-center p-3 bg-body-light rounded">
                            <div class="flex-shrink-0">
                                <i class="fa fa-shield fa-2x text-success"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fw-semibold">Auto-Update</div>
                                <div class="fs-sm" id="auto-update-status">
                                    <span class="badge bg-{{ $configuration['auto_update_enabled'] ? 'success' : 'secondary' }}">
                                        {{ $configuration['auto_update_enabled'] ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="d-flex align-items-center p-3 bg-body-light rounded">
                            <div class="flex-shrink-0">
                                <i class="fa fa-wrench fa-2x text-warning"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fw-semibold">Maintenance</div>
                                <div class="fs-sm" id="maintenance-status">
                                    <span class="badge bg-{{ $configuration['maintenance_mode'] ? 'warning' : 'secondary' }}">
                                        {{ $configuration['maintenance_mode'] ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scheduled Updates -->
<div class="row">
    <div class="col-md-12">
        <div class="block block-rounded block-themed">
            <div class="block-header bg-warning">
                <h3 class="block-title text-white">
                    <i class="fa fa-calendar me-1"></i> Scheduled Updates
                </h3>
                <div class="block-options">
                    <button class="btn btn-sm btn-outline-light" id="add-schedule-btn">
                        <i class="fa fa-plus me-1"></i> Add Schedule
                    </button>
                </div>
            </div>
            <div class="block-content">
                @if(count($scheduledUpdates) > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Version</th>
                                    <th>Scheduled Time</th>
                                    <th>Created By</th>
                                    <th>Status</th>
                                    <th>Options</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($scheduledUpdates as $schedule)
                                <tr>
                                    <td><strong>{{ $schedule->target_version }}</strong></td>
                                    <td>{{ $schedule->scheduled_at->format('M d, Y H:i') }}</td>
                                    <td>{{ $schedule->creator->name ?? 'System' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $schedule->status === 'active' ? 'success' : ($schedule->status === 'completed' ? 'info' : 'secondary') }}">
                                            {{ ucfirst($schedule->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($schedule->options)
                                            @if($schedule->options['force'] ?? false)
                                                <span class="badge bg-warning me-1">Force</span>
                                            @endif
                                            @if($schedule->options['skip_backup'] ?? false)
                                                <span class="badge bg-danger me-1">No Backup</span>
                                            @endif
                                            @if($schedule->options['skip_maintenance'] ?? false)
                                                <span class="badge bg-info me-1">No Maintenance</span>
                                            @endif
                                        @else
                                            <span class="text-muted">Default</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            @if($schedule->status === 'active')
                                            <button class="btn btn-outline-primary edit-schedule-btn" data-id="{{ $schedule->id }}" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger cancel-schedule-btn" data-id="{{ $schedule->id }}" title="Cancel">
                                                <i class="fa fa-times"></i>
                                            </button>
                                            @else
                                            <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center p-4 text-muted">
                        <i class="fa fa-calendar fa-2x mb-2"></i>
                        <p class="mb-0">No scheduled updates</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Recent Update History -->
<div class="row">
    <div class="col-md-12">
        <div class="block block-rounded block-themed">
            <div class="block-header bg-body-light">
                <h3 class="block-title">
                    <i class="fa fa-history me-1"></i> Recent Update History
                </h3>
                <div class="block-options">
                    <a href="{{ route('admin.updates.history') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fa fa-external-link me-1"></i> View All History
                    </a>
                </div>
            </div>
            <div class="block-content">
                @if(count($updateHistory) > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Version Change</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Initiated By</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($updateHistory as $session)
                                <tr>
                                    <td class="fw-medium">{{ $session->created_at->format('M d, Y H:i') }}</td>
                                    <td>
                                        <code class="text-muted">{{ $session->from_version }}</code> → 
                                        <code class="text-success">{{ $session->target_version }}</code>
                                    </td>
                                    <td>{{ $session->duration_formatted ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $session->status === 'completed' ? 'success' : ($session->status === 'failed' ? 'danger' : 'primary') }}">
                                            {{ ucfirst($session->status) }}
                                        </span>
                                        @if($session->rolled_back)
                                            <span class="badge bg-warning ms-1">Rolled Back</span>
                                        @endif
                                    </td>
                                    <td>{{ $session->initiator->name ?? 'System' }}</td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('admin.updates.session-details', $session->id) }}" class="btn btn-outline-info" title="View Details">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            @if($session->status === 'completed' && !$session->rolled_back)
                                            <button class="btn btn-outline-warning rollback-btn" data-id="{{ $session->id }}" title="Rollback">
                                                <i class="fa fa-undo"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center p-4 text-muted">
                        <i class="fa fa-history fa-2x mb-2"></i>
                        <p class="mb-0">No update history available</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Update Now Modal -->
<div class="modal fade" id="update-now-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Confirm Update</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fa fa-exclamation-triangle fa-2x"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="alert-heading">Important</h5>
                        <p class="mb-0">This will update your system to version <strong id="update-target-version"></strong>. Please ensure you have a backup before proceeding.</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Update Options:</label>
                    <div class="checkbox">
                        <label><input type="checkbox" id="modal-force-update"> Force update (skip compatibility checks)</label>
                    </div>
                    <div class="checkbox">
                        <label><input type="checkbox" id="modal-skip-backup"> Skip backup creation</label>
                    </div>
                    <div class="checkbox">
                        <label><input type="checkbox" id="modal-skip-maintenance"> Skip maintenance mode</label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirmation:</label>
                    <div class="checkbox">
                        <label><input type="checkbox" id="confirm-update" required> I understand the risks and want to proceed</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirm-update-btn" disabled>Start Update</button>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Update Modal -->
<div class="modal fade" id="schedule-update-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Schedule Update</h4>
            </div>
            <div class="modal-body">
                <form id="schedule-update-form">
                    <div class="form-group">
                        <label>Target Version:</label>
                        <input type="text" class="form-control" id="schedule-target-version" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Scheduled Date & Time:</label>
                        <input type="datetime-local" class="form-control" id="schedule-datetime" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Update Options:</label>
                        <div class="checkbox">
                            <label><input type="checkbox" id="schedule-force-update"> Force update</label>
                        </div>
                        <div class="checkbox">
                            <label><input type="checkbox" id="schedule-skip-backup"> Skip backup</label>
                        </div>
                        <div class="checkbox">
                            <label><input type="checkbox" id="schedule-skip-maintenance"> Skip maintenance mode</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirm-schedule-btn">Schedule Update</button>
            </div>
        </div>
    </div>
</div>

<!-- Error Display -->
@if(isset($error))
<div class="alert alert-danger">
    <h4><i class="icon fa fa-ban"></i> Management Error</h4>
    {{ $error }}
</div>
@endif

</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
            // Auto-check for updates when page loads
            console.log('Page loaded - automatically checking for updates...');
            refreshAvailableUpdates();
            
            // Event handlers using event delegation to handle dynamically loaded content
            $('#refresh-updates-btn').click(function() {
                refreshAvailableUpdates();
            });

            // Use event delegation for dynamically loaded buttons
            $(document).on('click', '.update-now-btn', function() {
                var version = $(this).data('version');
                console.log('Update Now clicked for version:', version); // Debug log
                showUpdateModal(version);
            });

            $(document).on('click', '.schedule-update-btn', function() {
                var version = $(this).data('version');
                showScheduleModal(version);
            });

            $(document).on('click', '.preview-update-btn', function() {
                var version = $(this).data('version');
                showPreviewModal(version);
            });

            $('#confirm-update-btn').click(function() {
                initiateUpdate();
            });

            $('#confirm-schedule-btn').click(function() {
                scheduleUpdate();
            });

            $('#confirm-update').change(function() {
                $('#confirm-update-btn').prop('disabled', !$(this).is(':checked'));
            });

            $('.edit-schedule-btn').click(function() {
                var scheduleId = $(this).data('id');
                editSchedule(scheduleId);
            });

            $('.cancel-schedule-btn').click(function() {
                var scheduleId = $(this).data('id');
                cancelSchedule(scheduleId);
            });

            $('.rollback-btn').click(function() {
                var sessionId = $(this).data('id');
                showRollbackModal(sessionId);
            });

            $('#test-update-system').click(function() {
                testUpdateSystem();
            });

            $('#check-system-health').click(function() {
                checkSystemHealth();
            });

            $('#view-update-logs').click(function() {
                window.location.href = '{{ route("admin.updates.history") }}';
            });
        });

        function refreshAvailableUpdates() {
            $('#refresh-updates-btn').prop('disabled', true).html('<i class="fa fa-spin fa-refresh"></i> Refreshing...');
            
            // First clear the GitHub release cache, then check for updates
            $.ajax({
                url: '{{ route("admin.updates.api.clear-cache") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(cacheResponse) {
                    console.log('Cache cleared, now checking for updates...');
                    
                    // Now check for available updates
                    $.ajax({
                        url: '{{ route("admin.updates.api.available-updates") }}',
                        type: 'GET',
                        success: function(response) {
                            if (response.success && response.available_updates) {
                                updateAvailableUpdatesUI(response.available_updates);
                                showToast('Updates refreshed successfully', 'success');
                            } else {
                                showAlert('error', response.error || 'No updates available');
                            }
                        },
                        error: function() {
                            showAlert('error', 'Failed to refresh updates');
                        },
                        complete: function() {
                            $('#refresh-updates-btn').prop('disabled', false).html('<i class="fa fa-refresh me-1"></i> Check Updates');
                        }
                    });
                },
                error: function() {
                    console.log('Cache clear failed, proceeding with update check anyway...');
                    
                    // If cache clear fails, still try to check for updates
                    $.ajax({
                        url: '{{ route("admin.updates.api.available-updates") }}',
                        type: 'GET',
                        success: function(response) {
                            if (response.success && response.available_updates) {
                                updateAvailableUpdatesUI(response.available_updates);
                                showToast('Updates refreshed successfully', 'success');
                            } else {
                                showAlert('error', response.error || 'No updates available');
                            }
                        },
                        error: function() {
                            showAlert('error', 'Failed to refresh updates');
                        },
                        complete: function() {
                            $('#refresh-updates-btn').prop('disabled', false).html('<i class="fa fa-refresh me-1"></i> Check Updates');
                        }
                    });
                }
            });
        }

        function updateAvailableUpdatesUI(availableUpdates) {
            const container = $('#available-updates');
            
            if (availableUpdates.length === 0) {
                container.html(`
                    <div class="alert alert-success d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fa fa-check-circle fa-2x"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="alert-heading mb-2">System Up to Date</h4>
                            <p class="mb-0">No updates are currently available. Your system is running the latest version.</p>
                        </div>
                    </div>
                `);
                return;
            }

            let html = '';
            availableUpdates.forEach(function(update) {
                const updateType = update.type || 'patch';
                let typeClass = 'success';
                let badgeClass = 'bg-success';
                
                if (updateType === 'major') {
                    typeClass = 'warning';
                    badgeClass = 'bg-warning';
                } else if (updateType === 'minor') {
                    typeClass = 'info';
                    badgeClass = 'bg-info';
                }

                const releaseDate = update.release_date || 'Unknown';
                const description = update.body ? (update.body.length > 150 ? update.body.substring(0, 150) + '...' : update.body) : '';
                const hasMigrations = update.has_migrations ? 'Yes' : 'No';
                const fileChanges = update.file_changes || 0;
                const estimatedDuration = update.estimated_duration || 'Unknown';

                html += `
                    <div class="alert alert-${typeClass} border-${typeClass}">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h4 class="alert-heading d-flex align-items-center">
                                    <i class="fa fa-tag me-2"></i>
                                    Version ${update.version}
                                    <span class="badge ${badgeClass} ms-2">
                                        ${updateType.charAt(0).toUpperCase() + updateType.slice(1)} Update
                                    </span>
                                </h4>
                                <p class="mb-1"><strong>Released:</strong> ${releaseDate}</p>
                                ${description ? `<p class="mb-1"><strong>Description:</strong> ${description}</p>` : ''}
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <h6 class="fw-semibold mb-2">Requirements:</h6>
                                <ul class="list-unstyled">
                                    <li class="mb-1">
                                        <i class="fa fa-database text-primary me-2"></i> 
                                        Database migrations: <span class="fw-medium">${hasMigrations}</span>
                                    </li>
                                    <li class="mb-1">
                                        <i class="fa fa-file text-info me-2"></i> 
                                        File changes: <span class="fw-medium">${fileChanges} files</span>
                                    </li>
                                    <li class="mb-1">
                                        <i class="fa fa-clock-o text-warning me-2"></i> 
                                        Est. duration: <span class="fw-medium">${estimatedDuration}</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-sm-6 d-flex align-items-end justify-content-end">
                                <div class="btn-group" role="group">
                                    <button class="btn btn-success update-now-btn" data-version="${update.version}">
                                        <i class="fa fa-play me-1"></i> Update Now
                                    </button>
                                    <button class="btn btn-info schedule-update-btn" data-version="${update.version}">
                                        <i class="fa fa-calendar me-1"></i> Schedule
                                    </button>
                                    <button class="btn btn-outline-secondary preview-update-btn" data-version="${update.version}">
                                        <i class="fa fa-eye me-1"></i> Preview
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.html(html);
        }

        function showUpdateModal(version) {
            console.log('showUpdateModal called with version:', version); // Debug log
            $('#update-target-version').text(version);
            console.log('Modal text set to:', $('#update-target-version').text()); // Debug log
            $('#update-now-modal').modal('show');
        }

        function showScheduleModal(version) {
            $('#schedule-target-version').val(version);
            $('#schedule-update-modal').modal('show');
        }

        function initiateUpdate() {
            var version = $('#update-target-version').text();
            var options = {
                target_version: version,
                update_type: 'immediate',
                force_update: $('#modal-force-update').is(':checked'),
                skip_backup: $('#modal-skip-backup').is(':checked'),
                skip_maintenance: $('#modal-skip-maintenance').is(':checked'),
                _token: '{{ csrf_token() }}'
            };

            $.ajax({
                url: '{{ route("admin.updates.initiate") }}',
                type: 'POST',
                data: options,
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Update initiated successfully');
                        $('#update-now-modal').modal('hide');
                        setTimeout(function() {
                            window.location.href = '{{ route("admin.updates.dashboard") }}';
                        }, 2000);
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to initiate update');
                }
            });
        }

        function scheduleUpdate() {
            var options = {
                target_version: $('#schedule-target-version').val(),
                update_type: 'scheduled',
                scheduled_at: $('#schedule-datetime').val(),
                force_update: $('#schedule-force-update').is(':checked'),
                skip_backup: $('#schedule-skip-backup').is(':checked'),
                skip_maintenance: $('#schedule-skip-maintenance').is(':checked'),
                _token: '{{ csrf_token() }}'
            };

            $.ajax({
                url: '{{ route("admin.updates.initiate") }}',
                type: 'POST',
                data: options,
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Update scheduled successfully');
                        $('#schedule-update-modal').modal('hide');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to schedule update');
                }
            });
        }

        function previewUpdate(version) {
            showAlert('info', 'Update preview functionality coming soon');
        }

        function editSchedule(scheduleId) {
            showAlert('info', 'Schedule editing functionality coming soon');
        }

        function cancelSchedule(scheduleId) {
            if (confirm('Are you sure you want to cancel this scheduled update?')) {
                showAlert('info', 'Schedule cancellation functionality coming soon');
            }
        }

        function showRollbackModal(sessionId) {
            if (confirm('Are you sure you want to rollback this update? This will revert all changes made during the update.')) {
                rollbackUpdate(sessionId);
            }
        }

        function rollbackUpdate(sessionId) {
            $.ajax({
                url: '{{ route("admin.updates.rollback", ":sessionId") }}'.replace(':sessionId', sessionId),
                type: 'POST',
                data: {
                    rollback_type: 'full',
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Rollback initiated successfully');
                        setTimeout(function() {
                            window.location.href = '{{ route("admin.updates.dashboard") }}';
                        }, 2000);
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to initiate rollback');
                }
            });
        }

        function testUpdateSystem() {
            $('#test-update-system').prop('disabled', true).html('<i class="fa fa-spin fa-stethoscope"></i> Testing...');
            
            $.ajax({
                url: '{{ route("admin.updates.test") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        var passed = response.test_results.filter(r => r.status === 'passed').length;
                        var total = response.test_results.length;
                        showAlert('success', 'System tests completed: ' + passed + '/' + total + ' passed');
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to run system tests');
                },
                complete: function() {
                    $('#test-update-system').prop('disabled', false).html('<i class="fa fa-stethoscope"></i> Test Update System');
                }
            });
        }

        function checkSystemHealth() {
            $('#check-system-health').prop('disabled', true).html('<i class="fa fa-spin fa-heartbeat"></i> Checking...');
            
            $.ajax({
                url: '{{ route("admin.updates.health-check") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'System health check completed');
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to run health check');
                },
                complete: function() {
                    $('#check-system-health').prop('disabled', false).html('<i class="fa fa-heartbeat"></i> Check System Health');
                }
            });
        }

        function showAlert(type, message) {
            // Map old types to toast types
            if (type === 'error') type = 'error';
            else if (type === 'success') type = 'success';
            else if (type === 'warning') type = 'warning';
            else type = 'info';
            
            // Use the new toast notification system
            showToast(message, type);
        }
    </script>
@endsection

@section('styles')
    @parent
    <style>
        .update-changelog {
            margin-top: 10px;
        }
        
        .update-changelog ul {
            margin-bottom: 0;
        }
        
        .callout {
            margin-bottom: 20px;
        }
        
        .info-box {
            margin-bottom: 15px;
        }
        
        .modal-body .checkbox {
            margin-bottom: 10px;
        }
        
        .alert {
            margin-bottom: 10px;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }
        
        .btn-group-xs > .btn {
            margin-right: 2px;
        }
    </style>
@endsection

@include('partials.admin.updates.toast-notifications')