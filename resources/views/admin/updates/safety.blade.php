@extends('layouts.admin')

@section('title')
    Update Safety Controls
@endsection

@section('content-header')
    <h1>Update Safety Controls</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.updates.dashboard') }}">Updates</a></li>
        <li class="active">Safety Controls</li>
    </ol>
@endsection

@section('content')
<div class="row" id="safety-controls">
    <!-- Emergency Controls -->
    <div class="col-md-6">
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-exclamation-triangle"></i> Emergency Controls
                </h3>
            </div>
            <div class="box-body">
                @if($activeUpdate)
                    <div class="alert alert-warning">
                        <h4><i class="fa fa-warning"></i> Update in Progress</h4>
                        <p>Update session: <strong>{{ $activeUpdate->session_id }}</strong></p>
                        <p>Started: {{ $activeUpdate->started_at ? $activeUpdate->started_at->diffForHumans() : 'Unknown' }}</p>
                        <p>Status: <span class="label label-warning">{{ ucfirst($activeUpdate->status) }}</span></p>
                    </div>

                    <div class="btn-group btn-group-justified" style="margin-bottom: 15px;">
                        <div class="btn-group">
                            <button type="button" class="btn btn-warning" id="pause-update-btn">
                                <i class="fa fa-pause"></i> Pause Update
                            </button>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-danger" id="stop-update-btn">
                                <i class="fa fa-stop"></i> Stop Update
                            </button>
                        </div>
                    </div>

                    <div class="progress">
                        <div class="progress-bar progress-bar-striped active" 
                             style="width: {{ $activeUpdate->progress_percentage ?? 0 }}%">
                            {{ $activeUpdate->progress_percentage ?? 0 }}%
                        </div>
                    </div>

                    <p class="text-muted">
                        Current step: {{ $activeUpdate->current_step ?? 'Initializing...' }}
                    </p>
                @else
                    <div class="alert alert-success">
                        <h4><i class="fa fa-check"></i> No Active Updates</h4>
                        <p>No updates are currently running. Emergency controls are not needed.</p>
                    </div>
                @endif

                <hr>

                <h4>System Emergency Actions</h4>
                
                <div class="btn-group btn-group-justified" style="margin-bottom: 10px;">
                    <div class="btn-group">
                        <button type="button" class="btn btn-warning" id="maintenance-mode-btn" 
                                data-status="{{ $maintenanceMode ? 'enabled' : 'disabled' }}">
                            <i class="fa fa-{{ $maintenanceMode ? 'unlock' : 'lock' }}"></i> 
                            {{ $maintenanceMode ? 'Disable' : 'Enable' }} Maintenance Mode
                        </button>
                    </div>
                </div>

                <div class="btn-group btn-group-justified" style="margin-bottom: 10px;">
                    <div class="btn-group">
                        <button type="button" class="btn btn-danger" id="emergency-rollback-btn">
                            <i class="fa fa-undo"></i> Emergency Rollback
                        </button>
                    </div>
                </div>

                <div class="btn-group btn-group-justified">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default" id="system-backup-btn">
                            <i class="fa fa-download"></i> Create Emergency Backup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rollback Manager -->
    <div class="col-md-6">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-undo"></i> Rollback Manager
                </h3>
            </div>
            <div class="box-body">
                @if($availableRollbacks && count($availableRollbacks) > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Version</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($availableRollbacks as $rollback)
                                <tr>
                                    <td>
                                        <strong>{{ $rollback->version }}</strong>
                                        @if($rollback->is_current)
                                            <span class="label label-success">Current</span>
                                        @endif
                                    </td>
                                    <td>{{ $rollback->created_at->format('M d, Y H:i') }}</td>
                                    <td>
                                        <span class="label label-{{ $rollback->type === 'auto' ? 'info' : 'primary' }}">
                                            {{ ucfirst($rollback->type) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="label label-{{ $rollback->status === 'available' ? 'success' : 'default' }}">
                                            {{ ucfirst($rollback->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if(!$rollback->is_current && $rollback->status === 'available')
                                            <div class="btn-group btn-group-xs">
                                                <button type="button" class="btn btn-info rollback-info-btn" 
                                                        data-id="{{ $rollback->id }}">
                                                    <i class="fa fa-info"></i>
                                                </button>
                                                <button type="button" class="btn btn-warning rollback-btn" 
                                                        data-id="{{ $rollback->id }}" data-version="{{ $rollback->version }}">
                                                    <i class="fa fa-undo"></i> Rollback
                                                </button>
                                                <button type="button" class="btn btn-danger delete-rollback-btn" 
                                                        data-id="{{ $rollback->id }}">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-muted">
                                                {{ $rollback->is_current ? 'Current version' : 'Not available' }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info">
                        <h4><i class="fa fa-info-circle"></i> No Rollback Points Available</h4>
                        <p>No rollback points are currently available. Rollback points are created automatically before major updates.</p>
                    </div>
                @endif

                <hr>

                <h4>Rollback Settings</h4>
                <form id="rollback-settings-form">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="auto_create_rollback" value="1" 
                                   {{ ($rollbackSettings['auto_create'] ?? true) ? 'checked' : '' }}>
                            Automatically create rollback points
                        </label>
                    </div>

                    <div class="form-group">
                        <label>Maximum Rollback Points</label>
                        <select class="form-control" name="max_rollback_points">
                            <option value="3" {{ ($rollbackSettings['max_points'] ?? 5) == 3 ? 'selected' : '' }}>3 points</option>
                            <option value="5" {{ ($rollbackSettings['max_points'] ?? 5) == 5 ? 'selected' : '' }}>5 points</option>
                            <option value="10" {{ ($rollbackSettings['max_points'] ?? 5) == 10 ? 'selected' : '' }}>10 points</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="verify_before_rollback" value="1" 
                                   {{ ($rollbackSettings['verify_before_rollback'] ?? true) ? 'checked' : '' }}>
                            Verify system health before rollback
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Save Rollback Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Safety Checks -->
    <div class="col-md-6">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-shield"></i> Safety Checks
                </h3>
            </div>
            <div class="box-body">
                <div class="safety-check-item">
                    <div class="row">
                        <div class="col-xs-8">
                            <strong>Database Backup</strong>
                            <p class="text-muted">Current database backup status</p>
                        </div>
                        <div class="col-xs-4 text-right">
                            <span class="label label-{{ $safetyChecks['database_backup'] ? 'success' : 'danger' }}">
                                {{ $safetyChecks['database_backup'] ? 'Available' : 'Missing' }}
                            </span>
                            <br>
                            @if($safetyChecks['database_backup_date'])
                                <small class="text-muted">{{ $safetyChecks['database_backup_date']->diffForHumans() }}</small>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="safety-check-item">
                    <div class="row">
                        <div class="col-xs-8">
                            <strong>File System Backup</strong>
                            <p class="text-muted">Application files backup status</p>
                        </div>
                        <div class="col-xs-4 text-right">
                            <span class="label label-{{ $safetyChecks['filesystem_backup'] ? 'success' : 'danger' }}">
                                {{ $safetyChecks['filesystem_backup'] ? 'Available' : 'Missing' }}
                            </span>
                            <br>
                            @if($safetyChecks['filesystem_backup_date'])
                                <small class="text-muted">{{ $safetyChecks['filesystem_backup_date']->diffForHumans() }}</small>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="safety-check-item">
                    <div class="row">
                        <div class="col-xs-8">
                            <strong>System Dependencies</strong>
                            <p class="text-muted">Required system components</p>
                        </div>
                        <div class="col-xs-4 text-right">
                            <span class="label label-{{ $safetyChecks['dependencies'] ? 'success' : 'warning' }}">
                                {{ $safetyChecks['dependencies'] ? 'Verified' : 'Issues Found' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="safety-check-item">
                    <div class="row">
                        <div class="col-xs-8">
                            <strong>Disk Space</strong>
                            <p class="text-muted">Available storage space</p>
                        </div>
                        <div class="col-xs-4 text-right">
                            <span class="label label-{{ $safetyChecks['disk_space_sufficient'] ? 'success' : 'danger' }}">
                                {{ $safetyChecks['available_space'] ?? 'Unknown' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="safety-check-item">
                    <div class="row">
                        <div class="col-xs-8">
                            <strong>Active Connections</strong>
                            <p class="text-muted">Current user sessions</p>
                        </div>
                        <div class="col-xs-4 text-right">
                            <span class="label label-info">
                                {{ $safetyChecks['active_connections'] ?? 0 }} users
                            </span>
                        </div>
                    </div>
                </div>

                <hr>

                <button type="button" class="btn btn-info btn-block" id="refresh-safety-checks">
                    <i class="fa fa-refresh"></i> Refresh Safety Checks
                </button>
            </div>
        </div>
    </div>

    <!-- Safety Configuration -->
    <div class="col-md-6">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-cog"></i> Safety Configuration
                </h3>
            </div>
            <div class="box-body">
                <form id="safety-config-form">
                    <h4>Pre-update Safety Checks</h4>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="require_backup" value="1" 
                                   {{ ($safetyConfig['require_backup'] ?? true) ? 'checked' : '' }}>
                            Require backup before updates
                        </label>
                        <p class="help-block">Prevent updates if backup is not available</p>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="check_disk_space" value="1" 
                                   {{ ($safetyConfig['check_disk_space'] ?? true) ? 'checked' : '' }}>
                            Check available disk space
                        </label>
                        <p class="help-block">Ensure sufficient storage for update</p>
                    </div>

                    <div class="form-group">
                        <label for="min_disk_space_gb">Minimum Required Disk Space (GB)</label>
                        <input type="number" class="form-control" name="min_disk_space_gb" id="min_disk_space_gb" 
                               value="{{ $safetyConfig['min_disk_space_gb'] ?? 2 }}" min="1" max="50">
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="verify_dependencies" value="1" 
                                   {{ ($safetyConfig['verify_dependencies'] ?? true) ? 'checked' : '' }}>
                            Verify system dependencies
                        </label>
                        <p class="help-block">Check required PHP extensions and system tools</p>
                    </div>

                    <div class="form-group">
                        <label for="max_active_users">Maximum Active Users During Update</label>
                        <input type="number" class="form-control" name="max_active_users" id="max_active_users" 
                               value="{{ $safetyConfig['max_active_users'] ?? 0 }}" min="0" max="100">
                        <p class="help-block">Prevent updates when too many users are active (0 = no limit)</p>
                    </div>

                    <hr>

                    <h4>Update Safety Measures</h4>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="enable_maintenance_mode" value="1" 
                                   {{ ($safetyConfig['enable_maintenance_mode'] ?? true) ? 'checked' : '' }}>
                            Enable maintenance mode during updates
                        </label>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="auto_rollback_on_failure" value="1" 
                                   {{ ($safetyConfig['auto_rollback_on_failure'] ?? true) ? 'checked' : '' }}>
                            Auto-rollback on critical failures
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="update_timeout_minutes">Update Timeout (minutes)</label>
                        <input type="number" class="form-control" name="update_timeout_minutes" id="update_timeout_minutes" 
                               value="{{ $safetyConfig['update_timeout_minutes'] ?? 60 }}" min="15" max="180">
                        <p class="help-block">Automatically stop updates that take too long</p>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="confirm_destructive_operations" value="1" 
                                   {{ ($safetyConfig['confirm_destructive_operations'] ?? true) ? 'checked' : '' }}>
                            Require confirmation for destructive operations
                        </label>
                    </div>

                    <hr>

                    <h4>Emergency Procedures</h4>

                    <div class="form-group">
                        <label for="emergency_contact_email">Emergency Contact Email</label>
                        <input type="email" class="form-control" name="emergency_contact_email" id="emergency_contact_email" 
                               value="{{ $safetyConfig['emergency_contact_email'] ?? '' }}" placeholder="admin@example.com">
                        <p class="help-block">Receive notifications for critical update failures</p>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="send_emergency_notifications" value="1" 
                                   {{ ($safetyConfig['send_emergency_notifications'] ?? true) ? 'checked' : '' }}>
                            Send emergency notifications
                        </label>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i> Save Safety Configuration
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modals -->
<div class="modal fade" id="emergency-action-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-red">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-exclamation-triangle"></i> Emergency Action Confirmation
                </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <strong>Warning:</strong> This action will interrupt the current update process.
                </div>
                
                <p id="emergency-action-description"></p>
                
                <div class="form-group">
                    <label for="emergency-reason">Reason for emergency action (required):</label>
                    <textarea class="form-control" id="emergency-reason" rows="3" 
                              placeholder="Describe the reason for this emergency action..."></textarea>
                </div>
                
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="confirm-emergency-action" required>
                        I understand this is an emergency action and accept responsibility
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-emergency-btn" disabled>
                    <i class="fa fa-exclamation-triangle"></i> Execute Emergency Action
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rollback-confirmation-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-yellow">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-undo"></i> Rollback Confirmation
                </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <strong>Warning:</strong> Rolling back will revert your system to a previous version.
                </div>
                
                <p>You are about to rollback to version: <strong id="rollback-version"></strong></p>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="confirm-rollback-backup">
                        Create backup of current version before rollback
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="rollback-reason">Rollback reason (optional):</label>
                    <textarea class="form-control" id="rollback-reason" rows="2" 
                              placeholder="Reason for rollback..."></textarea>
                </div>
                
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="confirm-rollback-action" required>
                        I confirm I want to rollback the system
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirm-rollback-btn" disabled>
                    <i class="fa fa-undo"></i> Perform Rollback
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rollback-info-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-info-circle"></i> Rollback Point Information
                </h4>
            </div>
            <div class="modal-body" id="rollback-info-content">
                <!-- Content loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
            // Emergency action confirmations
            $('#confirm-emergency-action').change(function() {
                $('#confirm-emergency-btn').prop('disabled', !$(this).is(':checked'));
            });

            $('#confirm-rollback-action').change(function() {
                $('#confirm-rollback-btn').prop('disabled', !$(this).is(':checked'));
            });

            // Emergency actions
            $('#pause-update-btn').click(function() {
                showEmergencyConfirmation('pause', 'Pause the current update process', pauseUpdate);
            });

            $('#stop-update-btn').click(function() {
                showEmergencyConfirmation('stop', 'Forcefully stop the current update process', stopUpdate);
            });

            $('#maintenance-mode-btn').click(function() {
                const status = $(this).data('status');
                const action = status === 'enabled' ? 'disable' : 'enable';
                showEmergencyConfirmation(action + '_maintenance', 
                    action.charAt(0).toUpperCase() + action.slice(1) + ' maintenance mode', 
                    function() { toggleMaintenanceMode(status); }
                );
            });

            $('#emergency-rollback-btn').click(function() {
                showEmergencyConfirmation('emergency_rollback', 
                    'Perform emergency rollback to the last known good state', 
                    emergencyRollback
                );
            });

            $('#system-backup-btn').click(function() {
                createEmergencyBackup();
            });

            // Regular rollback actions
            $('.rollback-btn').click(function() {
                const rollbackId = $(this).data('id');
                const version = $(this).data('version');
                showRollbackConfirmation(rollbackId, version);
            });

            $('.rollback-info-btn').click(function() {
                const rollbackId = $(this).data('id');
                showRollbackInfo(rollbackId);
            });

            $('.delete-rollback-btn').click(function() {
                const rollbackId = $(this).data('id');
                if (confirm('Are you sure you want to delete this rollback point? This action cannot be undone.')) {
                    deleteRollbackPoint(rollbackId);
                }
            });

            // Form submissions
            $('#rollback-settings-form').submit(function(e) {
                e.preventDefault();
                saveRollbackSettings();
            });

            $('#safety-config-form').submit(function(e) {
                e.preventDefault();
                saveSafetyConfiguration();
            });

            // Other actions
            $('#refresh-safety-checks').click(function() {
                refreshSafetyChecks();
            });

            // Modal confirmations
            $('#confirm-emergency-btn').click(function() {
                const action = $(this).data('action');
                const reason = $('#emergency-reason').val();
                
                if (!reason.trim()) {
                    showAlert('error', 'Please provide a reason for this emergency action');
                    return;
                }
                
                if (typeof window.emergencyActionCallback === 'function') {
                    window.emergencyActionCallback(reason);
                }
                
                $('#emergency-action-modal').modal('hide');
            });

            $('#confirm-rollback-btn').click(function() {
                const rollbackId = $(this).data('rollback-id');
                const createBackup = $('#confirm-rollback-backup').is(':checked');
                const reason = $('#rollback-reason').val();
                
                performRollback(rollbackId, createBackup, reason);
                $('#rollback-confirmation-modal').modal('hide');
            });

            // Auto-refresh active update status
            if ($('#pause-update-btn').length > 0) {
                setInterval(refreshUpdateStatus, 30000); // Every 30 seconds
            }
        });

        function showEmergencyConfirmation(action, description, callback) {
            $('#emergency-action-description').text(description);
            $('#confirm-emergency-btn').data('action', action);
            $('#emergency-reason').val('');
            $('#confirm-emergency-action').prop('checked', false);
            $('#confirm-emergency-btn').prop('disabled', true);
            window.emergencyActionCallback = callback;
            $('#emergency-action-modal').modal('show');
        }

        function showRollbackConfirmation(rollbackId, version) {
            $('#rollback-version').text(version);
            $('#confirm-rollback-btn').data('rollback-id', rollbackId);
            $('#confirm-rollback-backup').prop('checked', true);
            $('#rollback-reason').val('');
            $('#confirm-rollback-action').prop('checked', false);
            $('#confirm-rollback-btn').prop('disabled', true);
            $('#rollback-confirmation-modal').modal('show');
        }

        function pauseUpdate() {
            executeEmergencyAction('pause', 'Update paused successfully');
        }

        function stopUpdate() {
            executeEmergencyAction('stop', 'Update stopped successfully');
        }

        function toggleMaintenanceMode(currentStatus) {
            const newStatus = currentStatus === 'enabled' ? 'disable' : 'enable';
            executeEmergencyAction('maintenance-mode', 'Maintenance mode ' + newStatus + 'd successfully', {
                action: newStatus
            });
        }

        function emergencyRollback() {
            executeEmergencyAction('emergency-rollback', 'Emergency rollback initiated successfully');
        }

        function executeEmergencyAction(action, successMessage, additionalData = {}) {
            const reason = $('#emergency-reason').val();
            
            $.ajax({
                url: '{{ route("admin.updates.emergency-action") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    action: action,
                    reason: reason,
                    ...additionalData
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', successMessage);
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function(xhr) {
                    showAlert('error', 'Emergency action failed: ' + (xhr.responseJSON?.message || 'Unknown error'));
                }
            });
        }

        function createEmergencyBackup() {
            $('#system-backup-btn').prop('disabled', true).html('<i class="fa fa-spin fa-spinner"></i> Creating Backup...');
            
            $.ajax({
                url: '{{ route("admin.updates.emergency-backup") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Emergency backup created successfully');
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to create emergency backup');
                },
                complete: function() {
                    $('#system-backup-btn').prop('disabled', false).html('<i class="fa fa-download"></i> Create Emergency Backup');
                }
            });
        }

        function showRollbackInfo(rollbackId) {
            $.ajax({
                url: '{{ route("admin.updates.rollback.info", ":id") }}'.replace(':id', rollbackId),
                type: 'GET',
                success: function(response) {
                    $('#rollback-info-content').html(response.html);
                    $('#rollback-info-modal').modal('show');
                },
                error: function() {
                    showAlert('error', 'Failed to load rollback information');
                }
            });
        }

        function performRollback(rollbackId, createBackup, reason) {
            $.ajax({
                url: '{{ route("admin.updates.rollback.execute", ":id") }}'.replace(':id', rollbackId),
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    create_backup: createBackup,
                    reason: reason
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Rollback initiated successfully');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function(xhr) {
                    showAlert('error', 'Rollback failed: ' + (xhr.responseJSON?.message || 'Unknown error'));
                }
            });
        }

        function deleteRollbackPoint(rollbackId) {
            $.ajax({
                url: '{{ route("admin.updates.rollback.delete", ":id") }}'.replace(':id', rollbackId),
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Rollback point deleted successfully');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to delete rollback point');
                }
            });
        }

        function saveRollbackSettings() {
            const formData = $('#rollback-settings-form').serialize();
            
            $.ajax({
                url: '{{ route("admin.updates.rollback.settings") }}',
                type: 'POST',
                data: formData + '&_token=' + '{{ csrf_token() }}',
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Rollback settings saved successfully');
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to save rollback settings');
                }
            });
        }

        function saveSafetyConfiguration() {
            const formData = $('#safety-config-form').serialize();
            
            $.ajax({
                url: '{{ route("admin.updates.safety-config") }}',
                type: 'POST',
                data: formData + '&_token=' + '{{ csrf_token() }}',
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Safety configuration saved successfully');
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to save safety configuration');
                }
            });
        }

        function refreshSafetyChecks() {
            $('#refresh-safety-checks').prop('disabled', true).html('<i class="fa fa-spin fa-refresh"></i> Checking...');
            
            $.ajax({
                url: '{{ route("admin.updates.safety-checks") }}',
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        // Update safety check status indicators
                        location.reload();
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to refresh safety checks');
                },
                complete: function() {
                    $('#refresh-safety-checks').prop('disabled', false).html('<i class="fa fa-refresh"></i> Refresh Safety Checks');
                }
            });
        }

        function refreshUpdateStatus() {
            $.ajax({
                url: '{{ route("admin.updates.status") }}',
                type: 'GET',
                success: function(response) {
                    if (response.active_update) {
                        // Update progress bar and status
                        $('.progress-bar').css('width', response.active_update.progress_percentage + '%')
                                         .text(response.active_update.progress_percentage + '%');
                        
                        // Update current step
                        if (response.active_update.current_step) {
                            $('.text-muted').last().text('Current step: ' + response.active_update.current_step);
                        }
                    } else {
                        // No active update, reload page to show updated interface
                        location.reload();
                    }
                }
            });
        }

        function showAlert(type, message) {
            var alertClass = 'alert-' + (type === 'error' ? 'danger' : type);
            var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible">' +
                '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' +
                message + '</div>';
            
            $('#safety-controls').prepend(alertHtml);
            
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        }
    </script>
@endsection

@section('styles')
    @parent
    <style>
        .safety-check-item {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .safety-check-item:last-child {
            border-bottom: none;
        }
        
        .emergency-action {
            margin-bottom: 10px;
        }
        
        .modal-header.bg-red {
            background-color: #dd4b39;
            color: white;
        }
        
        .modal-header.bg-yellow {
            background-color: #f39c12;
            color: white;
        }
        
        .progress {
            margin-bottom: 10px;
        }
        
        .btn-group-justified {
            margin-bottom: 10px;
        }
        
        .form-group .help-block {
            font-size: 12px;
            margin-top: 5px;
        }
        
        .alert {
            margin-bottom: 15px;
        }
        
        .label {
            font-size: 11px;
        }
        
        .btn-group-xs > .btn {
            margin-right: 2px;
        }
        
        textarea {
            resize: vertical;
        }
        
        .checkbox {
            margin-bottom: 8px;
        }
    </style>
@endsection