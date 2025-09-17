@extends('layouts.admin')

@section('title')
    AI Model Library
@endsection

@section('content-header')
    <h1>AI Model Library <small>Download models from Ollama registry</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.ai.index') }}">AI Management</a></li>
        <li><a href="{{ route('admin.ai.models') }}">Models</a></li>
        <li class="active">Library</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li><a href="{{ route('admin.ai.models') }}">Installed Models</a></li>
                <li class="active"><a href="#library" data-toggle="tab">Model Library</a></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane active" id="library">
                    
                    <!-- Header Controls -->
                    <div class="box">
                        <div class="box-header">
                            <h3 class="box-title">
                                <i class="fa fa-book"></i> Ollama Model Library
                                <span class="badge bg-blue">{{ $totalModels }} models available</span>
                            </h3>
                            <div class="box-tools pull-right">
                                <form action="{{ route('admin.ai.models.library.refresh') }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fa fa-refresh"></i> Refresh Library
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle"></i>
                                        <strong>Dynamic Library:</strong> Models are scraped live from ollama.com/library. 
                                        Data is cached for 1 hour. Click "Refresh Library" to get the latest models.
                                        @if(request('refresh'))
                                            <span class="text-success"><strong>Just refreshed!</strong></span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box">
                        <div class="box-header">
                            <h3 class="box-title">Filter Models</h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Speed</label>
                                        <select id="speedFilter" class="form-control">
                                            <option value="">All Speeds</option>
                                            <option value="Extremely Fast">Extremely Fast (&lt;1s)</option>
                                            <option value="Very Fast">Very Fast (1-3s)</option>
                                            <option value="Fast">Fast (2-5s)</option>
                                            <option value="Medium">Medium (5-10s)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Family</label>
                                        <select id="familyFilter" class="form-control">
                                            <option value="">All Families</option>
                                            <option value="llama">Llama (Meta)</option>
                                            <option value="phi3">Phi-3 (Microsoft)</option>
                                            <option value="qwen2">Qwen2 (Alibaba)</option>
                                            <option value="gemma2">Gemma2 (Google)</option>
                                            <option value="mistral">Mistral AI</option>
                                            <option value="codellama">Code Llama</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Use Case</label>
                                        <select id="useCaseFilter" class="form-control">
                                            <option value="">All Use Cases</option>
                                            <option value="Chat">Chat</option>
                                            <option value="Code">Code Generation</option>
                                            <option value="Analysis">Analysis</option>
                                            <option value="Creative">Creative Writing</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select id="statusFilter" class="form-control">
                                            <option value="">All Models</option>
                                            <option value="available">Available to Download</option>
                                            <option value="installed">Already Installed</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Model Cards -->
                    @if(count($libraryModels) > 0)
                    <div class="row" id="modelCards">
                        @foreach($libraryModels as $model)
                        <div class="col-lg-4 col-md-6 col-sm-12 model-card" 
                             data-speed="{{ $model['speed'] ?? 'Unknown' }}" 
                             data-family="{{ $model['family'] ?? 'other' }}" 
                             data-use-cases="{{ is_array($model['use_cases'] ?? []) ? implode(',', $model['use_cases']) : '' }}"
                             data-status="{{ ($model['installed'] ?? false) ? 'installed' : 'available' }}"
                             data-downloads="{{ $model['downloads'] ?? 0 }}"
                             data-variants="{{ htmlspecialchars(json_encode($model['variants'] ?? []), ENT_QUOTES, 'UTF-8') }}"
                             data-slug="{{ $model['slug'] ?? $model['name'] }}"
                             style="margin-bottom: 30px;">
                            
                            <div class="box box-primary">
                                <div class="box-header with-border">
                                    <div class="box-title-container" style="display: flex; align-items: center;">
                                        <i class="fa {{ 
                                            strpos($model['input_type'] ?? 'Text', 'Code') !== false ? 'fa-code' : 
                                            (strpos($model['input_type'] ?? 'Text', 'Vision') !== false ? 'fa-eye' : 'fa-cube') 
                                        }} model-icon" style="margin-right: 10px; font-size: 18px;"></i>
                                        
                                        <h3 class="box-title" style="margin: 0;">{{ $model['title'] ?? $model['name'] }}</h3>
                                    </div>
                                    
                                    <div class="box-tools pull-right">
                                        @if(($model['downloads'] ?? 0) > 0)
                                            <span class="label label-info">
                                                @if($model['downloads'] >= 1000000)
                                                    {{ round($model['downloads'] / 1000000, 1) }}M downloads
                                                @elseif($model['downloads'] >= 1000)
                                                    {{ round($model['downloads'] / 1000, 1) }}K downloads
                                                @else
                                                    {{ $model['downloads'] }} downloads
                                                @endif
                                            </span>
                                        @endif
                                        @if($model['installed'] ?? false)
                                            <span class="label label-success">Installed</span>
                                        @else
                                            <span class="label label-default">Available</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="box-body">
                                    <p class="model-description" style="margin-bottom: 15px; color: #666;">
                                        {{ Str::limit($model['description'] ?? 'No description available.', 120) }}
                                    </p>
                                    
                                    <!-- Model Details Table -->
                                    <table class="table table-condensed">
                                        <tbody>
                                            <tr>
                                                <td><strong>Size:</strong></td>
                                                <td>{{ $model['size'] !== 'Unknown' && $model['size'] ? $model['size'] : 'Varies by variant' }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Variants:</strong></td>
                                                <td>{{ count($model['variants'] ?? []) }} available</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Context:</strong></td>
                                                <td>{{ $model['context'] ?? '4K tokens' }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Speed:</strong></td>
                                                <td>
                                                    <span class="label label-{{ 
                                                        strpos($model['speed'] ?? '', 'Extremely Fast') !== false ? 'success' : 
                                                        (strpos($model['speed'] ?? '', 'Very Fast') !== false ? 'info' : 
                                                        (strpos($model['speed'] ?? '', 'Fast') !== false ? 'primary' : 'default')) 
                                                    }}">{{ $model['speed'] ?? 'Average' }}</span>
                                                </td>
                                            </tr>
                                            @if($model['input_type'])
                                            <tr>
                                                <td><strong>Type:</strong></td>
                                                <td>{{ $model['input_type'] }}</td>
                                            </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                    
                                    <!-- Use Cases Tags -->
                                    @if(!empty($model['use_cases']) && is_array($model['use_cases']))
                                    <div style="margin-top: 15px;">
                                        <strong>Use Cases:</strong><br>
                                        @foreach(array_slice($model['use_cases'], 0, 3) as $useCase)
                                            <span class="label label-primary" style="margin-right: 5px; margin-top: 5px; display: inline-block;">{{ $useCase }}</span>
                                        @endforeach
                                        @if(count($model['use_cases']) > 3)
                                            <span class="label label-default" style="margin-top: 5px; display: inline-block;">+{{ count($model['use_cases']) - 3 }} more</span>
                                        @endif
                                    </div>
                                    @endif
                                    
                                    <!-- Model Tags -->
                                    @if(!empty($model['tags']) && is_array($model['tags']))
                                    <div style="margin-top: 10px;">
                                        <strong>Tags:</strong><br>
                                        @foreach(array_slice($model['tags'], 0, 4) as $tag)
                                            <span class="label label-{{ 
                                                in_array($tag, ['recommended', 'fast', 'ultra-fast', 'popular', 'latest']) ? 'warning' : 
                                                (in_array($tag, ['premium', 'flagship']) ? 'danger' : 'default') 
                                            }}" style="margin-right: 5px; margin-top: 5px; display: inline-block;">{{ $tag }}</span>
                                        @endforeach
                                        @if(count($model['tags']) > 4)
                                            <span class="label label-default" style="margin-top: 5px; display: inline-block;">+{{ count($model['tags']) - 4 }}</span>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                                
                                <div class="box-footer">
                                    @if($model['installed'] ?? false)
                                        <button class="btn btn-success btn-block" disabled>
                                            <i class="fa fa-check"></i> Already Installed
                                        </button>
                                    @else
                                        <button class="btn btn-primary btn-block show-variants-btn" 
                                                data-model="{{ $model['name'] }}"
                                                data-title="{{ $model['title'] ?? $model['name'] }}"
                                                data-description="{{ $model['description'] ?? '' }}"
                                                data-slug="{{ $model['slug'] ?? $model['name'] }}">
                                            <i class="fa fa-cubes"></i> Select Variant to Download
                                        </button>
                                    @endif
                                    
                                    <!-- Progress bar (hidden by default) -->
                                    <div class="progress" id="progress-{{ str_replace([':', '.'], '-', $model['name']) }}" style="display: none; margin-top: 10px;">
                                        <div class="progress-bar progress-bar-striped active" role="progressbar" style="width: 0%">
                                            <span class="sr-only">0% Complete</span>
                                        </div>
                                    </div>
                                    <div class="progress-status" id="status-{{ str_replace([':', '.'], '-', $model['name']) }}" style="display: none; margin-top: 5px;">
                                        <small class="text-muted"></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="box">
                        <div class="box-body text-center" style="padding: 50px;">
                            <i class="fa fa-exclamation-triangle fa-3x text-warning"></i>
                            <h3>No Models Available</h3>
                            <p class="text-muted">
                                Could not load models from the Ollama library. This might be due to:
                            </p>
                            <ul class="list-unstyled">
                                <li><i class="fa fa-globe"></i> Network connectivity issues</li>
                                <li><i class="fa fa-server"></i> Ollama.com might be temporarily unavailable</li>
                                <li><i class="fa fa-clock-o"></i> Rate limiting or timeout</li>
                            </ul>
                            <form action="{{ route('admin.ai.models.library.refresh') }}" method="POST" style="margin-top: 20px;">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-refresh"></i> Try Again
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif
                    
                    <div id="noResults" style="display: none; text-align: center; padding: 50px;">
                        <i class="fa fa-search fa-3x text-muted"></i>
                        <h3>No models found</h3>
                        <p class="text-muted">Try adjusting your filters to find models.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Variant Selection Modal -->
<div class="modal fade" id="variantModal" tabindex="-1" role="dialog" aria-labelledby="variantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="variantModalLabel">
                    <i class="fa fa-cubes"></i>
                    Select Model Variant
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="variantModelInfo" class="mb-3">
                    <div class="d-flex align-items-center">
                        <div id="variantModelIcon" class="mr-3">
                            <!-- Model icon will be inserted here -->
                        </div>
                        <div>
                            <h5 id="variantModelName" class="mb-1"></h5>
                            <p id="variantModelDescription" class="text-muted mb-0"></p>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div id="variantList" class="row">
                    <!-- Variants will be populated here -->
                </div>
                
                <div id="variantLoading" class="text-center py-4" style="display: none;">
                    <i class="fa fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="mt-2 text-muted">Loading variants...</p>
                </div>
                
                <div id="variantError" class="alert alert-danger" style="display: none;">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>Error:</strong> Unable to load model variants. Please try again.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancel
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
            // Enhanced filter functionality with animations
            function filterModels() {
                const speedFilter = $('#speedFilter').val();
                const familyFilter = $('#familyFilter').val();
                const useCaseFilter = $('#useCaseFilter').val();
                const statusFilter = $('#statusFilter').val();
                
                let visibleCount = 0;
                const cards = $('.model-card');
                
                // Add loading state
                $('#modelCards').addClass('filtering');
                
                cards.each(function(index) {
                    const card = $(this);
                    let show = true;
                    
                    // Apply filters
                    if (speedFilter && !card.data('speed').includes(speedFilter)) show = false;
                    if (familyFilter && card.data('family') !== familyFilter) show = false;
                    if (useCaseFilter && !card.data('use-cases').includes(useCaseFilter)) show = false;
                    if (statusFilter && card.data('status') !== statusFilter) show = false;
                    
                    // Animate card visibility with stagger
                    setTimeout(() => {
                        if (show) {
                            card.removeClass('filtered-out').addClass('filtered-in').show();
                            visibleCount++;
                        } else {
                            card.removeClass('filtered-in').addClass('filtered-out');
                            setTimeout(() => card.hide(), 300);
                        }
                    }, index * 50); // Stagger the animations
                });
                
                // Show/hide no results message
                setTimeout(() => {
                    $('#noResults').toggle(visibleCount === 0);
                    $('#modelCards').removeClass('filtering');
                }, cards.length * 50 + 300);
            }
            
            // Debounced filter function for better performance
            let filterTimeout;
            function debouncedFilter() {
                clearTimeout(filterTimeout);
                filterTimeout = setTimeout(filterModels, 150);
            }
            
            // Filter event listeners
            $('#speedFilter, #familyFilter, #useCaseFilter, #statusFilter').on('change', debouncedFilter);
            
            // Show variants modal functionality
            $('.show-variants-btn').on('click', function(e) {
                e.preventDefault();
                const card = $(this).closest('.model-card');
                const modelName = card.data('slug') || card.find('.box-title').text().trim();
                const modelTitle = card.find('.box-title').text().trim();
                const modelDescription = card.find('.model-description').text().trim();
                const modelIcon = card.find('.model-icon').clone();
                
                // Safely parse variants with error handling
                let variants = [];
                try {
                    const variantsData = card.data('variants');
                    console.log('Raw variants data:', variantsData);
                    if (variantsData && variantsData !== '') {
                        // Handle HTML-escaped JSON
                        const cleanData = typeof variantsData === 'string' ? 
                            $('<div/>').html(variantsData).text() : variantsData;
                        variants = typeof cleanData === 'string' ? JSON.parse(cleanData) : cleanData;
                    }
                    console.log('Parsed variants:', variants);
                } catch (e) {
                    console.error('Error parsing variants data:', e);
                    console.error('Raw data was:', card.data('variants'));
                    variants = [];
                }
                
                // Populate modal header
                $('#variantModalLabel').text(`Select ${modelTitle} Variant`);
                $('#variantModelName').text(modelTitle);
                $('#variantModelDescription').text(modelDescription);
                $('#variantModelIcon').empty().append(modelIcon);
                
                // Show loading
                $('#variantLoading').show();
                $('#variantList').hide();
                $('#variantError').hide();
                
                // Show modal
                $('#variantModal').modal('show');
                
                // Simulate small delay to show loading state
                setTimeout(() => {
                    if (variants.length > 0) {
                        populateVariants(variants, modelName);
                    } else {
                        $('#variantError').show();
                    }
                    $('#variantLoading').hide();
                }, 500);
            });
            
            // Helper function to calculate speed based on parameter size
            function calculateSpeed(parameterSize) {
                if (!parameterSize || parameterSize === 'Unknown') return 'Medium (5-10s)';
                
                let params = 7; // Default for 'Latest'
                
                if (parameterSize === 'Latest') {
                    params = 7;
                } else if (parameterSize.match(/(\d+(?:\.\d+)?)B?$/)) {
                    params = parseFloat(parameterSize.match(/(\d+(?:\.\d+)?)B?$/)[1]);
                }
                
                if (params <= 1) return 'Extremely Fast (<1s)';
                else if (params <= 3) return 'Very Fast (1-3s)';
                else if (params <= 8) return 'Fast (2-5s)';
                else if (params <= 15) return 'Medium (5-10s)';
                else if (params <= 35) return 'Slow (10-20s)';
                else if (params <= 75) return 'Very Slow (20-60s)';
                else return 'Extremely Slow (60s+)';
            }

            // Helper function to get speed color class
            function getSpeedColorClass(speed) {
                if (speed.includes('Extremely Fast')) return 'text-success';
                else if (speed.includes('Very Fast')) return 'text-info';
                else if (speed.includes('Fast')) return 'text-primary';
                else if (speed.includes('Medium')) return 'text-warning';
                else return 'text-danger';
            }
            
            function populateVariants(variants, baseModelName) {
                const variantContainer = $('#variantList');
                variantContainer.empty().show();
                
                variants.forEach((variant, index) => {
                    const speed = calculateSpeed(variant.size);
                    const speedClass = getSpeedColorClass(speed);
                    
                    const variantHtml = `
                        <div class="col-md-6 mb-3">
                            <div class="variant-card" data-model="${variant.full_name}">
                                <div class="variant-name">${variant.display_name || variant.name}</div>
                                <div class="variant-details">
                                    <div class="variant-size"><strong>Parameters:</strong> ${variant.size || 'Unknown size'}</div>
                                    <div class="variant-file-size"><strong>File Size:</strong> ${variant.file_size || 'Unknown'}</div>
                                    <div class="variant-context"><strong>Context:</strong> ${variant.context_length || variant.context || '4K'}</div>
                                    <div class="variant-speed"><strong>Speed:</strong> <span class="${speedClass}">${speed}</span></div>
                                </div>
                                <button class="btn variant-download-btn" data-model="${variant.full_name}" data-title="${variant.display_name || variant.name}">
                                    <i class="fa fa-download"></i> Download ${variant.display_name || variant.name}
                                </button>
                                <div class="variant-progress mt-2" id="progress-${variant.full_name.replace(/[:\.]/g, '-')}" style="display: none;">
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-striped active" role="progressbar" style="width: 0%">
                                            <span class="sr-only">0% Complete</span>
                                        </div>
                                    </div>
                                    <small class="progress-status text-muted" id="status-${variant.full_name.replace(/[:\.]/g, '-')}"></small>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Add with animation delay
                    setTimeout(() => {
                        variantContainer.append(variantHtml);
                    }, index * 100);
                });
                
                // Add event listeners for variant download buttons
                setTimeout(() => {
                    $('.variant-download-btn').off('click').on('click', function(e) {
                        e.stopPropagation();
                        downloadModel($(this));
                    });
                }, variants.length * 100 + 100);
            }
            
            // Enhanced download model functionality
            function downloadModel(button) {
                const modelName = button.data('model');
                const modelTitle = button.data('title');
                const progressId = modelName.replace(/[:\.]/g, '-');
                const progressContainer = $(`#progress-${progressId}`);
                const progressBar = progressContainer.find('.progress-bar');
                const statusDiv = $(`#status-${progressId}`);
                
                // Disable button and show progress with animation
                button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Downloading...');
                progressContainer.slideDown(300);
                
                // Show loading notification
                $.notify({
                    message: `Started downloading ${modelTitle}...`
                }, {
                    type: 'info',
                    delay: 3000
                });
                
                // Create EventSource for progress updates (this will also start the download)
                const eventSource = new EventSource(`/admin/ai/models/pull-progress?model=${encodeURIComponent(modelName)}`);
                
                eventSource.onmessage = function(event) {
                    const data = JSON.parse(event.data);
                    
                    // Update progress bar with direct inline style
                    if (data.percent !== undefined && data.percent !== null) {
                        // Debug: Check current values
                        console.log('Setting progress to:', data.percent + '%');
                        console.log('Progress bar element:', progressBar[0]);
                        console.log('Current width before:', progressBar[0].style.width);
                        
                        // Temporarily disable transitions to force immediate visual update
                        progressBar.css({'transition': 'none'});
                        
                        // Set width using multiple methods to ensure update
                        const newWidth = data.percent + '%';
                        progressBar[0].style.setProperty('width', newWidth, 'important');
                        progressBar.css('width', newWidth);
                        progressBar.attr('aria-valuenow', data.percent);
                        progressBar.find('.sr-only').text(data.percent + '% Complete');
                        
                        // Force browser reflow and repaint
                        progressBar[0].offsetWidth;
                        progressBar[0].getBoundingClientRect();
                        
                        // Re-enable transitions after a brief delay
                        setTimeout(function() {
                            progressBar.css({'transition': 'width 0.3s ease'});
                        }, 10);
                        
                        console.log('Current width after:', progressBar[0].style.width);
                        
                        // Also update any text display of percentage
                        const progressText = progressContainer.find('.progress-text');
                        if (progressText.length > 0) {
                            progressText.text(data.percent + '%');
                        }
                    }
                    
                    // Update status text with fade effect
                    if (data.message) {
                        statusDiv.fadeOut(150, function() {
                            $(this).text(data.message).fadeIn(150);
                        });
                    }
                    
                    if (data.status === 'complete') {
                        eventSource.close();
                        
                        // Update UI to show as installed with animation
                        button.removeClass('variant-download-btn').addClass('btn-success')
                              .html('<i class="fa fa-check"></i> Installed')
                              .prop('disabled', true);
                        
                        // Hide progress elements with animation
                        progressContainer.slideUp(300);
                        
                        // Show success notification
                        $.notify({
                            message: `${modelTitle} has been downloaded successfully!`
                        }, {
                            type: 'success',
                            delay: 5000
                        });
                        
                        // Update the original card status
                        const originalCard = $(`.model-card[data-name="${modelName.split(':')[0]}"]`);
                        if (originalCard.length > 0) {
                            originalCard.attr('data-status', 'installed');
                            originalCard.find('.model-header').removeClass('status-available').addClass('status-installed');
                            originalCard.find('.download-badge').text('Installed').removeClass('bg-primary').addClass('bg-success');
                        }
                        
                        // Refresh filters with delay to show the change
                        setTimeout(() => {
                            if (typeof debouncedFilter === 'function') {
                                debouncedFilter();
                            }
                        }, 1000);
                        
                    } else if (data.status === 'error') {
                        eventSource.close();
                        
                        // Reset button with animation
                        button.prop('disabled', false).html('<i class="fa fa-download"></i> Download ' + modelTitle);
                        progressContainer.slideUp(300);
                        
                        // Show error notification
                        $.notify({
                            message: `Failed to download ${modelTitle}: ${data.message || 'Unknown error'}`
                        }, {
                            type: 'danger'
                        });
                    }
                };
                
                eventSource.onerror = function(event) {
                    eventSource.close();
                    
                    // Reset button with animation
                    button.prop('disabled', false).html('<i class="fa fa-download"></i> Download ' + modelTitle);
                    progressContainer.slideUp(300);
                    
                    // Show error notification
                    $.notify({
                        message: `Failed to download ${modelTitle}. Please try again.`
                    }, {
                        type: 'danger'
                    });
                };
            }
            
            // Handle variant card hover effects
            $(document).on('mouseenter', '.variant-card', function() {
                $(this).addClass('shadow-lg');
            }).on('mouseleave', '.variant-card', function() {
                $(this).removeClass('shadow-lg');
            });
            
            // Initialize tooltips if available
            if ($.fn.tooltip) {
                $('[data-toggle="tooltip"]').tooltip();
            }
            
            // Auto-refresh model status every 30 seconds (optional)
            // setInterval(() => {
            //     location.reload();
            // }, 30000);
        });
    </script>
    
    <style>
        /* AdminLTE Box Enhancements for Model Cards */
        .model-card {
            margin-bottom: 25px;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        
        .model-card:hover {
            transform: translateY(-2px);
        }
        
        .model-card .box {
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transition: box-shadow 0.2s ease-in-out;
        }
        
        .model-card:hover .box {
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        /* Box Header Styling */
        .box-header.with-border {
            border-radius: 6px 6px 0 0;
            background: linear-gradient(135deg, #337ab7 0%, #2c5aa0 100%);
            color: #fff;
            padding: 15px 20px;
        }
        
        .box-title-container {
            display: flex;
            align-items: center;
            flex: 1;
        }
        
        .model-icon {
            color: rgba(255,255,255,0.9);
            font-size: 18px;
            margin-right: 10px;
        }
        
        .box-title {
            color: #fff !important;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
            margin: 0 !important;
        }
        
        .box-tools .label {
            font-size: 10px;
            font-weight: 500;
            margin-left: 5px;
            background-color: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: #fff;
        }
        
        /* Box Body Styling */
        .model-card .box-body {
            padding: 20px;
        }
        
        .model-description {
            font-size: 14px;
            line-height: 1.5;
            color: #666;
            margin-bottom: 15px;
        }
        
        .table-condensed {
            font-size: 13px;
        }
        
        .table-condensed td {
            padding: 6px 8px;
            border-top: 1px solid #f0f0f0;
        }
        
        .table-condensed td:first-child {
            width: 30%;
            font-weight: 500;
            color: #555;
        }
        
        /* Box Footer Styling */
        .model-card .box-footer {
            padding: 15px 20px;
            background-color: #2f67aa;
            border-top: 1px solid #1f2933;
        }
        
        .btn-block {
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn-block:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        /* Progress Bar Styling */
        .progress {
            height: 6px;
            border-radius: 3px;
            background-color: #f5f5f5;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
            margin-bottom: 5px;
        }
        
        .progress-bar {
            border-radius: 3px;
            transition: width 0.3s ease;
            background: linear-gradient(90deg, #5cb85c, #449d44);
        }
        
        .progress-bar.active {
            background-image: linear-gradient(
                45deg,
                rgba(255, 255, 255, 0.15) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255, 255, 255, 0.15) 50%,
                rgba(255, 255, 255, 0.15) 75%,
                transparent 75%,
                transparent
            );
            background-size: 40px 40px;
            animation: progress-bar-stripes 1s linear infinite;
        }
        
        @keyframes progress-bar-stripes {
            from {
                background-position: 0 0;
            }
            to {
                background-position: 40px 0;
            }
        }
        
        .progress-status small {
            font-size: 11px;
            color: #777;
        }
        
        /* Tag and Label Enhancements */
        .label {
            display: inline-block;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: 500;
            border-radius: 3px;
            text-transform: none;
        }
        
        .label-success {
            background-color: #5cb85c;
        }
        
        .label-info {
            background-color: #5bc0de;
        }
        
        .label-warning {
            background-color: #f0ad4e;
        }
        
        .label-danger {
            background-color: #d9534f;
        }
        
        .label-primary {
            background-color: #337ab7;
        }
        
        .label-default {
            background-color: #777;
        }
        
        /* Filter animations */
        .filtered-out {
            opacity: 0;
            transform: scale(0.95);
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
        }
        
        .filtered-in {
            opacity: 1;
            transform: scale(1);
            transition: opacity 0.3s ease-in, transform 0.3s ease-in;
        }
        
        #modelCards.filtering .model-card {
            transition-delay: 0.05s;
        }
        
        /* Variant Selection Modal Styles */
        .variant-card {
            background: #fff;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .variant-card:hover {
            border-color: #337ab7;
            box-shadow: 0 2px 8px rgba(51, 122, 183, 0.15);
            transform: translateY(-1px);
        }
        
        .variant-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .variant-details {
            margin: 10px 0;
            font-size: 13px;
        }
        
        .variant-details > div {
            margin: 5px 0;
            color: #666;
        }
        
        .variant-details strong {
            color: #333;
        }
        
        .variant-download-btn {
            background: linear-gradient(135deg, #5cb85c 0%, #449d44 100%);
            border: none;
            color: #fff;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 13px;
            width: 100%;
            transition: all 0.2s ease;
        }
        
        .variant-download-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(92, 184, 92, 0.3);
        }
        
        .variant-progress {
            margin-top: 10px;
        }
        
        .variant-progress .progress {
            height: 4px;
            margin-bottom: 5px;
        }
        
        .variant-progress .progress-status {
            font-size: 11px;
            color: #777;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .model-card .box-body {
                padding: 15px;
            }
            
            .model-card .box-footer {
                padding: 10px 15px;
            }
            
            .variant-details {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .variant-context {
                margin-top: 5px;
            }
        }
        
        /* Animation for card entrance */
        .model-card {
            animation: fadeInUp 0.5s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Notification positioning */
        .notifications {
            position: fixed;
            top: 60px;
            right: 20px;
            z-index: 9999;
        }
    </style>
@endsection