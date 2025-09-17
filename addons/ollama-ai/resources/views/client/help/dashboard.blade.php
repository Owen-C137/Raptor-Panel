@extends('templates/base')

@section('title')
    AI Learning Dashboard
@endsection

@section('content-header')
    <h1>AI Learning Dashboard <small>Track your learning progress and get personalized help</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('index') }}">Home</a></li>
        <li><a href="{{ route('client.ai.chat') }}">AI Assistant</a></li>
        <li class="active">Learning Dashboard</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <!-- Learning Statistics -->
    <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-blue"><i class="fa fa-book"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Topics Explored</span>
                <span class="info-box-number">{{ $dashboardData['total_topics'] }}</span>
            </div>
        </div>
    </div>
    
    <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-green"><i class="fa fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Completed</span>
                <span class="info-box-number">{{ $dashboardData['completed_tutorials'] }}</span>
            </div>
        </div>
    </div>
    
    <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-yellow"><i class="fa fa-clock-o"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">In Progress</span>
                <span class="info-box-number">{{ $dashboardData['in_progress'] }}</span>
            </div>
        </div>
    </div>
    
    <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-red"><i class="fa fa-hourglass"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Time Spent</span>
                <span class="info-box-number">{{ floor($dashboardData['total_time_spent'] / 3600) }}h</span>
                <span class="progress-description">{{ floor(($dashboardData['total_time_spent'] % 3600) / 60) }}m total</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Current Skill Levels -->
    <div class="col-xs-12 col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Your Current Skill Levels</h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" onclick="refreshSkillAssessment()">
                        <i class="fa fa-refresh"></i>
                    </button>
                </div>
            </div>
            <div class="box-body">
                @foreach($dashboardData['current_skill_levels'] as $skill => $level)
                    @if($skill !== 'overall')
                        <div class="skill-item" style="margin-bottom: 15px;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="skill-name">{{ ucfirst(str_replace('_', ' ', $skill)) }}</span>
                                <span class="skill-badge">
                                    @php
                                        $badgeClass = match($level) {
                                            'beginner' => 'label-default',
                                            'intermediate' => 'label-warning', 
                                            'advanced' => 'label-info',
                                            'expert' => 'label-success',
                                            default => 'label-default'
                                        };
                                    @endphp
                                    <span class="label {{ $badgeClass }}">{{ ucfirst($level) }}</span>
                                </span>
                            </div>
                            <div class="progress progress-xs" style="margin-top: 5px;">
                                @php
                                    $percentage = match($level) {
                                        'beginner' => 25,
                                        'intermediate' => 50,
                                        'advanced' => 75,
                                        'expert' => 100,
                                        default => 0
                                    };
                                @endphp
                                <div class="progress-bar progress-bar-primary" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="col-xs-12 col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Quick Actions</h3>
            </div>
            <div class="box-body">
                <div class="btn-group-vertical" style="width: 100%;">
                    <button type="button" class="btn btn-primary btn-flat" onclick="getContextualHelp()">
                        <i class="fa fa-magic"></i> Get Help for Current Page
                    </button>
                    <button type="button" class="btn btn-info btn-flat" onclick="browseTutorials()">
                        <i class="fa fa-book"></i> Browse Tutorials
                    </button>
                    <button type="button" class="btn btn-success btn-flat" onclick="getRecommendations()">
                        <i class="fa fa-lightbulb-o"></i> Get Learning Recommendations
                    </button>
                    <button type="button" class="btn btn-warning btn-flat" onclick="askQuestion()">
                        <i class="fa fa-question-circle"></i> Ask AI Assistant
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Learning Activity -->
    <div class="col-xs-12 col-md-8">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Recent Learning Activity</h3>
            </div>
            <div class="box-body">
                @if($dashboardData['recent_activity']->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Topic</th>
                                    <th>Progress</th>
                                    <th>Time Spent</th>
                                    <th>Last Active</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dashboardData['recent_activity'] as $activity)
                                <tr>
                                    <td>
                                        <strong>{{ ucfirst(str_replace('_', ' ', $activity->topic)) }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            <span class="label label-info">{{ ucfirst($activity->skill_level) }}</span>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="progress progress-xs">
                                            <div class="progress-bar 
                                                @if($activity->completion_percentage == 100) progress-bar-success
                                                @elseif($activity->completion_percentage >= 75) progress-bar-info  
                                                @elseif($activity->completion_percentage >= 50) progress-bar-warning
                                                @else progress-bar-danger @endif
                                                " style="width: {{ $activity->completion_percentage }}%"></div>
                                        </div>
                                        <small>{{ $activity->completion_percentage }}%</small>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $activity->getTotalTimeSpent() }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            {{ $activity->last_accessed ? $activity->last_accessed->diffForHumans() : 'Never' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-xs">
                                            <button class="btn btn-primary" onclick="continueTopic('{{ $activity->topic }}')">
                                                <i class="fa fa-play"></i> Continue
                                            </button>
                                            <button class="btn btn-info" onclick="getTutorial('{{ $activity->topic }}')">
                                                <i class="fa fa-book"></i> Tutorial
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted" style="padding: 40px;">
                        <i class="fa fa-graduation-cap fa-3x"></i>
                        <h4>Start Your Learning Journey!</h4>
                        <p>Begin exploring topics to track your progress here.</p>
                        <button class="btn btn-primary" onclick="browseTutorials()">
                            <i class="fa fa-book"></i> Browse Available Tutorials
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Learning Suggestions -->
    <div class="col-xs-12 col-md-4">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Suggested Learning</h3>
            </div>
            <div class="box-body">
                @if(!empty($dashboardData['suggested_topics']))
                    @foreach($dashboardData['suggested_topics'] as $suggestion)
                        <div class="suggestion-item" style="margin-bottom: 15px; padding: 10px; border-left: 3px solid #3c8dbc;">
                            <h5 style="margin-top: 0;">{{ $suggestion['category'] }}</h5>
                            <p class="text-muted" style="font-size: 12px;">{{ $suggestion['reason'] }}</p>
                            @if(!empty($suggestion['suggested_topics']))
                                <div class="suggested-topics">
                                    @foreach($suggestion['suggested_topics'] as $topic)
                                        <button class="btn btn-xs btn-default" onclick="getTutorial('{{ $topic }}')" style="margin: 2px;">
                                            {{ ucfirst(str_replace('_', ' ', $topic)) }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <p class="text-muted">Complete some tutorials to get personalized suggestions!</p>
                @endif
                
                <hr>
                
                <button class="btn btn-sm btn-success btn-block" onclick="getRecommendations()">
                    <i class="fa fa-magic"></i> Get AI Recommendations
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">AI Assistant Help</h4>
            </div>
            <div class="modal-body">
                <div id="helpContent">
                    <!-- Content loaded via AJAX -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Tutorial Modal -->
<div class="modal fade" id="tutorialModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Tutorial</h4>
            </div>
            <div class="modal-body">
                <div id="tutorialContent">
                    <!-- Content loaded via AJAX -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" onclick="markTutorialComplete()" id="completeTutorialBtn" style="display:none;">
                    <i class="fa fa-check"></i> Mark Complete
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('footer-scripts')
    @parent
    <script>
        let currentTopic = null;
        
        function getContextualHelp() {
            const route = window.location.pathname;
            
            $.get('/ai/help/contextual', { route: route })
                .done(function(response) {
                    const helpContent = formatHelpContent(response.help);
                    $('#helpContent').html(helpContent);
                    $('#helpModal').modal('show');
                })
                .fail(function(xhr) {
                    swal({
                        type: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Failed to get contextual help.',
                    });
                });
        }
        
        function browseTutorials() {
            $.get('/ai/help/topics')
                .done(function(response) {
                    const topics = response.topics;
                    let topicsHtml = '<div class="row">';
                    
                    topics.forEach(topic => {
                        const progressBar = topic.progress > 0 ? 
                            `<div class="progress progress-xs">
                                <div class="progress-bar progress-bar-primary" style="width: ${topic.progress}%"></div>
                            </div>` : '';
                            
                        topicsHtml += `
                            <div class="col-md-6">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <h5>${topic.display_name}</h5>
                                        <p class="text-muted">${topic.category} • ${topic.difficulty}</p>
                                        <small class="text-info">Est. ${topic.estimated_duration}</small>
                                        ${progressBar}
                                        <div class="btn-group btn-group-xs" style="margin-top: 10px;">
                                            <button class="btn btn-primary" onclick="getTutorial('${topic.name}')">
                                                <i class="fa fa-play"></i> Start
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    topicsHtml += '</div>';
                    $('#helpContent').html(topicsHtml);
                    $('#helpModal .modal-title').text('Available Tutorials');
                    $('#helpModal').modal('show');
                })
                .fail(function(xhr) {
                    swal({
                        type: 'error',
                        title: 'Error',
                        text: 'Failed to load tutorials.',
                    });
                });
        }
        
        function getRecommendations() {
            $.get('/ai/help/recommendations')
                .done(function(response) {
                    let recommendationsHtml = '<h5>Personalized Recommendations</h5>';
                    
                    if (response.recommendations.length > 0) {
                        response.recommendations.forEach(rec => {
                            recommendationsHtml += `
                                <div class="recommendation-item" style="border-left: 3px solid #5cb85c; padding: 15px; margin: 10px 0;">
                                    <h6>${rec.title}</h6>
                                    <p class="text-muted">${rec.reason}</p>
                                    ${rec.type === 'progression' ? 
                                        `<button class="btn btn-sm btn-success" onclick="getTutorial('${rec.topic}')">
                                            <i class="fa fa-arrow-right"></i> Continue Learning
                                        </button>` :
                                        ''
                                    }
                                </div>
                            `;
                        });
                    } else {
                        recommendationsHtml += '<p class="text-muted">Complete some tutorials to get personalized recommendations!</p>';
                    }
                    
                    $('#helpContent').html(recommendationsHtml);
                    $('#helpModal .modal-title').text('Learning Recommendations');
                    $('#helpModal').modal('show');
                })
                .fail(function(xhr) {
                    swal({
                        type: 'error',
                        title: 'Error',
                        text: 'Failed to load recommendations.',
                    });
                });
        }
        
        function getTutorial(topic) {
            currentTopic = topic;
            
            $.get('/ai/help/tutorial', { topic: topic })
                .done(function(response) {
                    const tutorial = response.tutorial;
                    let tutorialHtml = `
                        <div class="tutorial-header">
                            <h4>${tutorial.title || ucfirst(topic.replace('_', ' '))}</h4>
                            <p class="text-muted">
                                <span class="label label-info">${tutorial.difficulty_level?.overall || 'beginner'}</span>
                                <span class="label label-default">${tutorial.estimated_duration || '15 minutes'}</span>
                            </p>
                        </div>
                        <div class="tutorial-content">
                            <div class="alert alert-info">
                                <strong>AI Assistant:</strong> This tutorial is personalized based on your current skill level and learning progress.
                            </div>
                    `;
                    
                    if (tutorial.ai_explanations && tutorial.ai_explanations.ai_response) {
                        tutorialHtml += `
                            <div class="ai-explanation">
                                <h6><i class="fa fa-robot"></i> AI Explanation</h6>
                                <p>${tutorial.ai_explanations.ai_response}</p>
                            </div>
                        `;
                    }
                    
                    tutorialHtml += '</div>';
                    
                    $('#tutorialContent').html(tutorialHtml);
                    $('#tutorialModal .modal-title').text(`Tutorial: ${ucfirst(topic.replace('_', ' '))}`);
                    $('#completeTutorialBtn').show();
                    $('#tutorialModal').modal('show');
                })
                .fail(function(xhr) {
                    swal({
                        type: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Failed to load tutorial.',
                    });
                });
        }
        
        function continueTopic(topic) {
            getTutorial(topic);
        }
        
        function markTutorialComplete() {
            if (!currentTopic) return;
            
            $.post('/ai/help/progress', {
                topic: currentTopic,
                progress_data: {
                    completed: true,
                    completed_at: new Date().toISOString()
                },
                completed: true,
                _token: '{{ csrf_token() }}'
            })
            .done(function(response) {
                $('#tutorialModal').modal('hide');
                swal({
                    type: 'success',
                    title: 'Great job!',
                    text: 'Tutorial marked as complete. Your progress has been saved.',
                });
                setTimeout(() => location.reload(), 2000);
            })
            .fail(function(xhr) {
                swal({
                    type: 'error',
                    title: 'Error',
                    text: 'Failed to save progress.',
                });
            });
        }
        
        function askQuestion() {
            window.location.href = '/ai/chat';
        }
        
        function refreshSkillAssessment() {
            // This would trigger a skill reassessment
            swal({
                type: 'info',
                title: 'Skill Assessment',
                text: 'Your skills are automatically assessed based on your learning progress.',
            });
        }
        
        function formatHelpContent(help) {
            let content = '<div class="help-response">';
            
            if (help.ai_assistance && help.ai_assistance.ai_response) {
                content += `
                    <div class="ai-response">
                        <h6><i class="fa fa-robot"></i> AI Assistant</h6>
                        <p>${help.ai_assistance.ai_response}</p>
                    </div>
                `;
            }
            
            if (help.contextual_tips && help.contextual_tips.length > 0) {
                content += '<h6><i class="fa fa-lightbulb-o"></i> Tips</h6><ul>';
                help.contextual_tips.forEach(tip => {
                    content += `<li>${tip}</li>`;
                });
                content += '</ul>';
            }
            
            if (help.quick_actions && help.quick_actions.length > 0) {
                content += '<h6><i class="fa fa-flash"></i> Quick Actions</h6><ul>';
                help.quick_actions.forEach(action => {
                    content += `<li>${action}</li>`;
                });
                content += '</ul>';
            }
            
            content += '</div>';
            return content;
        }
        
        function ucfirst(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
    </script>
@endsection