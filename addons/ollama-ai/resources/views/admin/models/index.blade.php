@extends('layouts.admin')

@section('title')
    AI Models Management
@endsection

@section('content-header')
    <h1>AI Models Management</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.ai.index') }}">AI Management</a></li>
        <li class="active">Models</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Available AI Models</h3>
                <div class="box-tools">
                    <a href="{{ route('admin.ai.models.library') }}" class="btn btn-success btn-sm">
                        <i class="fa fa-book"></i> Browse Model Library
                    </a>
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#pullModelModal">
                        <i class="fa fa-download"></i> Pull New Model
                    </button>
                </div>
            </div>
            <div class="box-body">
                @if($models && count($models) > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Model Name</th>
                                <th>Family</th>
                                <th>Size</th>
                                <th>Parameter Size</th>
                                <th>Format</th>
                                <th>Modified</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($models as $model)
                            <tr data-model-name="{{ $model['name'] }}">
                                <td>
                                    <strong>{{ $model['name'] }}</strong>
                                    @if(in_array($model['name'], array_values($configuredModels)))
                                        <span class="label label-success">Configured</span>
                                    @endif
                                </td>
                                <td>
                                    @if(isset($model['details']['family']))
                                        <span class="label label-info">{{ $model['details']['family'] }}</span>
                                    @else
                                        <span class="text-muted">Unknown</span>
                                    @endif
                                </td>
                                <td>
                                    @if(isset($model['size']))
                                        {{ number_format($model['size'] / (1024*1024*1024), 2) }} GB
                                    @else
                                        <span class="text-muted">Unknown</span>
                                    @endif
                                </td>
                                <td>
                                    @if(isset($model['details']['parameter_size']))
                                        {{ $model['details']['parameter_size'] }}
                                    @else
                                        <span class="text-muted">Unknown</span>
                                    @endif
                                </td>
                                <td>
                                    @if(isset($model['details']['format']))
                                        {{ strtoupper($model['details']['format']) }}
                                    @else
                                        <span class="text-muted">Unknown</span>
                                    @endif
                                </td>
                                <td>
                                    @if(isset($model['modified_at']))
                                        {{ \Carbon\Carbon::parse($model['modified_at'])->format('M j, Y H:i') }}
                                    @else
                                        <span class="text-muted">Unknown</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="label label-success">Ready</span>
                                </td>
                                <td>
                                    <button class="btn btn-xs btn-info" onclick="showModelInfo('{{ $model['name'] }}')">
                                        <i class="fa fa-info-circle"></i> Info
                                    </button>
                                    <button class="btn btn-xs btn-danger" onclick="deleteModel('{{ $model['name'] }}')">
                                        <i class="fa fa-trash"></i> Remove
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="alert alert-info">
                    <h4><i class="icon fa fa-info"></i> No Models Available</h4>
                    No AI models are currently installed. Click "Pull New Model" to download models from the Ollama library.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Model Configuration -->
<div class="row">
    <div class="col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Model Configuration</h3>
            </div>
            <div class="box-body">
                <table class="table table-condensed">
                    <tr>
                        <td><strong>Chat Model:</strong></td>
                        <td>{{ $configuredModels['chat'] ?? 'Not configured' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Code Model:</strong></td>
                        <td>{{ $configuredModels['code'] ?? 'Not configured' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Analysis Model:</strong></td>
                        <td>{{ $configuredModels['analysis'] ?? 'Not configured' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Security Model:</strong></td>
                        <td>{{ $configuredModels['security'] ?? 'Not configured' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Documentation Model:</strong></td>
                        <td>{{ $configuredModels['documentation'] ?? 'Not configured' }}</td>
                    </tr>
                </table>
                <a href="{{ route('admin.ai.settings') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-cog"></i> Configure Models
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">System Information</h3>
            </div>
            <div class="box-body">
                <table class="table table-condensed">
                    <tr>
                        <td><strong>Ollama Status:</strong></td>
                        <td>
                            @if($systemStatus['connected'] ?? false)
                                <span class="label label-success">Connected</span>
                            @else
                                <span class="label label-danger">Disconnected</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Server URL:</strong></td>
                        <td>{{ $ollamaConfig['url'] ?? 'Not configured' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Models Available:</strong></td>
                        <td>{{ $models ? count($models) : 0 }}</td>
                    </tr>
                    <tr>
                        <td><strong>Total Size:</strong></td>
                        <td>
                            @if($models)
                                {{ number_format(collect($models)->sum('size') / (1024*1024*1024), 2) }} GB
                            @else
                                0 GB
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Pull Model Modal -->
<div class="modal fade" id="pullModelModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Pull New Model</h4>
            </div>
            <form id="pullModelForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="modelName">Model Name</label>
                        <input type="text" class="form-control" id="modelName" name="model" 
                               placeholder="e.g., llama3.1:8b, codellama:7b, mistral:7b">
                        <small class="form-text text-muted">
                            Enter the model name from the <a href="https://ollama.com/library" target="_blank">Ollama Library</a>
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Pull Model</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Model Confirmation Modal -->
<div class="modal fade" id="deleteModelModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-exclamation-triangle text-danger"></i> Confirm Deletion
                </h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to remove the model <strong id="modelToDelete"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fa fa-warning"></i> This action cannot be undone and will permanently delete the model files.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fa fa-trash"></i> Delete Model
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
<style>
/* Smooth transitions for model deletion */
.table tbody tr {
    transition: opacity 0.3s ease;
}

.table tbody tr.deleting {
    opacity: 0.6;
}

/* Loading button styles */
.btn-loading {
    pointer-events: none;
    position: relative;
}

.btn-loading:after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    margin: -8px 0 0 -8px;
    width: 16px;
    height: 16px;
    border: 2px solid transparent;
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Fade in animation for notifications */
.fade-in-up {
    animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

@section('footer-scripts')
    @parent
    <script>
        function showModelInfo(modelName) {
            window.open('{{ route("admin.ai.models.info", ["model" => "__MODEL__"]) }}'.replace('__MODEL__', modelName), '_blank');
        }

        function addModelToTable(modelName) {
            // Check if we have an empty state and need to create the table
            if ($('.table-responsive').length === 0) {
                // Replace the "no models" alert with a table
                $('.alert.alert-info').replaceWith(`
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Model Name</th>
                                    <th>Family</th>
                                    <th>Size</th>
                                    <th>Parameter Size</th>
                                    <th>Format</th>
                                    <th>Modified</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                `);
            }
            
            // Create a new table row for the model (with basic info since we don't have details)
            const newRow = `
                <tr data-model-name="${modelName}" class="new-model" style="display: none;">
                    <td>
                        <strong>${modelName}</strong>
                    </td>
                    <td><span class="text-muted">Loading...</span></td>
                    <td><span class="text-muted">Loading...</span></td>
                    <td><span class="text-muted">Loading...</span></td>
                    <td><span class="text-muted">Loading...</span></td>
                    <td><span class="text-muted">Just Downloaded</span></td>
                    <td><span class="label label-success">Ready</span></td>
                    <td>
                        <button class="btn btn-xs btn-info" onclick="showModelInfo('${modelName}')">
                            <i class="fa fa-info-circle"></i> Info
                        </button>
                        <button class="btn btn-xs btn-danger" onclick="deleteModel('${modelName}')">
                            <i class="fa fa-trash"></i> Remove
                        </button>
                    </td>
                </tr>
            `;
            
            // Add the row with animation
            $('tbody').append(newRow);
            $('tr.new-model').slideDown(500, function() {
                $(this).removeClass('new-model');
                // Optionally refresh the row data from server to get accurate info
                setTimeout(() => refreshModelRow(modelName), 1000);
            });
        }

        function refreshModelRow(modelName) {
            // Make an AJAX call to get updated model information
            $.get('{{ route("admin.ai.models") }}')
                .done(function(data) {
                    // This would require modifying the controller to return JSON
                    // For now, we'll just remove the "Loading..." text
                    const $row = $('tr[data-model-name="' + modelName + '"]');
                    $row.find('.text-muted:contains("Loading...")').text('Unknown');
                })
                .fail(function() {
                    // If refresh fails, just update loading text
                    const $row = $('tr[data-model-name="' + modelName + '"]');
                    $row.find('.text-muted:contains("Loading...")').text('Unknown');
                });
        }

        function deleteModel(modelName) {
            // Show the modal confirmation dialog
            $('#modelToDelete').text(modelName);
            $('#deleteModelModal').modal('show');
            
            // Handle the confirm button click
            $('#confirmDeleteBtn').off('click').on('click', function() {
                $('#deleteModelModal').modal('hide');
                performModelDeletion(modelName);
            });
        }
        
        function performModelDeletion(modelName) {
            // Find the table row for this model using data attribute
            const $row = $('tr[data-model-name="' + modelName + '"]');
            
            if ($row.length === 0) {
                $.notify({
                    message: 'Model row not found in table'
                }, {
                    type: 'danger'
                });
                return;
            }
            
            // Get the delete button and disable it
            const $deleteBtn = $row.find('button.btn-danger');
            const originalBtnContent = $deleteBtn.html();
            
            // Show loading state with better animation
            $deleteBtn.prop('disabled', true)
                     .html('<i class="fa fa-spinner fa-spin"></i> Removing...')
                     .removeClass('btn-danger')
                     .addClass('btn-warning');
            
            // Add loading class to the entire row
            $row.addClass('deleting');
            
            $.ajax({
                url: '{{ route("admin.ai.remove-model", ["model" => "__MODEL__"]) }}'.replace('__MODEL__', encodeURIComponent(modelName)),
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    // Animate row removal with slide up effect
                    $row.fadeOut(500, function() {
                        $(this).slideUp(300, function() {
                            $(this).remove();
                            
                            // Check if table is now empty
                            const remainingRows = $('tbody tr').length;
                            if (remainingRows === 0) {
                                // Replace table with "no models" message with fade effect
                                const noModelsHtml = `
                                    <div class="alert alert-info fade-in-up">
                                        <h4><i class="icon fa fa-info"></i> No Models Available</h4>
                                        No AI models are currently installed. Click "Pull New Model" to download models from the Ollama library.
                                    </div>
                                `;
                                $('.table-responsive').fadeOut(300, function() {
                                    $(this).replaceWith(noModelsHtml);
                                });
                            }
                        });
                    });
                    
                    $.notify({
                        message: 'Model "' + modelName + '" removed successfully'
                    }, {
                        type: 'success'
                    });
                },
                error: function(xhr) {
                    // Restore original button state
                    $deleteBtn.prop('disabled', false)
                             .html(originalBtnContent)
                             .removeClass('btn-warning')
                             .addClass('btn-danger');
                    
                    // Remove loading class
                    $row.removeClass('deleting');
                    
                    let errorMessage = 'Failed to remove model';
                    try {
                        const errorData = JSON.parse(xhr.responseText);
                        if (errorData.message) {
                            errorMessage = errorData.message;
                        } else if (errorData.errors && errorData.errors.length > 0) {
                            errorMessage = errorData.errors[0].detail || errorMessage;
                        }
                    } catch (e) {
                        // Use default error message if JSON parsing fails
                        errorMessage = 'Failed to remove model (Status: ' + xhr.status + ')';
                    }
                    
                    $.notify({
                        message: errorMessage
                    }, {
                        type: 'danger'
                    });
                }
            });
        }

        $('#pullModelForm').on('submit', function(e) {
            e.preventDefault();
            var modelName = $('#modelName').val();
            
            if (!modelName) {
                $.notify({
                    message: 'Please enter a model name'
                }, {
                    type: 'danger'
                });
                return;
            }

            var $btn = $(this).find('button[type="submit"]');
            var $modal = $('#pullModelModal');
            
            // Initialize progress display
            $btn.prop('disabled', true);
            
            // Add progress section to modal if not exists
            if ($modal.find('.progress-section').length === 0) {
                $modal.find('.modal-body').append(`
                    <div class="progress-section" style="display: none; margin-top: 15px;">
                        <div class="progress">
                            <div class="progress-bar progress-bar-primary progress-bar-striped active" 
                                 role="progressbar" style="width: 0%">
                                <span class="sr-only">0% Complete</span>
                            </div>
                        </div>
                        <div class="progress-text text-center" style="margin-top: 5px;">
                            <small>Initializing...</small>
                        </div>
                    </div>
                `);
            }
            
            var $progressSection = $modal.find('.progress-section');
            var $progressBar = $modal.find('.progress-bar');
            var $progressText = $modal.find('.progress-text');
            
            $progressSection.show();
            $progressBar.css('width', '0%').text('0%');
            $progressText.html('<small>Starting download...</small>');
            $btn.html('<i class="fa fa-download"></i> Downloading...');

            // Start server-sent events for real-time progress
            $.ajax({
                url: '{{ route("admin.ai.pull-model-progress") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    model: modelName
                },
                xhrFields: {
                    onprogress: function(e) {
                        // Handle progress
                    }
                },
                success: function(data, status, xhr) {
                    // This won't be called for streaming responses
                },
                error: function(xhr, status, error) {
                    $.notify({
                        message: 'Download failed: ' + error
                    }, {
                        type: 'danger'
                    });
                    resetModal();
                }
            });

            // Use XMLHttpRequest for streaming
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '{{ route("admin.ai.pull-model-progress") }}', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            var buffer = '';
            var lastProcessedIndex = 0;
            
            xhr.onprogress = function() {
                var response = xhr.responseText;
                var newData = response.slice(lastProcessedIndex);
                buffer += newData;
                lastProcessedIndex = response.length;
                
                var lines = buffer.split('\n\n');
                buffer = lines.pop(); // Keep incomplete line in buffer
                
                lines.forEach(function(line) {
                    if (line.startsWith('data: ')) {
                        try {
                            var data = JSON.parse(line.slice(6));
                            
                            if (data.status === 'error') {
                                $.notify({
                                    message: 'Download failed: ' + data.message
                                }, {
                                    type: 'danger'
                                });
                                xhr.abort();
                                resetModal();
                                return;
                            }
                            
                            // Update progress bar
                            var percent = data.percent || 0;
                            $progressBar.css('width', percent + '%').text(Math.round(percent) + '%');
                            $progressText.html('<small>' + data.message + '</small>');
                            
                            if (data.status === 'complete') {
                                $progressBar.removeClass('progress-bar-primary').addClass('progress-bar-success');
                                $btn.html('<i class="fa fa-check"></i> Complete');
                                
                                $.notify({
                                    message: 'Model "' + modelName + '" downloaded successfully!'
                                }, {
                                    type: 'success'
                                });
                                
                                setTimeout(function() {
                                    $modal.modal('hide');
                                    
                                    // Instead of page reload, dynamically add the new model
                                    addModelToTable(modelName);
                                }, 2000);
                                
                                xhr.abort();
                            }
                        } catch (e) {
                            console.error('Progress parsing error:', e);
                        }
                    }
                });
            };
            
            xhr.onerror = function() {
                $.notify({
                    message: 'Download request failed'
                }, {
                    type: 'danger'
                });
                resetModal();
            };
            
            xhr.send('_token={{ csrf_token() }}&model=' + encodeURIComponent(modelName));
            
            function resetModal() {
                $progressSection.hide();
                $btn.prop('disabled', false).html('Pull Model');
                $('#modelName').val('');
            }
            
            // Handle modal close
            $modal.on('hidden.bs.modal', function() {
                if (xhr && xhr.readyState !== XMLHttpRequest.DONE) {
                    xhr.abort();
                }
                resetModal();
            });
        });
    </script>
@endsection