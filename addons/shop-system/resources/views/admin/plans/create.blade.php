@extends('layouts.admin')

@section('title')
    Create Plan
@endsection

@push('head-scripts')
    <style>
        .converter-section .box-body .form-group.has-success .form-control {
            border-color: #00a65a;
        }
        
        .converter-section .box-body .form-group.has-error .form-control {
            border-color: #dd4b39;
        }
        
        .converter-section .box-body .form-group.has-success .form-text {
            color: #00a65a;
        }
        
        .converter-section .box-body .form-group.has-error .form-text {
            color: #dd4b39;
        }
        
        .converter-section .callout-success {
            border-left-color: #00a65a;
        }
        
        .converter-section .input-group-sm .form-control {
            height: 30px;
        }
    </style>
@endpush

@section('content-header')
    <h1>Create Plan <small>add a new hosting plan</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
        <li><a href="{{ route('admin.shop.plans.index') }}">Plans</a></li>
        <li class="active">Create</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <form method="POST" action="{{ route('admin.shop.plans.store') }}">
            @csrf
            
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Plan Information</h3>
                </div>
                
                <div class="box-body">
                    <div class="form-group">
                        <label for="name">Plan Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" 
                               value="{{ old('name') }}" required>
                        @error('name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                        @error('description')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="category_id">Game Category <span class="text-danger">*</span></label>
                                <select name="category_id" id="category_id" class="form-control" required>
                                    <option value="">Select a game category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" 
                                                {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sort_order">Sort Order</label>
                                <input type="number" name="sort_order" id="sort_order" class="form-control" 
                                       value="{{ old('sort_order', 0) }}" min="0">
                                @error('sort_order')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Pricing & Billing</h3>
                </div>
                
                <div class="box-body">
                    <div id="billing-cycles">
                        <div class="billing-cycle-item">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Billing Cycle</label>
                                        <select name="billing_cycles[0][cycle]" class="form-control">
                                            <option value="monthly" {{ old('billing_cycles.0.cycle') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                            <option value="quarterly" {{ old('billing_cycles.0.cycle') == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                            <option value="semi_annually" {{ old('billing_cycles.0.cycle') == 'semi_annually' ? 'selected' : '' }}>Semi-Annually</option>
                                            <option value="annually" {{ old('billing_cycles.0.cycle') == 'annually' ? 'selected' : '' }}>Annually</option>
                                            <option value="one_time" {{ old('billing_cycles.0.cycle') == 'one_time' ? 'selected' : '' }}>One Time</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Price ($)</label>
                                        <input type="number" name="billing_cycles[0][price]" class="form-control" 
                                               step="0.01" min="0" value="{{ old('billing_cycles.0.price', '9.99') }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Setup Fee ($)</label>
                                        <input type="number" name="billing_cycles[0][setup_fee]" class="form-control" 
                                               step="0.01" min="0" value="{{ old('billing_cycles.0.setup_fee', '0') }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="button" class="btn btn-success btn-sm add-cycle" style="display: block;">
                                            <i class="fa fa-plus"></i> Add Cycle
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Server Resources</h3>
                </div>
                
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cpu">CPU Percentage</label>
                                <input type="number" name="server_limits[cpu]" id="cpu" class="form-control" 
                                       value="{{ old('server_limits.cpu') }}" min="0" placeholder="100">
                                <small class="form-text text-muted">
                                    CPU percentage (100 = 1 core)
                                </small>
                                @error('server_limits.cpu')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="memory">Memory (MiB)</label>
                                <input type="number" name="server_limits[memory]" id="memory" class="form-control" 
                                       value="{{ old('server_limits.memory') }}" min="0" placeholder="1024">
                                @error('server_limits.memory')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="disk">Disk Space (MB)</label>
                                <input type="number" name="server_limits[disk]" id="disk" class="form-control" 
                                       value="{{ old('server_limits.disk') }}" min="0" placeholder="5120">
                                @error('server_limits.disk')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="swap">Swap Memory (MB)</label>
                                <input type="number" name="server_limits[swap]" id="swap" class="form-control" 
                                       value="{{ old('server_limits.swap', 0) }}" min="-1">
                                <small class="form-text text-muted">
                                    Set to -1 for unlimited swap
                                </small>
                                @error('server_limits.swap')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="io">IO Priority</label>
                                <input type="number" name="server_limits[io]" id="io" class="form-control" 
                                       value="{{ old('server_limits.io', 500) }}" min="10" max="1000">
                                @error('server_limits.io')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="oom_disabled">OOM Killer</label>
                                <select name="server_limits[oom_disabled]" id="oom_disabled" class="form-control">
                                    <option value="0" {{ old('server_limits.oom_disabled', '0') == '0' ? 'selected' : '' }}>Enabled</option>
                                    <option value="1" {{ old('server_limits.oom_disabled') == '1' ? 'selected' : '' }}>Disabled</option>
                                </select>
                                @error('server_limits.oom_disabled')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Feature Limits</h3>
                </div>
                
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="databases">Database Limit</label>
                                <input type="number" name="server_feature_limits[databases]" id="databases" class="form-control" 
                                       value="{{ old('server_feature_limits.databases', 1) }}" min="0">
                                @error('server_feature_limits.databases')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="allocations">Allocation Limit</label>
                                <input type="number" name="server_feature_limits[allocations]" id="allocations" class="form-control" 
                                       value="{{ old('server_feature_limits.allocations', 1) }}" min="0">
                                @error('server_feature_limits.allocations')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="backups">Backup Limit</label>
                                <input type="number" name="server_feature_limits[backups]" id="backups" class="form-control" 
                                       value="{{ old('server_feature_limits.backups', 1) }}" min="0">
                                @error('server_feature_limits.backups')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Server Configuration</h3>
                </div>
                
                <div class="box-body">
                    <div class="form-group">
                        <label for="egg_id">Egg</label>
                        <select name="egg_id" id="egg_id" class="form-control">
                            <option value="">Select an egg (optional)</option>
                            @foreach($eggs as $egg)
                                <option value="{{ $egg->id }}" {{ old('egg_id') == $egg->id ? 'selected' : '' }}>
                                    {{ $egg->nest->name }} - {{ $egg->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('egg_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="allowed_locations">Allowed Locations</label>
                        <select name="allowed_locations[]" id="allowed_locations" class="form-control" multiple>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" 
                                        {{ in_array($location->id, old('allowed_locations', [])) ? 'selected' : '' }}>
                                    {{ $location->long ?: $location->short }} ({{ $location->short }})
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            Leave empty to allow all locations. Hold Ctrl/Cmd to select multiple.
                        </small>
                        @error('allowed_locations')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="allowed_nodes">Allowed Nodes</label>
                        <select name="allowed_nodes[]" id="allowed_nodes" class="form-control" multiple>
                            @foreach($nodes as $node)
                                <option value="{{ $node->id }}" 
                                        {{ in_array($node->id, old('allowed_nodes', [])) ? 'selected' : '' }}>
                                    {{ $node->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            Leave empty to allow all nodes. Hold Ctrl/Cmd to select multiple.
                        </small>
                        @error('allowed_nodes')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Plan Status</h3>
                </div>
                
                <div class="box-body">
                    <div class="form-group">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input type="checkbox" id="visible" name="visible" value="1" 
                                   {{ old('visible', true) ? 'checked' : '' }}>
                            <label for="visible" class="strong">Visible to Customers</label>
                        </div>
                        <small class="form-text text-muted">
                            Hidden plans are not shown in the shop but existing orders remain active.
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="box">
                <div class="box-footer">
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i> Create Plan
                    </button>
                    <a href="{{ route('admin.shop.plans.index') }}" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> Back to Plans
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="col-md-4">
        <div class="box converter-section">
            <div class="box-header with-border">
                <h3 class="box-title">Resource Converters</h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse">
                        <i class="fa fa-minus"></i>
                    </button>
                </div>
            </div>
            
            <div class="box-body">
                <div class="callout callout-success">
                    <h4><i class="fa fa-calculator"></i> Quick Converters</h4>
                    <p class="text-muted">Use these converters to easily calculate resource values.</p>
                </div>
                
                <div class="form-group">
                    <label for="memory_converter">Memory (GB)</label>
                    <div class="input-group input-group-sm">
                        <input type="number" id="memory_converter" class="form-control" 
                               placeholder="Enter GB" min="0" step="0.1">
                        <span class="input-group-addon">GB</span>
                    </div>
                    <small class="form-text text-success" id="memory_conversion_result">
                        <i class="fa fa-arrow-right"></i> <span id="memory_result_text">Enter GB to see MiB conversion</span>
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="disk_converter">Disk Space (GB)</label>
                    <div class="input-group input-group-sm">
                        <input type="number" id="disk_converter" class="form-control" 
                               placeholder="Enter GB" min="0" step="0.1">
                        <span class="input-group-addon">GB</span>
                    </div>
                    <small class="form-text text-success" id="disk_conversion_result">
                        <i class="fa fa-arrow-right"></i> <span id="disk_result_text">Enter GB to see MB conversion</span>
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="cpu_converter">CPU Cores</label>
                    <div class="input-group input-group-sm">
                        <input type="number" id="cpu_converter" class="form-control" 
                               placeholder="Enter cores" min="0" step="0.1">
                        <span class="input-group-addon">Cores</span>
                    </div>
                    <small class="form-text text-success" id="cpu_conversion_result">
                        <i class="fa fa-arrow-right"></i> <span id="cpu_result_text">Enter cores to see % conversion</span>
                    </small>
                </div>
            </div>
        </div>
        
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Plan Guidelines</h3>
            </div>
            <div class="box-body">
                <div class="callout callout-info">
                    <h4><i class="fa fa-info-circle"></i> Tips</h4>
                    <ul>
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
        const parent = element.closest('.form-group');
        const helpText = parent.querySelector('.form-text');
        
        if (isValid) {
            parent.classList.remove('has-error');
            parent.classList.add('has-success');
            element.style.borderColor = '#00a65a';
            if (helpText) {
                helpText.style.color = '#00a65a';
            }
        } else {
            parent.classList.remove('has-success');
            parent.classList.add('has-error');
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
                            <div class="form-group">
                                <label>Billing Cycle</label>
                                <select name="billing_cycles[${cycleCount}][cycle]" class="form-control">
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="semi_annually">Semi-Annually</option>
                                    <option value="annually">Annually</option>
                                    <option value="one_time">One Time</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Price ($)</label>
                                <input type="number" name="billing_cycles[${cycleCount}][price]" class="form-control" 
                                       step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Setup Fee ($)</label>
                                <input type="number" name="billing_cycles[${cycleCount}][setup_fee]" class="form-control" 
                                       step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-danger btn-sm remove-cycle" style="display: block;">
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
