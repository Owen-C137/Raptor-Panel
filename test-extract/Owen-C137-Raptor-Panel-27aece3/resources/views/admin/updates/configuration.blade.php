@extends('layouts.admin')

@section('title')
    Update Configuration
@endsection

@section('content-header')
    <h1>Update System Configuration</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.updates.dashboard') }}">Updates</a></li>
            <li class="breadcrumb-item active" aria-current="page">Configuration</li>
        </ol>
    </nav>
@endsection

@section('content')
<div class="row" id="update-configuration">
    <!-- General Settings -->
    <div class="col-md-6">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-cogs me-1"></i> General Settings
                </h3>
            </div>
            <form action="{{ route('admin.updates.configuration.update') }}" method="POST" id="general-settings-form">
                @csrf
                <div class="block-content">
                    <div class="mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="auto_update_enabled" value="1" id="auto_update_enabled" {{ ($configuration->auto_update_enabled ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="auto_update_enabled">
                                Enable Automatic Updates
                            </label>
                        </div>
                        <div class="form-text">Automatically check for and install updates</div>
                    </div>

                    <div class="mb-4">
                        <label for="check_interval" class="form-label">Update Check Interval (hours)</label>
                        <select class="form-select" name="check_interval" id="check_interval">
                            <option value="1" {{ ($configuration->check_interval ?? 24) == 1 ? 'selected' : '' }}>Every Hour</option>
                            <option value="6" {{ ($configuration->check_interval ?? 24) == 6 ? 'selected' : '' }}>Every 6 Hours</option>
                            <option value="12" {{ ($configuration->check_interval ?? 24) == 12 ? 'selected' : '' }}>Every 12 Hours</option>
                            <option value="24" {{ ($configuration->check_interval ?? 24) == 24 ? 'selected' : '' }}>Daily</option>
                            <option value="72" {{ ($configuration->check_interval ?? 24) == 72 ? 'selected' : '' }}>Every 3 Days</option>
                            <option value="168" {{ ($configuration->check_interval ?? 24) == 168 ? 'selected' : '' }}>Weekly</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="update_timeout" class="form-label">Update Timeout (minutes)</label>
                        <input type="number" class="form-control" name="update_timeout" id="update_timeout" 
                               value="{{ $configuration->update_timeout ?? 60 }}" min="5" max="120">
                        <div class="form-text">Maximum time to wait for an update to complete</div>
                    </div>

                    <div class="mb-4">
                        <label for="max_concurrent_updates" class="form-label">Max Concurrent Updates</label>
                        <select class="form-select" name="max_concurrent_updates" id="max_concurrent_updates">
                            <option value="1" {{ ($configuration->max_concurrent_updates ?? 1) == 1 ? 'selected' : '' }}>1 Update</option>
                            <option value="2" {{ ($configuration->max_concurrent_updates ?? 1) == 2 ? 'selected' : '' }}>2 Updates</option>
                            <option value="3" {{ ($configuration->max_concurrent_updates ?? 1) == 3 ? 'selected' : '' }}>3 Updates</option>
                        </select>
                        <div class="form-text">Number of updates that can run simultaneously</div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="maintenance_mode" value="1" id="maintenance_mode" {{ ($configuration->maintenance_mode ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="maintenance_mode">
                                Enable Maintenance Mode During Updates
                            </label>
                        </div>
                        <div class="form-text">Put the system in maintenance mode while updating</div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="backup_enabled" value="1" id="backup_enabled" {{ ($configuration->backup_enabled ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="backup_enabled">
                                Create Backup Before Updates
                            </label>
                        </div>
                        <div class="form-text">Automatically create system backup before major updates</div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="rollback_enabled" value="1" id="rollback_enabled" {{ ($configuration->rollback_enabled ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="rollback_enabled">
                                Enable Automatic Rollback
                            </label>
                        </div>
                        <div class="form-text">Automatically rollback failed updates when possible</div>
                    </div>
                </div>
                <div class="block-content bg-body-light">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save me-1"></i> Save General Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notification Settings -->
    <div class="col-md-6">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-bell me-1"></i> Notification Settings
                </h3>
            </div>
            <form action="{{ route('admin.updates.notifications.update') }}" method="POST" id="notification-settings-form">
                @csrf
                <div class="block-content">
                    <div class="mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="notification_enabled" value="1" id="notification_enabled" {{ ($notifications['enabled'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="notification_enabled">
                                Enable Update Notifications
                            </label>
                        </div>
                        <div class="form-text">Send notifications about update events</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Notification Events</label>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="notify_update_available" value="1" id="notify_update_available" {{ ($notifications['update_available'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="notify_update_available">
                                New update available
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="notify_update_started" value="1" id="notify_update_started" {{ ($notifications['update_started'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="notify_update_started">
                                Update started
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="notify_update_completed" value="1" id="notify_update_completed" {{ ($notifications['update_completed'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="notify_update_completed">
                                Update completed
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="notify_update_failed" value="1" id="notify_update_failed" {{ ($notifications['update_failed'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="notify_update_failed">
                                Update failed
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="notify_rollback" value="1" id="notify_rollback" {{ ($notifications['rollback'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="notify_rollback">
                                Rollback performed
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="notification_email" class="form-label">Notification Email</label>
                        <input type="email" class="form-control" name="notification_email" id="notification_email" 
                               value="{{ $notifications['email'] ?? '' }}" placeholder="admin@example.com">
                        <div class="form-text">Email address to receive update notifications</div>
                    </div>

                    <div class="mb-4">
                        <label for="notification_webhook" class="form-label">Webhook URL</label>
                        <input type="url" class="form-control" name="notification_webhook" id="notification_webhook" 
                               value="{{ $notifications['webhook'] ?? '' }}" placeholder="https://hooks.slack.com/...">
                        <div class="form-text">Webhook URL for external notifications (Slack, Discord, etc.)</div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="notify_admins_only" value="1" id="notify_admins_only" {{ ($notifications['admins_only'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="notify_admins_only">
                                Notify Administrators Only
                            </label>
                        </div>
                        <div class="form-text">Only send notifications to admin users</div>
                    </div>
                </div>
                <div class="block-content bg-body-light">
                    <button type="submit" class="btn btn-info">
                        <i class="fa fa-save me-1"></i> Save Notification Settings
                    </button>
                    <button type="button" class="btn btn-secondary" id="test-notification">
                        <i class="fa fa-envelope me-1"></i> Send Test Notification
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row">
    <!-- Update Schedules -->
    <div class="col-md-8">
        <div class="block block-rounded block-themed">
            <div class="block-header bg-warning">
                <h3 class="block-title text-white">
                    <i class="fa fa-clock me-1"></i> Update Schedules
                </h3>
            </div>
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-calendar"></i> Update Schedules
                <div class="block-options">
                    <button class="btn btn-warning btn-sm" id="add-schedule-btn">
                        <i class="fa fa-plus me-1"></i> Add Schedule
                    </button>
                </div>
            </div>
            <div class="block-content">
                @if($schedules && count($schedules) > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-vcenter">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Schedule</th>
                                    <th>Update Type</th>
                                    <th>Status</th>
                                    <th>Next Run</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($schedules as $schedule)
                                <tr>
                                    <td><strong>{{ $schedule->name }}</strong></td>
                                    <td>
                                        <code>{{ $schedule->cron_expression }}</code>
                                        <br><small class="text-muted">{{ $schedule->human_readable_schedule ?? 'Custom schedule' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ ucfirst($schedule->update_type ?? 'auto') }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $schedule->status === 'active' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($schedule->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $schedule->next_run_at ? $schedule->next_run_at->format('M d, Y H:i') : 'Not scheduled' }}</td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-info edit-schedule-btn" data-id="{{ $schedule->id }}">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-{{ $schedule->status === 'active' ? 'warning' : 'success' }} toggle-schedule-btn" 
                                                    data-id="{{ $schedule->id }}" data-status="{{ $schedule->status }}">
                                                <i class="fa fa-{{ $schedule->status === 'active' ? 'pause' : 'play' }}"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-schedule-btn" data-id="{{ $schedule->id }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted p-3">
                        <i class="fa fa-calendar fa-2x mb-2"></i>
                        <p>No update schedules configured</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Health Check Settings -->
    <div class="col-md-4">
        <div class="block block-rounded block-themed">
            <div class="block-header bg-success">
                <h3 class="block-title text-white">
                    <i class="fa fa-heartbeat me-1"></i> Health Check Settings
                </h3>
            </h3>
            </div>
            <form action="{{ route('admin.updates.health-checks.update') }}" method="POST" id="health-settings-form">
                @csrf
                <div class="block-content">
                    <div class="mb-4">
                        <label for="health_check_interval" class="form-label">Check Interval (minutes)</label>
                        <select class="form-select" name="health_check_interval" id="health_check_interval">
                            <option value="5" {{ ($healthChecks['interval'] ?? 15) == 5 ? 'selected' : '' }}>Every 5 minutes</option>
                            <option value="15" {{ ($healthChecks['interval'] ?? 15) == 15 ? 'selected' : '' }}>Every 15 minutes</option>
                            <option value="30" {{ ($healthChecks['interval'] ?? 15) == 30 ? 'selected' : '' }}>Every 30 minutes</option>
                            <option value="60" {{ ($healthChecks['interval'] ?? 15) == 60 ? 'selected' : '' }}>Every hour</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Health Checks</label>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="check_database" value="1" id="check_database" {{ ($healthChecks['database'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="check_database">
                                Database connectivity
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="check_filesystem" value="1" id="check_filesystem" {{ ($healthChecks['filesystem'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="check_filesystem">
                                File system permissions
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="check_dependencies" value="1" id="check_dependencies" {{ ($healthChecks['dependencies'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="check_dependencies">
                                System dependencies
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="check_disk_space" value="1" id="check_disk_space" {{ ($healthChecks['disk_space'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="check_disk_space">
                                Available disk space
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="check_memory" value="1" id="check_memory" {{ ($healthChecks['memory'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="check_memory">
                                Memory usage
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="min_disk_space" class="form-label">Minimum Disk Space (GB)</label>
                        <input type="number" class="form-control" name="min_disk_space" id="min_disk_space" 
                               value="{{ $healthChecks['min_disk_space'] ?? 2 }}" min="1" max="100">
                    </div>
                </div>
                <div class="block-content bg-body-light">
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save me-1"></i> Save Health Settings
                    </button>
                    <button type="button" class="btn btn-secondary" id="run-health-check">
                        <i class="fa fa-play me-1"></i> Run Health Check
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Advanced Settings -->
<div class="row">
    <div class="col-md-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-wrench me-1"></i> Advanced Settings
                </h3>
                <div class="block-options">
                    <button type="button" class="btn-block-option" data-toggle="block-option" data-action="content_toggle">
                        <i class="fa fa-fw fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="block-content" style="display: none;">
                <form action="{{ route('admin.updates.advanced.update') }}" method="POST" id="advanced-settings-form">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-4">
                                <label for="update_server_url" class="form-label">Update Server URL</label>
                                <input type="url" class="form-control" name="update_server_url" id="update_server_url" 
                                       value="{{ $configuration->update_server_url ?? 'https://updates.pterodactyl.io' }}">
                                <div class="form-text">URL to check for updates</div>
                            </div>

                            <div class="mb-4">
                                <label for="update_channel" class="form-label">Update Channel</label>
                                <select class="form-select" name="update_channel" id="update_channel">
                                    <option value="stable" {{ ($configuration->update_channel ?? 'stable') === 'stable' ? 'selected' : '' }}>Stable</option>
                                    <option value="beta" {{ ($configuration->update_channel ?? 'stable') === 'beta' ? 'selected' : '' }}>Beta</option>
                                    <option value="alpha" {{ ($configuration->update_channel ?? 'stable') === 'alpha' ? 'selected' : '' }}>Alpha</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="verify_ssl" value="1" id="verify_ssl" {{ ($configuration->verify_ssl ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="verify_ssl">
                                        Verify SSL Certificates
                                    </label>
                                </div>
                                <div class="form-text">Verify SSL when downloading updates</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-4">
                                <label for="backup_retention_days" class="form-label">Backup Retention (days)</label>
                                <input type="number" class="form-control" name="backup_retention_days" id="backup_retention_days" 
                                       value="{{ $configuration->backup_retention_days ?? 7 }}" min="1" max="90">
                                <p class="help-block">How long to keep update backups</p>
                            </div>

                                <div class="form-text">How long to keep backup files</div>
                            </div>

                            <div class="mb-4">
                                <label for="log_retention_days" class="form-label">Log Retention (days)</label>
                                <input type="number" class="form-control" name="log_retention_days" id="log_retention_days" 
                                       value="{{ $configuration->log_retention_days ?? 30 }}" min="7" max="365">
                                <div class="form-text">How long to keep update logs</div>
                            </div>

                            <div class="mb-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="debug_mode" value="1" id="debug_mode" {{ ($configuration->debug_mode ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="debug_mode">
                                        Enable Debug Mode
                                    </label>
                                </div>
                                <div class="form-text">Enable detailed logging (may impact performance)</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-4">
                                <label for="parallel_jobs" class="form-label">Parallel Jobs</label>
                                <input type="number" class="form-control" name="parallel_jobs" id="parallel_jobs" 
                                       value="{{ $configuration->parallel_jobs ?? 2 }}" min="1" max="8">
                                <div class="form-text">Number of parallel update jobs</div>
                            </div>

                            <div class="mb-4">
                                <label for="memory_limit" class="form-label">Memory Limit (MB)</label>
                                <input type="number" class="form-control" name="memory_limit" id="memory_limit" 
                                       value="{{ $configuration->memory_limit ?? 512 }}" min="256" max="2048">
                                <div class="form-text">Memory limit for update processes</div>
                            </div>

                            <div class="mb-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="auto_retry_failed" value="1" id="auto_retry_failed" {{ ($configuration->auto_retry_failed ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="auto_retry_failed">
                                        Auto-retry Failed Updates
                                    </label>
                                </div>
                                <div class="form-text">Automatically retry failed updates after delay</div>
                            </div>
                        </div>
                    </div>

                    <div class="block-content bg-body-light">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save me-1"></i> Save Advanced Settings
                        <button type="button" class="btn btn-warning" id="reset-advanced">
                            <i class="fa fa-undo me-1"></i> Reset to Defaults
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Schedule Modal -->
<div class="modal fade" id="add-schedule-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-calendar"></i> Add Update Schedule
                </h4>
            </div>
            <form id="add-schedule-form">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Schedule Name</label>
                        <input type="text" class="form-control" name="name" required placeholder="Weekly updates">
                    </div>
                    
                    <div class="form-group">
                        <label>Schedule Type</label>
                        <select class="form-control" name="schedule_type" id="schedule-type">
                            <option value="preset">Preset Schedule</option>
                            <option value="custom">Custom Cron Expression</option>
                        </select>
                    </div>
                    
                    <div id="preset-schedules">
                        <div class="form-group">
                            <label>Preset Schedule</label>
                            <select class="form-control" name="preset_schedule">
                                <option value="0 2 * * 0">Weekly (Sunday 2 AM)</option>
                                <option value="0 2 1 * *">Monthly (1st day, 2 AM)</option>
                                <option value="0 2 * * 1">Weekly (Monday 2 AM)</option>
                                <option value="0 3 * * 6">Weekly (Saturday 3 AM)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="custom-schedule" style="display: none;">
                        <div class="form-group">
                            <label>Cron Expression</label>
                            <input type="text" class="form-control" name="cron_expression" placeholder="0 2 * * 0">
                            <p class="help-block">Format: minute hour day month weekday</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Update Type</label>
                        <select class="form-control" name="update_type">
                            <option value="auto">Automatic (recommended versions only)</option>
                            <option value="major">Include Major Updates</option>
                            <option value="all">All Available Updates</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="enabled" value="1" checked>
                            Enable Schedule
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fa fa-plus"></i> Add Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
            // Form submissions
            $('#general-settings-form').submit(function(e) {
                e.preventDefault();
                saveSettings($(this), 'General settings saved successfully');
            });

            $('#notification-settings-form').submit(function(e) {
                e.preventDefault();
                saveSettings($(this), 'Notification settings saved successfully');
            });

            $('#health-check-settings-form').submit(function(e) {
                e.preventDefault();
                saveSettings($(this), 'Health check settings saved successfully');
            });

            $('#advanced-settings-form').submit(function(e) {
                e.preventDefault();
                saveSettings($(this), 'Advanced settings saved successfully');
            });

            // Add schedule modal
            $('#add-schedule-btn').click(function() {
                $('#add-schedule-modal').modal('show');
            });

            $('#schedule-type').change(function() {
                if ($(this).val() === 'custom') {
                    $('#preset-schedules').hide();
                    $('#custom-schedule').show();
                } else {
                    $('#preset-schedules').show();
                    $('#custom-schedule').hide();
                }
            });

            $('#add-schedule-form').submit(function(e) {
                e.preventDefault();
                addSchedule();
            });

            // Schedule actions
            $('.edit-schedule-btn').click(function() {
                const scheduleId = $(this).data('id');
                editSchedule(scheduleId);
            });

            $('.toggle-schedule-btn').click(function() {
                const scheduleId = $(this).data('id');
                const status = $(this).data('status');
                toggleSchedule(scheduleId, status);
            });

            $('.delete-schedule-btn').click(function() {
                const scheduleId = $(this).data('id');
                deleteSchedule(scheduleId);
            });

            // Other actions
            $('#test-notification').click(function() {
                testNotification();
            });

            $('#run-health-check').click(function() {
                runHealthCheck();
            });

            $('#reset-advanced').click(function() {
                resetAdvancedSettings();
            });
        });

        function saveSettings(form, successMessage) {
            const submitButton = form.find('button[type="submit"]');
            const originalText = submitButton.html();
            
            submitButton.prop('disabled', true).html('<i class="fa fa-spin fa-spinner"></i> Saving...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        showAlert('success', successMessage);
                    } else {
                        showAlert('error', response.error || 'Failed to save settings');
                    }
                },
                error: function(xhr) {
                    const error = xhr.responseJSON?.message || 'Failed to save settings';
                    showAlert('error', error);
                },
                complete: function() {
                    submitButton.prop('disabled', false).html(originalText);
                }
            });
        }

        function addSchedule() {
            const form = $('#add-schedule-form');
            const formData = form.serialize();
            
            $.ajax({
                url: '{{ route("admin.updates.schedules.store") }}',
                type: 'POST',
                data: formData + '&_token=' + '{{ csrf_token() }}',
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Schedule added successfully');
                        $('#add-schedule-modal').modal('hide');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to add schedule');
                }
            });
        }

        function editSchedule(scheduleId) {
            showAlert('info', 'Schedule editing functionality coming soon');
        }

        function toggleSchedule(scheduleId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const action = newStatus === 'active' ? 'enable' : 'disable';
            
            if (confirm(`Are you sure you want to ${action} this schedule?`)) {
                $.ajax({
                    url: '{{ route("admin.updates.schedules.toggle", ":id") }}'.replace(':id', scheduleId),
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        status: newStatus
                    },
                    success: function(response) {
                        if (response.success) {
                            showAlert('success', `Schedule ${action}d successfully`);
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showAlert('error', response.error);
                        }
                    },
                    error: function() {
                        showAlert('error', `Failed to ${action} schedule`);
                    }
                });
            }
        }

        function deleteSchedule(scheduleId) {
            if (confirm('Are you sure you want to delete this schedule? This action cannot be undone.')) {
                $.ajax({
                    url: '{{ route("admin.updates.schedules.destroy", ":id") }}'.replace(':id', scheduleId),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            showAlert('success', 'Schedule deleted successfully');
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showAlert('error', response.error);
                        }
                    },
                    error: function() {
                        showAlert('error', 'Failed to delete schedule');
                    }
                });
            }
        }

        function testNotification() {
            $('#test-notification').prop('disabled', true).html('<i class="fa fa-spin fa-spinner"></i> Sending...');
            
            $.ajax({
                url: '{{ route("admin.updates.test-notification") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Test notification sent successfully');
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to send test notification');
                },
                complete: function() {
                    $('#test-notification').prop('disabled', false).html('<i class="fa fa-envelope"></i> Send Test Notification');
                }
            });
        }

        function runHealthCheck() {
            $('#run-health-check').prop('disabled', true).html('<i class="fa fa-spin fa-stethoscope"></i> Running...');
            
            $.ajax({
                url: '{{ route("admin.updates.health-check") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Health check completed successfully');
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to run health check');
                },
                complete: function() {
                    $('#run-health-check').prop('disabled', false).html('<i class="fa fa-stethoscope"></i> Run Check Now');
                }
            });
        }

        function resetAdvancedSettings() {
            if (confirm('Are you sure you want to reset all advanced settings to their defaults?')) {
                $.ajax({
                    url: '{{ route("admin.updates.advanced.reset") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            showAlert('success', 'Advanced settings reset to defaults');
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            showAlert('error', response.error);
                        }
                    },
                    error: function() {
                        showAlert('error', 'Failed to reset settings');
                    }
                });
            }
        }

        function showAlert(type, message) {
            var alertClass = 'alert-' + (type === 'error' ? 'danger' : type);
            var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible">' +
                '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' +
                message + '</div>';
            
            $('#update-configuration').prepend(alertHtml);
            
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        }
    </script>
@endsection

@section('styles')
    @parent
    <style>
        .form-group .help-block {
            font-size: 12px;
            margin-top: 5px;
        }
        
        .checkbox {
            margin-bottom: 8px;
        }
        
        .description-block {
            text-align: center;
            margin-bottom: 15px;
        }
        
        .box-footer {
            background: transparent;
            border-top: 1px solid #eee;
        }
        
        .collapsed-box .box-body {
            display: none;
        }
        
        code {
            background: #f8f8f8;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .modal-body .form-group:last-child {
            margin-bottom: 0;
        }
        
        .alert {
            margin-bottom: 15px;
        }
        
        .btn-group-xs > .btn {
            margin-right: 2px;
        }
    </style>
@endsection