@extends('layouts.admin')

@section('title')
    AI Predictive Analytics
@endsection

@section('content-header')
    <div class="col-sm-12 col-md-6">
        <div class="btn-group" role="group" aria-label="Header actions">
            <h1>AI Predictive Analytics @include('admin.components.usage')</h1>
        </div>
    </div>
    <div class="col-md-6 col-sm-12 text-right">
        <a class="btn btn-primary btn-sm" href="{{ route('admin.ai.predictive-analytics.export') }}" id="exportBtn">
            <i class="fa fa-download"></i> Export Data
        </a>
        <button class="btn btn-success btn-sm" id="bulkGenerateBtn">
            <i class="fa fa-magic"></i> Bulk Generate
        </button>
    </div>
@endsection

@section('content')
    <div class="row">
        <!-- Summary Statistics -->
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-blue"><i class="fa fa-server"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Servers</span>
                    <span class="info-box-number">{{ number_format($summaryStats['total_servers']) }}</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-line-chart"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Predictions Generated</span>
                    <span class="info-box-number">{{ number_format($summaryStats['predictions_generated']) }}</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-orange"><i class="fa fa-exclamation-triangle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Active Alerts</span>
                    <span class="info-box-number">{{ number_format($summaryStats['alerts_active']) }}</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-purple"><i class="fa fa-check-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">High Confidence</span>
                    <span class="info-box-number">{{ number_format($summaryStats['high_confidence_predictions']) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Predictive Alerts -->
        <div class="col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Predictive Alerts</h3>
                    <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                        <button class="btn btn-xs btn-info" id="refreshAlertsBtn">
                            <i class="fa fa-refresh"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div id="alertsContainer">
                        <div class="text-center">
                            <i class="fa fa-spinner fa-spin fa-2x"></i>
                            <p>Loading predictive alerts...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Predictions -->
        <div class="col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Recent Predictions</h3>
                    <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    @if($recentPredictions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Server</th>
                                        <th>Confidence</th>
                                        <th>Generated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentPredictions as $prediction)
                                        <tr>
                                            <td>
                                                @if($server = \Pterodactyl\Models\Server::find($prediction->context_id))
                                                    <a href="{{ route('admin.servers.view', $server->id) }}">
                                                        {{ $server->name }}
                                                    </a>
                                                @else
                                                    Server #{{ $prediction->context_id }}
                                                @endif
                                            </td>
                                            <td>
                                                <span class="label {{ $prediction->confidence_score >= 0.8 ? 'label-success' : ($prediction->confidence_score >= 0.6 ? 'label-warning' : 'label-danger') }}">
                                                    {{ round($prediction->confidence_score * 100) }}%
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $prediction->created_at->diffForHumans() }}
                                                </small>
                                            </td>
                                            <td>
                                                <button class="btn btn-xs btn-primary view-prediction" 
                                                        data-server-id="{{ $prediction->context_id }}">
                                                    <i class="fa fa-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center">
                            <i class="fa fa-chart-line fa-3x text-muted"></i>
                            <p class="text-muted">No predictions generated yet</p>
                            <button class="btn btn-primary btn-sm" id="generateFirstPrediction">
                                Generate First Prediction
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Server Predictions -->
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Server Predictions</h3>
                    <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                        <div class="btn-group">
                            <select class="form-control input-sm" id="periodFilter">
                                <option value="7_days">7 Days</option>
                                <option value="30_days" selected>30 Days</option>
                                <option value="90_days">90 Days</option>
                                <option value="180_days">180 Days</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="serversTable">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="selectAll">
                                    </th>
                                    <th>Server</th>
                                    <th>Node</th>
                                    <th>Status</th>
                                    <th>Last Prediction</th>
                                    <th>Confidence</th>
                                    <th>Alerts</th>
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
                                            <a href="{{ route('admin.servers.view', $server->id) }}">
                                                <strong>{{ $server->name }}</strong>
                                            </a>
                                            <br>
                                            <small class="text-muted">{{ $server->description ?: 'No description' }}</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.nodes.view', $server->node->id) }}">
                                                {{ $server->node->name }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="label label-{{ $server->status === 'online' ? 'success' : ($server->status === 'offline' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($server->status) }}
                                            </span>
                                        </td>
                                        <td class="prediction-date" data-server-id="{{ $server->id }}">
                                            <i class="fa fa-spinner fa-spin"></i> Loading...
                                        </td>
                                        <td class="prediction-confidence" data-server-id="{{ $server->id }}">
                                            <i class="fa fa-spinner fa-spin"></i>
                                        </td>
                                        <td class="prediction-alerts" data-server-id="{{ $server->id }}">
                                            <i class="fa fa-spinner fa-spin"></i>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-xs btn-primary generate-prediction" 
                                                        data-server-id="{{ $server->id }}">
                                                    <i class="fa fa-magic"></i> Generate
                                                </button>
                                                <button class="btn btn-xs btn-info view-prediction" 
                                                        data-server-id="{{ $server->id }}">
                                                    <i class="fa fa-eye"></i> View
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="box-footer">
                        {{ $servers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Prediction Details Modal -->
    <div class="modal fade" id="predictionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Prediction Details</h4>
                </div>
                <div class="modal-body" id="predictionModalBody">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p>Loading prediction details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="refreshPrediction">
                        <i class="fa fa-refresh"></i> Regenerate
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Export Predictions</h4>
                </div>
                <div class="modal-body">
                    <form id="exportForm">
                        <div class="form-group">
                            <label>Export Format</label>
                            <select class="form-control" name="format">
                                <option value="json">JSON</option>
                                <option value="csv">CSV</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date Range (days back)</label>
                            <input type="number" class="form-control" name="days" value="30" min="1" max="365">
                        </div>
                        <div class="form-group">
                            <label>Specific Servers (optional)</label>
                            <select class="form-control select2" name="server_ids[]" multiple>
                                @foreach($servers as $server)
                                    <option value="{{ $server->id }}">{{ $server->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="performExport">
                        <i class="fa fa-download"></i> Export
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
            let currentServerId = null;
            
            // Load initial data
            loadPredictiveAlerts();
            loadServerPredictions();
            
            // Refresh alerts
            $('#refreshAlertsBtn').click(function() {
                loadPredictiveAlerts();
            });
            
            // Generate prediction for individual server
            $(document).on('click', '.generate-prediction', function() {
                const serverId = $(this).data('server-id');
                const period = $('#periodFilter').val();
                generateServerPrediction(serverId, period);
            });
            
            // View prediction details
            $(document).on('click', '.view-prediction', function() {
                const serverId = $(this).data('server-id');
                currentServerId = serverId;
                viewPredictionDetails(serverId);
            });
            
            // Bulk generate predictions
            $('#bulkGenerateBtn').click(function() {
                const selectedServers = $('.server-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();
                
                if (selectedServers.length === 0) {
                    alert('Please select servers to generate predictions for.');
                    return;
                }
                
                if (selectedServers.length > 10) {
                    alert('Maximum 10 servers can be processed at once.');
                    return;
                }
                
                bulkGeneratePredictions(selectedServers);
            });
            
            // Select all servers
            $('#selectAll').change(function() {
                $('.server-checkbox').prop('checked', $(this).prop('checked'));
            });
            
            // Export data
            $('#exportBtn').click(function() {
                $('#exportModal').modal('show');
            });
            
            $('#performExport').click(function() {
                const formData = $('#exportForm').serialize();
                exportPredictions(formData);
            });
            
            // Period filter change
            $('#periodFilter').change(function() {
                loadServerPredictions();
            });
            
            // Refresh single prediction
            $('#refreshPrediction').click(function() {
                if (currentServerId) {
                    const period = $('#periodFilter').val();
                    generateServerPrediction(currentServerId, period, true);
                }
            });
            
            // Initialize Select2
            $('.select2').select2();
        });
        
        function loadPredictiveAlerts() {
            $.get('{{ route('admin.ai.predictive-analytics.alerts') }}')
                .done(function(response) {
                    if (response.success) {
                        renderAlerts(response.data);
                    } else {
                        $('#alertsContainer').html('<div class="alert alert-danger">Failed to load alerts</div>');
                    }
                })
                .fail(function() {
                    $('#alertsContainer').html('<div class="alert alert-danger">Error loading alerts</div>');
                });
        }
        
        function renderAlerts(alerts) {
            let html = '';
            
            if (alerts.length === 0) {
                html = '<div class="alert alert-success"><i class="fa fa-check"></i> No active alerts</div>';
            } else {
                html = '<div class="list-group">';
                alerts.slice(0, 10).forEach(function(alert) { // Show top 10 alerts
                    const severityClass = alert.severity === 'critical' ? 'danger' : (alert.severity === 'warning' ? 'warning' : 'info');
                    const icon = alert.severity === 'critical' ? 'exclamation-circle' : 'exclamation-triangle';
                    
                    html += `
                        <div class="list-group-item alert-${severityClass}">
                            <div class="list-group-item-heading">
                                <i class="fa fa-${icon}"></i>
                                <strong>${alert.server_name}</strong>
                                <span class="pull-right">
                                    <small class="text-muted">${alert.period}</small>
                                </span>
                            </div>
                            <p class="list-group-item-text">
                                ${alert.message}
                                <br><small>Confidence: ${Math.round(alert.confidence * 100)}%</small>
                            </p>
                        </div>
                    `;
                });
                html += '</div>';
                
                if (alerts.length > 10) {
                    html += `<div class="text-center"><small class="text-muted">... and ${alerts.length - 10} more alerts</small></div>`;
                }
            }
            
            $('#alertsContainer').html(html);
        }
        
        function loadServerPredictions() {
            $('.prediction-date, .prediction-confidence, .prediction-alerts').each(function() {
                $(this).html('<i class="fa fa-spinner fa-spin"></i>');
            });
            
            $('.server-checkbox').each(function() {
                const serverId = $(this).val();
                loadServerPredictionData(serverId);
            });
        }
        
        function loadServerPredictionData(serverId) {
            $.get(`{{ route('admin.ai.predictive-analytics.server', '') }}/${serverId}`)
                .done(function(response) {
                    if (response.success) {
                        updateServerRow(serverId, response.data);
                    } else {
                        updateServerRowError(serverId);
                    }
                })
                .fail(function() {
                    updateServerRowError(serverId);
                });
        }
        
        function updateServerRow(serverId, data) {
            $(`.prediction-date[data-server-id="${serverId}"]`).html(
                `<small class="text-muted">${moment(data.generated_at).fromNow()}</small>`
            );
            
            const confidence = Math.round((data.confidence_score || 0) * 100);
            const confidenceClass = confidence >= 80 ? 'success' : (confidence >= 60 ? 'warning' : 'danger');
            $(`.prediction-confidence[data-server-id="${serverId}"]`).html(
                `<span class="label label-${confidenceClass}">${confidence}%</span>`
            );
            
            // Count alerts
            let alertCount = 0;
            if (data.predictions) {
                Object.values(data.predictions).forEach(function(prediction) {
                    if (prediction.alerts) {
                        alertCount += prediction.alerts.length;
                    }
                });
            }
            
            $(`.prediction-alerts[data-server-id="${serverId}"]`).html(
                alertCount > 0 ? `<span class="badge bg-red">${alertCount}</span>` : '<span class="text-muted">None</span>'
            );
        }
        
        function updateServerRowError(serverId) {
            $(`.prediction-date[data-server-id="${serverId}"]`).html('<span class="text-muted">No data</span>');
            $(`.prediction-confidence[data-server-id="${serverId}"]`).html('<span class="text-muted">-</span>');
            $(`.prediction-alerts[data-server-id="${serverId}"]`).html('<span class="text-muted">-</span>');
        }
        
        function generateServerPrediction(serverId, period, forceRefresh = false) {
            const $btn = $(`.generate-prediction[data-server-id="${serverId}"]`);
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Generating...');
            
            $.post(`{{ route('admin.ai.predictive-analytics.generate', '') }}/${serverId}`, {
                period: period,
                force_refresh: forceRefresh,
                _token: '{{ csrf_token() }}'
            })
            .done(function(response) {
                if (response.success) {
                    updateServerRow(serverId, response.data);
                    showNotification('Success', 'Prediction generated successfully', 'success');
                } else {
                    showNotification('Error', response.error || 'Failed to generate prediction', 'error');
                }
            })
            .fail(function(xhr) {
                const error = xhr.responseJSON?.error || 'Request failed';
                showNotification('Error', error, 'error');
            })
            .always(function() {
                $btn.prop('disabled', false).html('<i class="fa fa-magic"></i> Generate');
            });
        }
        
        function bulkGeneratePredictions(serverIds) {
            const period = $('#periodFilter').val();
            $('#bulkGenerateBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');
            
            $.post('{{ route('admin.ai.predictive-analytics.bulk-generate') }}', {
                server_ids: serverIds,
                period: period,
                _token: '{{ csrf_token() }}'
            })
            .done(function(response) {
                if (response.success) {
                    showNotification('Success', `Generated predictions for ${response.summary.successful} servers`, 'success');
                    loadServerPredictions();
                } else {
                    showNotification('Warning', `Generated ${response.summary.successful} predictions, ${response.summary.failed} failed`, 'warning');
                }
            })
            .fail(function(xhr) {
                const error = xhr.responseJSON?.error || 'Bulk generation failed';
                showNotification('Error', error, 'error');
            })
            .always(function() {
                $('#bulkGenerateBtn').prop('disabled', false).html('<i class="fa fa-magic"></i> Bulk Generate');
            });
        }
        
        function viewPredictionDetails(serverId) {
            $('#predictionModal').modal('show');
            $('#predictionModalBody').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Loading prediction details...</p></div>');
            
            $.get(`{{ route('admin.ai.predictive-analytics.server', '') }}/${serverId}`)
                .done(function(response) {
                    if (response.success) {
                        renderPredictionDetails(response.data);
                    } else {
                        $('#predictionModalBody').html('<div class="alert alert-danger">Failed to load prediction details</div>');
                    }
                })
                .fail(function() {
                    $('#predictionModalBody').html('<div class="alert alert-danger">Error loading prediction details</div>');
                });
        }
        
        function renderPredictionDetails(data) {
            let html = '<div class="row">';
            
            // Overview
            html += `
                <div class="col-md-12">
                    <div class="box box-primary">
                        <div class="box-header">
                            <h3 class="box-title">Overview</h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-blue"><i class="fa fa-check-circle"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Confidence</span>
                                            <span class="info-box-number">${Math.round((data.confidence_score || 0) * 100)}%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-green"><i class="fa fa-clock-o"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Generated</span>
                                            <span class="info-box-number">${moment(data.generated_at).fromNow()}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-orange"><i class="fa fa-chart-line"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Periods</span>
                                            <span class="info-box-number">${Object.keys(data.predictions || {}).length}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Predictions by period
            if (data.predictions) {
                Object.entries(data.predictions).forEach(function([period, prediction]) {
                    html += `
                        <div class="col-md-6">
                            <div class="box">
                                <div class="box-header">
                                    <h3 class="box-title">${period.replace('_', ' ').toUpperCase()} Prediction</h3>
                                </div>
                                <div class="box-body">
                    `;
                    
                    // Resource predictions
                    if (prediction.resource_predictions) {
                        html += '<h5>Resource Predictions</h5>';
                        Object.entries(prediction.resource_predictions).forEach(function([metric, pred]) {
                            const progressClass = pred.predicted_value > 80 ? 'danger' : (pred.predicted_value > 60 ? 'warning' : 'success');
                            html += `
                                <div class="form-group">
                                    <label>${metric.replace('_', ' ').toUpperCase()}</label>
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-${progressClass}" style="width: ${pred.predicted_value}%">
                                            ${Math.round(pred.predicted_value)}% (${Math.round(pred.confidence * 100)}% confidence)
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                    
                    // Alerts
                    if (prediction.alerts && prediction.alerts.length > 0) {
                        html += '<h5>Alerts</h5>';
                        prediction.alerts.forEach(function(alert) {
                            const alertClass = alert.severity === 'critical' ? 'danger' : 'warning';
                            html += `<div class="alert alert-${alertClass}"><i class="fa fa-exclamation-triangle"></i> ${alert.message}</div>`;
                        });
                    }
                    
                    html += '</div></div></div>';
                });
            }
            
            html += '</div>';
            $('#predictionModalBody').html(html);
        }
        
        function exportPredictions(formData) {
            $('#performExport').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Exporting...');
            
            $.get('{{ route('admin.ai.predictive-analytics.export') }}?' + formData)
                .done(function(response) {
                    if (response.success) {
                        // Create download
                        const dataStr = JSON.stringify(response.data, null, 2);
                        const dataBlob = new Blob([dataStr], {type: 'application/json'});
                        const url = URL.createObjectURL(dataBlob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = `predictions_export_${new Date().toISOString().split('T')[0]}.${response.format}`;
                        link.click();
                        URL.revokeObjectURL(url);
                        
                        $('#exportModal').modal('hide');
                        showNotification('Success', `Exported ${response.count} records`, 'success');
                    } else {
                        showNotification('Error', response.error || 'Export failed', 'error');
                    }
                })
                .fail(function() {
                    showNotification('Error', 'Export request failed', 'error');
                })
                .always(function() {
                    $('#performExport').prop('disabled', false).html('<i class="fa fa-download"></i> Export');
                });
        }
        
        function showNotification(title, message, type) {
            // Implement your notification system here
            // This could be toastr, SweetAlert, or any other notification library
            if (typeof toastr !== 'undefined') {
                toastr[type](message, title);
            } else {
                alert(title + ': ' + message);
            }
        }
    </script>
@endsection