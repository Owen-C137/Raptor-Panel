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
    <!-- Current Version & Available Updates -->
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-arrow-up"></i> Available Updates
                </h3>
                <div class="box-tools pull-right">
                    <button class="btn btn-primary btn-sm" id="refresh-updates-btn">
                        <i class="fa fa-refresh"></i> Refresh
                    </button>
                </div>
            </div>
            <div class="box-body">
                <div class="alert alert-info">
                    <h4><i class="fa fa-info-circle"></i> Current Version</h4>
                    <p>
                        <strong>Version:</strong> {{ $currentVersion ?? 'Unknown' }}
                        <br>
                        <strong>Last Updated:</strong> {{ $configuration['last_updated'] ?? 'Never' }}
                    </p>
                </div>

                <div id="available-updates">
                    @if(count($availableUpdates) > 0)
                        @foreach($availableUpdates as $update)
                        <div class="callout callout-{{ $update['type'] === 'major' ? 'warning' : ($update['type'] === 'minor' ? 'info' : 'success') }}">
                            <h4>
                                <i class="fa fa-tag"></i>
                                Version {{ $update['version'] }}
                                <small class="label label-{{ $update['type'] === 'major' ? 'warning' : ($update['type'] === 'minor' ? 'info' : 'success') }}">
                                    {{ ucfirst($update['type']) }} Update
                                </small>
                            </h4>
                            <p><strong>Released:</strong> {{ $update['release_date'] ?? 'Unknown' }}</p>
                            <p><strong>Description:</strong> {{ $update['description'] ?? 'No description available' }}</p>
                            
                            @if(isset($update['changelog']) && count($update['changelog']) > 0)
                            <div class="update-changelog">
                                <strong>Changes:</strong>
                                <ul>
                                    @foreach($update['changelog'] as $change)
                                    <li>{{ $change }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif

                            <div class="row">
                                <div class="col-sm-6">
                                    <strong>Requirements:</strong>
                                    <ul class="list-unstyled">
                                        <li><i class="fa fa-database"></i> Database migrations: {{ $update['has_migrations'] ? 'Yes' : 'No' }}</li>
                                        <li><i class="fa fa-file"></i> File changes: {{ $update['file_changes'] ?? 0 }} files</li>
                                        <li><i class="fa fa-clock-o"></i> Est. duration: {{ $update['estimated_duration'] ?? 'Unknown' }}</li>
                                    </ul>
                                </div>
                                <div class="col-sm-6">
                                    <div class="btn-group pull-right" role="group">
                                        <button class="btn btn-success update-now-btn" data-version="{{ $update['version'] }}">
                                            <i class="fa fa-play"></i> Update Now
                                        </button>
                                        <button class="btn btn-info schedule-update-btn" data-version="{{ $update['version'] }}">
                                            <i class="fa fa-calendar"></i> Schedule
                                        </button>
                                        <button class="btn btn-default preview-update-btn" data-version="{{ $update['version'] }}">
                                            <i class="fa fa-eye"></i> Preview
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="alert alert-success">
                            <h4><i class="fa fa-check"></i> System Up to Date</h4>
                            <p>No updates are currently available. Your system is running the latest version.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Update Options & Quick Actions -->
    <div class="col-md-4">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-cogs"></i> Update Options
                </h3>
            </div>
            <div class="box-body">
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="force-update"> Force Update
                    </label>
                    <p class="help-block">Skip version compatibility checks</p>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="skip-backup"> Skip Backup
                    </label>
                    <p class="help-block">Skip creating system backup (faster but risky)</p>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="skip-maintenance"> Skip Maintenance Mode
                    </label>
                    <p class="help-block">Don't enable maintenance mode during update</p>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="dry-run"> Dry Run
                    </label>
                    <p class="help-block">Test update without applying changes</p>
                </div>

                <hr>

                <div class="btn-group-vertical btn-block" role="group">
                    <button class="btn btn-warning" id="test-update-system">
                        <i class="fa fa-stethoscope"></i> Test Update System
                    </button>
                    <button class="btn btn-info" id="check-system-health">
                        <i class="fa fa-heartbeat"></i> Check System Health
                    </button>
                    <button class="btn btn-default" id="view-update-logs">
                        <i class="fa fa-file-text"></i> View Update Logs
                    </button>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-info-circle"></i> System Status
                </h3>
            </div>
            <div class="box-body">
                <div class="info-box bg-light-blue">
                    <span class="info-box-icon">
                        <i class="fa fa-server"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Update System</span>
                        <span class="info-box-number" id="update-system-status">{{ $configuration['enabled'] ? 'Enabled' : 'Disabled' }}</span>
                    </div>
                </div>

                <div class="info-box bg-green">
                    <span class="info-box-icon">
                        <i class="fa fa-shield"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Auto-Update</span>
                        <span class="info-box-number" id="auto-update-status">{{ $configuration['auto_update_enabled'] ? 'Enabled' : 'Disabled' }}</span>
                    </div>
                </div>

                <div class="info-box bg-yellow">
                    <span class="info-box-icon">
                        <i class="fa fa-wrench"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Maintenance</span>
                        <span class="info-box-number" id="maintenance-status">{{ $configuration['maintenance_mode'] ? 'Active' : 'Inactive' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scheduled Updates -->
<div class="row">
    <div class="col-md-12">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-calendar"></i> Scheduled Updates
                </h3>
                <div class="box-tools pull-right">
                    <button class="btn btn-warning btn-sm" id="add-schedule-btn">
                        <i class="fa fa-plus"></i> Add Schedule
                    </button>
                </div>
            </div>
            <div class="box-body">
                @if(count($scheduledUpdates) > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Version</th>
                                    <th>Scheduled Time</th>
                                    <th>Created By</th>
                                    <th>Status</th>
                                    <th>Options</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($scheduledUpdates as $schedule)
                                <tr>
                                    <td><strong>{{ $schedule->target_version }}</strong></td>
                                    <td>{{ $schedule->scheduled_at->format('M d, Y H:i') }}</td>
                                    <td>{{ $schedule->creator->name ?? 'System' }}</td>
                                    <td>
                                        <span class="label label-{{ $schedule->status === 'active' ? 'success' : ($schedule->status === 'completed' ? 'info' : 'default') }}">
                                            {{ ucfirst($schedule->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($schedule->options)
                                            @if($schedule->options['force'] ?? false)
                                                <span class="label label-warning">Force</span>
                                            @endif
                                            @if($schedule->options['skip_backup'] ?? false)
                                                <span class="label label-danger">No Backup</span>
                                            @endif
                                            @if($schedule->options['skip_maintenance'] ?? false)
                                                <span class="label label-info">No Maintenance</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-xs">
                                            @if($schedule->status === 'active')
                                            <button class="btn btn-info edit-schedule-btn" data-id="{{ $schedule->id }}">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger cancel-schedule-btn" data-id="{{ $schedule->id }}">
                                                <i class="fa fa-times"></i>
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
                    <p class="text-muted">No scheduled updates</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Recent Update History -->
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-history"></i> Recent Update History
                </h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('admin.updates.history') }}" class="btn btn-default btn-sm">
                        <i class="fa fa-external-link"></i> View All History
                    </a>
                </div>
            </div>
            <div class="box-body">
                @if(count($updateHistory) > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Version Change</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Initiated By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($updateHistory as $session)
                                <tr>
                                    <td>{{ $session->created_at->format('M d, Y H:i') }}</td>
                                    <td>
                                        <strong>{{ $session->from_version }}</strong> → 
                                        <strong>{{ $session->target_version }}</strong>
                                    </td>
                                    <td>{{ $session->duration_formatted ?? 'N/A' }}</td>
                                    <td>
                                        <span class="label label-{{ $session->status === 'completed' ? 'success' : ($session->status === 'failed' ? 'danger' : 'primary') }}">
                                            {{ ucfirst($session->status) }}
                                        </span>
                                        @if($session->rolled_back)
                                            <span class="label label-warning">Rolled Back</span>
                                        @endif
                                    </td>
                                    <td>{{ $session->initiator->name ?? 'System' }}</td>
                                    <td>
                                        <div class="btn-group btn-group-xs">
                                            <a href="{{ route('admin.updates.session-details', $session->id) }}" class="btn btn-info">
                                                <i class="fa fa-eye"></i> Details
                                            </a>
                                            @if($session->status === 'completed' && !$session->rolled_back)
                                            <button class="btn btn-warning rollback-btn" data-id="{{ $session->id }}">
                                                <i class="fa fa-undo"></i> Rollback
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
                    <p class="text-muted">No update history available</p>
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
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Confirm Update</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <h4><i class="fa fa-exclamation-triangle"></i> Important</h4>
                    <p>This will update your system to version <strong id="update-target-version"></strong>. Please ensure you have a backup before proceeding.</p>
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
            // Event handlers
            $('#refresh-updates-btn').click(function() {
                refreshAvailableUpdates();
            });

            $('.update-now-btn').click(function() {
                var version = $(this).data('version');
                showUpdateModal(version);
            });

            $('.schedule-update-btn').click(function() {
                var version = $(this).data('version');
                showScheduleModal(version);
            });

            $('.preview-update-btn').click(function() {
                var version = $(this).data('version');
                previewUpdate(version);
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
            
            $.ajax({
                url: '{{ route("admin.updates.check") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to refresh updates');
                },
                complete: function() {
                    $('#refresh-updates-btn').prop('disabled', false).html('<i class="fa fa-refresh"></i> Refresh');
                }
            });
        }

        function showUpdateModal(version) {
            $('#update-target-version').text(version);
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
            var alertClass = 'alert-' + (type === 'error' ? 'danger' : type);
            var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible">' +
                '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' +
                message + '</div>';
            
            $('#update-management').prepend(alertHtml);
            
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
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