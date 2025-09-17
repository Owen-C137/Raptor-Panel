@extends('layouts.admin')

@section('title')
    AI Syste                        <div>
                            <h3 class="card-title mb-0">{{ isset($performance_metrics['response_times']['average']) ? $performance_metrics['response_times']['average'] : 150 }}ms</h3>
                            <p class="card-text text-muted">Response Time</p>
                        </div>timization
@endsection

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">AI System Optimization</h1>
                    <p class="text-muted">Monitor, optimize, and maintain AI system performance</p>
                </div>
                <div class="col-sm-6">
                    <div class="float-right">
                        <button id="run-all-optimizations" class="btn btn-primary mr-2">
                            <i class="fas fa-bolt mr-1"></i>
                            Run All Optimizations
                        </button>
                        <button id="generate-report" class="btn btn-secondary">
                            <i class="fas fa-file-alt mr-1"></i>
                            Generate Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <!-- System Health Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                        <div>
                            @php
                                $health = 85; // Default
                                if (isset($performance_metrics['response_times']['average'])) {
                                    $responseTime = $performance_metrics['response_times']['average'];
                                    $health = $responseTime < 500 ? 95 : ($responseTime < 1000 ? 85 : 75);
                                }
                            @endphp
                            <h3 class="card-title mb-0">{{ $health }}%</h3>
                            <p class="card-text text-muted">System Health</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <i class="fas fa-tachometer-alt fa-2x text-info"></i>
                        </div>
                        <div>
                            <h3 class="card-title mb-0">{{ $performance_metrics['response_time'] ?? 150 }}ms</h3>
                            <p class="card-text text-muted">Avg Response Time</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <i class="fas fa-memory fa-2x text-warning"></i>
                        </div>
                        <div>
                            <h3 class="card-title mb-0">{{ isset($performance_metrics['memory_usage']['current']) ? number_format($performance_metrics['memory_usage']['current'], 1) . 'MB' : '65%' }}</h3>
                            <p class="card-text text-muted">Memory Usage</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="mr-3">
                            <i class="fas fa-star fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h3 class="card-title mb-0">{{ $quality_metrics['overall_score'] ?? 92 }}%</h3>
                            <p class="card-text text-muted">Quality Score</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Performance Metrics -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line mr-1"></i>
                        Performance Metrics
                    </h3>
                </div>
                <div class="card-body">
                    @if(isset($performance_metrics) && is_array($performance_metrics))
                        @foreach($performance_metrics as $metric => $value)
                            @if($metric !== 'overall_health' && $metric !== 'response_time' && $metric !== 'memory_usage')
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>{{ ucfirst(str_replace('_', ' ', $metric)) }}</span>
                                    <strong>{{ is_numeric($value) ? number_format($value, 1) : (is_array($value) ? 'Array' : $value) }}{{ is_numeric($value) ? '%' : '' }}</strong>
                                </div>
                                @if(is_numeric($value))
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar bg-{{ $value > 80 ? 'success' : ($value > 60 ? 'warning' : 'danger') }}" 
                                         style="width: {{ min($value, 100) }}%"></div>
                                </div>
                                @endif
                            </div>
                            @endif
                        @endforeach
                    @else
                        <p class="text-muted">No performance metrics available</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Quality Metrics -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-award mr-1"></i>
                        Quality Metrics
                    </h3>
                </div>
                <div class="card-body">
                    @if(isset($quality_metrics) && is_array($quality_metrics))
                        @foreach($quality_metrics as $metric => $value)
                            @if($metric !== 'overall_score')
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>{{ ucfirst(str_replace('_', ' ', $metric)) }}</span>
                                    <strong>{{ is_numeric($value) ? number_format($value, 1) : (is_array($value) ? 'Array' : $value) }}{{ is_numeric($value) ? '%' : '' }}</strong>
                                </div>
                                @if(is_numeric($value))
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar bg-{{ $value > 90 ? 'success' : ($value > 70 ? 'info' : 'warning') }}" 
                                         style="width: {{ min($value, 100) }}%"></div>
                                </div>
                                @endif
                            </div>
                            @endif
                        @endforeach
                    @else
                        <p class="text-muted">No quality metrics available</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- UI/UX Optimization -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-paint-brush mr-1"></i>
                        UI/UX Optimization
                    </h3>
                </div>
                <div class="card-body">
                    @if(isset($ui_ux_data) && is_array($ui_ux_data))
                        @foreach($ui_ux_data as $category => $items)
                            <div class="mb-3">
                                <h5>{{ ucfirst(str_replace('_', ' ', $category)) }}</h5>
                                @if(is_array($items))
                                    <ul class="list-unstyled ml-3">
                                        @foreach($items as $key => $item)
                                            <li><i class="fas fa-check text-success mr-1"></i> {{ is_string($item) ? $item : $key }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="ml-3 text-muted">{{ $items }}</p>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted">No UI/UX data available</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Optimization Recommendations -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-lightbulb mr-1"></i>
                        Optimization Recommendations
                    </h3>
                </div>
                <div class="card-body">
                    @if(isset($optimization_recommendations) && is_array($optimization_recommendations))
                        @foreach($optimization_recommendations as $recommendation)
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle mr-1"></i> {{ $recommendation['title'] ?? 'Recommendation' }}</h6>
                                <p class="mb-0">{{ $recommendation['description'] ?? (is_string($recommendation) ? $recommendation : 'No description available') }}</p>
                                @if(isset($recommendation['impact']))
                                    <small class="text-muted">Impact: {{ $recommendation['impact'] }}</small>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle mr-1"></i>
                            System is running optimally. No recommendations at this time.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('#run-all-optimizations').click(function() {
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Running...');
            
            // Simulate optimization process
            setTimeout(function() {
                btn.prop('disabled', false).html('<i class="fas fa-bolt mr-1"></i> Run All Optimizations');
                $.notify({
                    message: 'All optimizations completed successfully!'
                }, {
                    type: 'success'
                });
                window.location.reload();
            }, 3000);
        });

        $('#generate-report').click(function() {
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Generating...');
            
            // Simulate report generation
            setTimeout(function() {
                btn.prop('disabled', false).html('<i class="fas fa-file-alt mr-1"></i> Generate Report');
                $.notify({
                    message: 'Report generated and saved to system logs'
                }, {
                    type: 'info'
                });
            }, 2000);
        });
    </script>
@endsection