@extends('layouts.admin')

@section('title')
    System Health Monitoring
@endsection

@section('content-header')
    <!-- Hero -->
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
                <div class="flex-grow-1">
                    <h1 class="h3 fw-bold mb-1">System Health Monitoring</h1>
                    <h2 class="fs-base lh-base fw-medium text-muted mb-0">Real-time monitoring and diagnostics</h2>
                </div>
                <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">
                            <a class="link-fx" href="{{ route('admin.index') }}">Admin</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a class="link-fx" href="{{ route('admin.updates.dashboard') }}">Updates</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Health Monitoring</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <!-- END Hero -->
@endsection

@section('content')
<div class="row" id="health-monitoring">
    <!-- Overall System Health -->
    <div class="col-md-12">
        <div class="block block-rounded block-mode-loading-refresh block-themed block-{{ $overallHealth['status'] === 'healthy' ? 'success' : ($overallHealth['status'] === 'warning' ? 'warning' : 'danger') }}">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-heartbeat"></i> Overall System Health
                </h3>
                <div class="block-options">
                    <button type="button" class="btn-block-option" id="refresh-all-health">
                        <i class="fa fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div class="row items-push text-center">
                    <div class="col-sm-6 col-xl-3">
                        <div class="fs-1 fw-bold text-{{ $overallHealth['status'] === 'healthy' ? 'success' : ($overallHealth['status'] === 'warning' ? 'warning' : 'danger') }}">
                            {{ $overallHealth['score'] }}/100
                        </div>
                        <div class="fw-semibold fs-sm text-muted text-uppercase">Health Score</div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="fs-1 fw-bold text-dark">
                            {{ $overallHealth['checks_passed'] }}/{{ $overallHealth['total_checks'] }}
                        </div>
                        <div class="fw-semibold fs-sm text-muted text-uppercase">Checks Passed</div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="fs-1 fw-bold text-{{ $overallHealth['uptime_status'] === 'excellent' ? 'success' : 'warning' }}">
                            {{ $overallHealth['uptime'] }}
                        </div>
                        <div class="fw-semibold fs-sm text-muted text-uppercase">Uptime</div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="fs-1 fw-bold text-dark">
                            {{ $overallHealth['last_check'] }}
                        </div>
                        <div class="fw-semibold fs-sm text-muted text-uppercase">Last Check</div>
                    </div>
                </div>
                
                @if($overallHealth['failed_checks'] > 0)
                    <div class="alert alert-{{ $overallHealth['status'] === 'warning' ? 'warning' : 'danger' }} d-flex">
                        <div class="flex-shrink-0">
                            <i class="fa fa-fw fa-exclamation-triangle"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="alert-heading">Issues Detected</h4>
                            <ul class="mb-0">
                                @foreach($overallHealth['checks'] as $check)
                                    @if(!$check['status'])
                                        <li>{{ $check['name'] }}: {{ $check['message'] }}</li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Database Health -->
    <div class="col-md-6">
        <div class="block block-rounded block-mode-loading-refresh block-themed block-{{ $databaseHealth['status'] === 'healthy' ? 'success' : ($databaseHealth['status'] === 'warning' ? 'warning' : 'danger') }}">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-database"></i> Database Health
                </h3>
                <div class="block-options">
                    <button type="button" class="btn-block-option" id="refresh-database-health">
                        <i class="fa fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div class="health-metric">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Connection Status</strong>
                        <span class="badge bg-{{ $databaseHealth['connection']['status'] === 'connected' ? 'success' : 'danger' }}">
                            {{ ucfirst($databaseHealth['connection']['status']) }}
                        </span>
                    </div>
                    @if(isset($databaseHealth['connection']['response_time']))
                        <small class="text-muted">Response time: {{ $databaseHealth['connection']['response_time'] }}ms</small>
                    @endif
                </div>

                <div class="health-metric">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Database Size</strong>
                        <span class="text-primary">{{ $databaseHealth['size']['formatted'] }}</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-{{ $databaseHealth['size']['usage_percentage'] > 80 ? 'danger' : ($databaseHealth['size']['usage_percentage'] > 60 ? 'warning' : 'success') }}" 
                             role="progressbar" style="width: {{ $databaseHealth['size']['usage_percentage'] }}%" 
                             aria-valuenow="{{ $databaseHealth['size']['usage_percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <small class="text-muted">{{ $databaseHealth['size']['usage_percentage'] }}% of allocated space</small>
                </div>

                <div class="health-metric">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Active Connections</strong>
                        <span class="text-info">{{ $databaseHealth['connections']['active'] }}/{{ $databaseHealth['connections']['max'] }}</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-{{ $databaseHealth['connections']['usage_percentage'] > 80 ? 'danger' : 'info' }}" 
                             role="progressbar" style="width: {{ $databaseHealth['connections']['usage_percentage'] }}%" 
                             aria-valuenow="{{ $databaseHealth['connections']['usage_percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <div class="health-metric">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Recent Queries</strong>
                        <span class="text-success">{{ $databaseHealth['performance']['queries_per_second'] }}/sec</span>
                    </div>
                    <small class="text-muted">Avg response: {{ $databaseHealth['performance']['avg_response_time'] }}ms</small>
                </div>

                @if($databaseHealth['migrations']['pending'] > 0)
                    <div class="alert alert-warning">
                        <strong>{{ $databaseHealth['migrations']['pending'] }}</strong> pending migrations detected
                    </div>
                @endif
            </div>
            <div class="block-content block-content-full block-content-sm bg-body-light fs-sm">
                <button class="btn btn-sm btn-info me-1" id="run-db-diagnostics">
                    <i class="fa fa-stethoscope"></i> Run Diagnostics
                </button>
                <button class="btn btn-sm btn-success" id="optimize-database">
                    <i class="fa fa-magic"></i> Optimize Database
                </button>
            </div>
        </div>
    </div>

    <!-- File System Health -->
    <div class="col-md-6">
        <div class="block block-rounded block-mode-loading-refresh block-themed block-{{ $filesystemHealth['status'] === 'healthy' ? 'success' : ($filesystemHealth['status'] === 'warning' ? 'warning' : 'danger') }}">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-folder"></i> File System Health
                </h3>
                <div class="block-options">
                    <button type="button" class="btn-block-option" id="refresh-filesystem-health">
                        <i class="fa fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div class="health-metric">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Disk Space</strong>
                        <span class="text-primary">{{ $filesystemHealth['disk']['free'] }} free</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-{{ $filesystemHealth['disk']['usage_percentage'] > 90 ? 'danger' : ($filesystemHealth['disk']['usage_percentage'] > 75 ? 'warning' : 'success') }}" 
                             role="progressbar" style="width: {{ $filesystemHealth['disk']['usage_percentage'] }}%" 
                             aria-valuenow="{{ $filesystemHealth['disk']['usage_percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <small class="text-muted">{{ $filesystemHealth['disk']['used'] }} used of {{ $filesystemHealth['disk']['total'] }}</small>
                </div>

                <div class="health-metric">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Inodes Usage</strong>
                        <span class="text-info">{{ $filesystemHealth['inodes']['usage_percentage'] }}%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-{{ $filesystemHealth['inodes']['usage_percentage'] > 90 ? 'danger' : 'info' }}" 
                             role="progressbar" style="width: {{ $filesystemHealth['inodes']['usage_percentage'] }}%" 
                             aria-valuenow="{{ $filesystemHealth['inodes']['usage_percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <div class="health-metric">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>File Permissions</strong>
                        <span class="badge bg-{{ $filesystemHealth['permissions']['status'] === 'correct' ? 'success' : 'danger' }}">
                            {{ ucfirst($filesystemHealth['permissions']['status']) }}
                        </span>
                    </div>
                    @if($filesystemHealth['permissions']['issues'] > 0)
                        <small class="text-danger">{{ $filesystemHealth['permissions']['issues'] }} permission issues found</small>
                    @endif
                </div>

                <div class="health-metric">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Storage Performance</strong>
                        <span class="text-{{ $filesystemHealth['performance']['status'] === 'good' ? 'success' : 'warning' }}">
                            {{ $filesystemHealth['performance']['read_speed'] }} read
                        </span>
                    </div>
                    <small class="text-muted">Write: {{ $filesystemHealth['performance']['write_speed'] }}</small>
                </div>

                @if($filesystemHealth['temp_files']['count'] > 1000)
                    <div class="alert alert-info">
                        <strong>{{ number_format($filesystemHealth['temp_files']['count']) }}</strong> temporary files ({{ $filesystemHealth['temp_files']['size'] }})
                    </div>
                @endif
            </div>
            <div class="block-content block-content-full block-content-sm bg-body-light fs-sm">
                <button class="btn btn-sm btn-info me-1" id="check-permissions">
                    <i class="fa fa-key"></i> Check Permissions
                </button>
                <button class="btn btn-sm btn-warning" id="cleanup-temp-files">
                    <i class="fa fa-trash"></i> Cleanup Temp Files
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- System Resources -->
    <div class="col-md-4">
        <div class="block block-rounded block-mode-loading-refresh block-themed block-{{ $systemResources['status'] === 'healthy' ? 'success' : ($systemResources['status'] === 'warning' ? 'warning' : 'danger') }}">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-microchip"></i> System Resources
                </h3>
                <div class="block-options">
                    <button type="button" class="btn-block-option" id="refresh-resources">
                        <i class="fa fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <!-- CPU Usage -->
                <div class="health-metric">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>CPU Usage</strong>
                        <span class="text-{{ $systemResources['cpu']['usage'] > 80 ? 'danger' : ($systemResources['cpu']['usage'] > 60 ? 'warning' : 'success') }}">
                            {{ $systemResources['cpu']['usage'] }}%
                        </span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-{{ $systemResources['cpu']['usage'] > 80 ? 'danger' : ($systemResources['cpu']['usage'] > 60 ? 'warning' : 'success') }}" 
                             role="progressbar" style="width: {{ $systemResources['cpu']['usage'] }}%" 
                             aria-valuenow="{{ $systemResources['cpu']['usage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <small class="text-muted">Load average: {{ $systemResources['cpu']['load_average'] }}</small>
                </div>

                <!-- Memory Usage -->
                <div class="health-metric">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Memory Usage</strong>
                        <span class="text-{{ $systemResources['memory']['usage_percentage'] > 85 ? 'danger' : ($systemResources['memory']['usage_percentage'] > 70 ? 'warning' : 'success') }}">
                            {{ $systemResources['memory']['usage_percentage'] }}%
                        </span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-{{ $systemResources['memory']['usage_percentage'] > 85 ? 'danger' : ($systemResources['memory']['usage_percentage'] > 70 ? 'warning' : 'success') }}" 
                             role="progressbar" style="width: {{ $systemResources['memory']['usage_percentage'] }}%" 
                             aria-valuenow="{{ $systemResources['memory']['usage_percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <small class="text-muted">{{ $systemResources['memory']['used'] }} / {{ $systemResources['memory']['total'] }}</small>
                </div>

                <!-- Swap Usage -->
                @if($systemResources['swap']['total_mb'] > 0)
                <div class="health-metric">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Swap Usage</strong>
                        <span class="text-{{ $systemResources['swap']['usage_percentage'] > 50 ? 'danger' : 'success' }}">
                            {{ $systemResources['swap']['usage_percentage'] }}%
                        </span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-{{ $systemResources['swap']['usage_percentage'] > 50 ? 'danger' : 'success' }}" 
                             role="progressbar" style="width: {{ $systemResources['swap']['usage_percentage'] }}%" 
                             aria-valuenow="{{ $systemResources['swap']['usage_percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                @endif

                <!-- Process Count -->
                <div class="health-metric">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Active Processes</strong>
                        <span class="text-info">{{ $systemResources['processes']['running'] }}</span>
                    </div>
                    <small class="text-muted">Total: {{ $systemResources['processes']['total'] }} processes</small>
                </div>
            </div>
            <div class="block-content block-content-full block-content-sm bg-body-light fs-sm">
                <button class="btn btn-sm btn-info" id="view-processes">
                    <i class="fa fa-list"></i> View Processes
                </button>
            </div>
        </div>
    </div>

    <!-- Dependencies Status -->
    <div class="col-md-4">
        <div class="block block-rounded block-mode-loading-refresh block-themed block-{{ $dependencies['status'] === 'healthy' ? 'success' : ($dependencies['status'] === 'warning' ? 'warning' : 'danger') }}">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-puzzle-piece"></i> Dependencies
                </h3>
                <div class="block-options">
                    <button type="button" class="btn-block-option" id="refresh-dependencies">
                        <i class="fa fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <!-- PHP Version -->
                <div class="health-metric">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>PHP Version</strong>
                        <span class="badge bg-{{ $dependencies['php']['status'] === 'compatible' ? 'success' : 'warning' }}">
                            {{ $dependencies['php']['version'] }}
                        </span>
                    </div>
                </div>

                <!-- PHP Extensions -->
                <div class="health-metric">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong>PHP Extensions</strong>
                        <span class="text-{{ $dependencies['extensions']['missing'] > 0 ? 'danger' : 'success' }}">
                            {{ $dependencies['extensions']['loaded'] }}/{{ $dependencies['extensions']['required'] }}
                        </span>
                    </div>
                    @if($dependencies['extensions']['missing'] > 0)
                        <small class="text-danger">{{ $dependencies['extensions']['missing'] }} extensions missing</small>
                    @endif
                </div>

                <!-- Composer -->
                <div class="health-metric">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong>Composer</strong>
                        <span class="badge bg-{{ $dependencies['composer']['status'] === 'up-to-date' ? 'success' : 'warning' }}">
                            {{ ucfirst($dependencies['composer']['status']) }}
                        </span>
                    </div>
                    @if($dependencies['composer']['outdated_packages'] > 0)
                        <small class="text-warning">{{ $dependencies['composer']['outdated_packages'] }} packages need updates</small>
                    @endif
                </div>

                <!-- Node.js (if applicable) -->
                @if(isset($dependencies['nodejs']))
                <div class="health-metric">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>Node.js</strong>
                        <span class="badge bg-{{ $dependencies['nodejs']['status'] === 'compatible' ? 'success' : 'warning' }}">
                            {{ $dependencies['nodejs']['version'] }}
                        </span>
                    </div>
                </div>
                @endif

                <!-- System Tools -->
                <div class="health-metric">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong>System Tools</strong>
                        <span class="text-{{ $dependencies['system_tools']['missing'] > 0 ? 'danger' : 'success' }}">
                            {{ $dependencies['system_tools']['available'] }}/{{ $dependencies['system_tools']['required'] }}
                        </span>
                    </div>
                    @if($dependencies['system_tools']['missing'] > 0)
                        <small class="text-danger">Missing: {{ implode(', ', $dependencies['system_tools']['missing_tools']) }}</small>
                    @endif
                </div>
            </div>
            <div class="block-content block-content-full block-content-sm bg-body-light fs-sm">
                <button class="btn btn-sm btn-info me-1" id="detailed-dependencies">
                    <i class="fa fa-list"></i> Detailed View
                </button>
                <button class="btn btn-sm btn-success" id="update-dependencies">
                    <i class="fa fa-sync-alt"></i> Update Dependencies
                </button>
            </div>
        </div>
    </div>

    <!-- Service Status -->
    <div class="col-md-4">
        <div class="block block-rounded block-mode-loading-refresh block-themed block-{{ $services['status'] === 'healthy' ? 'success' : ($services['status'] === 'warning' ? 'warning' : 'danger') }}">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-cogs"></i> Services Status
                </h3>
                <div class="block-options">
                    <button type="button" class="btn-block-option" id="refresh-services">
                        <i class="fa fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                @foreach($services['services'] as $service)
                <div class="health-metric">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong>{{ $service['name'] }}</strong>
                        <span class="badge bg-{{ $service['status'] === 'running' ? 'success' : ($service['status'] === 'stopped' ? 'danger' : 'warning') }}">
                            {{ ucfirst($service['status']) }}
                        </span>
                    </div>
                    @if($service['uptime'])
                        <small class="text-muted">Uptime: {{ $service['uptime'] }}</small>
                    @endif
                    @if($service['memory_usage'])
                        <small class="text-muted"> | Memory: {{ $service['memory_usage'] }}</small>
                    @endif
                </div>
                @endforeach

                @if($services['failed_services'] > 0)
                    <div class="alert alert-danger">
                        <strong>{{ $services['failed_services'] }}</strong> services are not running properly
                    </div>
                @endif
            </div>
            <div class="block-content block-content-full block-content-sm bg-body-light fs-sm">
                <button class="btn btn-sm btn-info me-1" id="service-logs">
                    <i class="fa fa-file-text"></i> View Logs
                </button>
                @if($services['failed_services'] > 0)
                    <button class="btn btn-sm btn-warning" id="restart-services">
                        <i class="fa fa-sync-alt"></i> Restart Failed Services
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Performance Metrics -->
<div class="row">
    <div class="col-md-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-chart-line"></i> Performance Metrics (Last 24 Hours)
                </h3>
                <div class="block-options">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-period="1h">1H</button>
                        <button type="button" class="btn btn-sm btn-outline-primary active" data-period="24h">24H</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-period="7d">7D</button>
                    </div>
                </div>
            </div>
            <div class="block-content">
                <div class="row">
                    <div class="col-md-8">
                        <canvas id="performance-chart" height="300"></canvas>
                    </div>
                    <div class="col-md-4">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Metric</th>
                                        <th>Current</th>
                                        <th>Average</th>
                                        <th>Peak</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>CPU Usage</td>
                                        <td>{{ $performanceMetrics['cpu']['current'] }}%</td>
                                        <td>{{ $performanceMetrics['cpu']['average'] }}%</td>
                                        <td>{{ $performanceMetrics['cpu']['peak'] }}%</td>
                                    </tr>
                                    <tr>
                                        <td>Memory Usage</td>
                                        <td>{{ $performanceMetrics['memory']['current'] }}%</td>
                                        <td>{{ $performanceMetrics['memory']['average'] }}%</td>
                                        <td>{{ $performanceMetrics['memory']['peak'] }}%</td>
                                    </tr>
                                    <tr>
                                        <td>Disk I/O</td>
                                        <td>{{ $performanceMetrics['disk_io']['current'] }}</td>
                                        <td>{{ $performanceMetrics['disk_io']['average'] }}</td>
                                        <td>{{ $performanceMetrics['disk_io']['peak'] }}</td>
                                    </tr>
                                    <tr>
                                        <td>Network I/O</td>
                                        <td>{{ $performanceMetrics['network_io']['current'] }}</td>
                                        <td>{{ $performanceMetrics['network_io']['average'] }}</td>
                                        <td>{{ $performanceMetrics['network_io']['peak'] }}</td>
                                    </tr>
                                    <tr>
                                        <td>Response Time</td>
                                        <td>{{ $performanceMetrics['response_time']['current'] }}ms</td>
                                        <td>{{ $performanceMetrics['response_time']['average'] }}ms</td>
                                        <td>{{ $performanceMetrics['response_time']['peak'] }}ms</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Health Check History -->
<div class="row">
    <div class="col-md-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-history"></i> Recent Health Check History
                </h3>
                <div class="block-options">
                    <button type="button" class="btn btn-sm btn-primary" id="export-health-report">
                        <i class="fa fa-download"></i> Export Report
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-striped table-vcenter" id="health-history-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Overall Status</th>
                                <th>Health Score</th>
                                <th>Database</th>
                                <th>File System</th>
                                <th>Resources</th>
                                <th>Dependencies</th>
                                <th>Services</th>
                                <th>Issues</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($healthHistory as $check)
                            <tr>
                                <td>{{ $check->created_at->format('M d, H:i') }}</td>
                                <td>
                                    <span class="badge bg-{{ $check->overall_status === 'healthy' ? 'success' : ($check->overall_status === 'warning' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($check->overall_status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-{{ $check->health_score >= 80 ? 'success' : ($check->health_score >= 60 ? 'warning' : 'danger') }}">
                                        {{ $check->health_score }}/100
                                    </span>
                                </td>
                                <td class="text-center">
                                    <i class="fa fa-{{ $check->database_status === 'healthy' ? 'check text-success' : ($check->database_status === 'warning' ? 'exclamation-triangle text-warning' : 'times text-danger') }}"></i>
                                </td>
                                <td class="text-center">
                                    <i class="fa fa-{{ $check->filesystem_status === 'healthy' ? 'check text-success' : ($check->filesystem_status === 'warning' ? 'exclamation-triangle text-warning' : 'times text-danger') }}"></i>
                                </td>
                                <td class="text-center">
                                    <i class="fa fa-{{ $check->resources_status === 'healthy' ? 'check text-success' : ($check->resources_status === 'warning' ? 'exclamation-triangle text-warning' : 'times text-danger') }}"></i>
                                </td>
                                <td class="text-center">
                                    <i class="fa fa-{{ $check->dependencies_status === 'healthy' ? 'check text-success' : ($check->dependencies_status === 'warning' ? 'exclamation-triangle text-warning' : 'times text-danger') }}"></i>
                                </td>
                                <td class="text-center">
                                    <i class="fa fa-{{ $check->services_status === 'healthy' ? 'check text-success' : ($check->services_status === 'warning' ? 'exclamation-triangle text-warning' : 'times text-danger') }}"></i>
                                </td>
                                <td>
                                    @if($check->issues_count > 0)
                                        <span class="badge bg-danger">{{ $check->issues_count }}</span>
                                    @else
                                        <span class="text-muted">None</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-info view-health-details" data-id="{{ $check->id }}">
                                        <i class="fa fa-fw fa-eye"></i>
                                    </button>
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

<!-- Health Details Modal -->
<div class="modal fade" id="health-details-modal" tabindex="-1" aria-labelledby="health-details-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="health-details-modal-label">
                    <i class="fa fa-heartbeat"></i> Health Check Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="health-details-content">
                <!-- Content loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let performanceChart;
        
        $(document).ready(function() {
            initializePerformanceChart();
            
            // Auto-refresh health checks
            setInterval(function() {
                refreshAllHealthChecks();
            }, 300000); // Every 5 minutes
            
            // Refresh buttons
            $('#refresh-all-health').click(refreshAllHealthChecks);
            $('#refresh-database-health').click(refreshDatabaseHealth);
            $('#refresh-filesystem-health').click(refreshFilesystemHealth);
            $('#refresh-resources').click(refreshResourcesHealth);
            $('#refresh-dependencies').click(refreshDependenciesHealth);
            $('#refresh-services').click(refreshServicesHealth);
            
            // Action buttons
            $('#run-db-diagnostics').click(runDatabaseDiagnostics);
            $('#optimize-database').click(optimizeDatabase);
            $('#check-permissions').click(checkFilePermissions);
            $('#cleanup-temp-files').click(cleanupTempFiles);
            $('#view-processes').click(viewProcesses);
            $('#detailed-dependencies').click(showDetailedDependencies);
            $('#update-dependencies').click(updateDependencies);
            $('#service-logs').click(viewServiceLogs);
            $('#restart-services').click(restartFailedServices);
            $('#export-health-report').click(exportHealthReport);
            
            // Performance chart period buttons
            $('.btn-group button[data-period]').click(function() {
                $('.btn-group button').removeClass('active');
                $(this).addClass('active');
                updatePerformanceChart($(this).data('period'));
            });
            
            // Health history details
            $('.view-health-details').click(function() {
                const checkId = $(this).data('id');
                viewHealthDetails(checkId);
            });
        });

        function initializePerformanceChart() {
            const ctx = document.getElementById('performance-chart').getContext('2d');
            performanceChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($performanceMetrics['timestamps']),
                    datasets: [
                        {
                            label: 'CPU Usage (%)',
                            data: @json($performanceMetrics['cpu']['data']),
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.1)',
                            tension: 0.1
                        },
                        {
                            label: 'Memory Usage (%)',
                            data: @json($performanceMetrics['memory']['data']),
                            borderColor: 'rgb(54, 162, 235)',
                            backgroundColor: 'rgba(54, 162, 235, 0.1)',
                            tension: 0.1
                        },
                        {
                            label: 'Disk I/O (MB/s)',
                            data: @json($performanceMetrics['disk_io']['data']),
                            borderColor: 'rgb(255, 205, 86)',
                            backgroundColor: 'rgba(255, 205, 86, 0.1)',
                            tension: 0.1,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Percentage (%)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'MB/s'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    }
                }
            });
        }

        function updatePerformanceChart(period) {
            $.ajax({
                url: '{{ route("admin.updates.health.performance") }}',
                data: { period: period },
                success: function(response) {
                    performanceChart.data.labels = response.timestamps;
                    performanceChart.data.datasets[0].data = response.cpu.data;
                    performanceChart.data.datasets[1].data = response.memory.data;
                    performanceChart.data.datasets[2].data = response.disk_io.data;
                    performanceChart.update();
                }
            });
        }

        function refreshAllHealthChecks() {
            $('#refresh-all-health').html('<i class="fa fa-spin fa-spinner"></i> Refreshing...');
            
            $.ajax({
                url: '{{ route("admin.updates.health.refresh") }}',
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Health checks refreshed successfully');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to refresh health checks');
                },
                complete: function() {
                    $('#refresh-all-health').html('<i class="fa fa-refresh"></i> Refresh All');
                }
            });
        }

        function refreshDatabaseHealth() {
            performHealthAction('database', 'refresh', 'Database health refreshed');
        }

        function refreshFilesystemHealth() {
            performHealthAction('filesystem', 'refresh', 'File system health refreshed');
        }

        function refreshResourcesHealth() {
            performHealthAction('resources', 'refresh', 'System resources refreshed');
        }

        function refreshDependenciesHealth() {
            performHealthAction('dependencies', 'refresh', 'Dependencies status refreshed');
        }

        function refreshServicesHealth() {
            performHealthAction('services', 'refresh', 'Services status refreshed');
        }

        function runDatabaseDiagnostics() {
            performHealthAction('database', 'diagnostics', 'Database diagnostics completed', '#run-db-diagnostics');
        }

        function optimizeDatabase() {
            performHealthAction('database', 'optimize', 'Database optimization completed', '#optimize-database');
        }

        function checkFilePermissions() {
            performHealthAction('filesystem', 'check-permissions', 'File permissions checked', '#check-permissions');
        }

        function cleanupTempFiles() {
            performHealthAction('filesystem', 'cleanup-temp', 'Temporary files cleaned up', '#cleanup-temp-files');
        }

        function updateDependencies() {
            performHealthAction('dependencies', 'update', 'Dependencies updated', '#update-dependencies');
        }

        function restartFailedServices() {
            if (confirm('Are you sure you want to restart failed services? This may temporarily disrupt service.')) {
                performHealthAction('services', 'restart-failed', 'Failed services restarted', '#restart-services');
            }
        }

        function performHealthAction(component, action, successMessage, buttonSelector = null) {
            if (buttonSelector) {
                const $button = $(buttonSelector);
                const originalText = $button.html();
                $button.html('<i class="fa fa-spin fa-spinner"></i> Processing...');
            }
            
            $.ajax({
                url: '{{ route("admin.updates.health.action") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    component: component,
                    action: action
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', successMessage);
                        if (response.reload) {
                            setTimeout(() => location.reload(), 2000);
                        }
                    } else {
                        showAlert('error', response.error);
                    }
                },
                error: function() {
                    showAlert('error', 'Action failed');
                },
                complete: function() {
                    if (buttonSelector) {
                        const $button = $(buttonSelector);
                        setTimeout(() => {
                            $button.html($button.data('original-text') || originalText);
                        }, 2000);
                    }
                }
            });
        }

        function viewProcesses() {
            window.open('{{ route("admin.updates.health.processes") }}', '_blank');
        }

        function showDetailedDependencies() {
            window.open('{{ route("admin.updates.health.dependencies") }}', '_blank');
        }

        function viewServiceLogs() {
            window.open('{{ route("admin.updates.health.service-logs") }}', '_blank');
        }

        function viewHealthDetails(checkId) {
            $.ajax({
                url: '{{ route("admin.updates.health.details", ":id") }}'.replace(':id', checkId),
                success: function(response) {
                    $('#health-details-content').html(response.html);
                    const modal = new bootstrap.Modal(document.getElementById('health-details-modal'));
                    modal.show();
                },
                error: function() {
                    showAlert('error', 'Failed to load health check details');
                }
            });
        }

        function exportHealthReport() {
            const period = $('.btn-group .active').data('period') || '24h';
            window.location = '{{ route("admin.updates.health.export") }}?period=' + period;
        }

        function showAlert(type, message) {
            const alertClass = 'alert-' + (type === 'error' ? 'danger' : type);
            const alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible">' +
                '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' +
                message + '</div>';
            
            $('#health-monitoring').prepend(alertHtml);
            
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        }
    </script>
@endsection

@section('styles')
    @parent
    <style>
        .health-metric {
            padding: 12px 0;
            border-bottom: 1px solid var(--bs-border-color);
        }
        
        .health-metric:last-child {
            border-bottom: none;
        }
        
        .progress {
            margin-bottom: 8px;
        }
        
        .btn-group button.active {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
            color: white;
        }
        
        .alert {
            margin-bottom: 1rem;
        }
        
        .badge {
            font-size: 0.75em;
        }
        
        canvas {
            max-height: 300px;
        }
        
        #health-history-table {
            font-size: 0.875rem;
        }
        
        #health-history-table th,
        #health-history-table td {
            padding: 0.75rem 0.5rem;
            vertical-align: middle;
        }
        
        .table-sm th,
        .table-sm td {
            padding: 0.5rem 0.25rem;
            font-size: 0.875rem;
        }
    </style>
@endsection