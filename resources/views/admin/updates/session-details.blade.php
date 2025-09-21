@extends('layouts.admin')

@section('title')
    Update Session Details
@endsection

@section('content-header')
    <h1>
        Update Session Details
        <small>Session ID: {{ $session->id ?? 'Unknown' }}</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.updates.dashboard') }}">Updates</a></li>
        <li><a href="{{ route('admin.updates.history') }}">History</a></li>
        <li class="active">Session Details</li>
    </ol>
@endsection

@section('content')
@if($session)
<div class="row" id="session-details">
    <!-- Session Overview -->
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-info-circle"></i> Session Overview
                </h3>
                <div class="box-tools pull-right">
                    @if($session->status === 'running')
                        <button class="btn btn-danger btn-sm" id="stop-session">
                            <i class="fa fa-stop"></i> Stop Update
                        </button>
                    @endif
                    @if($session->status === 'completed' && !$session->rolled_back)
                        <button class="btn btn-warning btn-sm" id="rollback-session">
                            <i class="fa fa-undo"></i> Rollback
                        </button>
                    @endif
                </div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-6">
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">Session ID</th>
                                <td><code>{{ $session->id }}</code></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span class="label label-{{ $session->status === 'completed' ? 'success' : ($session->status === 'failed' ? 'danger' : ($session->status === 'running' ? 'primary' : 'default')) }}">
                                        {{ ucfirst($session->status) }}
                                    </span>
                                    @if($session->rolled_back)
                                        <span class="label label-warning">Rolled Back</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Version Change</th>
                                <td>
                                    <strong>{{ $session->from_version }}</strong> 
                                    <i class="fa fa-arrow-right"></i> 
                                    <strong>{{ $session->target_version }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <th>Update Type</th>
                                <td>{{ ucfirst($session->update_type ?? 'Manual') }}</td>
                            </tr>
                            <tr>
                                <th>Initiated By</th>
                                <td>
                                    {{ $session->initiator->name ?? 'System' }}
                                    <small class="text-muted">(via {{ $session->initiated_via ?? 'unknown' }})</small>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-sm-6">
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">Created</th>
                                <td>{{ $session->created_at->format('M d, Y H:i:s') }}</td>
                            </tr>
                            <tr>
                                <th>Started</th>
                                <td>{{ $session->started_at ? $session->started_at->format('M d, Y H:i:s') : 'Not started' }}</td>
                            </tr>
                            <tr>
                                <th>Completed</th>
                                <td>{{ $session->completed_at ? $session->completed_at->format('M d, Y H:i:s') : 'Not completed' }}</td>
                            </tr>
                            <tr>
                                <th>Duration</th>
                                <td>{{ $session->duration_formatted ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Progress</th>
                                <td>
                                    @if($session->status === 'running')
                                        <div class="progress">
                                            <div class="progress-bar progress-bar-primary" 
                                                 style="width: {{ $session->progress_percentage ?? 0 }}%">
                                                {{ $session->progress_percentage ?? 0 }}%
                                            </div>
                                        </div>
                                    @else
                                        {{ $session->completed_steps ?? 0 }}/{{ $session->total_steps ?? 0 }} steps
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if($session->error_message)
                <div class="alert alert-danger">
                    <h4><i class="fa fa-exclamation-triangle"></i> Error</h4>
                    <p>{{ $session->error_message }}</p>
                    @if($session->error_details)
                        <details>
                            <summary>Error Details</summary>
                            <pre>{{ json_encode($session->error_details, JSON_PRETTY_PRINT) }}</pre>
                        </details>
                    @endif
                </div>
                @endif

                @if($session->options)
                <div class="session-options">
                    <h5><i class="fa fa-cogs"></i> Update Options</h5>
                    <div class="row">
                        @foreach($session->options as $key => $value)
                        <div class="col-sm-3">
                            <div class="option-item">
                                <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                <span class="label label-{{ $value ? 'success' : 'default' }}">
                                    {{ $value ? 'Yes' : 'No' }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-md-4">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-flash"></i> Quick Actions
                </h3>
            </div>
            <div class="box-body">
                <div class="btn-group-vertical btn-block">
                    <button class="btn btn-primary" id="view-logs-btn">
                        <i class="fa fa-file-text"></i> View Logs
                    </button>
                    <button class="btn btn-info" id="download-session-btn">
                        <i class="fa fa-download"></i> Download Session Data
                    </button>
                    @if($session->status === 'running')
                        <button class="btn btn-warning" id="pause-session-btn">
                            <i class="fa fa-pause"></i> Pause Update
                        </button>
                    @endif
                    <a href="{{ route('admin.updates.history') }}" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> Back to History
                    </a>
                </div>
            </div>
        </div>

        <!-- Session Statistics -->
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-bar-chart"></i> Session Statistics
                </h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-xs-6">
                        <div class="description-block">
                            <h5 class="description-header">{{ count($session->steps ?? []) }}</h5>
                            <span class="description-text">TOTAL STEPS</span>
                        </div>
                    </div>
                    <div class="col-xs-6">
                        <div class="description-block">
                            <h5 class="description-header">{{ count($session->migrations ?? []) }}</h5>
                            <span class="description-text">MIGRATIONS</span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-6">
                        <div class="description-block">
                            <h5 class="description-header text-green">{{ $session->steps->where('status', 'completed')->count() ?? 0 }}</h5>
                            <span class="description-text">COMPLETED</span>
                        </div>
                    </div>
                    <div class="col-xs-6">
                        <div class="description-block">
                            <h5 class="description-header text-red">{{ $session->steps->where('status', 'failed')->count() ?? 0 }}</h5>
                            <span class="description-text">FAILED</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Steps Timeline -->
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-list-ol"></i> Update Steps Timeline
                </h3>
                <div class="box-tools pull-right">
                    @if($session->status === 'running')
                        <span class="label label-primary">Live Updates</span>
                    @endif
                </div>
            </div>
            <div class="box-body">
                @if($session->steps && count($session->steps) > 0)
                    <div class="timeline">
                        @foreach($session->steps as $index => $step)
                        <div class="time-label">
                            <span class="bg-{{ $step->status === 'completed' ? 'green' : ($step->status === 'failed' ? 'red' : ($step->status === 'running' ? 'blue' : 'gray')) }}">
                                {{ $step->started_at ? $step->started_at->format('H:i:s') : 'Pending' }}
                            </span>
                        </div>
                        <div>
                            <i class="fa fa-{{ $step->status === 'completed' ? 'check bg-green' : ($step->status === 'failed' ? 'times bg-red' : ($step->status === 'running' ? 'cog fa-spin bg-blue' : 'circle-o bg-gray')) }}"></i>
                            <div class="timeline-item">
                                <span class="time">
                                    @if($step->duration)
                                        <i class="fa fa-clock-o"></i> {{ $step->duration_formatted }}
                                    @endif
                                </span>
                                <h3 class="timeline-header">
                                    <strong>Step {{ $index + 1 }}:</strong> {{ $step->step_name }}
                                    <span class="label label-{{ $step->status === 'completed' ? 'success' : ($step->status === 'failed' ? 'danger' : ($step->status === 'running' ? 'primary' : 'default')) }}">
                                        {{ ucfirst($step->status) }}
                                    </span>
                                </h3>
                                <div class="timeline-body">
                                    <p>{{ $step->description }}</p>
                                    @if($step->details)
                                        <div class="step-details">
                                            <strong>Details:</strong>
                                            <pre class="step-details-content">{{ is_array($step->details) ? json_encode($step->details, JSON_PRETTY_PRINT) : $step->details }}</pre>
                                        </div>
                                    @endif
                                    @if($step->error_message)
                                        <div class="alert alert-danger alert-sm">
                                            <strong>Error:</strong> {{ $step->error_message }}
                                        </div>
                                    @endif
                                </div>
                                @if($step->status === 'failed' && $session->status !== 'running')
                                    <div class="timeline-footer">
                                        <button class="btn btn-warning btn-xs retry-step-btn" data-step-id="{{ $step->id }}">
                                            <i class="fa fa-refresh"></i> Retry Step
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                        <div><i class="fa fa-clock-o bg-gray"></i></div>
                    </div>
                @else
                    <p class="text-muted">No update steps recorded for this session.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Migration Details -->
@if($session->migrations && count($session->migrations) > 0)
<div class="row">
    <div class="col-md-12">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-database"></i> Database Migrations
                </h3>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Migration</th>
                                <th>Status</th>
                                <th>Executed At</th>
                                <th>Duration</th>
                                <th>Rollback SQL</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($session->migrations as $migration)
                            <tr>
                                <td>
                                    <strong>{{ $migration->migration_file }}</strong>
                                    @if($migration->batch_number)
                                        <br><small class="text-muted">Batch: {{ $migration->batch_number }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="label label-{{ $migration->status === 'completed' ? 'success' : ($migration->status === 'failed' ? 'danger' : ($migration->status === 'running' ? 'primary' : 'default')) }}">
                                        {{ ucfirst($migration->status) }}
                                    </span>
                                </td>
                                <td>{{ $migration->executed_at ? $migration->executed_at->format('H:i:s') : 'Not executed' }}</td>
                                <td>{{ $migration->duration_formatted ?? 'N/A' }}</td>
                                <td>
                                    @if($migration->rollback_sql)
                                        <button class="btn btn-xs btn-info view-rollback-sql" data-migration-id="{{ $migration->id }}">
                                            <i class="fa fa-eye"></i> View SQL
                                        </button>
                                    @else
                                        <span class="text-muted">None</span>
                                    @endif
                                </td>
                                <td>
                                    @if($migration->status === 'completed' && $migration->rollback_sql)
                                        <button class="btn btn-xs btn-warning rollback-migration" data-migration-id="{{ $migration->id }}">
                                            <i class="fa fa-undo"></i> Rollback
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Session Timeline -->
@if($timeline && count($timeline) > 0)
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-clock-o"></i> Session Timeline
                </h3>
            </div>
            <div class="box-body">
                <div class="timeline timeline-inverse">
                    @foreach($timeline as $event)
                    <div class="time-label">
                        <span class="bg-blue">{{ $event['timestamp']->format('H:i:s') }}</span>
                    </div>
                    <div>
                        <i class="fa fa-{{ $event['icon'] ?? 'info' }} bg-{{ $event['color'] ?? 'blue' }}"></i>
                        <div class="timeline-item">
                            <h3 class="timeline-header">{{ $event['title'] }}</h3>
                            @if(isset($event['description']))
                                <div class="timeline-body">{{ $event['description'] }}</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@else
<div class="alert alert-danger">
    <h4><i class="fa fa-exclamation-triangle"></i> Session Not Found</h4>
    <p>{{ $error ?? 'The requested update session could not be found.' }}</p>
    <a href="{{ route('admin.updates.history') }}" class="btn btn-default">
        <i class="fa fa-arrow-left"></i> Back to History
    </a>
</div>
@endif

</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
            @if($session && $session->status === 'running')
            // Auto-refresh for running sessions
            setInterval(function() {
                location.reload();
            }, 10000);
            @endif

            // Event handlers
            $('#stop-session').click(function() {
                stopSession();
            });

            $('#rollback-session').click(function() {
                rollbackSession();
            });

            $('#view-logs-btn').click(function() {
                viewLogs();
            });

            $('#download-session-btn').click(function() {
                downloadSessionData();
            });

            $('#pause-session-btn').click(function() {
                pauseSession();
            });

            $('.retry-step-btn').click(function() {
                const stepId = $(this).data('step-id');
                retryStep(stepId);
            });

            $('.view-rollback-sql').click(function() {
                const migrationId = $(this).data('migration-id');
                viewRollbackSql(migrationId);
            });

            $('.rollback-migration').click(function() {
                const migrationId = $(this).data('migration-id');
                rollbackMigration(migrationId);
            });
        });

        function stopSession() {
            if (confirm('Are you sure you want to stop this update session?')) {
                $.ajax({
                    url: '{{ route("admin.updates.stop") }}',
                    type: 'POST',
                    data: {
                        session_id: '{{ $session->id ?? "" }}',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            showAlert('success', 'Update session stopped successfully');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            showAlert('error', response.error);
                        }
                    },
                    error: function() {
                        showAlert('error', 'Failed to stop update session');
                    }
                });
            }
        }

        function rollbackSession() {
            if (confirm('Are you sure you want to rollback this entire update session?')) {
                $.ajax({
                    url: '{{ route("admin.updates.rollback", $session->id ?? "unknown") }}',
                    type: 'POST',
                    data: {
                        rollback_type: 'full',
                        _token: '{{ csrf_token() }}'
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
                    error: function() {
                        showAlert('error', 'Failed to initiate rollback');
                    }
                });
            }
        }

        function viewLogs() {
            window.open('{{ route("admin.updates.session-logs", $session->id ?? "unknown") }}', '_blank');
        }

        function downloadSessionData() {
            window.open('{{ route("admin.updates.download-session", $session->id ?? "unknown") }}');
        }

        function pauseSession() {
            showAlert('info', 'Pause functionality coming soon');
        }

        function retryStep(stepId) {
            if (confirm('Are you sure you want to retry this step?')) {
                showAlert('info', 'Step retry functionality coming soon');
            }
        }

        function viewRollbackSql(migrationId) {
            showAlert('info', 'Rollback SQL viewer coming soon');
        }

        function rollbackMigration(migrationId) {
            if (confirm('Are you sure you want to rollback this specific migration?')) {
                showAlert('info', 'Individual migration rollback coming soon');
            }
        }

        function showAlert(type, message) {
            var alertClass = 'alert-' + (type === 'error' ? 'danger' : type);
            var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible">' +
                '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' +
                message + '</div>';
            
            $('#session-details').prepend(alertHtml);
            
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        }
    </script>
@endsection

@section('styles')
    @parent
    <style>
        .option-item {
            margin-bottom: 10px;
        }
        
        .step-details {
            margin-top: 10px;
        }
        
        .step-details-content {
            background: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .timeline-item {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 15px;
        }
        
        .description-block {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .alert-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .btn-group-vertical .btn {
            margin-bottom: 5px;
        }
        
        .table th {
            white-space: nowrap;
        }
        
        code {
            background: #f8f8f8;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 13px;
        }
    </style>
@endsection