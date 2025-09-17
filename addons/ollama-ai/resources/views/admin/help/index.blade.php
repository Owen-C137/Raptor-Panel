@extends('layouts.admin')

@section('title')
    AI Help System Management
@endsection

@section('content-header')
    <h1>AI Help System Management <small>Monitor and manage the AI-powered help system</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.ai.dashboard') }}">AI Dashboard</a></li>
        <li class="active">Help System</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <!-- Overview Statistics -->
    <div class="col-xs-12 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-blue"><i class="fa fa-question-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Help Contexts</span>
                <span class="info-box-number">{{ number_format($stats['total_help_contexts']) }}</span>
            </div>
        </div>
    </div>
    
    <div class="col-xs-12 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-green"><i class="fa fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Active Learners</span>
                <span class="info-box-number">{{ number_format($stats['active_learners']) }}</span>
                <span class="progress-description">Last 7 days</span>
            </div>
        </div>
    </div>
    
    <div class="col-xs-12 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-yellow"><i class="fa fa-graduation-cap"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Completed Tutorials</span>
                <span class="info-box-number">{{ number_format($stats['completed_tutorials']) }}</span>
            </div>
        </div>
    </div>
    
    <div class="col-xs-12 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-red"><i class="fa fa-clock-o"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Help Requests Today</span>
                <span class="info-box-number">{{ number_format($stats['help_requests_today']) }}</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Actions -->
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Quick Actions</h3>
            </div>
            <div class="box-body">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-primary" onclick="generateHelpForUser()">
                        <i class="fa fa-magic"></i> Generate Help for User
                    </button>
                    <button type="button" class="btn btn-info" onclick="createTutorial()">
                        <i class="fa fa-plus"></i> Create Tutorial
                    </button>
                    <a href="{{ route('admin.ai.help-system.analytics') }}" class="btn btn-success">
                        <i class="fa fa-chart-bar"></i> View Analytics
                    </a>
                    <a href="{{ route('admin.ai.help-system.tutorials') }}" class="btn btn-warning">
                        <i class="fa fa-book"></i> Manage Tutorials
                    </a>
                    <button type="button" class="btn btn-default" onclick="exportData()">
                        <i class="fa fa-download"></i> Export Data
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Help Activity -->
    <div class="col-xs-12 col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Recent Help Activity</h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse">
                        <i class="fa fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="box-body">
                @if($recentActivity->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Route</th>
                                    <th>Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentActivity as $activity)
                                <tr>
                                    <td>
                                        @if($activity->user)
                                            <a href="{{ route('admin.ai.help-system.user.progress', $activity->user->id) }}">
                                                {{ $activity->user->name }}
                                            </a>
                                        @else
                                            <em>Unknown User</em>
                                        @endif
                                    </td>
                                    <td>
                                        <code>{{ Str::limit($activity->route_name, 30) }}</code>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $activity->created_at->diffForHumans() }}</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-xs btn-info" onclick="viewHelpContext({{ $activity->id }})">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted">No recent help activity found.</p>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Learning Progress -->
    <div class="col-xs-12 col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Active Learning Progress</h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse">
                        <i class="fa fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="box-body">
                @if($learningProgress->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Topic</th>
                                    <th>Progress</th>
                                    <th>Last Active</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($learningProgress as $progress)
                                <tr>
                                    <td>
                                        @if($progress->user)
                                            <a href="{{ route('admin.ai.help-system.user.progress', $progress->user->id) }}">
                                                {{ $progress->user->name }}
                                            </a>
                                        @else
                                            <em>Unknown User</em>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="label label-info">{{ ucfirst(str_replace('_', ' ', $progress->topic)) }}</span>
                                    </td>
                                    <td>
                                        <div class="progress progress-xs">
                                            <div class="progress-bar progress-bar-primary" 
                                                 style="width: {{ $progress->completion_percentage }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ $progress->completion_percentage }}%</small>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            {{ $progress->last_accessed ? $progress->last_accessed->diffForHumans() : 'Never' }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted">No active learning progress found.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Generate Help Modal -->
<div class="modal fade" id="generateHelpModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Generate Help for User</h4>
            </div>
            <div class="modal-body">
                <form id="generateHelpForm">
                    <div class="form-group">
                        <label for="userId">Select User</label>
                        <select class="form-control" id="userId" name="user_id" required>
                            <option value="">Select a user...</option>
                            <!-- Users will be loaded via AJAX -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="route">Route/Page</label>
                        <input type="text" class="form-control" id="route" name="route" 
                               placeholder="e.g., admin.servers.index" required>
                    </div>
                    <div class="form-group">
                        <label for="context">Additional Context (JSON)</label>
                        <textarea class="form-control" id="context" name="context" rows="4"
                                  placeholder='{"server_id": 1, "action": "create"}'></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitGenerateHelp()">
                    <i class="fa fa-magic"></i> Generate Help
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Help Context Modal -->
<div class="modal fade" id="helpContextModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Help Context Details</h4>
            </div>
            <div class="modal-body">
                <div id="helpContextContent">
                    <!-- Content loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('footer-scripts')
    @parent
    <script>
        function generateHelpForUser() {
            // Load users list
            $.get('/admin/users', function(users) {
                const userSelect = $('#userId');
                userSelect.empty().append('<option value="">Select a user...</option>');
                users.forEach(user => {
                    userSelect.append(`<option value="${user.id}">${user.name} (${user.email})</option>`);
                });
            });
            
            $('#generateHelpModal').modal('show');
        }
        
        function submitGenerateHelp() {
            const formData = {
                user_id: $('#userId').val(),
                route: $('#route').val(),
                context: $('#context').val() ? JSON.parse($('#context').val()) : {}
            };
            
            $.post('/admin/ai/help-system/generate', formData)
                .done(function(response) {
                    $('#generateHelpModal').modal('hide');
                    swal({
                        type: 'success',
                        title: 'Help Generated!',
                        text: 'Contextual help has been generated for the user.',
                    });
                    setTimeout(() => location.reload(), 2000);
                })
                .fail(function(xhr) {
                    swal({
                        type: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Failed to generate help.',
                    });
                });
        }
        
        function viewHelpContext(contextId) {
            $.get(`/admin/ai/help-system/context/${contextId}`)
                .done(function(context) {
                    const content = `
                        <div class="row">
                            <div class="col-sm-6">
                                <h5>Context Information</h5>
                                <pre>${JSON.stringify(context.context_data, null, 2)}</pre>
                            </div>
                            <div class="col-sm-6">
                                <h5>Generated Help</h5>
                                <pre>${JSON.stringify(context.help_data, null, 2)}</pre>
                            </div>
                        </div>
                    `;
                    $('#helpContextContent').html(content);
                    $('#helpContextModal').modal('show');
                })
                .fail(function() {
                    swal({
                        type: 'error',
                        title: 'Error',
                        text: 'Failed to load help context details.',
                    });
                });
        }
        
        function createTutorial() {
            swal({
                title: 'Create Tutorial',
                text: 'Enter the tutorial topic:',
                type: 'input',
                showCancelButton: true,
                inputValidator: function(value) {
                    return new Promise(function(resolve, reject) {
                        if (value) {
                            resolve();
                        } else {
                            reject('Please enter a topic name');
                        }
                    });
                }
            }).then(function(result) {
                if (result.value) {
                    $.post('/admin/ai/help-system/tutorial', {
                        topic: result.value,
                        skill_level: 'beginner'
                    })
                    .done(function(response) {
                        swal({
                            type: 'success',
                            title: 'Tutorial Created!',
                            text: 'The tutorial has been generated successfully.',
                        });
                    })
                    .fail(function(xhr) {
                        swal({
                            type: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Failed to create tutorial.',
                        });
                    });
                }
            });
        }
        
        function exportData() {
            window.location.href = '/admin/ai/help-system/export?format=csv';
        }
    </script>
@endsection