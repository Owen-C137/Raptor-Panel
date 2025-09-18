@extends('layouts.admin')

@section('title')
    Create Plan
@endsection

@push('head-scripts')
    <style>
        .converter-section .block-content .mb-4.is-valid .form-control {
            border-color: #00a65a;
        }
        
        .converter-section .block-content .mb-4.is-invalid .form-control {
            border-color: #dd4b39;
        }
        
        .converter-section .block-content .mb-4.is-valid .form-text {
            color: #00a65a;
        }
        
        .converter-section .block-content .mb-4.is-invalid .form-text {
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
          Create Plan add a new hosting plan
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          add a new hosting plan
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.plans.index') }}">Plans</a></li>
          <li class="breadcrumb-item" aria-current="page">Create</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <form method="POST" action="{{ route('admin.shop.plans.store') }}">
            @csrf
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Plan Information</h3>
                </div>
                
                <div class="block-content">
                    <div class="mb-4">
                        <label for="name" class="form-label">Plan Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" 
                               value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="4">{{ old('description') }}</textarea>
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
                                                {{ old('category_id') == $category->id ? 'selected' : '' }}>
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
                                       value="{{ old('sort_order', 0) }}" min="0">
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
                        <div class="billing-cycle-item">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-4">
                                        <label class="form-label">Billing Cycle</label>
                                        <select name="billing_cycles[0][cycle]" class="form-select">
                                            <option value="monthly" {{ old('billing_cycles.0.cycle') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                            <option value="quarterly" {{ old('billing_cycles.0.cycle') == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                            <option value="semi_annually" {{ old('billing_cycles.0.cycle') == 'semi_annually' ? 'selected' : '' }}>Semi-Annually</option>
                                            <option value="annually" {{ old('billing_cycles.0.cycle') == 'annually' ? 'selected' : '' }}>Annually</option>
                                            <option value="one_time" {{ old('billing_cycles.0.cycle') == 'one_time' ? 'selected' : '' }}>One Time</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-4">
                                        <label class="form-label">Price ({{ $currencySymbol }})</label>
                                        <input type="number" name="billing_cycles[0][price]" class="form-control" 
                                               step="0.01" min="0" value="{{ old('billing_cycles.0.price', '9.99') }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-4">
                                        <label class="form-label">Setup Fee ({{ $currencySymbol }})</label>
                                        <input type="number" name="billing_cycles[0][setup_fee]" class="form-control" 
                                               step="0.01" min="0" value="{{ old('billing_cycles.0.setup_fee', '0') }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-4">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="button" class="btn btn-success btn-sm add-cycle d-block">
                                            <i class="fa fa-plus"></i> Add Cycle
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Server Resources</h3>
                </div>
                
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="cpu" class="form-label">CPU Percentage</label>
                                <input type="number" name="server_limits[cpu]" id="cpu" class="form-control" 
                                       value="{{ old('server_limits.cpu') }}" min="0" placeholder="100">
                                <div class="form-text text-muted">
                                    CPU percentage (100 = 1 core)
                                </div>
                                @error('server_limits.cpu')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="memory" class="form-label">Memory (MiB)</label>
                                <input type="number" name="server_limits[memory]" id="memory" class="form-control" 
                                       value="{{ old('server_limits.memory') }}" min="0" placeholder="1024">
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
                                       value="{{ old('server_limits.disk') }}" min="0" placeholder="5120">
                                @error('server_limits.disk')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="swap" class="form-label">Swap Memory (MB)</label>
                                <input type="number" name="server_limits[swap]" id="swap" class="form-control" 
                                       value="{{ old('server_limits.swap', 0) }}" min="-1">
                                <div class="form-text text-muted">
                                    Set to -1 for unlimited swap
                                </div>
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
                                       value="{{ old('server_limits.io', 500) }}" min="10" max="1000">
                                @error('server_limits.io')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="oom_disabled" class="form-label">OOM Killer</label>
                                <select name="server_limits[oom_disabled]" id="oom_disabled" class="form-select">
                                    <option value="0" {{ old('server_limits.oom_disabled', '0') == '0' ? 'selected' : '' }}>Enabled</option>
                                    <option value="1" {{ old('server_limits.oom_disabled') == '1' ? 'selected' : '' }}>Disabled</option>
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
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="databases" class="form-label">Database Limit</label>
                                <input type="number" name="server_feature_limits[databases]" id="databases" class="form-control" 
                                       value="{{ old('server_feature_limits.databases', 1) }}" min="0">
                                @error('server_feature_limits.databases')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="allocations" class="form-label">Allocation Limit</label>
                                <input type="number" name="server_feature_limits[allocations]" id="allocations" class="form-control" 
                                       value="{{ old('server_feature_limits.allocations', 1) }}" min="0">
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
                                       value="{{ old('server_feature_limits.backups', 1) }}" min="0">
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
                                <option value="{{ $egg->id }}" {{ old('egg_id') == $egg->id ? 'selected' : '' }}>
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
                                <option value="{{ $location->id }}" 
                                        {{ in_array($location->id, old('allowed_locations', [])) ? 'selected' : '' }}>
                                    {{ $location->long ?: $location->short }} ({{ $location->short }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text text-muted">
                            Leave empty to allow all locations. Hold Ctrl/Cmd to select multiple.
                        </div>
                        @error('allowed_locations')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label for="allowed_nodes" class="form-label">Allowed Nodes</label>
                        <select name="allowed_nodes[]" id="allowed_nodes" class="form-select" multiple>
                            @foreach($nodes as $node)
                                <option value="{{ $node->id }}" 
                                        {{ in_array($node->id, old('allowed_nodes', [])) ? 'selected' : '' }}>
                                    {{ $node->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text text-muted">
                            Leave empty to allow all nodes. Hold Ctrl/Cmd to select multiple.
                        </div>
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
                            <input type="checkbox" class="form-check-input" id="visible" name="visible" value="1" 
                                   {{ old('visible', true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-medium" for="visible">Visible to Customers</label>
                        </div>
                        <div class="form-text text-muted">
                            Hidden plans are not shown in the shop but existing orders remain active.
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="block block-rounded">
                <div class="block-content block-content-full">
                    <button type="submit" class="btn btn-success me-1">
                        <i class="fa fa-save me-1"></i> Create Plan
                    </button>
                    <a href="{{ route('admin.shop.plans.index') }}" class="btn btn-alt-secondary">
                        <i class="fa fa-arrow-left me-1"></i> Back to Plans
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="col-md-4">
        <div class="block block-rounded converter-section">
            <div class="block-header block-header-default">
                <h3 class="block-title">Resource Converters</h3>
                <div class="block-options">
                    <button type="button" class="btn-block-option" data-toggle="block-option" data-action="content_toggle">
                        <i class="fa fa-minus"></i>
                    </button>
                </div>
            </div>
            
            <div class="block-content">
                <div class="alert alert-success d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fa fa-calculator"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="alert-heading mb-1">Quick Converters</h4>
                        <p class="mb-0 text-muted">Use these converters to easily calculate resource values.</p>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="memory_converter" class="form-label">Memory (GB)</label>
                    <div class="input-group input-group-sm">
                        <input type="number" id="memory_converter" class="form-control" 
                               placeholder="Enter GB" min="0" step="0.1">
                        <span class="input-group-text">GB</span>
                    </div>
                    <div class="form-text text-success" id="memory_conversion_result">
                        <i class="fa fa-arrow-right"></i> <span id="memory_result_text">Enter GB to see MiB conversion</span>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="disk_converter" class="form-label">Disk Space (GB)</label>
                    <div class="input-group input-group-sm">
                        <input type="number" id="disk_converter" class="form-control" 
                               placeholder="Enter GB" min="0" step="0.1">
                        <span class="input-group-text">GB</span>
                    </div>
                    <div class="form-text text-success" id="disk_conversion_result">
                        <i class="fa fa-arrow-right"></i> <span id="disk_result_text">Enter GB to see MB conversion</span>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="cpu_converter" class="form-label">CPU Cores</label>
                    <div class="input-group input-group-sm">
                        <input type="number" id="cpu_converter" class="form-control" 
                               placeholder="Enter cores" min="0" step="0.1">
                        <span class="input-group-text">Cores</span>
                    </div>
                    <div class="form-text text-success" id="cpu_conversion_result">
                        <i class="fa fa-arrow-right"></i> <span id="cpu_result_text">Enter cores to see % conversion</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Plan Guidelines</h3>
            </div>
            <div class="block-content">
                <div class="alert alert-info d-flex align-items-start">
                    <div class="flex-shrink-0">
                        <i class="fa fa-info-circle"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="alert-heading mb-2">Tips</h4>
                        <ul class="mb-0">
                            <li><strong>Pricing:</strong> Add multiple billing cycles for discounts</li>
                            <li><strong>Resources:</strong> Use converters for easy calculations</li>
                            <li><strong>Limits:</strong> Set realistic limits based on your infrastructure</li>
                            <li><strong>Visibility:</strong> Hide plans during testing</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('footer-scripts')
    @parent
<script>
    $(document).ready(function() {
        setupConverters();
        setupBillingCycles();
    });

    // Helper function to format numbers without unnecessary decimals
    function formatNumber(num, maxDecimals = 2) {
        if (num % 1 === 0) {
            return num.toString();
        }
        return parseFloat(num.toFixed(maxDecimals)).toString();
    }

    // Resource Converters
    function setupConverters() {
        const memoryConverter = document.getElementById('memory_converter');
        const diskConverter = document.getElementById('disk_converter');
        const cpuConverter = document.getElementById('cpu_converter');
        
        const memoryField = document.getElementById('memory');
        const diskField = document.getElementById('disk');
        const cpuField = document.getElementById('cpu');

        // Memory converter (GB to MiB)
        if (memoryConverter && memoryField) {
            const memoryResultText = document.getElementById('memory_result_text');
            
            memoryConverter.addEventListener('input', function() {
                const gb = parseFloat(this.value);
                if (!isNaN(gb) && gb >= 0) {
                    const mib = Math.round(gb * 953.674); // 1 GB = 953.674 MiB
                    memoryField.value = mib;
                    updateConverterStatus(memoryConverter, true);
                    
                    if (memoryResultText) {
                        const message = gb > 0 ? `${formatNumber(gb)} GB = ${mib} MiB (applied to Memory field)` : '0 GB = 0 MiB';
                        memoryResultText.textContent = message;
                    }
                } else {
                    updateConverterStatus(memoryConverter, false);
                    if (memoryResultText) {
                        memoryResultText.textContent = 'Enter valid GB to see MiB conversion';
                    }
                }
            });

            // Reverse conversion (MiB to GB)
            memoryField.addEventListener('input', function() {
                const mib = parseFloat(this.value);
                if (!isNaN(mib) && mib >= 0) {
                    const gb = formatNumber(mib / 953.674, 2);
                    if (memoryConverter.value !== gb) {
                        memoryConverter.value = gb;
                        if (memoryResultText) {
                            memoryResultText.textContent = `${gb} GB = ${formatNumber(mib, 0)} MiB`;
                        }
                    }
                }
            });
        }

        // Disk converter (GB to MB)
        if (diskConverter && diskField) {
            const diskResultText = document.getElementById('disk_result_text');
            
            diskConverter.addEventListener('input', function() {
                const gb = parseFloat(this.value);
                if (!isNaN(gb) && gb >= 0) {
                    const mb = Math.round(gb * 1000); // 1 GB = 1000 MB
                    diskField.value = mb;
                    updateConverterStatus(diskConverter, true);
                    
                    if (diskResultText) {
                        const message = gb > 0 ? `${formatNumber(gb)} GB = ${mb} MB (applied to Disk field)` : '0 GB = 0 MB';
                        diskResultText.textContent = message;
                    }
                } else {
                    updateConverterStatus(diskConverter, false);
                    if (diskResultText) {
                        diskResultText.textContent = 'Enter valid GB to see MB conversion';
                    }
                }
            });

            // Reverse conversion (MB to GB)
            diskField.addEventListener('input', function() {
                const mb = parseFloat(this.value);
                if (!isNaN(mb) && mb >= 0) {
                    const gb = formatNumber(mb / 1000, 2);
                    if (diskConverter.value !== gb) {
                        diskConverter.value = gb;
                        if (diskResultText) {
                            diskResultText.textContent = `${gb} GB = ${formatNumber(mb, 0)} MB`;
                        }
                    }
                }
            });
        }

        // CPU converter (cores to percentage)
        if (cpuConverter && cpuField) {
            const cpuResultText = document.getElementById('cpu_result_text');
            
            cpuConverter.addEventListener('input', function() {
                const cores = parseFloat(this.value);
                if (!isNaN(cores) && cores >= 0) {
                    const percentage = Math.round(cores * 100); // 1 core = 100%
                    cpuField.value = percentage;
                    updateConverterStatus(cpuConverter, true);
                    
                    if (cpuResultText) {
                        const message = cores > 0 ? `${formatNumber(cores)} cores = ${percentage}% (applied to CPU field)` : '0 cores = 0%';
                        cpuResultText.textContent = message;
                    }
                } else {
                    updateConverterStatus(cpuConverter, false);
                    if (cpuResultText) {
                        cpuResultText.textContent = 'Enter valid cores to see % conversion';
                    }
                }
            });

            // Reverse conversion (% to cores)
            cpuField.addEventListener('input', function() {
                const percentage = parseFloat(this.value);
                if (!isNaN(percentage) && percentage >= 0) {
                    const cores = formatNumber(percentage / 100, 1);
                    if (cpuConverter.value !== cores) {
                        cpuConverter.value = cores;
                        if (cpuResultText) {
                            cpuResultText.textContent = `${cores} cores = ${formatNumber(percentage, 0)}%`;
                        }
                    }
                }
            });
        }
    }

    function updateConverterStatus(element, isValid) {
        const parent = element.closest('.mb-4');
        const helpText = parent.querySelector('.form-text');
        
        if (isValid) {
            parent.classList.remove('is-invalid');
            parent.classList.add('is-valid');
            element.style.borderColor = '#00a65a';
            if (helpText) {
                helpText.style.color = '#00a65a';
            }
        } else {
            parent.classList.remove('is-valid');
            parent.classList.add('is-invalid');
            element.style.borderColor = '#dd4b39';
            if (helpText) {
                helpText.style.color = '#dd4b39';
            }
        }
    }

    // Billing Cycles Management
    function setupBillingCycles() {
        let cycleCount = 1;

        $(document).on('click', '.add-cycle', function() {
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
                                       step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-4">
                                <label class="form-label">Setup Fee ({{ $currencySymbol }})</label>
                                <input type="number" name="billing_cycles[${cycleCount}][setup_fee]" class="form-control" 
                                       step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-4">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-danger btn-sm remove-cycle d-block">
                                    <i class="fa fa-minus"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('#billing-cycles').append(cycleHtml);
            cycleCount++;
        });

        $(document).on('click', '.remove-cycle', function() {
            $(this).closest('.billing-cycle-item').remove();
        });
    }
</script>
@endsection
