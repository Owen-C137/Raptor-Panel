@extends('layouts.admin')

@section('title')
    AI Settings
@endsection

@section('content-header')
    <h1>AI Settings <small>Configure Ollama Integration</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">AI Settings</li>
    </ol>
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h4><i class="icon fa fa-check"></i> Success!</h4>
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h4><i class="icon fa fa-ban"></i> Error!</h4>
        {{ session('error') }}
    </div>
@endif

<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Ollama Configuration</h3>
            </div>
            <form action="{{ route('admin.ai.settings.update') }}" method="POST" id="ai-settings-form">
                {!! csrf_field() !!}
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ollama_api_url">Ollama API URL</label>
                                <input type="url" id="ollama_api_url" name="ollama_api_url" 
                                       class="form-control" 
                                       value="{{ old('ollama_api_url', env('OLLAMA_BASE_URL', config('ai.ollama.base_url', 'http://localhost:11434'))) }}"
                                       placeholder="http://localhost:11434">
                                <p class="text-muted">The URL where your Ollama server is running</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ollama_timeout">Request Timeout (seconds)</label>
                                <input type="number" id="ollama_timeout" name="ollama_timeout" 
                                       class="form-control" 
                                       value="{{ old('ollama_timeout', env('OLLAMA_TIMEOUT', config('ai.ollama.timeout', 30))) }}"
                                       min="5" max="300">
                                <p class="text-muted">Maximum time to wait for Ollama responses</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="default_model">Default Model</label>
                                <select id="default_model" name="default_model" class="form-control">
                                    <option value="">Select a model...</option>
                                    @if(count($availableModels) > 0)
                                        @foreach($availableModels as $model)
                                        <option value="{{ $model['name'] }}" 
                                                {{ old('default_model', env('AI_DEFAULT_MODEL', config('ai.models.default'))) === $model['name'] ? 'selected' : '' }}>
                                            {{ $model['name'] }} ({{ $model['size'] ?? 'Unknown size' }})
                                        </option>
                                        @endforeach
                                    @else
                                        <!-- Fallback models when Ollama is not available -->
                                        @php
                                            $fallbackModels = ['llama3.1:8b', 'mistral:7b', 'codellama:7b', 'gemma:7b'];
                                            $currentDefault = old('default_model', env('AI_DEFAULT_MODEL', config('ai.models.default')));
                                        @endphp
                                        @foreach($fallbackModels as $model)
                                        <option value="{{ $model }}" {{ $currentDefault === $model ? 'selected' : '' }}>
                                            {{ $model }} (Not downloaded)
                                        </option>
                                        @endforeach
                                    @endif
                                </select>
                                <p class="text-muted">
                                    Primary model for AI interactions
                                    @if(count($availableModels) == 0)
                                        <br><small class="text-warning">⚠️ Unable to connect to Ollama. Showing common models.</small>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="enable_ai">Enable AI Features</label>
                                <div class="form-control-static">
                                    <label class="switch">
                                        <input type="hidden" name="enable_ai" value="0">
                                        <input type="checkbox" name="enable_ai" value="1" 
                                               {{ old('enable_ai', env('AI_ENABLED', config('ai.enabled', false))) ? 'checked' : '' }}>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                                <p class="text-muted">Enable or disable all AI functionality</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Advanced Settings</label>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="max_tokens">Max Tokens</label>
                                <input type="number" id="max_tokens" name="max_tokens" 
                                       class="form-control" 
                                       value="{{ old('max_tokens', env('AI_MAX_TOKENS', config('ai.ollama.max_tokens', 2048))) }}"
                                       min="128" max="8192">
                                <p class="text-muted">Maximum response length</p>
                            </div>
                            <div class="col-md-4">
                                <label for="temperature">Temperature</label>
                                <input type="number" id="temperature" name="temperature" 
                                       class="form-control" step="0.1"
                                       value="{{ old('temperature', env('AI_TEMPERATURE', config('ai.ollama.temperature', 0.8))) }}"
                                       min="0" max="2">
                                <p class="text-muted">Response creativity (0-2)</p>
                            </div>
                            <div class="col-md-4">
                                <label for="top_p">Top P</label>
                                <input type="number" id="top_p" name="top_p" 
                                       class="form-control" step="0.1"
                                       value="{{ old('top_p', env('AI_TOP_P', config('ai.ollama.top_p', 0.9))) }}"
                                       min="0" max="1">
                                <p class="text-muted">Response diversity (0-1)</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Feature Toggles</label>
                        <div class="row">
                            <div class="col-md-4">
                                
                                    <label>
                                        <input type="hidden" name="enable_chat" value="0">
                                        <input type="checkbox" name="enable_chat" value="1" 
                                               {{ old('enable_chat', env('AI_CHAT_ENABLED', config('ai.features.chat_support', true))) ? 'checked' : '' }}>
                                        Enable Chat Assistant
                                    </label>
                                
                            </div>
                            <div class="col-md-4">
                                
                                    <label>
                                        <input type="hidden" name="enable_analysis" value="0">
                                        <input type="checkbox" name="enable_analysis" value="1" 
                                               {{ old('enable_analysis', env('AI_ANALYSIS_ENABLED', config('ai.features.server_analysis', true))) ? 'checked' : '' }}>
                                        Enable Server Analysis
                                    </label>
                                
                            </div>
                            <div class="col-md-4">
                               
                                    <label>
                                        <input type="hidden" name="enable_insights" value="0">
                                        <input type="checkbox" name="enable_insights" value="1" 
                                               {{ old('enable_insights', env('AI_ADMIN_INSIGHTS_ENABLED', config('ai.features.admin_insights', true))) ? 'checked' : '' }}>
                                        Enable AI Insights
                                    </label>
                              
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <button type="button" class="btn btn-info" id="test-connection">
                        <i class="fa fa-plug"></i> Test Connection
                    </button>
                    <button type="submit" class="btn btn-success pull-right">
                        <i class="fa fa-save"></i> Save Configuration
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Model Management</h3>
            </div>
            <div class="box-body">
                <div class="form-group">
                    <label for="model-to-pull">Pull New Model</label>
                    <div class="input-group">
                        <input type="text" id="model-to-pull" class="form-control" 
                               placeholder="e.g., llama3.1:8b, codellama:7b">
                        <span class="input-group-btn">
                            <button type="button" class="btn btn-primary" id="pull-model">
                                <i class="fa fa-download"></i> Pull
                            </button>
                        </span>
                    </div>
                    <p class="text-muted">Download a new model from Ollama registry</p>
                </div>

                <div class="available-models">
                    <h4>Available Models</h4>
                    @if($availableModels && count($availableModels) > 0)
                        <div class="table-responsive">
                            <table class="table table-condensed">
                                <thead>
                                    <tr>
                                        <th>Model</th>
                                        <th>Size</th>
                                        <th>Modified</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="models-list">
                                    @foreach($availableModels as $model)
                                    <tr>
                                        <td>
                                            <strong>{{ $model['name'] }}</strong>
                                            @if($model['name'] === config('ai.ollama.default_model'))
                                                <span class="label label-success">Default</span>
                                            @endif
                                        </td>
                                        <td>{{ $model['size'] ?? 'Unknown' }}</td>
                                        <td>{{ isset($model['modified_at']) ? \Carbon\Carbon::parse($model['modified_at'])->format('M j, Y') : 'Unknown' }}</td>
                                        <td>
                                            <button type="button" class="btn btn-xs btn-danger" 
                                                    onclick="removeModel('{{ $model['name'] }}')">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No models available. Pull a model to get started.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">System Status</h3>
            </div>
            <div class="box-body" id="system-status">
                <div class="info-box">
                    <span class="info-box-icon bg-{{ ($systemStatus['connected'] ?? false) ? 'green' : 'red' }}">
                        <i class="fa fa-{{ ($systemStatus['connected'] ?? false) ? 'check' : 'times' }}"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Connection Status</span>
                        <span class="info-box-number">{{ ($systemStatus['connected'] ?? false) ? 'Connected' : 'Disconnected' }}</span>
                    </div>
                </div>

                @if($systemStatus['connected'] ?? false)
                <div class="system-info">
                    <h5>Ollama Server Info</h5>
                    <ul class="list-unstyled">
                        <li><strong>Version:</strong> {{ $systemStatus['version'] ?? 'Unknown' }}</li>
                        <li><strong>Models Available:</strong> {{ $availableModels ? count($availableModels) : 0 }}</li>
                        <li><strong>Last Check:</strong> {{ now()->format('M j, Y H:i:s') }}</li>
                    </ul>
                </div>
                @endif

                <div class="cleanup-actions mt-3">
                    <h5>Maintenance</h5>
                    <button type="button" class="btn btn-warning btn-sm" onclick="cleanupConversations()">
                        <i class="fa fa-broom"></i> Cleanup Old Conversations
                    </button>
                    <button type="button" class="btn btn-info btn-sm" onclick="refreshModels()">
                        <i class="fa fa-refresh"></i> Refresh Model List
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <style>
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            -webkit-transition: .4s;
            transition: .4s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            -webkit-transition: .4s;
            transition: .4s;
        }

        input:checked + .slider {
            background-color: #2196F3;
        }

        input:focus + .slider {
            box-shadow: 0 0 1px #2196F3;
        }

        input:checked + .slider:before {
            -webkit-transform: translateX(26px);
            -ms-transform: translateX(26px);
            transform: translateX(26px);
        }

        .slider.round {
            border-radius: 34px;
        }

        .slider.round:before {
            border-radius: 50%;
        }

        .system-info {
            padding: 10px 0;
        }
        
        .cleanup-actions {
            border-top: 1px solid #f0f0f0;
            padding-top: 10px;
        }
    </style>

    <script>
        $(document).ready(function() {
            // Test connection
            $('#test-connection').click(function() {
                const btn = $(this);
                const icon = btn.find('i');
                
                btn.prop('disabled', true);
                icon.removeClass('fa-plug').addClass('fa-spinner fa-spin');
                
                $.ajax({
                    url: '{{ route("admin.ai.test-connection") }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.connected) {
                            $.notify({
                                message: 'Connection successful!'
                            }, {
                                type: 'success'
                            });
                            updateSystemStatus(true, response.status);
                        } else {
                            $.notify({
                                message: 'Connection failed'
                            }, {
                                type: 'danger'
                            });
                            updateSystemStatus(false);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error:', xhr.responseText);
                        $.notify({
                            message: 'Failed to test connection: ' + error
                        }, {
                            type: 'danger'
                        });
                        updateSystemStatus(false);
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                        icon.removeClass('fa-spinner fa-spin').addClass('fa-plug');
                    }
                });
            });

            // Pull model
            $('#pull-model').click(function() {
                const modelName = $('#model-to-pull').val().trim();
                if (!modelName) {
                    $.notify({
                        message: 'Please enter a model name'
                    }, {
                        type: 'danger'
                    });
                    return;
                }

                const btn = $(this);
                const icon = btn.find('i');
                
                btn.prop('disabled', true);
                icon.removeClass('fa-download').addClass('fa-spinner fa-spin');
                
                $.ajax({
                    url: '{{ route("admin.ai.pull-model") }}',
                    method: 'POST',
                    data: { model: modelName },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            $.notify({
                                message: response.message || 'Model pull started'
                            }, {
                                type: 'success'
                            });
                            $('#model-to-pull').val('');
                            // Refresh models list after a delay
                            setTimeout(refreshModels, 2000);
                        } else {
                            $.notify({
                                message: response.message || 'Failed to pull model'
                            }, {
                                type: 'danger'
                            });
                        }
                    },
                    error: function() {
                        $.notify({
                            message: 'Failed to pull model'
                        }, {
                            type: 'danger'
                        });
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                        icon.removeClass('fa-spinner fa-spin').addClass('fa-download');
                    }
                });
            });
        });

        function removeModel(modelName) {
            if (!confirm(`Are you sure you want to remove the model "${modelName}"?`)) {
                return;
            }

            $.ajax({
                url: '{{ route("admin.ai.remove-model", ":model") }}'.replace(':model', encodeURIComponent(modelName)),
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        $.notify({
                            message: 'Model removed successfully'
                        }, {
                            type: 'success'
                        });
                        refreshModels();
                    } else {
                        $.notify({
                            message: response.message || 'Failed to remove model'
                        }, {
                            type: 'danger'
                        });
                    }
                },
                error: function() {
                    $.notify({
                        message: 'Failed to remove model'
                    }, {
                        type: 'danger'
                    });
                }
            });
        }

        function refreshModels() {
            location.reload(); // Simple refresh for now
        }

        function cleanupConversations() {
            if (!confirm('This will remove old conversations and free up space. Continue?')) {
                return;
            }

            $.ajax({
                url: '{{ route("admin.ai.cleanup") }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        $.notify({
                            message: response.message || 'Cleanup completed'
                        }, {
                            type: 'success'
                        });
                    } else {
                        $.notify({
                            message: response.message || 'Cleanup failed'
                        }, {
                            type: 'danger'
                        });
                    }
                },
                error: function() {
                    $.notify({
                        message: 'Cleanup failed'
                    }, {
                        type: 'danger'
                    });
                }
            });
        }

        function updateSystemStatus(connected, data = {}) {
            const statusBox = $('#system-status .info-box');
            const icon = statusBox.find('.info-box-icon i');
            const text = statusBox.find('.info-box-number');
            
            if (connected) {
                statusBox.find('.info-box-icon').removeClass('bg-red').addClass('bg-green');
                icon.removeClass('fa-times').addClass('fa-check');
                text.text('Connected');
            } else {
                statusBox.find('.info-box-icon').removeClass('bg-green').addClass('bg-red');
                icon.removeClass('fa-check').addClass('fa-times');
                text.text('Disconnected');
            }
        }
    </script>
@endsection