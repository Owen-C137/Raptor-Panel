@extends('layouts.admin')

@section('title')
    AI Dashboard
@endsection

@section('content-header')
    <h1>AI Dashboard <small>Artificial Intelligence Overview</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">AI Dashboard</li>
    </ol>
@endsection

@section('content')
<div class="row" id="ai-dashboard">
    {{-- System Status Card --}}
    <div class="col-md-6">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-robot"></i> AI System Status
                </h3>
                <div class="box-tools pull-right">
                    <button class="btn btn-box-tool" onclick="refreshSystemStatus()">
                        <i class="fa fa-refresh" id="status-refresh-icon"></i>
                    </button>
                </div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="text-center">
                            <div class="status-indicator {{ $overview['system_status']['connected'] ? 'online' : 'offline' }}">
                                <i class="fa {{ $overview['system_status']['connected'] ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i>
                            </div>
                            <h4>{{ $overview['system_status']['connected'] ? 'Connected' : 'Disconnected' }}</h4>
                            <p class="text-muted">Ollama Status</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="info-box-content">
                            <span class="info-box-number">{{ count($overview['system_status']['running_models']) }}</span>
                            <span class="info-box-text">Running Models</span>
                        </div>
                    </div>
                </div>
                
                @if(count($overview['system_status']['running_models']) > 0)
                <div class="running-models mt-3">
                    <strong>Active Models:</strong>
                    <ul class="list-unstyled">
                        @foreach($overview['system_status']['running_models'] as $model)
                        <li><code>{{ $model['name'] ?? $model }}</code></li>
                        @endforeach
                    </ul>
                </div>
                @endif
                
                <small class="text-muted">
                    Last checked: {{ \Carbon\Carbon::parse($overview['system_status']['last_check'])->diffForHumans() }}
                </small>
            </div>
        </div>
    </div>

    {{-- Usage Statistics Card --}}
    <div class="col-md-6">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-bar-chart"></i> Usage Statistics
                </h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="info-box-content text-center">
                            <span class="info-box-number">{{ $overview['usage_stats']['total_conversations'] }}</span>
                            <span class="info-box-text">Conversations</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="info-box-content text-center">
                            <span class="info-box-number">{{ $overview['usage_stats']['total_messages'] }}</span>
                            <span class="info-box-text">Messages</span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="info-box-content text-center">
                            <span class="info-box-number">{{ number_format($overview['usage_stats']['total_tokens']) }}</span>
                            <span class="info-box-text">Tokens Used</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="info-box-content text-center">
                            <span class="info-box-number">{{ $overview['usage_stats']['avg_processing_time_ms'] }}ms</span>
                            <span class="info-box-text">Avg Response</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Server Insights Card --}}
    <div class="col-md-6">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-server"></i> Server Insights
                </h3>
                <div class="box-tools pull-right">
                    <span class="label label-{{ $overview['server_insights']['health_score'] >= 80 ? 'success' : ($overview['server_insights']['health_score'] >= 60 ? 'warning' : 'danger') }}">
                        {{ $overview['server_insights']['health_score'] }}% Health
                    </span>
                </div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-3">
                        <div class="text-center">
                            <span class="info-box-number text-green">{{ $overview['server_insights']['active_servers'] }}</span>
                            <span class="info-box-text">Running</span>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="text-center">
                            <span class="info-box-number text-yellow">{{ $overview['server_insights']['inactive_servers'] }}</span>
                            <span class="info-box-text">Stopped</span>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="text-center">
                            <span class="info-box-number text-red">{{ $overview['server_insights']['suspended_servers'] }}</span>
                            <span class="info-box-text">Suspended</span>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="text-center">
                            <span class="info-box-number">{{ $overview['server_insights']['total_servers'] }}</span>
                            <span class="info-box-text">Total</span>
                        </div>
                    </div>
                </div>

                @if(count($overview['server_insights']['servers_needing_attention']) > 0)
                <div class="mt-3">
                    <strong>Servers Needing Attention:</strong>
                    <ul class="list-unstyled">
                        @foreach($overview['server_insights']['servers_needing_attention'] as $server)
                        <li>
                            <a href="{{ route('admin.servers.view', $server['id']) }}">
                                {{ $server['name'] }}
                            </a>
                            <span class="label label-warning">{{ $server['status'] }}</span>
                            <small class="text-muted">({{ $server['last_updated'] }})</small>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Quick Actions Card --}}
    <div class="col-md-6">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-exclamation-triangle"></i> Quick Actions
                </h3>
            </div>
            <div class="box-body">
                @if(count($overview['quick_actions']) > 0)
                    @foreach($overview['quick_actions'] as $action)
                    <div class="alert alert-{{ $action['type'] === 'error' ? 'danger' : ($action['type'] === 'warning' ? 'warning' : 'info') }} alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">×</button>
                        <h4>{{ $action['title'] }}</h4>
                        <p>{{ $action['description'] }}</p>
                        <p>
                            <a href="{{ $action['action_url'] }}" class="btn btn-outline">{{ $action['action_text'] }}</a>
                        </p>
                    </div>
                    @endforeach
                @else
                    <div class="text-center text-muted">
                        <i class="fa fa-check-circle fa-3x"></i>
                        <p>All systems are running smoothly!</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Recent Activity Card --}}
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-comments"></i> Recent AI Activity
                </h3>
                <div class="box-tools pull-right">
                    <button class="btn btn-box-tool" onclick="refreshActivity()">
                        <i class="fa fa-refresh" id="activity-refresh-icon"></i>
                    </button>
                </div>
            </div>
            <div class="box-body">
                @if(count($overview['recent_activity']) > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Context</th>
                                <th>Conversation</th>
                                <th>Last Message</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody id="recent-activity-table">
                            @foreach($overview['recent_activity'] as $activity)
                            <tr>
                                <td>{{ $activity['user'] }}</td>
                                <td>
                                    <span class="label label-default">{{ $activity['context'] }}</span>
                                </td>
                                <td>{{ $activity['title'] }}</td>
                                <td>{{ $activity['last_message'] ?: 'No messages yet' }}</td>
                                <td>{{ $activity['time_ago'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center text-muted">
                    <i class="fa fa-comments-o fa-3x"></i>
                    <p>No recent AI activity</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- System Health Card --}}
    <div class="col-md-6">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-heartbeat"></i> System Health
                </h3>
                <div class="box-tools pull-right">
                    <span class="label label-{{ $overview['system_health']['overall_score'] >= 80 ? 'success' : ($overview['system_health']['overall_score'] >= 60 ? 'warning' : 'danger') }}">
                        {{ $overview['system_health']['overall_score'] }}%
                    </span>
                </div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-6">
                        <strong>Platform:</strong>
                        <ul class="list-unstyled">
                            <li>Users: {{ $overview['system_health']['platform']['users'] }}</li>
                            <li>Nodes: {{ $overview['system_health']['platform']['nodes'] }}</li>
                            <li>Growth: {{ $overview['system_health']['platform']['growth_rate'] }}%/week</li>
                        </ul>
                    </div>
                    <div class="col-sm-6">
                        <strong>AI Health:</strong>
                        <ul class="list-unstyled">
                            <li>Models: {{ $overview['system_health']['ai_health']['models_available'] }}</li>
                            <li>Success Rate: {{ $overview['system_health']['ai_health']['success_rate'] }}%</li>
                            <li>Active Chats: {{ $overview['system_health']['ai_health']['active_conversations'] }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Stats Card --}}
    <div class="col-md-6">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-tachometer"></i> AI Performance
                </h3>
            </div>
            <div class="box-body">
                <div class="progress-group">
                    <span class="progress-text">Response Time</span>
                    <span class="float-right">{{ number_format($overview['system_health']['ai_health']['avg_response_time']) }}ms</span>
                    <div class="progress progress-sm">
                        <div class="progress-bar progress-bar-{{ $overview['system_health']['ai_health']['avg_response_time'] < 2000 ? 'success' : ($overview['system_health']['ai_health']['avg_response_time'] < 5000 ? 'warning' : 'danger') }}" 
                             style="width: {{ min(100, (5000 - $overview['system_health']['ai_health']['avg_response_time']) / 50) }}%"></div>
                    </div>
                </div>

                <div class="progress-group">
                    <span class="progress-text">Success Rate</span>
                    <span class="float-right">{{ $overview['system_health']['ai_health']['success_rate'] }}%</span>
                    <div class="progress progress-sm">
                        <div class="progress-bar progress-bar-success" style="width: {{ $overview['system_health']['ai_health']['success_rate'] }}%"></div>
                    </div>
                </div>

                <div class="progress-group">
                    <span class="progress-text">System Health</span>
                    <span class="float-right">{{ $overview['system_health']['overall_score'] }}%</span>
                    <div class="progress progress-sm">
                        <div class="progress-bar progress-bar-{{ $overview['system_health']['overall_score'] >= 80 ? 'success' : ($overview['system_health']['overall_score'] >= 60 ? 'warning' : 'danger') }}" 
                             style="width: {{ $overview['system_health']['overall_score'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <style>
        .status-indicator {
            font-size: 3em;
            margin-bottom: 10px;
        }
        .status-indicator.online {
            color: #00a65a;
        }
        .status-indicator.offline {
            color: #dd4b39;
        }
        .running-models {
            border-top: 1px solid #f0f0f0;
            padding-top: 10px;
            margin-top: 10px;
        }
        .info-box-content {
            padding: 5px 0;
        }
        .progress-group {
            margin-bottom: 15px;
        }
        .progress-group:last-child {
            margin-bottom: 0;
        }
        #ai-dashboard .box {
            margin-bottom: 20px;
        }
    </style>

    <script>
        // Auto-refresh dashboard data every 30 seconds
        let dashboardInterval;
        
        function initDashboard() {
            refreshDashboard();
            dashboardInterval = setInterval(refreshDashboard, 30000);
        }

        function refreshDashboard() {
            fetch('{{ route("admin.ai.dashboard.data") }}')
                .then(response => response.json())
                .then(data => {
                    // Update dashboard with new data
                    updateDashboardData(data);
                })
                .catch(error => {
                    console.error('Error refreshing dashboard:', error);
                });
        }

        function refreshSystemStatus() {
            const icon = document.getElementById('status-refresh-icon');
            icon.classList.add('fa-spin');
            
            fetch('{{ route("admin.ai.test-connection") }}')
                .then(response => response.json())
                .then(data => {
                    // Update system status
                    location.reload(); // Simple reload for now
                })
                .finally(() => {
                    icon.classList.remove('fa-spin');
                });
        }

        function refreshActivity() {
            const icon = document.getElementById('activity-refresh-icon');
            icon.classList.add('fa-spin');
            
            fetch('{{ route("admin.ai.dashboard.activity") }}')
                .then(response => response.json())
                .then(data => {
                    updateActivityTable(data);
                })
                .finally(() => {
                    icon.classList.remove('fa-spin');
                });
        }

        function updateActivityTable(activities) {
            const tableBody = document.getElementById('recent-activity-table');
            if (!tableBody || !activities.length) return;
            
            tableBody.innerHTML = activities.map(activity => `
                <tr>
                    <td>${activity.user}</td>
                    <td><span class="label label-default">${activity.context}</span></td>
                    <td>${activity.title}</td>
                    <td>${activity.preview || 'No messages yet'}</td>
                    <td>${activity.time_ago}</td>
                </tr>
            `).join('');
        }

        function updateDashboardData(data) {
            // Update various dashboard elements with new data
            // This is a simplified version - in practice, you'd update each section
            console.log('Dashboard data updated:', data);
        }

        // Initialize dashboard when page loads
        document.addEventListener('DOMContentLoaded', initDashboard);

        // Clean up interval when page unloads
        window.addEventListener('beforeunload', function() {
            if (dashboardInterval) {
                clearInterval(dashboardInterval);
            }
        });
    </script>
@endsection