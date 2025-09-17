@extends('layouts.admin')

@section('title')
    AI Custom Reports
@endsection

@section('content-header')
    <div class="col-sm-12 col-md-6">
        <div class="btn-group" role="group" aria-label="Header actions">
            <h1>AI Custom Reports @include('admin.components.usage')</h1>
        </div>
    </div>
    <div class="col-md-6 col-sm-12 text-right">
        <a class="btn btn-success btn-sm" href="{{ route('admin.ai.custom-reports.create') }}">
            <i class="fa fa-plus"></i> Create Report
        </a>
        <button class="btn btn-primary btn-sm" id="viewStatsBtn">
            <i class="fa fa-bar-chart"></i> Statistics
        </button>
    </div>
@endsection

@section('content')
    <div class="row">
        <!-- Summary Statistics -->
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-blue"><i class="fa fa-file-text"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Reports</span>
                    <span class="info-box-number">{{ number_format($summaryStats['total_reports']) }}</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-calendar"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">This Month</span>
                    <span class="info-box-number">{{ number_format($summaryStats['reports_this_month']) }}</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-orange"><i class="fa fa-clock-o"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Scheduled</span>
                    <span class="info-box-number">{{ number_format($summaryStats['scheduled_reports']) }}</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-purple"><i class="fa fa-download"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Export Formats</span>
                    <span class="info-box-number">{{ number_format($summaryStats['export_formats']) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Report Templates -->
        <div class="col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Report Templates</h3>
                    <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        @foreach($templates as $templateKey => $template)
                            <div class="col-md-6 col-sm-12 template-card" data-template="{{ $templateKey }}">
                                <div class="small-box bg-light">
                                    <div class="inner">
                                        <h4>{{ $template['name'] }}</h4>
                                        <p>{{ $template['description'] }}</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fa fa-{{ $this->getTemplateIcon($templateKey) }}"></i>
                                    </div>
                                    <div class="small-box-footer">
                                        <button class="btn btn-sm btn-primary generate-from-template" 
                                                data-template="{{ $templateKey }}">
                                            <i class="fa fa-magic"></i> Generate Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Quick Actions</h3>
                    <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="list-group">
                        <a href="#" class="list-group-item" id="quickServerPerformance">
                            <i class="fa fa-server text-blue"></i>
                            <strong>Server Performance Report</strong>
                            <br><small class="text-muted">Generate performance analysis for all servers</small>
                        </a>
                        <a href="#" class="list-group-item" id="quickCapacityPlanning">
                            <i class="fa fa-line-chart text-green"></i>
                            <strong>Capacity Planning Report</strong>
                            <br><small class="text-muted">AI-powered capacity forecasting</small>
                        </a>
                        <a href="#" class="list-group-item" id="quickSystemHealth">
                            <i class="fa fa-heart text-red"></i>
                            <strong>System Health Report</strong>
                            <br><small class="text-muted">Overall system health analysis</small>
                        </a>
                        <a href="#" class="list-group-item" id="quickUsageAnalytics">
                            <i class="fa fa-pie-chart text-orange"></i>
                            <strong>Usage Analytics Report</strong>
                            <br><small class="text-muted">Detailed usage patterns analysis</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Reports -->
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Recent Reports</h3>
                    <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                        <div class="btn-group">
                            <button class="btn btn-xs btn-info" id="refreshReports">
                                <i class="fa fa-refresh"></i> Refresh
                            </button>
                            <button class="btn btn-xs btn-success" id="compareReports">
                                <i class="fa fa-exchange"></i> Compare
                            </button>
                        </div>
                    </div>
                </div>
                <div class="box-body">
                    @if($recentReports->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped" id="reportsTable">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="selectAll">
                                        </th>
                                        <th>Type</th>
                                        <th>Title</th>
                                        <th>Confidence</th>
                                        <th>Generated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentReports as $report)
                                        @php
                                            $metadata = json_decode($report->metadata, true);
                                            $insights = json_decode($report->insights, true);
                                            $reportId = $metadata['report_id'] ?? 'RPT-' . $report->id;
                                        @endphp
                                        <tr data-report-id="{{ $reportId }}">
                                            <td>
                                                <input type="checkbox" class="report-checkbox" value="{{ $reportId }}">
                                            </td>
                                            <td>
                                                <span class="label label-primary">
                                                    {{ ucfirst(str_replace('_', ' ', $report->analysis_type)) }}
                                                </span>
                                            </td>
                                            <td>
                                                <strong>{{ $insights['header']['title'] ?? 'Custom Report' }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $insights['header']['subtitle'] ?? '' }}</small>
                                            </td>
                                            <td>
                                                <span class="label {{ $report->confidence_score >= 0.8 ? 'label-success' : ($report->confidence_score >= 0.6 ? 'label-warning' : 'label-danger') }}">
                                                    {{ round($report->confidence_score * 100) }}%
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted" title="{{ $report->created_at->format('Y-m-d H:i:s') }}">
                                                    {{ $report->created_at->diffForHumans() }}
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-xs btn-primary view-report" 
                                                            data-report-id="{{ $reportId }}">
                                                        <i class="fa fa-eye"></i> View
                                                    </button>
                                                    <div class="btn-group">
                                                        <button class="btn btn-xs btn-success dropdown-toggle" 
                                                                data-toggle="dropdown">
                                                            <i class="fa fa-download"></i> Export
                                                            <span class="caret"></span>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><a href="#" class="export-report" 
                                                                   data-report-id="{{ $reportId }}" 
                                                                   data-format="pdf">PDF</a></li>
                                                            <li><a href="#" class="export-report" 
                                                                   data-report-id="{{ $reportId }}" 
                                                                   data-format="csv">CSV</a></li>
                                                            <li><a href="#" class="export-report" 
                                                                   data-report-id="{{ $reportId }}" 
                                                                   data-format="json">JSON</a></li>
                                                            <li><a href="#" class="export-report" 
                                                                   data-report-id="{{ $reportId }}" 
                                                                   data-format="html">HTML</a></li>
                                                        </ul>
                                                    </div>
                                                    <button class="btn btn-xs btn-warning duplicate-report" 
                                                            data-report-id="{{ $reportId }}">
                                                        <i class="fa fa-copy"></i> Duplicate
                                                    </button>
                                                    <button class="btn btn-xs btn-danger delete-report" 
                                                            data-report-id="{{ $reportId }}">
                                                        <i class="fa fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center">
                            <i class="fa fa-file-text-o fa-3x text-muted"></i>
                            <p class="text-muted">No reports generated yet</p>
                            <a href="{{ route('admin.ai.custom-reports.create') }}" class="btn btn-primary">
                                Create Your First Report
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Report Viewer Modal -->
    <div class="modal fade" id="reportModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Report Details</h4>
                </div>
                <div class="modal-body" id="reportModalBody">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p>Loading report...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-download"></i> Export <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a href="#" id="exportModalPdf">PDF</a></li>
                            <li><a href="#" id="exportModalCsv">CSV</a></li>
                            <li><a href="#" id="exportModalJson">JSON</a></li>
                            <li><a href="#" id="exportModalHtml">HTML</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Modal -->
    <div class="modal fade" id="statisticsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Report Statistics</h4>
                </div>
                <div class="modal-body" id="statisticsModalBody">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p>Loading statistics...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Report Generation Modal -->
    <div class="modal fade" id="quickReportModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Generate Quick Report</h4>
                </div>
                <div class="modal-body">
                    <form id="quickReportForm">
                        <input type="hidden" name="type" id="quickReportType">
                        
                        <div class="form-group">
                            <label>Time Range</label>
                            <select class="form-control" name="time_range">
                                <option value="7_days">Last 7 days</option>
                                <option value="30_days" selected>Last 30 days</option>
                                <option value="90_days">Last 90 days</option>
                                <option value="180_days">Last 6 months</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Report Format</label>
                            <select class="form-control" name="format">
                                <option value="structured">Structured</option>
                                <option value="executive">Executive Summary</option>
                                <option value="detailed">Detailed Analysis</option>
                                <option value="dashboard">Dashboard View</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Options</label>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="include_charts" checked> Include Charts
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="include_recommendations" checked> Include AI Recommendations
                                </label>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="include_anomalies"> Include Anomaly Detection
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group" id="serverSelection" style="display: none;">
                            <label>Select Servers (optional)</label>
                            <select class="form-control select2" name="server_ids[]" multiple>
                                @foreach($servers as $server)
                                    <option value="{{ $server->id }}">{{ $server->name }} ({{ $server->node->name }})</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="generateQuickReport">
                        <i class="fa fa-magic"></i> Generate Report
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
            let currentReportId = null;
            
            // Initialize Select2
            $('.select2').select2();
            
            // Template icon mapping
            const templateIcons = {
                'server_performance': 'server',
                'capacity_planning': 'line-chart',
                'usage_analytics': 'pie-chart',
                'security_analysis': 'shield',
                'cost_optimization': 'dollar',
                'user_activity': 'users',
                'system_health': 'heartbeat',
                'predictive_forecast': 'crystal-ball'
            };
            
            // Generate from template
            $('.generate-from-template').click(function() {
                const templateKey = $(this).data('template');
                generateFromTemplate(templateKey);
            });
            
            // Quick actions
            $('#quickServerPerformance').click(function(e) {
                e.preventDefault();
                showQuickReportModal('server_performance');
            });
            
            $('#quickCapacityPlanning').click(function(e) {
                e.preventDefault();
                showQuickReportModal('capacity_planning');
            });
            
            $('#quickSystemHealth').click(function(e) {
                e.preventDefault();
                showQuickReportModal('system_health');
            });
            
            $('#quickUsageAnalytics').click(function(e) {
                e.preventDefault();
                showQuickReportModal('usage_analytics');
            });
            
            // Generate quick report
            $('#generateQuickReport').click(function() {
                const formData = $('#quickReportForm').serializeArray();
                let config = {};
                
                formData.forEach(function(item) {
                    if (item.name.endsWith('[]')) {
                        const key = item.name.replace('[]', '');
                        if (!config[key]) config[key] = [];
                        config[key].push(item.value);
                    } else {
                        config[item.name] = item.value;
                    }
                });
                
                // Handle checkboxes
                config.include_charts = $('#quickReportForm input[name="include_charts"]').prop('checked');
                config.include_recommendations = $('#quickReportForm input[name="include_recommendations"]').prop('checked');
                config.include_anomalies = $('#quickReportForm input[name="include_anomalies"]').prop('checked');
                
                generateReport(config);
            });
            
            // View report
            $(document).on('click', '.view-report', function() {
                const reportId = $(this).data('report-id');
                currentReportId = reportId;
                viewReport(reportId);
            });
            
            // Export report
            $(document).on('click', '.export-report', function(e) {
                e.preventDefault();
                const reportId = $(this).data('report-id');
                const format = $(this).data('format');
                exportReport(reportId, format);
            });
            
            // Delete report
            $(document).on('click', '.delete-report', function() {
                const reportId = $(this).data('report-id');
                if (confirm('Are you sure you want to delete this report?')) {
                    deleteReport(reportId);
                }
            });
            
            // Duplicate report
            $(document).on('click', '.duplicate-report', function() {
                const reportId = $(this).data('report-id');
                duplicateReport(reportId);
            });
            
            // Select all reports
            $('#selectAll').change(function() {
                $('.report-checkbox').prop('checked', $(this).prop('checked'));
            });
            
            // Compare reports
            $('#compareReports').click(function() {
                const selectedReports = $('.report-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();
                
                if (selectedReports.length < 2) {
                    alert('Please select at least 2 reports to compare.');
                    return;
                }
                
                if (selectedReports.length > 4) {
                    alert('Maximum 4 reports can be compared at once.');
                    return;
                }
                
                compareReports(selectedReports);
            });
            
            // View statistics
            $('#viewStatsBtn').click(function() {
                viewStatistics();
            });
            
            // Refresh reports
            $('#refreshReports').click(function() {
                location.reload();
            });
        });
        
        function generateFromTemplate(templateKey) {
            showQuickReportModal(templateKey);
        }
        
        function showQuickReportModal(reportType) {
            $('#quickReportType').val(reportType);
            $('#quickReportModal .modal-title').text('Generate ' + reportType.replace('_', ' ').toUpperCase() + ' Report');
            
            // Show/hide server selection based on report type
            if (['server_performance', 'capacity_planning'].includes(reportType)) {
                $('#serverSelection').show();
            } else {
                $('#serverSelection').hide();
            }
            
            $('#quickReportModal').modal('show');
        }
        
        function generateReport(config) {
            $('#generateQuickReport').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Generating...');
            
            $.post('{{ route('admin.ai.custom-reports.generate') }}', {
                ...config,
                _token: '{{ csrf_token() }}'
            })
            .done(function(response) {
                if (response.success) {
                    $('#quickReportModal').modal('hide');
                    showNotification('Success', 'Report generated successfully', 'success');
                    
                    // Optionally view the generated report immediately
                    if (confirm('Report generated successfully! Would you like to view it now?')) {
                        viewReport(response.report_id);
                    }
                    
                    // Refresh the page to show new report
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showNotification('Error', response.error || 'Report generation failed', 'error');
                }
            })
            .fail(function(xhr) {
                const error = xhr.responseJSON?.error || 'Request failed';
                showNotification('Error', error, 'error');
            })
            .always(function() {
                $('#generateQuickReport').prop('disabled', false).html('<i class="fa fa-magic"></i> Generate Report');
            });
        }
        
        function viewReport(reportId) {
            $('#reportModal').modal('show');
            $('#reportModalBody').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Loading report...</p></div>');
            
            $.get(`{{ route('admin.ai.custom-reports.view', '') }}/${reportId}`)
                .done(function(response) {
                    if (response.success) {
                        renderReportDetails(response.data);
                        
                        // Set up export buttons
                        $('#exportModalPdf').data('report-id', reportId).data('format', 'pdf');
                        $('#exportModalCsv').data('report-id', reportId).data('format', 'csv');
                        $('#exportModalJson').data('report-id', reportId).data('format', 'json');
                        $('#exportModalHtml').data('report-id', reportId).data('format', 'html');
                    } else {
                        $('#reportModalBody').html('<div class="alert alert-danger">Failed to load report</div>');
                    }
                })
                .fail(function() {
                    $('#reportModalBody').html('<div class="alert alert-danger">Error loading report</div>');
                });
        }
        
        function renderReportDetails(report) {
            let html = '<div class="report-content">';
            
            // Header
            if (report.data.header) {
                html += `
                    <div class="report-header text-center">
                        <h2>${report.data.header.title}</h2>
                        <h4 class="text-muted">${report.data.header.subtitle}</h4>
                        <p><small>Generated: ${report.data.header.generated_at}</small></p>
                        <p><small>Report ID: ${report.data.header.report_id}</small></p>
                    </div>
                    <hr>
                `;
            }
            
            // Executive Summary
            if (report.data.executive_summary) {
                html += `
                    <div class="section">
                        <h3><i class="fa fa-summary"></i> Executive Summary</h3>
                        <div class="well">
                            ${report.data.executive_summary}
                        </div>
                    </div>
                `;
            }
            
            // AI Insights
            if (report.data.ai_insights) {
                const insights = report.data.ai_insights;
                
                html += '<div class="section"><h3><i class="fa fa-lightbulb-o"></i> AI Insights</h3>';
                
                if (insights.key_insights && insights.key_insights.length > 0) {
                    html += '<h4>Key Insights</h4><ul>';
                    insights.key_insights.forEach(function(insight) {
                        html += `<li>${insight}</li>`;
                    });
                    html += '</ul>';
                }
                
                if (insights.recommendations && insights.recommendations.length > 0) {
                    html += '<h4>Recommendations</h4><div class="list-group">';
                    insights.recommendations.forEach(function(rec) {
                        const priorityClass = rec.priority <= 2 ? 'danger' : (rec.priority <= 3 ? 'warning' : 'info');
                        html += `
                            <div class="list-group-item">
                                <span class="badge badge-${priorityClass}">Priority ${rec.priority}</span>
                                <strong>${rec.category || 'General'}</strong><br>
                                ${rec.text}
                            </div>
                        `;
                    });
                    html += '</div>';
                }
                
                if (insights.confidence_score) {
                    const confidence = Math.round(insights.confidence_score * 100);
                    const confidenceClass = confidence >= 80 ? 'success' : (confidence >= 60 ? 'warning' : 'danger');
                    html += `
                        <div class="alert alert-${confidenceClass}">
                            <strong>Confidence Score: ${confidence}%</strong>
                        </div>
                    `;
                }
                
                html += '</div>';
            }
            
            // Charts
            if (report.data.charts && Object.keys(report.data.charts).length > 0) {
                html += '<div class="section"><h3><i class="fa fa-bar-chart"></i> Charts & Visualizations</h3>';
                html += '<p class="text-muted">Charts would be rendered here in a full implementation.</p>';
                html += '</div>';
            }
            
            html += '</div>';
            $('#reportModalBody').html(html);
        }
        
        function exportReport(reportId, format) {
            showNotification('Info', 'Preparing export...', 'info');
            
            window.location.href = `{{ route('admin.ai.custom-reports.export', '') }}/${reportId}?format=${format}`;
        }
        
        function deleteReport(reportId) {
            $.ajax({
                url: `{{ route('admin.ai.custom-reports.delete', '') }}/${reportId}`,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .done(function(response) {
                if (response.success) {
                    showNotification('Success', 'Report deleted successfully', 'success');
                    $(`tr[data-report-id="${reportId}"]`).fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    showNotification('Error', response.error || 'Deletion failed', 'error');
                }
            })
            .fail(function() {
                showNotification('Error', 'Delete request failed', 'error');
            });
        }
        
        function duplicateReport(reportId) {
            $.post(`{{ route('admin.ai.custom-reports.duplicate', '') }}/${reportId}`, {
                _token: '{{ csrf_token() }}'
            })
            .done(function(response) {
                if (response.success) {
                    showNotification('Success', 'Report duplicated successfully', 'success');
                    
                    if (confirm('Report duplicated! Would you like to view the new report?')) {
                        viewReport(response.report_id);
                    }
                    
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showNotification('Error', response.error || 'Duplication failed', 'error');
                }
            })
            .fail(function() {
                showNotification('Error', 'Duplication request failed', 'error');
            });
        }
        
        function compareReports(reportIds) {
            $.post('{{ route('admin.ai.custom-reports.compare') }}', {
                report_ids: reportIds,
                _token: '{{ csrf_token() }}'
            })
            .done(function(response) {
                if (response.success) {
                    // Display comparison results
                    alert('Comparison completed! (Full comparison UI would be shown here)');
                } else {
                    showNotification('Error', response.error || 'Comparison failed', 'error');
                }
            })
            .fail(function() {
                showNotification('Error', 'Comparison request failed', 'error');
            });
        }
        
        function viewStatistics() {
            $('#statisticsModal').modal('show');
            $('#statisticsModalBody').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Loading statistics...</p></div>');
            
            $.get('{{ route('admin.ai.custom-reports.statistics') }}')
                .done(function(response) {
                    if (response.success) {
                        renderStatistics(response.data, response.period);
                    } else {
                        $('#statisticsModalBody').html('<div class="alert alert-danger">Failed to load statistics</div>');
                    }
                })
                .fail(function() {
                    $('#statisticsModalBody').html('<div class="alert alert-danger">Error loading statistics</div>');
                });
        }
        
        function renderStatistics(stats, period) {
            let html = `
                <div class="row">
                    <div class="col-md-12">
                        <h4>Report Statistics (Last ${period.days} days)</h4>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-blue"><i class="fa fa-file-text"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Reports</span>
                                <span class="info-box-number">${stats.total_reports}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-green"><i class="fa fa-check-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">High Confidence</span>
                                <span class="info-box-number">${stats.high_confidence_reports}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            if (stats.reports_by_type) {
                html += '<div class="row"><div class="col-md-12"><h5>Reports by Type</h5><ul>';
                Object.entries(stats.reports_by_type).forEach(function([type, count]) {
                    html += `<li>${type.replace('_', ' ').toUpperCase()}: ${count}</li>`;
                });
                html += '</ul></div></div>';
            }
            
            $('#statisticsModalBody').html(html);
        }
        
        function showNotification(title, message, type) {
            // Implement your notification system here
            if (typeof toastr !== 'undefined') {
                toastr[type](message, title);
            } else {
                alert(title + ': ' + message);
            }
        }

        @php
        function getTemplateIcon($templateKey) {
            $icons = [
                'server_performance' => 'server',
                'capacity_planning' => 'line-chart',
                'usage_analytics' => 'pie-chart',
                'security_analysis' => 'shield',
                'cost_optimization' => 'dollar',
                'user_activity' => 'users',
                'system_health' => 'heartbeat',
                'predictive_forecast' => 'crystal-ball'
            ];
            
            return $icons[$templateKey] ?? 'file-text';
        }
        @endphp
    </script>
@endsection