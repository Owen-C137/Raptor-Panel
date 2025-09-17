@extends('layouts.admin')

@section('title')
    Server Analysis
@endsection

@section('content-header')
    <h1>AI Server Analysis <small>Performance insights and recommendations</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.ai.dashboard') }}">AI Dashboard</a></li>
        <li class="active">Server Analysis</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        {{-- Analysis Overview Cards --}}
        <div class="row">
            <div class="col-md-3">
                <div class="small-box bg-blue">
                    <div class="inner">
                        <h3>{{ $analysis['total_servers'] }}</h3>
                        <p>Total Servers</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-server"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-green">
                    <div class="inner">
                        <h3>{{ $analysis['analyzed_servers'] }}</h3>
                        <p>Analyzed</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-check-circle"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-yellow">
                    <div class="inner">
                        <h3>{{ $analysis['recent_analyses'] }}</h3>
                        <p>This Week</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-clock-o"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-red">
                    <div class="inner">
                        <h3>{{ number_format($analysis['avg_health_score'], 1) }}%</h3>
                        <p>Avg Health Score</p>
                    </div>
                    <div class="icon">
                        <i class="fa fa-heartbeat"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        {{-- Server List for Analysis --}}
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Server Analysis</h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-primary btn-sm" onclick="bulkAnalyzeSelected()">
                        <i class="fa fa-cogs"></i> Analyze Selected
                    </button>
                    <button type="button" class="btn btn-info btn-sm" onclick="refreshAnalysis()">
                        <i class="fa fa-refresh"></i> Refresh
                    </button>
                </div>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="servers-table">
                        <thead>
                            <tr>
                                <th width="20">
                                    <input type="checkbox" id="select-all">
                                </th>
                                <th>Server</th>
                                <th>Owner</th>
                                <th>Node</th>
                                <th>Status</th>
                                <th>Resources</th>
                                <th>Last Analysis</th>
                                <th>Health Score</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($servers as $server)
                            <tr data-server-id="{{ $server->id }}">
                                <td>
                                    <input type="checkbox" class="server-checkbox" value="{{ $server->id }}">
                                </td>
                                <td>
                                    <strong>{{ $server->name }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $server->uuid_short }}</small>
                                </td>
                                <td>{{ $server->user->username }}</td>
                                <td>{{ $server->node->name }}</td>
                                <td>
                                    <span class="label label-{{ $server->status === 'running' ? 'success' : ($server->status === 'offline' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($server->status) }}
                                    </span>
                                    @if($server->suspended)
                                        <span class="label label-danger">Suspended</span>
                                    @endif
                                </td>
                                <td>
                                    <small>
                                        CPU: {{ $server->cpu }}%<br>
                                        RAM: {{ $server->memory }}MB<br>
                                        Disk: {{ $server->disk }}MB
                                    </small>
                                </td>
                                <td class="last-analysis-{{ $server->id }}">
                                    <span class="text-muted">Never</span>
                                </td>
                                <td class="health-score-{{ $server->id }}">
                                    <span class="text-muted">-</span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-xs btn-primary" onclick="analyzeServer({{ $server->id }})">
                                            <i class="fa fa-search"></i> Analyze
                                        </button>
                                        <button type="button" class="btn btn-xs btn-info" onclick="viewServerInsights({{ $server->id }})">
                                            <i class="fa fa-eye"></i> Insights
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        {{-- Analysis Statistics --}}
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">Analysis Statistics</h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" onclick="refreshStats()">
                        <i class="fa fa-refresh" id="stats-refresh-icon"></i>
                    </button>
                </div>
            </div>
            <div class="box-body" id="analysis-stats">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin"></i> Loading statistics...
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">Quick Actions</h3>
            </div>
            <div class="box-body">
                <div class="btn-group-vertical btn-block">
                    <button type="button" class="btn btn-default" onclick="analyzeAllServers()">
                        <i class="fa fa-cogs"></i> Analyze All Servers
                    </button>
                    <button type="button" class="btn btn-default" onclick="generateReport()">
                        <i class="fa fa-file-text"></i> Generate Report
                    </button>
                    <button type="button" class="btn btn-default" onclick="exportAnalysis()">
                        <i class="fa fa-download"></i> Export Data
                    </button>
                    <button type="button" class="btn btn-default" onclick="scheduleAnalysis()">
                        <i class="fa fa-clock-o"></i> Schedule Analysis
                    </button>
                </div>
            </div>
        </div>

        {{-- Recent Analysis Results --}}
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">Recent Analysis</h3>
            </div>
            <div class="box-body" id="recent-analysis">
                <div class="text-center text-muted">
                    <i class="fa fa-clock-o"></i> No recent analysis results
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Server Insights Modal --}}
<div class="modal fade" id="server-insights-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Server Insights</h4>
            </div>
            <div class="modal-body" id="server-insights-content">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin"></i> Loading insights...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Analysis Progress Modal --}}
<div class="modal fade" id="analysis-progress-modal" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Analyzing Servers</h4>
            </div>
            <div class="modal-body">
                <div class="progress">
                    <div class="progress-bar progress-bar-striped active" id="analysis-progress-bar" role="progressbar" style="width: 0%">
                        <span class="sr-only">0% Complete</span>
                    </div>
                </div>
                <div id="analysis-progress-text" class="text-center">
                    Preparing analysis...
                </div>
                <div id="analysis-results" style="display: none;">
                    <h5>Results:</h5>
                    <ul id="analysis-results-list"></ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal" disabled id="close-progress-btn">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <style>
        .server-checkbox {
            cursor: pointer;
        }
        
        #servers-table tbody tr:hover {
            background-color: #f5f5f5;
        }
        
        .analysis-result {
            padding: 8px;
            margin: 5px 0;
            border-radius: 4px;
            border-left: 3px solid #ccc;
        }
        
        .analysis-result.success {
            background-color: #dff0d8;
            border-left-color: #5cb85c;
        }
        
        .analysis-result.failed {
            background-color: #f2dede;
            border-left-color: #d9534f;
        }
        
        .health-score {
            font-weight: bold;
        }
        
        .health-score.excellent {
            color: #5cb85c;
        }
        
        .health-score.good {
            color: #f0ad4e;
        }
        
        .health-score.poor {
            color: #d9534f;
        }
    </style>

    <script>
        $(document).ready(function() {
            initializeAnalysisPage();
        });

        function initializeAnalysisPage() {
            // Load statistics
            refreshStats();
            
            // Setup select all checkbox
            $('#select-all').change(function() {
                $('.server-checkbox').prop('checked', this.checked);
            });
            
            // Update select all when individual checkboxes change
            $('.server-checkbox').change(function() {
                const total = $('.server-checkbox').length;
                const checked = $('.server-checkbox:checked').length;
                $('#select-all').prop('checked', total === checked);
            });
        }

        function analyzeServer(serverId) {
            const btn = $(`tr[data-server-id="${serverId}"] .btn-primary`);
            const originalText = btn.html();
            
            btn.html('<i class="fa fa-spinner fa-spin"></i> Analyzing...').prop('disabled', true);
            
            $.ajax({
                url: `/admin/ai/analysis/server/${serverId}`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        updateServerAnalysisResults(serverId, response.analysis);
                        toastr.success(`Analysis completed for ${response.analysis.server_name}`);
                    } else {
                        toastr.error(response.message || 'Analysis failed');
                    }
                },
                error: function() {
                    toastr.error('Failed to analyze server');
                },
                complete: function() {
                    btn.html(originalText).prop('disabled', false);
                }
            });
        }

        function updateServerAnalysisResults(serverId, analysis) {
            // Update last analysis time
            $(`.last-analysis-${serverId}`).html(`
                <small class="text-success">${analysis.analysis_time}</small>
            `);
            
            // Update health score
            const healthScore = analysis.health_score || 0;
            const healthClass = healthScore >= 80 ? 'excellent' : (healthScore >= 60 ? 'good' : 'poor');
            
            $(`.health-score-${serverId}`).html(`
                <span class="health-score ${healthClass}">${healthScore}%</span>
            `);
        }

        function viewServerInsights(serverId) {
            $('#server-insights-modal').modal('show');
            
            $.ajax({
                url: `/admin/ai/analysis/server/${serverId}/insights`,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        displayServerInsights(response.insights);
                    } else {
                        $('#server-insights-content').html(`
                            <div class="alert alert-danger">
                                Failed to load insights: ${response.message}
                            </div>
                        `);
                    }
                },
                error: function() {
                    $('#server-insights-content').html(`
                        <div class="alert alert-danger">
                            Failed to load server insights
                        </div>
                    `);
                }
            });
        }

        function displayServerInsights(insights) {
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h4>Server Information</h4>
                        <ul class="list-unstyled">
                            <li><strong>Name:</strong> ${insights.server_info.name}</li>
                            <li><strong>Status:</strong> <span class="label label-${getStatusClass(insights.server_info.status)}">${insights.server_info.status}</span></li>
                            <li><strong>CPU Limit:</strong> ${insights.server_info.cpu_limit}%</li>
                            <li><strong>Memory Limit:</strong> ${insights.server_info.memory_limit}MB</li>
                            <li><strong>Disk Limit:</strong> ${insights.server_info.disk_limit}MB</li>
                            <li><strong>Node:</strong> ${insights.server_info.node}</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h4>Recommendations</h4>
                        <ul>
            `;
            
            insights.recommendations.forEach(function(rec) {
                html += `<li>${rec}</li>`;
            });
            
            html += `
                        </ul>
                    </div>
                </div>
            `;
            
            if (insights.recent_analysis.length > 0) {
                html += `
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h4>Recent Analysis</h4>
                            <div class="table-responsive">
                                <table class="table table-condensed">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Health Score</th>
                                            <th>Key Insights</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                `;
                
                insights.recent_analysis.forEach(function(analysis) {
                    html += `
                        <tr>
                            <td>${analysis.created_at}</td>
                            <td>${analysis.type}</td>
                            <td><span class="health-score ${getHealthClass(analysis.health_score)}">${analysis.health_score}%</span></td>
                            <td>${analysis.key_insights.join(', ')}</td>
                        </tr>
                    `;
                });
                
                html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            $('#server-insights-content').html(html);
        }

        function bulkAnalyzeSelected() {
            const selectedServers = $('.server-checkbox:checked').map(function() {
                return parseInt($(this).val());
            }).get();
            
            if (selectedServers.length === 0) {
                toastr.warning('Please select servers to analyze');
                return;
            }
            
            if (!confirm(`Analyze ${selectedServers.length} selected servers?`)) {
                return;
            }
            
            $('#analysis-progress-modal').modal('show');
            runBulkAnalysis(selectedServers);
        }

        function runBulkAnalysis(serverIds) {
            const total = serverIds.length;
            let completed = 0;
            
            $('#analysis-progress-text').text(`Analyzing ${total} servers...`);
            $('#analysis-results-list').empty();
            
            $.ajax({
                url: '/admin/ai/analysis/bulk',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    server_ids: serverIds
                },
                success: function(response) {
                    if (response.success) {
                        displayBulkAnalysisResults(response.summary, response.results);
                    } else {
                        toastr.error('Bulk analysis failed');
                    }
                },
                error: function() {
                    toastr.error('Failed to perform bulk analysis');
                },
                complete: function() {
                    $('#analysis-progress-bar').css('width', '100%');
                    $('#analysis-progress-text').text('Analysis complete');
                    $('#close-progress-btn').prop('disabled', false);
                }
            });
        }

        function displayBulkAnalysisResults(summary, results) {
            $('#analysis-results').show();
            
            let html = `
                <li class="text-success"><strong>Processed:</strong> ${summary.processed}</li>
                <li class="text-danger"><strong>Failed:</strong> ${summary.failed}</li>
                <li><strong>Total:</strong> ${summary.total}</li>
            `;
            
            $('#analysis-results-list').html(html);
            
            // Update individual server results
            results.forEach(function(result) {
                if (result.status === 'success') {
                    updateServerAnalysisResults(result.server_id, {
                        server_name: result.server_name,
                        health_score: result.health_score,
                        analysis_time: 'Just now'
                    });
                }
            });
        }

        function refreshStats() {
            const icon = $('#stats-refresh-icon');
            icon.addClass('fa-spin');
            
            $.ajax({
                url: '/admin/ai/analysis/stats',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        displayAnalysisStats(response.stats);
                    }
                },
                error: function() {
                    $('#analysis-stats').html('<div class="text-danger">Failed to load statistics</div>');
                },
                complete: function() {
                    icon.removeClass('fa-spin');
                }
            });
        }

        function displayAnalysisStats(stats) {
            let html = `
                <div class="info-box-content">
                    <span class="info-box-number">${stats.total_analyses}</span>
                    <span class="info-box-text">Total Analyses</span>
                </div>
                <div class="info-box-content">
                    <span class="info-box-number">${stats.avg_health_score}%</span>
                    <span class="info-box-text">Average Health</span>
                </div>
            `;
            
            if (stats.top_issues) {
                html += `
                    <h5>Top Issues:</h5>
                    <ul class="list-unstyled">
                `;
                
                Object.entries(stats.top_issues).forEach(([issue, count]) => {
                    html += `<li>${issue}: <strong>${count}</strong></li>`;
                });
                
                html += '</ul>';
            }
            
            $('#analysis-stats').html(html);
        }

        function refreshAnalysis() {
            location.reload();
        }

        function analyzeAllServers() {
            if (!confirm('This will analyze all servers. This may take some time. Continue?')) {
                return;
            }
            
            // Select all servers and run bulk analysis
            $('.server-checkbox').prop('checked', true);
            $('#select-all').prop('checked', true);
            bulkAnalyzeSelected();
        }

        function generateReport() {
            toastr.info('Report generation feature coming soon');
        }

        function exportAnalysis() {
            toastr.info('Export feature coming soon');
        }

        function scheduleAnalysis() {
            toastr.info('Scheduled analysis feature coming soon');
        }

        function getStatusClass(status) {
            switch(status) {
                case 'running': return 'success';
                case 'offline': return 'danger';
                case 'starting': return 'warning';
                case 'stopping': return 'warning';
                default: return 'default';
            }
        }

        function getHealthClass(score) {
            if (score >= 80) return 'excellent';
            if (score >= 60) return 'good';
            return 'poor';
        }
    </script>
@endsection