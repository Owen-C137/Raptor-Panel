@extends('layouts.admin')

@section('title')
    Edit Plan: {{ $plan->name }}
@endsection

@push('head-scripts')
    <style>
        .converter-section .block-content .mb-4.has-success .form-control {
            border-color: #00a65a;
        }
        
        .converter-section .block-content .mb-4.has-error .form-control {
            border-color: #dd4b39;
        }
        
        .converter-section .block-content .mb-4.has-success .form-text {
            color: #00a65a;
        }
        
        .converter-section .block-content .mb-4.has-error .form-text {
            color: #dd4b39;
        }
        
        .converter-section .alert-success {
            border-left-color: #00a65a;
        }
        
        .converter-section .input-group-sm .form-control {
            height: 30px;
        }
    </style>
@endpush

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Edit Plan {{ $plan->name }}
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          {{ $plan->name }}
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.plans.index') }}">Plans</a></li>
          <li class="breadcrumb-item" aria-current="page">Edit</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <form method="POST" action="{{ route('admin.shop.plans.update', $plan->id) }}">
            @csrf
            @method('PUT')
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Plan Information</h3>
                </div>
                
                <div class="block-content">
                    <div class="mb-4">
                        <label for="name" class="form-label">Plan Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" 
                               value="{{ old('name', $plan->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="4">{{ old('description', $plan->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="category_id" class="form-label">Game Category <span class="text-danger">*</span></label>
                                <select name="category_id" id="category_id" class="form-select" required>
                                    <option value="">Select a game category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" 
                                                {{ old('category_id', $plan->category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="sort_order" class="form-label">Sort Order</label>
                                <input type="number" name="sort_order" id="sort_order" class="form-control" 
                                       value="{{ old('sort_order', $plan->sort_order) }}" min="0">
                                @error('sort_order')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Pricing & Billing</h3>
                </div>
                
                <div class="block-content">
                    <div id="billing-cycles">
                        @php
                            $cycles = is_array($plan->billing_cycles) ? $plan->billing_cycles : [];
                            if (empty($cycles)) {
                                $cycles = [['cycle' => 'monthly', 'price' => '9.99', 'setup_fee' => '0']];
                            }
                        @endphp
                        @foreach($cycles as $index => $cycle)
                        <div class="billing-cycle-item">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-4">
                                        <label class="form-label">Billing Cycle</label>
                                        <select name="billing_cycles[{{ $index }}][cycle]" class="form-select">
                                            <option value="monthly" {{ ($cycle['cycle'] ?? '') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                            <option value="quarterly" {{ ($cycle['cycle'] ?? '') == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                            <option value="semi_annually" {{ ($cycle['cycle'] ?? '') == 'semi_annually' ? 'selected' : '' }}>Semi-Annually</option>
                                            <option value="annually" {{ ($cycle['cycle'] ?? '') == 'annually' ? 'selected' : '' }}>Annually</option>
                                            <option value="one_time" {{ ($cycle['cycle'] ?? '') == 'one_time' ? 'selected' : '' }}>One Time</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-4">
                                        <label class="form-label">Price ({{ $currencySymbol }})</label>
                                        <input type="number" name="billing_cycles[{{ $index }}][price]" class="form-control" 
                                               step="0.01" min="0" value="{{ $cycle['price'] ?? '9.99' }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-4">
                                        <label class="form-label">Setup Fee ({{ $currencySymbol }})</label>
                                        <input type="number" name="billing_cycles[{{ $index }}][setup_fee]" class="form-control" 
                                               step="0.01" min="0" value="{{ $cycle['setup_fee'] ?? '0' }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-4">
                                        <label class="form-label">&nbsp;</label>
                                        @if($index == 0)
                                        <button type="button" class="btn btn-success btn-sm add-cycle" style="display: block;">
                                            <i class="fa fa-plus"></i> Add Cycle
                                        </button>
                                        @else
                                        <button type="button" class="btn btn-danger btn-sm remove-cycle" style="display: block;">
                                            <i class="fa fa-minus"></i> Remove
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Server Resources</h3>
                </div>
                
                <div class="block-content">
                    @php
                        $limits = is_array($plan->server_limits) ? $plan->server_limits : [];
                    @endphp
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="cpu" class="form-label">CPU Percentage</label>
                                <input type="number" name="server_limits[cpu]" id="cpu" class="form-control" 
                                       value="{{ old('server_limits.cpu', $limits['cpu'] ?? '') }}" 
                                       placeholder="{{ $limits['cpu'] ?? 100 }}" min="0">
                                <small class="form-text text-muted">
                                    CPU percentage (100 = 1 core)
                                </small>
                                @error('server_limits.cpu')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="memory" class="form-label">Memory (MiB)</label>
                                <input type="number" name="server_limits[memory]" id="memory" class="form-control" 
                                       value="{{ old('server_limits.memory', $limits['memory'] ?? '') }}" 
                                       placeholder="{{ $limits['memory'] ?? 1024 }}" min="0">
                                @error('server_limits.memory')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="disk" class="form-label">Disk Space (MB)</label>
                                <input type="number" name="server_limits[disk]" id="disk" class="form-control" 
                                       value="{{ old('server_limits.disk', $limits['disk'] ?? '') }}" 
                                       placeholder="{{ $limits['disk'] ?? 5120 }}" min="0">
                                @error('server_limits.disk')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="swap" class="form-label">Swap Memory (MB)</label>
                                <input type="number" name="server_limits[swap]" id="swap" class="form-control" 
                                       value="{{ old('server_limits.swap', $limits['swap'] ?? 0) }}" min="-1">
                                <small class="form-text text-muted">
                                    Set to -1 for unlimited swap
                                </small>
                                @error('server_limits.swap')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="io" class="form-label">IO Priority</label>
                                <input type="number" name="server_limits[io]" id="io" class="form-control" 
                                       value="{{ old('server_limits.io', $limits['io'] ?? 500) }}" min="10" max="1000">
                                @error('server_limits.io')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="oom_disabled" class="form-label">OOM Killer</label>
                                <select name="server_limits[oom_disabled]" id="oom_disabled" class="form-select">
                                    <option value="0" {{ old('server_limits.oom_disabled', $limits['oom_disabled'] ?? '0') == '0' ? 'selected' : '' }}>Enabled</option>
                                    <option value="1" {{ old('server_limits.oom_disabled', $limits['oom_disabled'] ?? '0') == '1' ? 'selected' : '' }}>Disabled</option>
                                </select>
                                @error('server_limits.oom_disabled')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Feature Limits</h3>
                </div>
                
                <div class="block-content">
                    @php
                        $featureLimits = is_array($plan->server_feature_limits) ? $plan->server_feature_limits : [];
                    @endphp
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="databases" class="form-label">Database Limit</label>
                                <input type="number" name="server_feature_limits[databases]" id="databases" class="form-control" 
                                       value="{{ old('server_feature_limits.databases', $featureLimits['databases'] ?? 1) }}" min="0">
                                @error('server_feature_limits.databases')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="allocations" class="form-label">Allocation Limit</label>
                                <input type="number" name="server_feature_limits[allocations]" id="allocations" class="form-control" 
                                       value="{{ old('server_feature_limits.allocations', $featureLimits['allocations'] ?? 1) }}" min="0">
                                @error('server_feature_limits.allocations')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="backups" class="form-label">Backup Limit</label>
                                <input type="number" name="server_feature_limits[backups]" id="backups" class="form-control" 
                                       value="{{ old('server_feature_limits.backups', $featureLimits['backups'] ?? 1) }}" min="0">
                                @error('server_feature_limits.backups')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Server Configuration</h3>
                </div>
                
                <div class="block-content">
                    <div class="mb-4">
                        <label for="egg_id" class="form-label">Egg</label>
                        <select name="egg_id" id="egg_id" class="form-select">
                            <option value="">Select an egg (optional)</option>
                            @foreach($eggs as $egg)
                                <option value="{{ $egg->id }}" {{ old('egg_id', $plan->egg_id) == $egg->id ? 'selected' : '' }}>
                                    {{ $egg->nest->name }} - {{ $egg->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('egg_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label for="allowed_locations" class="form-label">Allowed Locations</label>
                        <select name="allowed_locations[]" id="allowed_locations" class="form-select" multiple>
                            @foreach($locations as $location)
                                @php
                                    $allowedLocations = is_array($plan->allowed_locations) ? $plan->allowed_locations : [];
                                @endphp
                                <option value="{{ $location->id }}" 
                                        {{ in_array($location->id, old('allowed_locations', $allowedLocations)) ? 'selected' : '' }}>
                                    {{ $location->long ?: $location->short }} ({{ $location->short }})
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            Leave empty to allow all locations. Hold Ctrl/Cmd to select multiple.
                        </small>
                        @error('allowed_locations')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label for="allowed_nodes" class="form-label">Allowed Nodes</label>
                        <select name="allowed_nodes[]" id="allowed_nodes" class="form-select" multiple>
                            @foreach($nodes as $node)
                                @php
                                    $allowedNodes = is_array($plan->allowed_nodes) ? $plan->allowed_nodes : [];
                                @endphp
                                <option value="{{ $node->id }}" 
                                        {{ in_array($node->id, old('allowed_nodes', $allowedNodes)) ? 'selected' : '' }}>
                                    {{ $node->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            Leave empty to allow all nodes. Hold Ctrl/Cmd to select multiple.
                        </small>
                        @error('allowed_nodes')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Plan Status</h3>
                </div>
                
                <div class="block-content">
                    <div class="mb-4">
                        <div class="form-check">
                            <input type="checkbox" id="visible" name="visible" value="1" class="form-check-input"
                                   {{ old('visible', $plan->visible) ? 'checked' : '' }}>
                            <label for="visible" class="form-check-label fw-medium">Visible to Customers</label>
                        </div>
                        <small class="form-text text-muted">
                            Hidden plans are not shown in the shop but existing orders remain active.
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="block block-rounded">
                <div class="block-content">
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i> Update Plan
                    </button>
                    <a href="{{ route('admin.shop.plans.index') }}" class="btn btn-alt-secondary">
                        <i class="fa fa-arrow-left"></i> Back to Plans
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="col-md-4">
        <div class="block block-rounded converter-section">
            <div class="block-header block-header-default">
                <h3 class="block-title">Resource Converters</h3>
            </div>
            
            <div class="block-content">
                <div class="alert alert-success">
                    <h4><i class="fa fa-calculator"></i> Quick Converters</h4>
                    <p class="text-muted mb-0">Use these converters to easily calculate resource values.</p>
                </div>
                
                <div class="mb-4">
                    <label for="memory_converter" class="form-label">Memory (GB)</label>
                    <div class="input-group input-group-sm">
                        <input type="number" id="memory_converter" class="form-control" 
                               placeholder="GB" min="0" step="0.1">
                        <span class="input-group-text">GB</span>
                    </div>
                    <small class="form-text text-success">
                        <i class="fa fa-arrow-right"></i> Converts to MiB
                    </small>
                </div>
                
                <div class="mb-4">
                    <label for="disk_converter" class="form-label">Disk Space (GB)</label>
                    <div class="input-group input-group-sm">
                        <input type="number" id="disk_converter" class="form-control" 
                               placeholder="GB" min="0" step="0.1">
                        <span class="input-group-text">GB</span>
                    </div>
                    <small class="form-text text-success">
                        <i class="fa fa-arrow-right"></i> Converts to MB
                    </small>
                </div>
                
                <div class="mb-4">
                    <label for="cpu_converter" class="form-label">CPU Cores</label>
                    <div class="input-group input-group-sm">
                        <input type="number" id="cpu_converter" class="form-control" 
                               placeholder="Cores" min="0" step="0.1">
                        <span class="input-group-text">Cores</span>
                    </div>
                    <small class="form-text text-success">
                        <i class="fa fa-arrow-right"></i> Converts to %
                    </small>
                </div>
            </div>
        </div>
        
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Plan Statistics</h3>
            </div>
            <div class="block-content">
                <div class="alert alert-info">
                    <h4><i class="fa fa-info-circle"></i> Plan Info</h4>
                    <ul class="mb-0">
                        <li><strong>Created:</strong> {{ $plan->created_at->format('M j, Y') }}</li>
                        <li><strong>Updated:</strong> {{ $plan->updated_at->diffForHumans() }}</li>
                        <li><strong>Status:</strong> {{ $plan->visible ? 'Visible' : 'Hidden' }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('footer-scripts')
    @parent
<script>
    // Check if jQuery is available, if not use vanilla JS
    function initializeEditPage() {
        console.log('📄 Edit page initializing...');
        
        // Add a small delay to ensure all elements are rendered
        setTimeout(function() {
            console.log('⏰ Starting delayed initialization...');
            testConverters();
            setupConverters();
            setupBillingCycles();
        }, 100);
    }

    // Use jQuery if available, otherwise use vanilla JS
    if (typeof $ !== 'undefined') {
        $(document).ready(initializeEditPage);
    } else {
        // Vanilla JS fallback
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeEditPage);
        } else {
            initializeEditPage();
        }
    }

    // Helper function to format numbers without unnecessary decimals
    function formatNumber(num, maxDecimals = 2) {
        // If it's a whole number, return without decimals
        if (num % 1 === 0) {
            return num.toString();
        }
        // Otherwise, format with up to maxDecimals, removing trailing zeros
        return parseFloat(num.toFixed(maxDecimals)).toString();
    }

    // Test function to debug converter issues
    function testConverters() {
        console.log('🧪 Testing converter elements...');
        console.log('Memory field:', document.getElementById('memory'));
        console.log('Memory converter:', document.getElementById('memory_converter'));
        console.log('Disk field:', document.getElementById('disk'));
        console.log('Disk converter:', document.getElementById('disk_converter'));
        console.log('CPU field:', document.getElementById('cpu'));
        console.log('CPU converter:', document.getElementById('cpu_converter'));
        
        // Test current values
        const memoryField = document.getElementById('memory');
        const diskField = document.getElementById('disk');
        const cpuField = document.getElementById('cpu');
        
        if (memoryField) console.log('Memory value:', memoryField.value, 'placeholder:', memoryField.placeholder);
        if (diskField) console.log('Disk value:', diskField.value, 'placeholder:', diskField.placeholder);
        if (cpuField) console.log('CPU value:', cpuField.value, 'placeholder:', cpuField.placeholder);
    }

    // Resource Converters
    function setupConverters() {
        const memoryConverter = document.getElementById('memory_converter');
        const diskConverter = document.getElementById('disk_converter');
        const cpuConverter = document.getElementById('cpu_converter');
        
        const memoryField = document.getElementById('memory');
        const diskField = document.getElementById('disk');
        const cpuField = document.getElementById('cpu');

        console.log('🔧 Setting up converters for edit page...');
        console.log('Fields found:', { 
            memory: !!memoryField, 
            disk: !!diskField, 
            cpu: !!cpuField,
            memoryConverter: !!memoryConverter,
            diskConverter: !!diskConverter,
            cpuConverter: !!cpuConverter
        });

        // Memory converter (GB ↔ MiB)
        if (memoryConverter && memoryField) {
            console.log('🧠 Setting up memory converter...');
            
            // GB to MiB conversion (when user types in converter)
            memoryConverter.addEventListener('input', function() {
                const gb = parseFloat(this.value);
                if (!isNaN(gb) && gb >= 0) {
                    const mib = Math.round(gb * 953.674);
                    memoryField.value = mib;
                    updateConverterStatus(memoryConverter, true);
                    console.log('🔄 Memory: ', gb, 'GB →', mib, 'MiB');
                } else {
                    updateConverterStatus(memoryConverter, false);
                    memoryField.value = '';
                }
            });

            // MiB to GB conversion (when main field changes)
            memoryField.addEventListener('input', function() {
                const mib = parseFloat(this.value);
                if (!isNaN(mib) && mib >= 0) {
                    const gb = formatNumber(mib / 953.674, 2);
                    memoryConverter.value = gb;
                    console.log('🔄 Memory: ', mib, 'MiB →', gb, 'GB');
                } else {
                    memoryConverter.value = '';
                }
            });

            // Converters start empty - user can use them as needed
            console.log('💾 Memory converter ready - starts empty');
        }

        // Disk converter (GB ↔ MB)
        if (diskConverter && diskField) {
            console.log('💽 Setting up disk converter...');
            
            // GB to MB conversion (when user types in converter)
            diskConverter.addEventListener('input', function() {
                const gb = parseFloat(this.value);
                if (!isNaN(gb) && gb >= 0) {
                    const mb = Math.round(gb * 1024);
                    diskField.value = mb;
                    updateConverterStatus(diskConverter, true);
                    console.log('🔄 Disk: ', gb, 'GB →', mb, 'MB');
                } else {
                    updateConverterStatus(diskConverter, false);
                    diskField.value = '';
                }
            });

            // MB to GB conversion (when main field changes)
            diskField.addEventListener('input', function() {
                const mb = parseFloat(this.value);
                if (!isNaN(mb) && mb >= 0) {
                    const gb = formatNumber(mb / 1024, 2);
                    diskConverter.value = gb;
                    console.log('🔄 Disk: ', mb, 'MB →', gb, 'GB');
                } else {
                    diskConverter.value = '';
                }
            });

            // Converters start empty - user can use them as needed
            console.log('💾 Disk converter ready - starts empty');
        }

        // CPU converter (cores ↔ percentage)
        if (cpuConverter && cpuField) {
            console.log('⚡ Setting up CPU converter...');
            
            // Cores to percentage conversion (when user types in converter)
            cpuConverter.addEventListener('input', function() {
                const cores = parseFloat(this.value);
                if (!isNaN(cores) && cores >= 0) {
                    const percentage = Math.round(cores * 100);
                    cpuField.value = percentage;
                    updateConverterStatus(cpuConverter, true);
                    console.log('🔄 CPU: ', cores, 'cores →', percentage, '%');
                } else {
                    updateConverterStatus(cpuConverter, false);
                    cpuField.value = '';
                }
            });

            // Percentage to cores conversion (when main field changes)
            cpuField.addEventListener('input', function() {
                const percentage = parseFloat(this.value);
                if (!isNaN(percentage) && percentage >= 0) {
                    const cores = formatNumber(percentage / 100, 1);
                    cpuConverter.value = cores;
                    console.log('🔄 CPU: ', percentage, '% →', cores, 'cores');
                } else {
                    cpuConverter.value = '';
                }
            });

            // Converters start empty - user can use them as needed
            console.log('💾 CPU converter ready - starts empty');
        }
    }

    function updateConverterStatus(element, isValid) {
        const parent = element.closest('.mb-4');
        if (isValid) {
            parent.classList.remove('has-error');
            parent.classList.add('has-success');
        } else {
            parent.classList.remove('has-success');
            parent.classList.add('has-error');
        }
    }

    // Billing Cycles Management
    function setupBillingCycles() {
        let cycleCount = {{ count(is_array($plan->billing_cycles) ? $plan->billing_cycles : []) }};
        console.log('🔄 Setting up billing cycles management, current count:', cycleCount);

        // Add cycle button handler - vanilla JS
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('add-cycle') || e.target.closest('.add-cycle')) {
                console.log('➕ Add cycle button clicked');
                
                const cycleHtml = `
                    <div class="billing-cycle-item">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-4">
                                    <label class="form-label">Billing Cycle</label>
                                    <select name="billing_cycles[${cycleCount}][cycle]" class="form-select">
                                        <option value="monthly">Monthly</option>
                                        <option value="quarterly">Quarterly</option>
                                        <option value="semi_annually">Semi-Annually</option>
                                        <option value="annually">Annually</option>
                                        <option value="one_time">One Time</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-4">
                                    <label class="form-label">Price ({{ $currencySymbol }})</label>
                                    <input type="number" name="billing_cycles[${cycleCount}][price]" class="form-control" 
                                           step="0.01" min="0" value="9.99">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-4">
                                    <label class="form-label">Setup Fee ({{ $currencySymbol }})</label>
                                    <input type="number" name="billing_cycles[${cycleCount}][setup_fee]" class="form-control" 
                                           step="0.01" min="0" value="0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-4">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="button" class="btn btn-danger btn-sm remove-cycle" style="display: block;">
                                        <i class="fa fa-minus"></i> Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                const billingCyclesContainer = document.getElementById('billing-cycles');
                if (billingCyclesContainer) {
                    billingCyclesContainer.insertAdjacentHTML('beforeend', cycleHtml);
                    cycleCount++;
                    console.log('✅ Added new billing cycle, count now:', cycleCount);
                } else {
                    console.error('❌ Could not find billing-cycles container');
                }
            }
        });

        // Remove cycle button handler - vanilla JS
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-cycle') || e.target.closest('.remove-cycle')) {
                console.log('🗑️ Remove cycle button clicked');
                
                const cycleItem = e.target.closest('.billing-cycle-item');
                if (cycleItem) {
                    cycleItem.remove();
                    console.log('✅ Removed billing cycle item');
                } else {
                    console.error('❌ Could not find billing cycle item to remove');
                }
            }
        });
    }
</script>
@endsection
