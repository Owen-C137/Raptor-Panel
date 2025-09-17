@extends('layouts.admin')

@section('title')
    AI Model Information - {{ $modelName }}
@endsection

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Model Information</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.ai.settings') }}">AI Settings</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.ai.models') }}">Models</a></li>
                        <li class="breadcrumb-item active">{{ $modelName }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-robot mr-1"></i>
                        {{ $modelName }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.ai.models') }}" class="btn btn-tool">
                            <i class="fas fa-arrow-left"></i> Back to Models
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($modelInfo)
                        <div class="row">
                            <div class="col-md-6">
                                <h4>Basic Information</h4>
                                <table class="table table-striped">
                                    <tr>
                                        <td><strong>Name:</strong></td>
                                        <td>{{ $modelInfo['name'] ?? $modelName }}</td>
                                    </tr>
                                    @if(isset($modelInfo['size']))
                                    <tr>
                                        <td><strong>Size:</strong></td>
                                        <td>{{ number_format($modelInfo['size'] / (1024*1024*1024), 2) }} GB</td>
                                    </tr>
                                    @endif
                                    @if(isset($modelInfo['modified_at']))
                                    <tr>
                                        <td><strong>Modified:</strong></td>
                                        <td>{{ \Carbon\Carbon::parse($modelInfo['modified_at'])->format('M j, Y g:i A') }}</td>
                                    </tr>
                                    @endif
                                    @if(isset($modelInfo['digest']))
                                    <tr>
                                        <td><strong>Digest:</strong></td>
                                        <td><code>{{ substr($modelInfo['digest'], 0, 16) }}...</code></td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h4>Model Details</h4>
                                @if(isset($modelInfo['details']))
                                <table class="table table-striped">
                                    @if(isset($modelInfo['details']['parent_model']))
                                    <tr>
                                        <td><strong>Parent Model:</strong></td>
                                        <td>{{ $modelInfo['details']['parent_model'] }}</td>
                                    </tr>
                                    @endif
                                    @if(isset($modelInfo['details']['format']))
                                    <tr>
                                        <td><strong>Format:</strong></td>
                                        <td>{{ $modelInfo['details']['format'] }}</td>
                                    </tr>
                                    @endif
                                    @if(isset($modelInfo['details']['family']))
                                    <tr>
                                        <td><strong>Family:</strong></td>
                                        <td>{{ $modelInfo['details']['family'] }}</td>
                                    </tr>
                                    @endif
                                    @if(isset($modelInfo['details']['parameter_size']))
                                    <tr>
                                        <td><strong>Parameters:</strong></td>
                                        <td>{{ $modelInfo['details']['parameter_size'] }}</td>
                                    </tr>
                                    @endif
                                    @if(isset($modelInfo['details']['quantization_level']))
                                    <tr>
                                        <td><strong>Quantization:</strong></td>
                                        <td>{{ $modelInfo['details']['quantization_level'] }}</td>
                                    </tr>
                                    @endif
                                </table>
                                @else
                                <p class="text-muted">No detailed information available.</p>
                                @endif
                            </div>
                        </div>

                        @if(isset($modelInfo['modelfile']))
                        <div class="mt-4">
                            <h4>Modelfile</h4>
                            <pre class="bg-light p-3" style="max-height: 300px; overflow-y: auto;"><code>{{ $modelInfo['modelfile'] }}</code></pre>
                        </div>
                        @endif

                        @if(isset($modelInfo['template']))
                        <div class="mt-4">
                            <h4>Template</h4>
                            <pre class="bg-light p-3"><code>{{ $modelInfo['template'] }}</code></pre>
                        </div>
                        @endif

                        @if(isset($modelInfo['parameters']) && is_array($modelInfo['parameters']))
                        <div class="mt-4">
                            <h4>Parameters</h4>
                            <table class="table table-striped">
                                @foreach($modelInfo['parameters'] as $key => $value)
                                <tr>
                                    <td><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong></td>
                                    <td>{{ is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value }}</td>
                                </tr>
                                @endforeach
                            </table>
                        </div>
                        @elseif(isset($modelInfo['parameters']))
                        <div class="mt-4">
                            <h4>Parameters</h4>
                            <pre class="bg-light p-3"><code>{{ $modelInfo['parameters'] }}</code></pre>
                        </div>
                        @endif
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Model information is not available. The model may not be installed or accessible.
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-sm-6">
                            <a href="{{ route('admin.ai.models') }}" class="btn btn-default">
                                <i class="fas fa-arrow-left mr-1"></i> Back to Models
                            </a>
                        </div>
                        <div class="col-sm-6 text-right">
                            @if($modelInfo)
                            <button type="button" class="btn btn-info" onclick="refreshModelInfo()">
                                <i class="fas fa-sync mr-1"></i> Refresh Info
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($modelInfo)
                        <button type="button" class="btn btn-primary btn-block" onclick="testModel()">
                            <i class="fas fa-play mr-1"></i> Test Model
                        </button>
                        @endif
                        
                        <a href="{{ route('admin.ai.models') }}" class="btn btn-outline-secondary btn-block">
                            <i class="fas fa-list mr-1"></i> All Models
                        </a>
                        
                        @if($modelInfo)
                        <button type="button" class="btn btn-outline-danger btn-block" onclick="confirmDeleteModel()">
                            <i class="fas fa-trash mr-1"></i> Delete Model
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            
            @if($modelInfo && isset($modelInfo['size']))
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Storage Information</h3>
                </div>
                <div class="card-body">
                    <div class="progress mb-3">
                        <div class="progress-bar" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                            {{ number_format($modelInfo['size'] / (1024*1024*1024), 2) }} GB
                        </div>
                    </div>
                    <small class="text-muted">
                        This model uses {{ number_format($modelInfo['size'] / (1024*1024*1024), 2) }} GB of disk space.
                    </small>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        function refreshModelInfo() {
            window.location.reload();
        }

        function testModel() {
            // Redirect to chat interface with this model pre-selected
            window.open('{{ route("admin.ai.conversations.index") }}?model={{ urlencode($modelName) }}', '_blank');
        }

        function confirmDeleteModel() {
            if (confirm('Are you sure you want to delete the model "{{ $modelName }}"? This action cannot be undone.')) {
                // Make delete request
                fetch('{{ route("admin.ai.remove-model", $modelName) }}', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Model deleted successfully.');
                        window.location.href = '{{ route("admin.ai.models") }}';
                    } else {
                        alert('Error deleting model: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting model. Please try again.');
                });
            }
        }
    </script>
@endsection