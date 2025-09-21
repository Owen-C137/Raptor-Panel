@extends('layouts.admin')

@section('title')
    Update History & Logs
@endsection

@section('content-header')
    <h1>Update History & Logs</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.updates.dashboard') }}">Updates</a></li>
        <li class="active">History & Logs</li>
    </ol>
@endsection

@section('content')
<div class="row" id="update-history">
    <!-- Statistics Overview -->
    <div class="col-md-12">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-bar-chart"></i> Update Statistics
                </h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-3">
                        <div class="description-block border-right">
                            <span class="description-percentage text-blue">
                                <i class="fa fa-database"></i>
                            </span>
                            <h5 class="description-header">{{ $statistics['total_sessions'] ?? 0 }}</h5>
                            <span class="description-text">TOTAL UPDATES</span>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="description-block border-right">
                            <span class="description-percentage text-green">
                                <i class="fa fa-check"></i>
                                {{ number_format(($statistics['successful_rate'] ?? 0) * 100, 1) }}%
                            </span>
                            <h5 class="description-header">{{ $sessions->where('status', 'completed')->count() }}</h5>
                            <span class="description-text">SUCCESSFUL</span>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="description-block border-right">
                            <span class="description-percentage text-red">
                                <i class="fa fa-times"></i>
                                {{ number_format((1 - ($statistics['successful_rate'] ?? 0)) * 100, 1) }}%
                            </span>
                            <h5 class="description-header">{{ $sessions->where('status', 'failed')->count() }}</h5>
                            <span class="description-text">FAILED</span>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="description-block">
                            <span class="description-percentage text-yellow">
                                <i class="fa fa-clock-o"></i>
                            </span>
                            <h5 class="description-header">{{ $statistics['avg_duration'] ?? '0m' }}</h5>
                            <span class="description-text">AVG DURATION</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Filters & Search -->
    <div class="col-md-3">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-filter"></i> Filters
                </h3>
            </div>
            <div class="box-body">
                <form id="history-filters">
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" id="status-filter" name="status">
                            <option value="">All Statuses</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                            <option value="running">Running</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="rolled_back">Rolled Back</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Date Range</label>
                        <div class="row">
                            <div class="col-xs-6">
                                <input type="date" class="form-control" id="date-from" name="date_from" placeholder="From">
                            </div>
                            <div class="col-xs-6">
                                <input type="date" class="form-control" id="date-to" name="date_to" placeholder="To">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Version</label>
                        <input type="text" class="form-control" id="version-filter" name="version" placeholder="Target version">
                    </div>
                    
                    <div class="form-group">
                        <label>Initiated By</label>
                        <select class="form-control" id="user-filter" name="user">
                            <option value="">All Users</option>
                            <option value="system">System</option>
                            <option value="manual">Manual</option>
                            <option value="scheduled">Scheduled</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="button" class="btn btn-primary btn-block" id="apply-filters">
                            <i class="fa fa-search"></i> Apply Filters
                        </button>
                        <button type="button" class="btn btn-default btn-block" id="clear-filters">
                            <i class="fa fa-times"></i> Clear Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Common Failures -->
        @if(isset($statistics['most_common_failures']) && count($statistics['most_common_failures']) > 0)
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-exclamation-triangle"></i> Common Failures
                </h3>
            </div>
            <div class="box-body">
                @foreach($statistics['most_common_failures'] as $failure)
                <div class="callout callout-warning callout-sm">
                    <h5>{{ $failure['error'] }}</h5>
                    <p><small>{{ $failure['count'] }} occurrences</small></p>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Update Sessions History -->
    <div class="col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-history"></i> Update Sessions
                </h3>
                <div class="box-tools pull-right">
                    <button class="btn btn-primary btn-sm" id="refresh-history">
                        <i class="fa fa-refresh"></i> Refresh
                    </button>
                    <button class="btn btn-success btn-sm" id="export-history">
                        <i class="fa fa-download"></i> Export
                    </button>
                </div>
            </div>
            <div class="box-body">
                @if($sessions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="history-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Version Change</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th>Initiated By</th>
                                    <th>Progress</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sessions as $session)
                                <tr class="session-row" data-session-id="{{ $session->id }}">
                                    <td>
                                        <strong>{{ $session->created_at->format('M d, Y') }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $session->created_at->format('H:i:s') }}</small>
                                    </td>
                                    <td>
                                        <div class="version-change">
                                            <strong>{{ $session->from_version }}</strong>
                                            <i class="fa fa-arrow-right text-muted"></i>
                                            <strong>{{ $session->target_version }}</strong>
                                        </div>
                                        @if($session->update_type)
                                            <small class="label label-info">{{ ucfirst($session->update_type) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($session->duration)
                                            <span class="duration-badge">{{ $session->duration_formatted }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="label label-{{ $session->status === 'completed' ? 'success' : ($session->status === 'failed' ? 'danger' : ($session->status === 'running' ? 'primary' : 'default')) }}">
                                            {{ ucfirst($session->status) }}
                                        </span>
                                        @if($session->rolled_back)
                                            <br><span class="label label-warning">Rolled Back</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <strong>{{ $session->initiator->name ?? 'System' }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                via {{ ucfirst($session->initiated_via ?? 'unknown') }}
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($session->status === 'running')
                                            <div class="progress progress-xs">
                                                <div class="progress-bar progress-bar-primary" 
                                                     style="width: {{ $session->progress_percentage ?? 0 }}%"></div>
                                            </div>
                                            <small>{{ $session->progress_percentage ?? 0 }}%</small>
                                        @else
                                            @if($session->completed_steps && $session->total_steps)
                                                <small>{{ $session->completed_steps }}/{{ $session->total_steps }} steps</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-xs">
                                            <a href="{{ route('admin.updates.session-details', $session->id) }}" 
                                               class="btn btn-info" title="View Details">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <button class="btn btn-default view-logs-btn" 
                                                    data-session-id="{{ $session->id }}" title="View Logs">
                                                <i class="fa fa-file-text"></i>
                                            </button>
                                            @if($session->status === 'completed' && !$session->rolled_back)
                                                <button class="btn btn-warning rollback-btn" 
                                                        data-session-id="{{ $session->id }}" title="Rollback">
                                                    <i class="fa fa-undo"></i>
                                                </button>
                                            @endif
                                            @if($session->status === 'running')
                                                <button class="btn btn-danger stop-btn" 
                                                        data-session-id="{{ $session->id }}" title="Stop Update">
                                                    <i class="fa fa-stop"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="row">
                        <div class="col-sm-5">
                            <div class="dataTables_info">
                                Showing {{ $sessions->firstItem() ?? 0 }} to {{ $sessions->lastItem() ?? 0 }} 
                                of {{ $sessions->total() ?? 0 }} entries
                            </div>
                        </div>
                        <div class="col-sm-7">
                            <div class="dataTables_paginate paging_simple_numbers">
                                {{ $sessions->links() }}
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-info">
                        <h4><i class="fa fa-info-circle"></i> No Update History</h4>
                        <p>No update sessions found. Once you perform updates, they will appear here.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Session Logs Modal -->
<div class="modal fade" id="session-logs-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-file-text"></i> Session Logs
                    <span id="modal-session-id"></span>
                </h4>
            </div>
            <div class="modal-body">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="active"><a href="#system-logs" data-toggle="tab">System Logs</a></li>
                        <li><a href="#error-logs" data-toggle="tab">Error Logs</a></li>
                        <li><a href="#migration-logs" data-toggle="tab">Migration Logs</a></li>
                        <li><a href="#debug-logs" data-toggle="tab">Debug Logs</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="system-logs">
                            <div class="log-container">
                                <pre id="system-logs-content" class="log-content">Loading...</pre>
                            </div>
                        </div>
                        <div class="tab-pane" id="error-logs">
                            <div class="log-container">
                                <pre id="error-logs-content" class="log-content">Loading...</pre>
                            </div>
                        </div>
                        <div class="tab-pane" id="migration-logs">
                            <div class="log-container">
                                <pre id="migration-logs-content" class="log-content">Loading...</pre>
                            </div>
                        </div>
                        <div class="tab-pane" id="debug-logs">
                            <div class="log-container">
                                <pre id="debug-logs-content" class="log-content">Loading...</pre>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Log Controls -->
                <div class="log-controls">
                    <div class="btn-group pull-left">
                        <button class="btn btn-default btn-sm" id="auto-scroll-toggle">
                            <i class="fa fa-arrows-v"></i> Auto Scroll
                        </button>
                        <button class="btn btn-default btn-sm" id="wrap-text-toggle">
                            <i class="fa fa-align-left"></i> Wrap Text
                        </button>
                    </div>
                    <div class="btn-group pull-right">
                        <button class="btn btn-success btn-sm" id="download-logs">
                            <i class="fa fa-download"></i> Download
                        </button>
                        <button class="btn btn-primary btn-sm" id="refresh-logs">
                            <i class="fa fa-refresh"></i> Refresh
                        </button>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Rollback Confirmation Modal -->
<div class="modal fade" id="rollback-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-undo"></i> Confirm Rollback
                </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <h4><i class="fa fa-exclamation-triangle"></i> Warning</h4>
                    <p>This will rollback the update and revert all changes made during the selected update session. This action cannot be undone.</p>
                </div>
                
                <div class="form-group">
                    <label>Rollback Type:</label>
                    <div class="radio">
                        <label>
                            <input type="radio" name="rollback_type" value="full" checked>
                            Full Rollback - Revert all changes from this update
                        </label>
                    </div>
                    <div class="radio">
                        <label>
                            <input type="radio" name="rollback_type" value="selective">
                            Selective Rollback - Choose specific steps to rollback
                        </label>
                    </div>
                </div>
                
                <div id="selective-options" style="display: none;">
                    <div class="form-group">
                        <label>Steps to Rollback:</label>
                        <div id="rollback-steps-list">
                            <!-- Will be populated dynamically -->
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="confirm-rollback" required>
                        I understand the risks and want to proceed with the rollback
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirm-rollback-btn" disabled>
                    <i class="fa fa-undo"></i> Start Rollback
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Error Display -->
@if(isset($error))
<div class="alert alert-danger">
    <h4><i class="icon fa fa-ban"></i> History Error</h4>
    {{ $error }}
</div>
@endif

</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
            let currentSessionId = null;
            let autoScroll = true;
            let wrapText = false;

            // Event handlers
            $('#apply-filters').click(function() {
                applyFilters();
            });

            $('#clear-filters').click(function() {
                clearFilters();
            });

            $('#refresh-history').click(function() {
                refreshHistory();
            });

            $('#export-history').click(function() {
                exportHistory();
            });

            $('.view-logs-btn').click(function() {
                const sessionId = $(this).data('session-id');
                viewSessionLogs(sessionId);
            });

            $('.rollback-btn').click(function() {
                const sessionId = $(this).data('session-id');
                showRollbackModal(sessionId);
            });

            $('.stop-btn').click(function() {
                const sessionId = $(this).data('session-id');
                stopUpdate(sessionId);
            });

            $('#confirm-rollback').change(function() {
                $('#confirm-rollback-btn').prop('disabled', !$(this).is(':checked'));
            });

            $('#confirm-rollback-btn').click(function() {
                initiateRollback();
            });

            $('input[name="rollback_type"]').change(function() {
                $('#selective-options').toggle($(this).val() === 'selective');
            });

            // Log modal controls
            $('#auto-scroll-toggle').click(function() {
                autoScroll = !autoScroll;
                $(this).toggleClass('active', autoScroll);
            });

            $('#wrap-text-toggle').click(function() {
                wrapText = !wrapText;
                $('.log-content').css('white-space', wrapText ? 'pre-wrap' : 'pre');
                $(this).toggleClass('active', wrapText);
            });

            $('#download-logs').click(function() {
                downloadLogs();
            });

            $('#refresh-logs').click(function() {
                refreshLogs();
            });

            // Auto-refresh running sessions
            setInterval(function() {
                refreshRunningSessions();
            }, 10000);
        });

        function applyFilters() {
            const formData = $('#history-filters').serialize();
            const currentUrl = window.location.href.split('?')[0];
            window.location.href = currentUrl + '?' + formData;
        }

        function clearFilters() {
            $('#history-filters')[0].reset();
            window.location.href = window.location.href.split('?')[0];
        }

        function refreshHistory() {
            $('#refresh-history').prop('disabled', true).html('<i class="fa fa-spin fa-refresh"></i> Refreshing...');
            location.reload();
        }

        function exportHistory() {
            const formData = $('#history-filters').serialize();
            window.open('{{ route("admin.updates.export-history") }}?' + formData);
        }

        function viewSessionLogs(sessionId) {
            currentSessionId = sessionId;
            $('#modal-session-id').text('(Session: ' + sessionId + ')');
            $('#session-logs-modal').modal('show');
            
            // Load initial logs
            loadLogs('system');
        }

        function loadLogs(type) {
            if (!currentSessionId) return;

            const contentElement = $('#' + type + '-logs-content');
            contentElement.text('Loading...');

            $.ajax({
                url: '{{ route("admin.updates.session-logs", ":id") }}'.replace(':id', currentSessionId),
                type: 'GET',
                data: { type: type },
                success: function(response) {
                    if (response.success) {
                        contentElement.text(response.logs || 'No logs available');
                        if (autoScroll) {
                            contentElement.scrollTop(contentElement[0].scrollHeight);
                        }
                    } else {
                        contentElement.text('Error loading logs: ' + response.error);
                    }
                },
                error: function() {
                    contentElement.text('Failed to load logs');
                }
            });
        }

        function downloadLogs() {
            if (!currentSessionId) return;
            
            const activeTab = $('.tab-pane.active').attr('id').replace('-logs', '').replace('-', '_');
            window.open('{{ route("admin.updates.download-logs", ":id") }}'.replace(':id', currentSessionId) + '?type=' + activeTab);
        }

        function refreshLogs() {
            const activeTab = $('.tab-pane.active').attr('id').replace('-logs', '').replace('-', '_');
            loadLogs(activeTab);
        }

        function showRollbackModal(sessionId) {
            currentSessionId = sessionId;
            
            // Load rollback steps
            $.ajax({
                url: '{{ route("admin.updates.rollback-steps", ":id") }}'.replace(':id', sessionId),
                type: 'GET',
                success: function(response) {
                    if (response.success && response.steps) {
                        let stepsHtml = '';
                        response.steps.forEach(function(step) {
                            stepsHtml += `
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="rollback_steps[]" value="${step.id}">
                                        ${step.step_name} - ${step.description}
                                    </label>
                                </div>
                            `;
                        });
                        $('#rollback-steps-list').html(stepsHtml);
                    }
                },
                error: function() {
                    showAlert('error', 'Failed to load rollback steps');
                }
            });

            $('#rollback-modal').modal('show');
        }

        function initiateRollback() {
            const rollbackType = $('input[name="rollback_type"]:checked').val();
            let rollbackSteps = [];
            
            if (rollbackType === 'selective') {
                rollbackSteps = $('input[name="rollback_steps[]"]:checked').map(function() {
                    return $(this).val();
                }).get();
            }

            $.ajax({
                url: '{{ route("admin.updates.rollback", ":sessionId") }}'.replace(':sessionId', currentSessionId),
                type: 'POST',
                data: {
                    rollback_type: rollbackType,
                    selective_steps: rollbackSteps,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Rollback initiated successfully');
                        $('#rollback-modal').modal('hide');
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

        function stopUpdate(sessionId) {
            if (confirm('Are you sure you want to stop this update? This may leave the system in an inconsistent state.')) {
                $.ajax({
                    url: '{{ route("admin.updates.stop") }}',
                    type: 'POST',
                    data: {
                        session_id: sessionId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            showAlert('success', 'Update stop initiated');
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

        function refreshRunningSessions() {
            $('.session-row').each(function() {
                const sessionId = $(this).data('session-id');
                const statusLabel = $(this).find('.label');
                
                if (statusLabel.text().trim() === 'Running') {
                    updateSessionProgress(sessionId, $(this));
                }
            });
        }

        function updateSessionProgress(sessionId, row) {
            $.ajax({
                url: '{{ route("admin.updates.session-progress", ":id") }}'.replace(':id', sessionId),
                type: 'GET',
                success: function(response) {
                    if (response.success && response.progress) {
                        const progressBar = row.find('.progress-bar');
                        const progressText = row.find('small');
                        
                        if (progressBar.length) {
                            progressBar.css('width', response.progress.percentage + '%');
                        }
                        
                        if (progressText.length) {
                            progressText.text(response.progress.percentage + '%');
                        }
                    }
                },
                error: function() {
                    // Session might have completed, refresh page
                    location.reload();
                }
            });
        }

        // Tab switching for logs
        $('.nav-tabs a').on('shown.bs.tab', function (e) {
            const tabId = $(e.target).attr('href').replace('#', '').replace('-logs', '').replace('-', '_');
            loadLogs(tabId);
        });

        function showAlert(type, message) {
            var alertClass = 'alert-' + (type === 'error' ? 'danger' : type);
            var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible">' +
                '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' +
                message + '</div>';
            
            $('#update-history').prepend(alertHtml);
            
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        }
    </script>
@endsection

@section('styles')
    @parent
    <style>
        .description-block {
            text-align: center;
        }
        
        .border-right {
            border-right: 1px solid #eee;
        }
        
        .version-change {
            font-family: 'Monaco', 'Consolas', monospace;
        }
        
        .duration-badge {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .user-info strong {
            display: block;
        }
        
        .session-row:hover {
            background-color: #f9f9f9;
        }
        
        .log-container {
            height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            background: #000;
            color: #fff;
            margin-bottom: 10px;
        }
        
        .log-content {
            margin: 0;
            padding: 10px;
            white-space: pre;
            word-wrap: normal;
            background: #000;
            color: #fff;
            border: none;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 12px;
        }
        
        .log-controls {
            margin-top: 10px;
        }
        
        .log-controls .btn.active {
            background-color: #337ab7;
            color: white;
        }
        
        #history-table th {
            white-space: nowrap;
        }
        
        .progress-xs {
            height: 5px;
        }
        
        .modal-lg {
            width: 90%;
        }
    </style>
@endsection