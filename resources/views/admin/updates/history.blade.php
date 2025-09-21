@extends('layouts.admin')
@include('partials/admin.updates.nav', ['activeTab' => 'history'])

@section('title')
    Update History & Logs
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Update History & Logs
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          View and manage update session history and statistics.
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
            History & Logs
          </li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
@yield('updates::nav')
<div class="row" id="update-history">
    <!-- Statistics Overview -->
    <div class="col-12 mb-4">
        <div class="block block-rounded block-themed">
            <div class="block-header bg-primary">
                <h3 class="block-title text-white">
                    <i class="fa fa-chart-bar me-1"></i> Update Statistics
                </h3>
                <div class="block-options">
                    <button class="btn btn-sm btn-outline-light" id="refresh-stats-btn">
                        <i class="fa fa-refresh me-1"></i> Refresh
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div class="row text-center">
                    <div class="col-6 col-md-3 py-3">
                        <div class="fs-2 fw-bold text-primary">{{ $statistics['total_updates'] ?? 0 }}</div>
                        <div class="fs-sm fw-medium text-muted text-uppercase tracking-wider">
                            <i class="fa fa-database me-1"></i>Total Updates
                        </div>
                    </div>
                    <div class="col-6 col-md-3 py-3 border-start">
                        <div class="fs-2 fw-bold text-success">{{ $statistics['successful_updates'] ?? 0 }}</div>
                        <div class="fs-sm fw-medium text-muted text-uppercase tracking-wider">
                            <i class="fa fa-check-circle me-1"></i>Successful
                        </div>
                        @if(($statistics['total_updates'] ?? 0) > 0)
                        <div class="fs-xs text-success">
                            {{ number_format((($statistics['successful_updates'] ?? 0) / $statistics['total_updates']) * 100, 1) }}% Success Rate
                        </div>
                        @endif
                    </div>
                    <div class="col-6 col-md-3 py-3 border-start">
                        <div class="fs-2 fw-bold text-danger">{{ $statistics['failed_updates'] ?? 0 }}</div>
                        <div class="fs-sm fw-medium text-muted text-uppercase tracking-wider">
                            <i class="fa fa-times-circle me-1"></i>Failed
                        </div>
                        @if(($statistics['total_updates'] ?? 0) > 0)
                        <div class="fs-xs text-danger">
                            {{ number_format((($statistics['failed_updates'] ?? 0) / $statistics['total_updates']) * 100, 1) }}% Failure Rate
                        </div>
                        @endif
                    </div>
                    <div class="col-6 col-md-3 py-3 border-start">
                        <div class="fs-2 fw-bold text-warning">{{ $statistics['average_duration'] ?? '0m' }}</div>
                        <div class="fs-sm fw-medium text-muted text-uppercase tracking-wider">
                            <i class="fa fa-clock me-1"></i>Avg Duration
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Filters & Search -->
    <div class="col-lg-3 mb-4">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-filter me-1"></i> Filters
                </h3>
            </div>
            <div class="block-content">
                <form id="history-filters">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="status-filter" name="status">
                            <option value="">All Statuses</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                            <option value="running">Running</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="rolled_back">Rolled Back</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Date Range</label>
                        <div class="row">
                            <div class="col-6">
                                <input type="date" class="form-control" id="date-from" name="date_from" placeholder="From">
                            </div>
                            <div class="col-6">
                                <input type="date" class="form-control" id="date-to" name="date_to" placeholder="To">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Version</label>
                        <input type="text" class="form-control" id="version-filter" name="version" placeholder="Target version">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Initiated By</label>
                        <select class="form-select" id="user-filter" name="user">
                            <option value="">All Users</option>
                            <option value="system">System</option>
                            <option value="manual">Manual</option>
                            <option value="scheduled">Scheduled</option>
                        </select>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary" id="apply-filters">
                            <i class="fa fa-search me-1"></i> Apply Filters
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="clear-filters">
                            <i class="fa fa-times me-1"></i> Clear Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Common Failures -->
        @if(isset($statistics['most_common_failures']) && count($statistics['most_common_failures']) > 0)
        <div class="block block-rounded">
            <div class="block-header bg-warning text-white">
                <h3 class="block-title">
                    <i class="fa fa-exclamation-triangle me-1"></i> Common Failures
                </h3>
            </div>
            <div class="block-content">
                @foreach($statistics['most_common_failures'] as $failure)
                <div class="alert alert-warning d-flex align-items-start mb-2">
                    <div class="flex-shrink-0">
                        <i class="fa fa-exclamation-triangle"></i>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <h6 class="alert-heading mb-1">{{ $failure['error'] }}</h6>
                        <small class="text-muted">{{ $failure['count'] }} occurrences</small>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Update Sessions History -->
    <div class="col-lg-9">
        <div class="block block-rounded block-themed">
            <div class="block-header bg-primary">
                <h3 class="block-title text-white">
                    <i class="fa fa-history me-1"></i> Update Sessions
                </h3>
                <div class="block-options">
                    <button class="btn btn-sm btn-outline-light me-1" id="refresh-history">
                        <i class="fa fa-refresh me-1"></i> Refresh
                    </button>
                    <button class="btn btn-sm btn-outline-light" id="export-history">
                        <i class="fa fa-download me-1"></i> Export
                    </button>
                </div>
            </div>
            <div class="block-content">
                @if($sessions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle" id="history-table">
                            <thead class="table-dark">
                                <tr>
                                    <th style="min-width: 120px;">Date</th>
                                    <th style="min-width: 180px;">Version Change</th>
                                    <th style="min-width: 80px;">Duration</th>
                                    <th style="min-width: 100px;">Status</th>
                                    <th style="min-width: 140px;">Initiated By</th>
                                    <th style="min-width: 120px;">Progress</th>
                                    <th style="min-width: 140px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sessions as $session)
                                <tr class="session-row" data-session-id="{{ $session->id }}">
                                    <td>
                                        <div class="text-nowrap">
                                            <div><strong>{{ $session->created_at->format('M d, Y') }}</strong></div>
                                            <small class="text-muted">{{ $session->created_at->format('H:i:s') }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="version-change">
                                            <strong>{{ $session->from_version ?: 'Unknown' }}</strong>
                                            <i class="fa fa-arrow-right text-muted mx-2"></i>
                                            <strong>{{ $session->to_version ?? $session->target_version ?: 'Unknown' }}</strong>
                                        </div>
                                        @if($session->update_type)
                                            <div class="mt-1">
                                                <small class="badge bg-info text-white">{{ ucfirst($session->update_type) }}</small>
                                            </div>
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
                                        <span class="badge bg-{{ $session->status === 'completed' ? 'success' : ($session->status === 'failed' ? 'danger' : ($session->status === 'running' ? 'primary' : 'secondary')) }}">
                                            {{ ucfirst($session->status) }}
                                        </span>
                                        @if($session->rolled_back)
                                            <br><span class="badge bg-warning text-dark">Rolled Back</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <div><strong>{{ $session->initiator->name ?? 'System' }}</strong></div>
                                            <small class="text-muted">
                                                via {{ ucfirst($session->initiated_via ?? 'unknown') }}
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($session->status === 'running')
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-primary" role="progressbar" 
                                                     style="width: {{ $session->progress_percentage ?? 0 }}%"></div>
                                            </div>
                                            <small class="text-muted">{{ $session->progress_percentage ?? 0 }}%</small>
                                        @else
                                            @if($session->completed_steps && $session->total_steps)
                                                <small class="text-muted">{{ $session->completed_steps }}/{{ $session->total_steps }} steps</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('admin.updates.session-details', $session->id) }}" 
                                               class="btn btn-outline-info btn-sm" title="View Details">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <button class="btn btn-outline-secondary btn-sm view-logs-btn" 
                                                    data-session-id="{{ $session->id }}" title="View Logs">
                                                <i class="fa fa-file-text"></i>
                                            </button>
                                            @if($session->status === 'completed' && !$session->rolled_back)
                                                <button class="btn btn-outline-warning btn-sm rollback-btn" 
                                                        data-session-id="{{ $session->id }}" title="Rollback">
                                                    <i class="fa fa-undo"></i>
                                                </button>
                                            @endif
                                            @if($session->status === 'running')
                                                <button class="btn btn-outline-danger btn-sm stop-btn" 
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
                    <div class="row mt-3">
                        <div class="col-sm-12 col-md-5">
                            <div class="text-muted">
                                Showing {{ $sessions->firstItem() ?? 0 }} to {{ $sessions->lastItem() ?? 0 }} 
                                of {{ $sessions->total() ?? 0 }} entries
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-7">
                            <div class="d-flex justify-content-end">
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
<div class="modal fade" id="session-logs-modal" tabindex="-1" aria-labelledby="sessionLogsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sessionLogsModalLabel">
                    <i class="fa fa-file-text"></i> Session Logs
                    <span id="modal-session-id"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="log-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="system-logs-tab" data-bs-toggle="tab" 
                                        data-bs-target="#system-logs" type="button" role="tab">System Logs</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="error-logs-tab" data-bs-toggle="tab" 
                                        data-bs-target="#error-logs" type="button" role="tab">Error Logs</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="migration-logs-tab" data-bs-toggle="tab" 
                                        data-bs-target="#migration-logs" type="button" role="tab">Migration Logs</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="debug-logs-tab" data-bs-toggle="tab" 
                                        data-bs-target="#debug-logs" type="button" role="tab">Debug Logs</button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="log-tabs-content">
                            <div class="tab-pane fade show active" id="system-logs" role="tabpanel">
                                <div class="log-container">
                                    <pre id="system-logs-content" class="log-content">Loading...</pre>
                                </div>
                                <div class="tab-pane fade" id="error-logs" role="tabpanel">
                                <div class="log-container">
                                    <pre id="error-logs-content" class="log-content">Loading...</pre>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="migration-logs" role="tabpanel">
                                <div class="log-container">
                                    <pre id="migration-logs-content" class="log-content">Loading...</pre>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="debug-logs" role="tabpanel">
                                <div class="log-container">
                                    <pre id="debug-logs-content" class="log-content">Loading...</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Log Controls -->
                <div class="log-controls mt-3">
                    <div class="d-flex justify-content-between">
                        <div class="btn-group" role="group">
                            <button class="btn btn-outline-secondary btn-sm" id="auto-scroll-toggle">
                                <i class="fa fa-arrows-v"></i> Auto Scroll
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" id="wrap-text-toggle">
                                <i class="fa fa-align-left"></i> Wrap Text
                            </button>
                        </div>
                        <div class="btn-group" role="group">
                            <button class="btn btn-outline-success btn-sm" id="download-logs">
                                <i class="fa fa-download"></i> Download
                            </button>
                            <button class="btn btn-outline-primary btn-sm" id="refresh-logs">
                                <i class="fa fa-refresh"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Rollback Confirmation Modal -->
<div class="modal fade" id="rollback-modal" tabindex="-1" aria-labelledby="rollbackModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rollbackModalLabel">
                    <i class="fa fa-undo"></i> Confirm Rollback
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <h6 class="alert-heading"><i class="fa fa-exclamation-triangle"></i> Warning</h6>
                    <p class="mb-0">This will rollback the update and revert all changes made during the selected update session. This action cannot be undone.</p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Rollback Type:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="rollback_type" id="full-rollback" value="full" checked>
                        <label class="form-check-label" for="full-rollback">
                            Full Rollback - Revert all changes from this update
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="rollback_type" id="selective-rollback" value="selective">
                        <label class="form-check-label" for="selective-rollback">
                            Selective Rollback - Choose specific steps to rollback
                        </label>
                    </div>
                </div>
                
                <div id="selective-options" style="display: none;">
                    <div class="mb-3">
                        <label class="form-label">Steps to Rollback:</label>
                        <div id="rollback-steps-list">
                            <!-- Will be populated dynamically -->
                        </div>
                    </div>
                </div>
                
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="confirm-rollback" required>
                    <label class="form-check-label" for="confirm-rollback">
                        I understand the risks and want to proceed with the rollback
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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
    
    <!-- Toast Notifications -->
    <div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3">
        <!-- Toast notifications will be added here -->
    </div>
    
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
            
            const modal = new bootstrap.Modal(document.getElementById('session-logs-modal'));
            modal.show();
            
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
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="rollback_steps[]" value="${step.id}" id="step-${step.id}">
                                    <label class="form-check-label" for="step-${step.id}">
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

            const modal = new bootstrap.Modal(document.getElementById('rollback-modal'));
            modal.show();
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
                        const modal = bootstrap.Modal.getInstance(document.getElementById('rollback-modal'));
                        modal.hide();
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
                const statusBadge = $(this).find('.badge');
                
                if (statusBadge.text().trim() === 'Running') {
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

        document.getElementById('log-tabs').addEventListener('shown.bs.tab', function (event) {
            const tabId = event.target.getAttribute('data-bs-target').replace('#', '').replace('-logs', '').replace('-', '_');
            loadLogs(tabId);
        });

        // Toast notification function
        window.showToast = function(message, type = 'info') {
            const toastId = 'toast-' + Date.now();
            const iconClass = type === 'success' ? 'fa-check-circle text-success' : 
                             type === 'error' ? 'fa-exclamation-triangle text-danger' : 
                             type === 'warning' ? 'fa-exclamation-circle text-warning' :
                             'fa-info-circle text-primary';
                             
            const toastHtml = `
                <div class="toast" id="${toastId}" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header">
                        <i class="fa ${iconClass} me-2"></i>
                        <strong class="me-auto">Update System</strong>
                        <small class="text-muted">just now</small>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;
            
            $('#toast-container').append(toastHtml);
            
            const toast = new bootstrap.Toast(document.getElementById(toastId), {
                autohide: true,
                delay: 5000
            });
            toast.show();
            
            // Remove toast element after it's hidden
            document.getElementById(toastId).addEventListener('hidden.bs.toast', function () {
                this.remove();
            });
        };

        function showAlert(type, message) {
            // Use toast notifications if available
            if (typeof window.showToast === 'function') {
                window.showToast(message, type);
                return;
            }
            
            // Fallback to inline alerts
            var alertClass = 'alert-' + (type === 'error' ? 'danger' : type);
            var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                '<i class="fa fa-' + (type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle') + '"></i> ' +
                message + 
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            
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
        .version-change {
            font-family: 'Monaco', 'Consolas', monospace;
            white-space: nowrap;
        }
        
        .version-change i {
            margin: 0 0.5rem;
            opacity: 0.7;
        }
        
        .duration-badge {
            background: var(--bs-gray-200);
            color: var(--bs-gray-800);
            padding: 4px 8px;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
            white-space: nowrap;
        }
        
        .user-info {
            min-width: 120px;
        }
        
        .user-info strong {
            display: block;
            font-size: 0.875rem;
        }
        
        .user-info small {
            font-size: 0.75rem;
        }
        
        .session-row:hover {
            background-color: var(--bs-gray-50);
        }
        
        .log-container {
            height: 400px;
            overflow-y: auto;
            border: 1px solid var(--bs-border-color);
            background: #1a1a1a;
            color: #f8f9fa;
            margin-bottom: 10px;
            border-radius: 0.375rem;
        }
        
        .log-content {
            margin: 0;
            padding: 1rem;
            white-space: pre;
            word-wrap: normal;
            background: #1a1a1a;
            color: #f8f9fa;
            border: none;
            font-family: 'Monaco', 'Consolas', 'Courier New', monospace;
            font-size: 0.75rem;
            line-height: 1.2;
        }
        
        .log-controls .btn.active {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
            color: white;
        }
        
        #history-table th {
            white-space: nowrap;
            font-weight: 600;
        }
        
        .progress {
            background-color: var(--bs-gray-200);
        }
        
        .modal-lg {
            max-width: 90%;
        }
        
        .table > :not(caption) > * > * {
            vertical-align: middle;
        }
        
        .btn-group .btn {
            border-radius: 0.375rem;
            margin-right: 0.25rem;
        }
        
        .btn-group .btn:last-child {
            margin-right: 0;
        }
        
        .alert-heading {
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .card-header-tabs {
            margin-bottom: 0;
            border-bottom: none;
        }
        
        .card-header-tabs .nav-link.active {
            background-color: transparent;
            border-color: var(--bs-primary);
            color: var(--bs-primary);
        }
        
        .form-check-input:checked {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }
    </style>
@endsection