@extends('layouts.admin')
@include('partials/admin.updates.nav', ['activeTab' => 'dashboard'])

@section('title')
    Update System Dashboard
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Update System Dashboard
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Monitor and manage your Pterodactyl panel updates.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.index') }}">Admin</a>
          </li>
          <li class="breadcrumb-item" aria-current="page">
            Update System
          </li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
@yield('updates::nav')
<div class="row" id="update-dashboard">
    <!-- System Status Cards -->
    <div class="col-xl-3 col-sm-6">
        <div class="block block-rounded text-center">
            <div class="block-content">
                <div class="fs-2 fw-bold text-body-color-dark" id="status-icon">
                    @if(isset($currentStatus['status']))
                        @if($currentStatus['status'] === 'idle')
                            <i class="fa fa-check-circle text-success"></i>
                        @elseif($currentStatus['status'] === 'pending' || $currentStatus['status'] === 'running')
                            <i class="fa fa-circle-notch fa-spin text-primary"></i>
                        @else
                            <i class="fa fa-exclamation-triangle text-warning"></i>
                        @endif
                    @else
                        <i class="fa fa-question text-muted"></i>
                    @endif
                </div>
                <div class="fs-sm fw-semibold text-uppercase text-muted pt-1">System Status</div>
                <div class="fs-3 fw-bold" id="system-status">
                    @if(isset($currentStatus['status']))
                        @if($currentStatus['status'] === 'idle')
                            <span class="text-success">Up To Date</span>
                        @elseif($currentStatus['status'] === 'pending')
                            <span class="text-primary">Update Pending</span>
                        @elseif($currentStatus['status'] === 'running')
                            <span class="text-primary">Updating...</span>
                        @else
                            <span class="text-warning">{{ ucfirst($currentStatus['status']) }}</span>
                        @endif
                    @else
                        <span class="text-muted">Unknown</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="block block-rounded text-center">
            <div class="block-content">
                <div class="fs-2 fw-bold text-primary">
                    <i class="fa fa-code-fork"></i>
                </div>
                <div class="fs-sm fw-semibold text-uppercase text-muted pt-1">Current Version</div>
                <div class="fs-3 fw-bold text-dark" id="current-version">
                    {{ $currentStatus['current_version'] ?? 'Unknown' }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="block block-rounded text-center">
            <div class="block-content">
                <div class="fs-2 fw-bold" id="health-icon">
                    @if(isset($systemHealth['overall_status']))
                        @if($systemHealth['overall_status'] === 'healthy')
                            <i class="fa fa-heartbeat text-success"></i>
                        @elseif($systemHealth['overall_status'] === 'warning')
                            <i class="fa fa-heart text-warning"></i>
                        @elseif($systemHealth['overall_status'] === 'error')
                            <i class="fa fa-heart-broken text-danger"></i>
                        @else
                            <i class="fa fa-question-circle text-muted"></i>
                        @endif
                    @else
                        <i class="fa fa-question-circle text-muted"></i>
                    @endif
                </div>
                <div class="fs-sm fw-semibold text-uppercase text-muted pt-1">System Health</div>
                <div class="fs-3 fw-bold text-dark" id="system-health">
                    @if(isset($systemHealth['overall_status']) && $systemHealth['overall_status'] !== 'unknown')
                        {{ ucfirst($systemHealth['overall_status']) }}
                    @else
                        <span class="text-muted">Not Checked</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="block block-rounded text-center">
            <div class="block-content">
                <div class="fs-2 fw-bold text-success">
                    <i class="fa fa-calendar"></i>
                </div>
                <div class="fs-sm fw-semibold text-uppercase text-muted pt-1">Last Update</div>
                <div class="fs-3 fw-bold text-dark" id="last-update">
                    {{ $statistics['last_successful_update'] ?? 'Never' }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Active Update Progress -->
@if($activeSession)
<div class="row">
    <div class="col-md-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-cog fa-spin me-1"></i> Active Update Session
                </h3>
                <div class="block-options">
                    <button class="btn btn-danger btn-sm" id="stop-update-btn">
                        <i class="fa fa-stop me-1"></i> Stop Update
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div class="row">
                    <div class="col-md-8">
                        <h4 class="fw-bold mb-3">Updating to Version {{ $activeSession->target_version }}</h4>
                        <div class="progress mb-3" style="height: 20px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 id="update-progress-bar" 
                                 role="progressbar" 
                                 style="width: {{ $activeSession->progress_percentage ?? 0 }}%"
                                 aria-valuenow="{{ $activeSession->progress_percentage ?? 0 }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                <span id="progress-text">
                                    {{ $activeSession->progress_percentage ?? 0 }}% Complete
                                </span>
                            </div>
                        </div>
                        <p class="text-muted" id="current-step">
                            Current Step: {{ $activeSession->current_step ?? 'Initializing...' }}
                        </p>
                    </div>
                    <div class="col-md-4">
                        <div class="block block-rounded text-center">
                            <div class="block-content">
                                <div class="fs-2 fw-bold text-primary">
                                    <i class="fa fa-clock"></i>
                                </div>
                                <div class="fs-sm fw-semibold text-uppercase text-muted pt-1">Duration</div>
                                <div class="fs-3 fw-bold text-dark" id="update-duration">
                                    {{ $activeSession->duration_formatted ?? '0m' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Update Steps Timeline -->
                <div class="row">
                    <div class="col-md-12">
                        <h5>Update Timeline</h5>
                        <div class="timeline" id="update-timeline">
                            @foreach($activeSession->steps ?? [] as $step)
                            <div class="time-label">
                                <span class="bg-{{ $step->status === 'completed' ? 'green' : ($step->status === 'running' ? 'blue' : 'gray') }}">
                                    {{ $step->started_at ? $step->started_at->format('H:i:s') : 'Pending' }}
                                </span>
                            </div>
                            <div>
                                <i class="fa fa-{{ $step->status === 'completed' ? 'check bg-green' : ($step->status === 'running' ? 'cog fa-spin bg-blue' : 'circle-o bg-gray') }}"></i>
                                <div class="timeline-item">
                                    <h3 class="timeline-header">{{ $step->step_name }}</h3>
                                    <div class="timeline-body">
                                        {{ $step->description }}
                                        @if($step->status === 'running' && $step->details)
                                            <div class="text-muted">
                                                <small>{{ $step->details }}</small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- System Health Overview -->
<div class="row">
    <div class="col-md-6">
        <div class="block block-rounded block-themed">
            <div class="block-header bg-success">
                <h3 class="block-title text-white">
                    <i class="fa fa-heartbeat me-1"></i> System Health Overview
                </h3>
                <div class="block-options">
                    <button class="btn btn-success btn-sm" id="run-health-check">
                        <i class="fa fa-refresh me-1"></i> Run Check
                    </button>
                </div>
            </div>
            <div class="block-content" id="system-health-container">
                <div class="row" id="health-checks">
                    @if(isset($healthOverview) && !empty($healthOverview))
                        @php $checks = $healthOverview['checks'] ?? []; @endphp
                        
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fa fa-hdd-o fa-2x mb-2 text-primary"></i>
                                    <h5 class="card-title">Disk Space</h5>
                                    @if(isset($checks['disk_space']))
                                        <p class="card-text"><strong>{{ $checks['disk_space']['details']['usage_percent'] }}%</strong> used</p>
                                        <small class="text-muted">{{ $checks['disk_space']['details']['free_space'] }} free of {{ $checks['disk_space']['details']['total_space'] }}</small>
                                    @else
                                        <p class="card-text text-muted">Loading...</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fa fa-microchip fa-2x mb-2 text-success"></i>
                                    <h5 class="card-title">Memory Usage</h5>
                                    @if(isset($checks['memory_usage']))
                                        <p class="card-text"><strong>{{ $checks['memory_usage']['details']['usage_percent'] }}%</strong> used</p>
                                        <small class="text-muted">{{ $checks['memory_usage']['details']['current_usage'] }} / {{ $checks['memory_usage']['details']['total_memory'] ?? $checks['memory_usage']['details']['memory_limit'] }}</small>
                                    @else
                                        <p class="card-text text-muted">Loading...</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    @if(isset($checks['database_connection']) && $checks['database_connection']['status'] === 'healthy')
                                        <i class="fa fa-database fa-2x mb-2 text-success"></i>
                                        <h5 class="card-title">Database</h5>
                                        <p class="card-text"><span class="text-success"><strong>Connected</strong></span></p>
                                        <small class="text-muted">{{ $checks['database_connection']['details']['driver'] ?? 'Unknown' }}
                                            @if(isset($checks['database_connection']['details']['connection_time']))
                                                ({{ $checks['database_connection']['details']['connection_time'] }})
                                            @endif
                                        </small>
                                    @else
                                        <i class="fa fa-database fa-2x mb-2 text-danger"></i>
                                        <h5 class="card-title">Database</h5>
                                        <p class="card-text"><span class="text-danger"><strong>Disconnected</strong></span></p>
                                        <small class="text-muted">{{ $checks['database_connection']['details']['driver'] ?? 'Unknown' }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    @if(isset($checks['file_permissions']) && $checks['file_permissions']['status'] === 'healthy')
                                        <i class="fa fa-shield fa-2x mb-2 text-success"></i>
                                        <h5 class="card-title">File Permissions</h5>
                                        <p class="card-text"><span class="text-success"><strong>OK</strong></span></p>
                                        <small class="text-muted">All permissions correct</small>
                                    @else
                                        <i class="fa fa-shield fa-2x mb-2 text-warning"></i>
                                        <h5 class="card-title">File Permissions</h5>
                                        <p class="card-text"><span class="text-warning"><strong>Issues Found</strong></span></p>
                                        <small class="text-muted">Permission issues detected</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="col-sm-12">
                            <div class="text-center text-muted p-3">
                                <i class="fa fa-spinner fa-spin fa-2x mb-2"></i>
                                <p>Loading system health data...</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="block block-rounded block-themed">
            <div class="block-header bg-info">
                <h3 class="block-title text-white">
                    <i class="fa fa-chart-bar me-1"></i> Update Statistics
                </h3>
            </div>
            <div class="block-content">
                <div class="row text-center">
                    <div class="col-sm-6 py-3">
                        <div class="text-success mb-1">
                            <i class="fa fa-caret-up"></i> 
                            {{ number_format((($statistics['successful_updates'] ?? 0) / max(($statistics['total_updates'] ?? 1), 1)) * 100, 1) }}%
                        </div>
                        <div class="fs-3 fw-bold text-dark">{{ $statistics['successful_updates'] ?? 0 }}</div>
                        <div class="fs-sm fw-semibold text-uppercase text-muted">Successful Updates</div>
                    </div>
                    <div class="col-sm-6 py-3">
                        <div class="text-danger mb-1">
                            <i class="fa fa-caret-down"></i>
                            {{ number_format((($statistics['failed_updates'] ?? 0) / max(($statistics['total_updates'] ?? 1), 1)) * 100, 1) }}%
                        </div>
                        <div class="fs-3 fw-bold text-dark">{{ $statistics['failed_updates'] ?? 0 }}</div>
                        <div class="fs-sm fw-semibold text-uppercase text-muted">Failed Updates</div>
                    </div>
                </div>
                <div class="row text-center border-top pt-3">
                    <div class="col-sm-6 py-2">
                        <div class="fs-3 fw-bold text-dark">{{ $statistics['rollbacks_performed'] ?? 0 }}</div>
                        <div class="fs-sm fw-semibold text-uppercase text-muted">Rollbacks</div>
                    </div>
                    <div class="col-sm-6 py-2">
                        <div class="fs-3 fw-bold text-dark">{{ $statistics['avg_update_duration'] ?? '0m' }}</div>
                        <div class="fs-sm fw-semibold text-uppercase text-muted">Avg Duration</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Update History & Scheduled Updates -->
<div class="row">
    <div class="col-md-6">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-history me-1"></i> Recent Updates
                </h3>
                <div class="block-options">
                    <a href="{{ route('admin.updates.history') }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-external-link me-1"></i> View All
                    </a>
                </div>
            </div>
            <div class="block-content">
                @if(count($updateHistory) > 0)
                    <div class="timeline">
                        @foreach($updateHistory as $session)
                        <div class="timeline-item">
                            <div class="timeline-point timeline-point-{{ $session->status === 'completed' ? 'success' : ($session->status === 'failed' ? 'danger' : 'primary') }}">
                                <i class="fa fa-{{ $session->status === 'completed' ? 'check' : ($session->status === 'failed' ? 'times' : 'cog') }}"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div class="fw-semibold">
                                        <strong>{{ $session->from_version }}</strong> → 
                                        <strong>{{ $session->target_version }}</strong>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fa fa-clock me-1"></i>{{ $session->created_at->diffForHumans() }}
                                    </small>
                                </div>
                                <div class="mb-2">
                                    <span class="badge bg-{{ $session->status === 'completed' ? 'success' : ($session->status === 'failed' ? 'danger' : 'primary') }}">
                                        {{ ucfirst($session->status) }}
                                    </span>
                                    @if($session->duration)
                                        <span class="text-muted ms-2">Duration: {{ $session->duration_formatted }}</span>
                                    @endif
                                    @if($session->rolled_back)
                                        <span class="badge bg-warning ms-2">Rolled Back</span>
                                    @endif
                                </div>
                                <div>
                                    <a href="{{ route('admin.updates.session-details', $session->id) }}" class="btn btn-sm btn-primary">
                                        <i class="fa fa-eye me-1"></i>View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-muted p-3">
                        <i class="fa fa-history fa-2x mb-2"></i>
                        <p>No update history available</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="block block-rounded block-themed">
            <div class="block-header bg-warning">
                <h3 class="block-title text-white">
                    <i class="fa fa-calendar me-1"></i> Scheduled Updates
                </h3>
                <div class="block-options">
                    <a href="{{ route('admin.updates.manage') }}" class="btn btn-warning btn-sm">
                        <i class="fa fa-plus me-1"></i> Schedule Update
                    </a>
                </div>
            </div>
            <div class="block-content">
                @if(count($scheduledUpdates) > 0)
                    @foreach($scheduledUpdates as $schedule)
                    <div class="alert alert-{{ $schedule->status === 'active' ? 'info' : 'secondary' }} d-flex align-items-start">
                        <div class="me-2">
                            <i class="fa fa-calendar"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="alert-heading mb-1">
                                Update to {{ $schedule->target_version }}
                            </h6>
                            <div class="mb-2">
                                <strong>Scheduled:</strong> {{ $schedule->scheduled_at->format('M d, Y H:i') }}
                                <br>
                                <strong>Status:</strong> 
                                <span class="badge bg-{{ $schedule->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($schedule->status) }}
                                </span>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-info btn-sm edit-schedule" data-id="{{ $schedule->id }}">
                                    <i class="fa fa-edit me-1"></i> Edit
                                </button>
                                <button class="btn btn-danger btn-sm cancel-schedule" data-id="{{ $schedule->id }}">
                                    <i class="fa fa-times me-1"></i> Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="text-center text-muted p-3">
                        <i class="fa fa-calendar fa-2x mb-2"></i>
                        <p>No scheduled updates</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-md-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-bolt me-1"></i> Quick Actions
                </h3>
            </div>
            <div class="block-content">
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-primary" id="check-updates-btn">
                        <i class="fa fa-refresh me-1"></i> Check for Updates
                    </button>
                    <button type="button" class="btn btn-success" id="manual-update-btn">
                        <i class="fa fa-arrow-up me-1"></i> Manual Update
                    </button>
                    <button type="button" class="btn btn-info" id="test-system-btn">
                        <i class="fa fa-stethoscope me-1"></i> Test System
                    </button>
                    <a href="{{ route('admin.updates.configuration') }}" class="btn btn-warning">
                        <i class="fa fa-cog me-1"></i> Configuration
                    </a>
                    <a href="{{ route('admin.updates.health') }}" class="btn btn-info">
                        <i class="fa fa-heartbeat me-1"></i> Health Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Error Display -->
@if(isset($error))
<div class="row">
    <div class="col-md-12">
        <div class="alert alert-danger">
            <h4><i class="icon fa fa-ban"></i> Dashboard Error</h4>
            {{ $error }}
        </div>
    </div>
</div>
@endif

</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
            // Load initial system health data only if not already loaded
            @if(!isset($healthOverview) || empty($healthOverview))
            loadSystemHealth();
            @endif
            
            // Auto-refresh dashboard every 30 seconds
            setInterval(function() {
                refreshDashboard();
                loadSystemHealth();
            }, 30000);

            // Real-time update progress monitoring
            @if($activeSession)
            setInterval(function() {
                updateProgress();
            }, 5000);
            @endif

            // Event handlers
            $('#stop-update-btn').click(function() {
                stopUpdate();
            });

            $('#run-health-check').click(function() {
                runHealthCheck();
            });

            $('#check-updates-btn').click(function() {
                checkForUpdates();
            });

            $('#manual-update-btn').click(function() {
                window.location.href = '{{ route("admin.updates.manage") }}';
            });

            $('#test-system-btn').click(function() {
                testSystem();
            });

            $('.edit-schedule').click(function() {
                var scheduleId = $(this).data('id');
                editSchedule(scheduleId);
            });

            $('.cancel-schedule').click(function() {
                var scheduleId = $(this).data('id');
                cancelSchedule(scheduleId);
            });
        });

        function refreshDashboard() {
            $.ajax({
                url: '{{ route("admin.updates.status") }}',
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        updateStatusDisplay(response.status);
                    }
                },
                error: function() {
                    console.log('Failed to refresh dashboard status');
                }
            });

            $.ajax({
                url: '{{ route("admin.updates.health-data") }}',
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        updateHealthDisplay(response.health);
                    }
                },
                error: function() {
                    console.log('Failed to refresh health status');
                }
            });
        }

        function updateProgress() {
            $.ajax({
                url: '{{ route("admin.updates.current-progress") }}',
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        updateProgressDisplay(response.progress);
                    }
                },
                error: function() {
                    // Update might have completed, reload page
                    location.reload();
                }
            });
        }

        function stopUpdate() {
            if (confirm('Are you sure you want to stop the current update? This may leave the system in an inconsistent state.')) {
                $.ajax({
                    url: '{{ route("admin.updates.stop") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            showAlert('success', 'Update stop initiated successfully');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            showAlert('error', response.error);
                        }
                    },
                    error: function() {
                        showAlert('error', 'Failed to stop update');
                    }
                });
            }
        }

        function runHealthCheck() {
            $('#run-health-check').prop('disabled', true).html('<i class="fa fa-spin fa-refresh"></i> Running...');
            
            $.ajax({
                url: '{{ route("admin.updates.health-check") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Health check completed successfully');
                        // Refresh health display
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to run health check');
                },
                complete: function() {
                    $('#run-health-check').prop('disabled', false).html('<i class="fa fa-refresh"></i> Run Check');
                }
            });
        }

        function checkForUpdates() {
            $('#check-updates-btn').prop('disabled', true).html('<i class="fa fa-spin fa-refresh"></i> Checking...');
            
            $.ajax({
                url: '{{ route("admin.updates.check") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        if (response.updates && response.updates.length > 0) {
                            showAlert('info', 'Found ' + response.updates.length + ' available update(s)');
                        } else {
                            showAlert('success', 'System is up to date');
                        }
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to check for updates');
                },
                complete: function() {
                    $('#check-updates-btn').prop('disabled', false).html('<i class="fa fa-refresh"></i> Check for Updates');
                }
            });
        }

        function testSystem() {
            $('#test-system-btn').prop('disabled', true).html('<i class="fa fa-spin fa-stethoscope"></i> Testing...');
            
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
                    $('#test-system-btn').prop('disabled', false).html('<i class="fa fa-stethoscope"></i> Test System');
                }
            });
        }

        function updateStatusDisplay(status) {
            $('#system-status').text(status.status);
            $('#current-version').text(status.current_version);
            
            // Update status icon
            $('#status-icon i').hide();
            switch(status.status) {
                case 'running':
                    $('#status-icon .fa-circle-o-notch').show();
                    break;
                case 'idle':
                    $('#status-icon .fa-check').show();
                    break;
                case 'failed':
                    $('#status-icon .fa-times').show();
                    break;
                default:
                    $('#status-icon .fa-exclamation-triangle').show();
            }
        }

        function updateHealthDisplay(health) {
            $('#system-health').text(health.overall_status);
            
            // Update health icon
            $('#health-icon i').hide();
            switch(health.overall_status) {
                case 'healthy':
                    $('#health-icon .fa-heartbeat').show();
                    break;
                case 'warning':
                    $('#health-icon .fa-heart').show();
                    break;
                default:
                    $('#health-icon .fa-heart-o').show();
            }
        }

        function updateProgressDisplay(progress) {
            $('#update-progress-bar').css('width', progress.percentage + '%');
            $('#progress-text').text(progress.percentage + '% Complete');
            $('#current-step').html('Current Step: ' + progress.current_step);
            $('#update-duration').text(progress.duration_formatted);
        }

        function loadSystemHealth() {
            console.log('Loading system health data...');
            $.ajax({
                url: '{{ route("admin.updates.api.system-health-overview") }}',
                type: 'GET',
                success: function(response) {
                    console.log('Health data response:', response);
                    if (response.success && response.health) {
                        updateSystemHealthDisplay(response.health);
                    } else {
                        console.error('Invalid response format:', response);
                        $('#health-checks').html('<div class="col-12 text-center text-danger"><p>Failed to load health data</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Health data request failed:', status, error, xhr.responseText);
                    $('#health-checks').html('<div class="col-12 text-center text-danger"><p>Error loading health data: ' + error + '</p></div>');
                }
            });
        }

        function updateSystemHealthDisplay(health) {
            // Extract checks from health data
            const checks = health.checks || health;
            
            // Create the health cards HTML structure
            let healthCardsHtml = `
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fa fa-hdd-o fa-2x mb-2 text-primary"></i>
                            <h5 class="card-title">Disk Space</h5>
                            <p class="card-text"><strong>${checks.disk_space.details.usage_percent}%</strong> used</p>
                            <small class="text-muted">${checks.disk_space.details.free_space} free of ${checks.disk_space.details.total_space}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fa fa-microchip fa-2x mb-2 text-success"></i>
                            <h5 class="card-title">Memory Usage</h5>
                            <p class="card-text"><strong>${checks.memory_usage.details.usage_percent}%</strong> used</p>
                            <small class="text-muted">${checks.memory_usage.details.current_usage} / ${checks.memory_usage.details.total_memory || checks.memory_usage.details.memory_limit}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body text-center">`;
            
            if (checks.database_connection.status === 'healthy') {
                healthCardsHtml += `
                            <i class="fa fa-database fa-2x mb-2 text-success"></i>
                            <h5 class="card-title">Database</h5>
                            <p class="card-text"><span class="text-success"><strong>Connected</strong></span></p>
                            <small class="text-muted">${checks.database_connection.details.driver}`;
                if (checks.database_connection.details.connection_time) {
                    healthCardsHtml += ` (${checks.database_connection.details.connection_time})`;
                }
                healthCardsHtml += `</small>`;
            } else {
                healthCardsHtml += `
                            <i class="fa fa-database fa-2x mb-2 text-danger"></i>
                            <h5 class="card-title">Database</h5>
                            <p class="card-text"><span class="text-danger"><strong>Disconnected</strong></span></p>
                            <small class="text-muted">${checks.database_connection.details.driver}</small>`;
            }
            
            healthCardsHtml += `
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body text-center">`;
            
            if (checks.file_permissions.status === 'healthy') {
                healthCardsHtml += `
                            <i class="fa fa-shield fa-2x mb-2 text-success"></i>
                            <h5 class="card-title">File Permissions</h5>
                            <p class="card-text"><span class="text-success"><strong>OK</strong></span></p>
                            <small class="text-muted">All permissions correct</small>`;
            } else {
                healthCardsHtml += `
                            <i class="fa fa-shield fa-2x mb-2 text-warning"></i>
                            <h5 class="card-title">File Permissions</h5>
                            <p class="card-text"><span class="text-warning"><strong>Issues Found</strong></span></p>
                            <small class="text-muted">Permission issues detected</small>`;
            }
            
            healthCardsHtml += `
                        </div>
                    </div>
                </div>`;
            
            // Replace the entire health-checks content
            $('#health-checks').html(healthCardsHtml);
        }

        function showAlert(type, message) {
            // Use the new toast notification system
            if (window.showToast) {
                window.showToast(message, type);
            } else {
                // Fallback to console if toast system isn't loaded yet
                console.log(`${type.toUpperCase()}: ${message}`);
            }
        }

        function editSchedule(scheduleId) {
            // Implement schedule editing functionality
            showAlert('info', 'Schedule editing functionality coming soon');
        }

        function cancelSchedule(scheduleId) {
            if (confirm('Are you sure you want to cancel this scheduled update?')) {
                // Implement schedule cancellation
                showAlert('info', 'Schedule cancellation functionality coming soon');
            }
        }
    </script>
@endsection

@section('styles')
    @parent
    <style>
        .health-check-item {
            margin-bottom: 10px;
            padding: 5px;
        }
        
        .timeline-inverse .timeline-item {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 3px;
            margin-right: 15px;
        }
        
        .description-block {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .info-box-icon {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }
        
        #update-timeline {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .alert {
            margin-bottom: 10px;
        }
    </style>
@endsection

@include('partials.admin.updates.toast-notifications')