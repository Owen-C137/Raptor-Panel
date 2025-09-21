@extends('layouts.admin')

@section('title')
    Plans
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Plans manage hosting plans
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          manage hosting plans
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
          <li class="breadcrumb-item" aria-current="page">Plans</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@push('head-styles')
<style>
    .pricing-more-link {
        cursor: pointer;
        text-decoration: underline;
    }
    
    .pricing-more-link:hover {
        text-decoration: none;
        color: #337ab7 !important;
    }
    
    .popover {
        max-width: 250px;
    }
    
    .popover-content {
        padding: 8px 12px;
    }
    
    .popover-content div {
        margin-bottom: 4px;
        line-height: 1.2;
    }
    
    .popover-content div:last-child {
        margin-bottom: 0;
    }
    
    /* Server link styling */
    .server-link {
        display: inline-block;
        padding: 2px 6px;
        margin: 1px 0;
        background-color: #f5f5f5;
        border-radius: 3px;
        color: #337ab7;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    
    .server-link:hover {
        background-color: #337ab7;
        color: white;
        text-decoration: none;
    }
    
    .server-link:focus {
        color: white;
        text-decoration: none;
    }
    
    .server-link i {
        margin-right: 4px;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="block block-rounded">
            <div class="block-header">
                <h3 class="block-title">Plans</h3>
                <div class="block-options">
                    <a href="{{ route('admin.shop.plans.create') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" title="Create New Plan">
                        <i class="fa fa-plus me-1"></i> Create Plan
                    </a>
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#importPlansModal" title="Import Plans from JSON File">
                        <i class="fa fa-upload me-1"></i> Import Plans
                    </button>
                </div>
            </div>
            
            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible d-flex align-items-center" role="alert">
                    <div class="flex-shrink-0">
                        <i class="fa fa-check"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        {{ session('success') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible d-flex align-items-center" role="alert">
                    <div class="flex-shrink-0">
                        <i class="fa fa-exclamation-triangle"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        {{ session('error') }}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            <!-- Search and Filter Form -->
            <div class="block-content">
                <form method="GET" action="{{ route('admin.shop.plans.index') }}" class="row">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Search plans..." 
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-search me-1"></i> Search
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('admin.shop.plans.index') }}" class="btn btn-secondary">
                            <i class="fa fa-refresh me-1"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="block block-rounded">
            <div class="block-content">
                <!-- Batch Actions Bar -->
                <div class="row mb-3" id="batchActionsBar" style="display: none;">
                    <div class="col-md-12">
                        <div class="alert alert-info d-flex align-items-center mb-0">
                            <div class="flex-grow-1">
                                <span id="selectedCount">0</span> plans selected
                            </div>
                            <div class="flex-shrink-0">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-success" 
                                            onclick="batchAction('activate')"
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="top" 
                                            title="Activate Selected Plans">
                                        <i class="fa fa-check me-1"></i> Activate Selected
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            onclick="batchAction('deactivate')"
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="top" 
                                            title="Deactivate Selected Plans">
                                        <i class="fa fa-pause me-1"></i> Deactivate Selected
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="batchAction('delete')"
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="top" 
                                            title="Delete Selected Plans">
                                        <i class="fa fa-trash me-1"></i> Delete Selected
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary" 
                                            onclick="clearSelection()"
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="top" 
                                            title="Clear All Selections">
                                        <i class="fa fa-times me-1"></i> Clear Selection
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @if($plans->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-vcenter">
                            <thead>
                                <tr>
                                    <th width="30px">
                                        <input type="checkbox" id="master-checkbox" data-bs-toggle="tooltip" data-bs-placement="top" title="Select All Plans">
                                    </th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Server(s)</th>
                                    <th>Pricing</th>
                                    <th>Resources</th>
                                    <th>Status</th>
                                    <th>Sort</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($plans as $plan)
                                <tr data-plan-id="{{ $plan->id }}">
                                    <td>
                                        <input type="checkbox" class="plan-checkbox" value="{{ $plan->id }}" 
                                               data-name="{{ $plan->name }}"
                                               data-bs-toggle="tooltip" 
                                               data-bs-placement="right" 
                                               title="Select {{ $plan->name }}">
                                    </td>
                                    <td>
                                        <strong>{{ $plan->name }}</strong>
                                        @if($plan->description)
                                            <br><small class="text-muted">{{ Str::limit($plan->description, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($plan->category)
                                            <span class="fs-xs fw-semibold d-inline-block py-1 px-3 rounded-pill bg-primary-light text-primary">{{ $plan->category->name }}</span>
                                        @else
                                            <span class="text-muted">No Category</span>
                                        @endif
                                    </td>
                                    <td class="server-links">
                                        @if($plan->associatedServers && $plan->associatedServers->count() > 0)
                                            @foreach($plan->associatedServers->take(3) as $server)
                                                <div style="margin-bottom: 2px;">
                                                    <a href="{{ route('admin.servers.view', $server->id) }}" 
                                                       class="server-link" 
                                                       data-toggle="tooltip" 
                                                       data-placement="right" 
                                                       title="View Server: {{ $server->name }}">
                                                        <i class="fa fa-server"></i>{{ Str::limit($server->name, 18) }}
                                                    </a>
                                                </div>
                                            @endforeach
                                            @if($plan->associatedServers->count() > 3)
                                                <small class="text-muted">
                                                    <i class="fa fa-plus-circle"></i> {{ $plan->associatedServers->count() - 3 }} more
                                                </small>
                                            @endif
                                        @else
                                            <span class="text-muted">
                                                <i class="fa fa-server"></i> No servers
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $cycles = is_array($plan->billing_cycles) ? $plan->billing_cycles : [];
                                            $monthly = collect($cycles)->where('cycle', 'monthly')->first();
                                        @endphp
                                        @if($monthly)
                                            <strong>{{ $currencySymbol }}{{ number_format($monthly['price'], 2) }}/mo</strong>
                                            @if(count($cycles) > 1)
                                                @php
                                                    $otherCycles = collect($cycles)->reject(function($cycle) {
                                                        return $cycle['cycle'] === 'monthly';
                                                    });
                                                    
                                                    $cycleLabels = [
                                                        'hourly' => 'hr',
                                                        'monthly' => 'mo', 
                                                        'quarterly' => '3mo',
                                                        'semi_annually' => '6mo',
                                                        'annually' => 'yr',
                                                        'one_time' => 'once'
                                                    ];
                                                    
                                                    $popoverContent = '';
                                                    foreach($otherCycles as $cycle) {
                                                        $label = $cycleLabels[$cycle['cycle']] ?? $cycle['cycle'];
                                                        $price = number_format($cycle['price'], 2);
                                                        $setup = isset($cycle['setup_fee']) && $cycle['setup_fee'] > 0 
                                                            ? ' (+' . $currencySymbol . number_format($cycle['setup_fee'], 2) . ' setup)'
                                                            : '';
                                                        $popoverContent .= "<div><strong>" . $currencySymbol . $price . "/{$label}</strong>{$setup}</div>";
                                                    }
                                                @endphp
                                                <br><small>
                                                    <a href="javascript:void(0)" 
                                                       class="text-primary pricing-more-link" 
                                                       data-bs-toggle="popover" 
                                                       data-bs-placement="top"
                                                       data-bs-html="true"
                                                       data-bs-trigger="click"
                                                       data-bs-content="{!! htmlspecialchars($popoverContent) !!}"
                                                       title="All Pricing Options">
                                                        +{{ count($cycles) - 1 }} more
                                                    </a>
                                                </small>
                                            @endif
                                        @else
                                            <span class="text-muted">No pricing set</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $limits = is_array($plan->server_limits) ? $plan->server_limits : [];
                                            
                                            // Convert MiB to GB for display
                                            // 1 MiB = 1.048576 MB, 1 GB = 1000 MB
                                            $memoryGB = isset($limits['memory']) ? round(($limits['memory'] * 1.048576) / 1000, 1) : 0;
                                            $diskGB = isset($limits['disk']) ? round(($limits['disk'] * 1.048576) / 1000, 1) : 0;
                                        @endphp
                                        @if(!empty($limits))
                                            <small>
                                                {{ $memoryGB }}GB RAM<br>
                                                {{ $diskGB }}GB Disk<br>
                                                {{ $limits['cpu'] ?? 0 }}% CPU
                                            </small>
                                        @else
                                            <span class="text-muted">No limits set</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($plan->status === 'active')
                                            <span class="fs-xs fw-semibold d-inline-block py-1 px-3 rounded-pill bg-success-light text-success" data-status="active">Active</span>
                                        @elseif($plan->status === 'inactive')
                                            <span class="fs-xs fw-semibold d-inline-block py-1 px-3 rounded-pill bg-danger-light text-danger" data-status="inactive">Inactive</span>
                                        @else
                                            <span class="fs-xs fw-semibold d-inline-block py-1 px-3 rounded-pill bg-secondary-light text-secondary" data-status="archived">Archived</span>
                                        @endif
                                    </td>
                                    <td>{{ $plan->sort_order }}</td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="{{ route('admin.shop.plans.show', $plan->id) }}" 
                                               class="btn btn-sm btn-primary" 
                                               data-bs-toggle="tooltip" 
                                               data-bs-placement="top" 
                                               title="View Plan Details">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.shop.plans.edit', $plan->id) }}" 
                                               class="btn btn-sm btn-warning" 
                                               data-bs-toggle="tooltip" 
                                               data-bs-placement="top" 
                                               title="Edit Plan">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                            @if($plan->status === 'active')
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="showToggleModal({{ $plan->id }}, 'active', '{{ $plan->name }}')" 
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="top" 
                                                        title="Deactivate Plan">
                                                    <i class="fa fa-pause"></i>
                                                </button>
                                            @else
                                                <button class="btn btn-sm btn-success" 
                                                        onclick="showToggleModal({{ $plan->id }}, 'inactive', '{{ $plan->name }}')" 
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="top" 
                                                        title="Activate Plan">
                                                    <i class="fa fa-play"></i>
                                                </button>
                                            @endif
                                            <button class="btn btn-sm btn-info" 
                                                    onclick="showDuplicateModal({{ $plan->id }}, '{{ $plan->name }}')" 
                                                    data-bs-toggle="tooltip" 
                                                    data-bs-placement="top" 
                                                    title="Duplicate Plan">
                                                <i class="fa fa-copy"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="showDeleteModal({{ $plan->id }}, '{{ $plan->name }}')" 
                                                    data-bs-toggle="tooltip" 
                                                    data-bs-placement="top" 
                                                    title="Delete Plan">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="fa fa-list-alt fa-3x text-muted"></i>
                        </div>
                        <h4 class="fw-normal text-muted">No Plans Found</h4>
                        <p class="fs-sm text-muted">Get started by creating your first hosting plan.</p>
                        <a href="{{ route('admin.shop.plans.create') }}" class="btn btn-primary">
                            <i class="fa fa-plus me-1"></i> Create Plan
                        </a>
                    </div>
                @endif
            @if($plans->hasPages())
                <div class="block-content border-top">
                    <div class="text-center">
                        {{ $plans->appends(request()->query())->links() }}
                    </div>
                </div>
            @endif
            </div>
        </div>
    </div>
</div>

<!-- Toggle Status Modal -->
<div class="modal fade" id="toggleModal" tabindex="-1" role="dialog" aria-labelledby="toggleModal" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title" id="toggleModalTitle">Confirm Action</h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa fa-fw fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content fs-sm">
                    <p id="toggleModalMessage">Are you sure you want to perform this action?</p>
                </div>
                <div class="block-content block-content-full text-end bg-body">
                    <button type="button" class="btn btn-sm btn-alt-secondary me-1" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-primary" id="confirmToggleBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Plan Modal -->
<div class="modal fade" id="deletePlanModal" tabindex="-1" role="dialog" aria-labelledby="deletePlanModal" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Delete Plan</h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa fa-fw fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content fs-sm">
                    <p id="deleteModalMessage">Are you sure you want to delete this plan? This action cannot be undone.</p>
                    <div class="alert alert-warning d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fa fa-warning"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <strong>Warning:</strong> Plans with existing orders cannot be deleted.
                        </div>
                    </div>
                    
                    <!-- Server Deletion Section -->
                    <div id="serverDeletionSection" style="display: none;">
                        <hr>
                        <div class="alert alert-info d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fa fa-server"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <strong>Connected Servers:</strong>
                                <span id="serverCount">0 servers</span> are currently connected to this plan.
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="deleteServers" name="delete_servers" value="1" checked>
                            <label class="form-check-label fw-bold" for="deleteServers">
                                <i class="fa fa-trash text-danger me-1"></i> Also delete all connected servers
                            </label>
                        </div>
                        <div class="fs-sm text-muted mt-2">
                            <i class="fa fa-exclamation-triangle text-warning me-1"></i> 
                            <strong>Warning:</strong> This will permanently delete all servers and their data. 
                            Server backups will be preserved if configured.
                        </div>
                    </div>
                </div>
                <div class="block-content block-content-full text-end bg-body">
                    <button type="button" class="btn btn-sm btn-alt-secondary me-1" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-danger" id="confirmDelete">
                        <i class="fa fa-trash me-1"></i> Delete Plan
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Duplicate Plan Modal -->
<div class="modal fade" id="duplicatePlanModal" tabindex="-1" role="dialog" aria-labelledby="duplicatePlanModal" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-copy text-info me-1"></i> Duplicate Plan
                    </h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa fa-fw fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content fs-sm">
                    <p id="duplicateModalMessage">Are you sure you want to duplicate this plan?</p>
                    <div class="alert alert-info d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fa fa-info-circle"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <strong>Note:</strong> The duplicated plan will be created as "inactive" and you can edit it before making it available.
                        </div>
                    </div>
                </div>
                <div class="block-content block-content-full text-end bg-body">
                    <button type="button" class="btn btn-sm btn-alt-secondary me-1" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-info" id="confirmDuplicate">
                        <i class="fa fa-copy me-1"></i> Duplicate Plan
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
        .pulse-success {
            animation: pulse-green 1s ease-in-out;
        }
        
        .pulse-danger {
            animation: pulse-red 1s ease-in-out;
        }
        
        @keyframes pulse-green {
            0% { background-color: transparent; }
            50% { background-color: #d4edda; }
            100% { background-color: transparent; }
        }
        
        @keyframes pulse-red {
            0% { background-color: transparent; }
            50% { background-color: #f8d7da; }
            100% { background-color: transparent; }
        }
    </style>
    
    <script>
        // Global variables for modal actions
        let currentPlanId = null;
        let currentStatus = null;

        $(document).ready(function() {
            // Modal event handlers
            $('#confirmToggleBtn').on('click', function() {
                togglePlan(currentPlanId, currentStatus);
            });
            
            $('#confirmDelete').on('click', function() {
                deletePlan(currentPlanId);
            });
            
            $('#confirmDuplicate').on('click', function() {
                duplicatePlan(currentPlanId);
            });
            
            // Update delete button text when checkbox changes
            $('#deleteServers').on('change', function() {
                updateDeleteButtonText();
            });
            
            // Batch functionality
            // Master checkbox toggle
            $('#master-checkbox').on('change', function() {
                const isChecked = $(this).prop('checked');
                $('.plan-checkbox').prop('checked', isChecked);
                updateBatchUI();
            });
            
            // Individual checkbox change
            $(document).on('change', '.plan-checkbox', function() {
                const totalCheckboxes = $('.plan-checkbox').length;
                const checkedCheckboxes = $('.plan-checkbox:checked').length;
                
                // Update master checkbox state
                $('#master-checkbox').prop('checked', checkedCheckboxes === totalCheckboxes);
                $('#master-checkbox').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
                
                updateBatchUI();
            });
            
            // Modal confirmation handlers
            $('#confirmBatchActivate').on('click', function() {
                executeBatchAction('activate');
            });
            
            $('#confirmBatchDeactivate').on('click', function() {
                executeBatchAction('deactivate');
            });
            
            $('#confirmBatchDelete').on('click', function() {
                executeBatchAction('delete');
            });
        });

        function showToggleModal(planId, status, planName) {
            currentPlanId = planId;
            currentStatus = status;
            
            const action = status === 'active' ? 'deactivate' : 'activate';
            const btnClass = status === 'active' ? 'btn-warning' : 'btn-success';
            const icon = status === 'active' ? 'fa-pause' : 'fa-play';
            
            $('#toggleModalTitle').text(status === 'active' ? 'Deactivate Plan' : 'Activate Plan');
            $('#toggleModalMessage').html(`Are you sure you want to <strong>${action}</strong> the plan <code>${planName}</code>?`);
            $('#confirmToggleBtn').removeClass('btn-primary btn-success btn-warning').addClass(btnClass);
            $('#confirmToggleBtn').html(`<i class="fa ${icon}"></i> ${action.charAt(0).toUpperCase() + action.slice(1)}`);
            
            new bootstrap.Modal(document.getElementById('toggleModal')).show();
        }
        
        function showDeleteModal(planId, planName) {
            currentPlanId = planId;
            $('#deleteModalMessage').html(`Are you sure you want to delete the plan <code>${planName}</code>?`);
            
            // Get server information for this plan from the table data
            const planRow = $('tr[data-plan-id="' + planId + '"]');
            const serverCell = planRow.find('.server-links');
            const serverLinks = serverCell.find('a.server-link');
            const serverCount = serverLinks.length;
            
            if (serverCount > 0) {
                $('#serverCount').text(serverCount + (serverCount === 1 ? ' server' : ' servers'));
                $('#serverDeletionSection').show();
                
                // Update the delete button text to reflect server deletion
                updateDeleteButtonText();
            } else {
                $('#serverDeletionSection').hide();
            }
            
            new bootstrap.Modal(document.getElementById('deletePlanModal')).show();
        }

        function updateDeleteButtonText() {
            const deleteServers = $('#deleteServers').is(':checked');
            const serverCount = $('#serverCount').text();
            
            if ($('#serverDeletionSection').is(':visible')) {
                if (deleteServers) {
                    $('#confirmDelete').html('<i class="fa fa-trash"></i> Delete Plan & Servers');
                    $('#confirmDelete').removeClass('btn-danger').addClass('btn-danger'); // Keep danger style
                } else {
                    $('#confirmDelete').html('<i class="fa fa-trash"></i> Delete Plan Only');
                    $('#confirmDelete').removeClass('btn-danger').addClass('btn-warning'); // Change to warning
                }
            } else {
                $('#confirmDelete').html('<i class="fa fa-trash"></i> Delete Plan');
                $('#confirmDelete').removeClass('btn-warning').addClass('btn-danger'); // Reset to danger
            }
        }

        function showDuplicateModal(planId, planName) {
            currentPlanId = planId;
            $('#duplicateModalMessage').html(`Are you sure you want to duplicate the plan <code>${planName}</code>?`);
            new bootstrap.Modal(document.getElementById('duplicatePlanModal')).show();
        }

        function togglePlan(planId, status) {
            showLoadingState('#confirmToggleBtn', true);
            
            $.ajax({
                url: '{{ url('admin/shop/plans') }}/' + planId + '/toggle-status',
                type: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    bootstrap.Modal.getInstance(document.getElementById('toggleModal')).hide();
                    showLoadingState('#confirmToggleBtn', false);
                    
                    if (response.success) {
                        showAlert('success', response.message || 'Plan status updated successfully!');
                        
                        // Update the row dynamically using the status from server response
                        updatePlanRow(planId, response.status);
                    } else {
                        showAlert('error', 'Error: ' + (response.message || 'Unknown error occurred'));
                    }
                },
                error: function(xhr) {
                    bootstrap.Modal.getInstance(document.getElementById('toggleModal')).hide();
                    showLoadingState('#confirmToggleBtn', false);
                    
                    let errorMessage = 'An error occurred while updating the plan.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    showAlert('error', errorMessage);
                }
            });
        }

        function deletePlan(planId) {
            showLoadingState('#confirmDelete', true);
            
            // Check if we should also delete servers
            const deleteServers = $('#deleteServers').is(':checked');
            
            $.ajax({
                url: '{{ url('admin/shop/plans') }}/' + planId,
                type: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                data: {
                    _token: '{{ csrf_token() }}',
                    delete_servers: deleteServers ? 1 : 0
                },
                success: function(response) {
                    bootstrap.Modal.getInstance(document.getElementById('deletePlanModal')).hide();
                    showLoadingState('#confirmDelete', false);
                    
                    if (response.success) {
                        let message = 'Plan deleted successfully!';
                        if (response.servers_deleted && response.servers_deleted > 0) {
                            message += ` ${response.servers_deleted} server(s) were also deleted.`;
                        }
                        showAlert('success', message);
                        
                        // Remove the row from table with animation
                        $('tr[data-plan-id="' + planId + '"]').addClass('pulse-danger').fadeOut(500, function() {
                            $(this).remove();
                        });
                    } else {
                        showAlert('error', 'Error: ' + (response.message || 'Unknown error occurred'));
                    }
                },
                error: function(xhr) {
                    bootstrap.Modal.getInstance(document.getElementById('deletePlanModal')).hide();
                    showLoadingState('#confirmDelete', false);
                    
                    let errorMessage = 'An error occurred while deleting the plan.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    showAlert('error', errorMessage);
                }
            });
        }

        function duplicatePlan(planId) {
            showLoadingState('#confirmDuplicate', true);
            
            $.ajax({
                url: '{{ url('admin/shop/plans') }}/' + planId + '/duplicate',
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function(response) {
                    bootstrap.Modal.getInstance(document.getElementById('duplicatePlanModal')).hide();
                    showLoadingState('#confirmDuplicate', false);
                    
                    if (response.success) {
                        showAlert('success', response.message || 'Plan duplicated successfully!');
                        
                        // Redirect to edit the new plan if we have the URL
                        if (response.redirect) {
                            setTimeout(() => {
                                window.location.href = response.redirect;
                            }, 1500);
                        } else {
                            // Otherwise just reload the page to show the new plan
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        }
                    } else {
                        showAlert('error', 'Error: ' + (response.message || 'Unknown error occurred'));
                    }
                },
                error: function(xhr) {
                    bootstrap.Modal.getInstance(document.getElementById('duplicatePlanModal')).hide();
                    showLoadingState('#confirmDuplicate', false);
                    
                    let errorMessage = 'An error occurred while duplicating the plan.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    showAlert('error', errorMessage);
                }
            });
        }
        
        function updatePlanRow(planId, newStatus) {
            const $row = $('tr[data-plan-id="' + planId + '"]');
            const $statusBadge = $row.find('span[data-status]');
            const $toggleBtn = $row.find('.btn-group button[onclick*="showToggleModal"]');
            
            // Add pulse animation
            $row.addClass('pulse-success');
            
            // Update status badge
            $statusBadge.removeClass('bg-success-light text-success bg-danger-light text-danger bg-warning-light text-warning bg-secondary-light text-secondary')
                       .attr('data-status', newStatus);
            
            if (newStatus === 'active') {
                $statusBadge.addClass('bg-success-light text-success').text('Active');
            } else if (newStatus === 'inactive') {
                $statusBadge.addClass('bg-danger-light text-danger').text('Inactive');
            } else if (newStatus === 'archived') {
                $statusBadge.addClass('bg-secondary-light text-secondary').text('Archived');
            }
            
            // Update toggle button
            const planName = $toggleBtn.closest('tr').find('td:first strong').text();
            
            if (newStatus === 'active') {
                $toggleBtn.removeClass('btn-success').addClass('btn-danger')
                         .attr('onclick', `showToggleModal(${planId}, 'active', '${planName}')`)
                         .attr('title', 'Deactivate')
                         .html('<i class="fa fa-pause"></i>');
            } else {
                $toggleBtn.removeClass('btn-danger').addClass('btn-success')
                         .attr('onclick', `showToggleModal(${planId}, 'inactive', '${planName}')`)
                         .attr('title', 'Activate')
                         .html('<i class="fa fa-play"></i>');
            }
            
            // Remove animation after delay
            setTimeout(() => {
                $row.removeClass('pulse-success');
            }, 2000);
        }
        
        function showLoadingState(selector, loading) {
            if (loading) {
                $(selector).prop('disabled', true).append(' <i class="fa fa-spinner fa-spin"></i>');
            } else {
                $(selector).prop('disabled', false).find('.fa-spinner').remove();
            }
        }
        
        function showAlert(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <i class="fa ${icon}"></i> ${message}
                </div>
            `;
            
            $('body').append(alertHtml);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        // Initialize pricing popovers and tooltips
        $(document).ready(function() {
            // Initialize popovers with Bootstrap 5
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl, {
                    container: 'body'
                });
            });
            
            // Initialize tooltips with Bootstrap 5
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Close popover when clicking elsewhere
            $(document).on('click', function (e) {
                popoverList.forEach(function(popover) {
                    if (!$(popover._element).is(e.target) && $(popover._element).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
                        popover.hide();
                    }
                });
            });
        });
    </script>
@endsection

<!-- Import Plans Modal -->
<div class="modal fade" id="importPlansModal" tabindex="-1" role="dialog" aria-labelledby="importPlansModal" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-upload me-1"></i> Import Plans from JSON
                    </h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa fa-fw fa-times"></i>
                        </button>
                    </div>
                </div>
                <form id="importPlansForm" action="{{ route('admin.shop.plans.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="block-content fs-sm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="block block-rounded">
                                    <div class="block-header">
                                        <h3 class="block-title">Upload JSON File</h3>
                                    </div>
                                    <div class="block-content">
                                        <div class="mb-4">
                                            <label for="import_file" class="form-label">Select JSON File <span class="text-danger">*</span></label>
                                            <input type="file" name="import_file" id="import_file" class="form-control" accept=".json" required>
                                            <div class="fs-sm text-muted mt-1">Upload a JSON file containing an array of plans</div>
                                        </div>
                                        
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" name="overwrite_existing" value="1" id="overwrite_existing">
                                            <label class="form-check-label" for="overwrite_existing">
                                                Overwrite existing plans with same name
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        
                        <div class="col-md-6">
                            <div class="block block-rounded">
                                <div class="block-header">
                                    <h3 class="block-title">JSON Template</h3>
                                </div>
                                <div class="block-content">
                                    <p class="text-muted">Use this template to create your plans JSON file:</p>
                                    <button type="button" class="btn btn-sm btn-info" onclick="copyTemplate()" data-bs-toggle="tooltip" data-bs-placement="top" title="Copy JSON Template to Clipboard">
                                        <i class="fa fa-copy me-1"></i> Copy Template
                                    </button>
                                    <pre id="jsonTemplate" style="max-height: 400px; overflow-y: auto; padding: 15px; background-color: #f8f9fa; border-radius: 6px;"><code class="json hljs"><span class="hljs-comment">// Available Categories: {{ implode(', ', $availableCategories) }}</span>
<span class="hljs-comment">// Available Eggs: {{ implode(', ', $availableEggs) }}</span>
<span class="hljs-comment">// Available Locations: {{ implode(', ', $availableLocations) }}</span>
<span class="hljs-comment">// Available Nodes: {{ implode(', ', $availableNodes) }}</span>

[
  {
    <span class="hljs-attr">"name"</span>: <span class="hljs-string">"Basic Minecraft"</span>,
    <span class="hljs-attr">"description"</span>: <span class="hljs-string">"Perfect for small communities"</span>,
    <span class="hljs-attr">"category_name"</span>: <span class="hljs-string">"{{ $availableCategories[0] ?? 'Minecraft' }}"</span>,
    <span class="hljs-attr">"sort_order"</span>: <span class="hljs-number">0</span>,
    <span class="hljs-attr">"visible"</span>: <span class="hljs-literal">true</span>,
    <span class="hljs-attr">"billing_cycles"</span>: [
      {
        <span class="hljs-attr">"cycle"</span>: <span class="hljs-string">"monthly"</span>, 
        <span class="hljs-attr">"price"</span>: <span class="hljs-number">9.99</span>,
        <span class="hljs-attr">"setup_fee"</span>: <span class="hljs-number">0</span>
      },
      {
        <span class="hljs-attr">"cycle"</span>: <span class="hljs-string">"annually"</span>,
        <span class="hljs-attr">"price"</span>: <span class="hljs-number">99.99</span>,
        <span class="hljs-attr">"setup_fee"</span>: <span class="hljs-number">0</span>
      }
    ],
    <span class="hljs-attr">"server_limits"</span>: {
      <span class="hljs-attr">"cpu"</span>: <span class="hljs-number">100</span>,
      <span class="hljs-attr">"memory"</span>: <span class="hljs-number">2048</span>,
      <span class="hljs-attr">"disk"</span>: <span class="hljs-number">5120</span>,
      <span class="hljs-attr">"swap"</span>: <span class="hljs-number">0</span>,
      <span class="hljs-attr">"io"</span>: <span class="hljs-number">500</span>,
      <span class="hljs-attr">"oom_disabled"</span>: <span class="hljs-literal">false</span>
    },
    <span class="hljs-attr">"server_feature_limits"</span>: {
      <span class="hljs-attr">"databases"</span>: <span class="hljs-number">2</span>,
      <span class="hljs-attr">"allocations"</span>: <span class="hljs-number">1</span>,
      <span class="hljs-attr">"backups"</span>: <span class="hljs-number">3</span>
    },
    <span class="hljs-attr">"egg_name"</span>: <span class="hljs-string">"{{ $availableEggs[0] ?? 'Vanilla Minecraft' }}"</span>,
    <span class="hljs-attr">"allowed_location_names"</span>: [{{ count($availableLocations) > 0 ? '"' . implode('", "', array_slice($availableLocations, 0, 2)) . '"' : '"US East", "EU West"' }}],
    <span class="hljs-attr">"allowed_node_names"</span>: [{{ count($availableNodes) > 0 ? '"' . implode('", "', array_slice($availableNodes, 0, 2)) . '"' : '"Node-1", "Node-2"' }}]
  }
]</code></pre></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-info d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <i class="fa fa-info-circle fs-3"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="alert-heading">Import Notes</h5>
                                        <ul class="fs-sm mb-0">
                                            <li><strong>category_name:</strong> Must match existing category name exactly</li>
                                            <li><strong>egg_name:</strong> Must match existing egg name (optional field)</li>
                                            <li><strong>location_names/node_names:</strong> Must match existing names (optional)</li>
                                            <li><strong>billing_cycles:</strong> At least one cycle is required</li>
                                            <li><strong>visible:</strong> true/false - whether plan appears in shop</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="block-content block-content-full text-end bg-body">
                        <button type="button" class="btn btn-sm btn-alt-secondary me-1" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="fa fa-upload me-1"></i> Import Plans
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Batch Delete Confirmation Modal -->
<div class="modal fade" id="batchDeleteModal" tabindex="-1" role="dialog" aria-labelledby="batchDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title" id="batchDeleteModalLabel">
                        <i class="fa fa-exclamation-triangle text-danger"></i> Confirm Batch Deletion
                    </h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa fa-fw fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content fs-sm">
                    <p>Are you sure you want to delete the selected plans?</p>
                    <p class="text-danger">
                        <strong>This action cannot be undone.</strong>
                    </p>
                    <div id="batchDeleteDetails" class="alert alert-info">
                        <strong>Selected Plans:</strong>
                        <ul id="batchDeleteList"></ul>
                    </div>
                    <div id="batchDeleteWarnings" class="alert alert-warning" style="display: none;">
                        <strong>Warning:</strong> Plans with associated servers will also have their servers deleted.
                    </div>
                </div>
                <div class="block-content block-content-full text-end bg-body">
                    <button type="button" class="btn btn-sm btn-alt-secondary me-1" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" id="confirmBatchDelete">
                        <i class="fa fa-trash"></i> Delete Selected Plans
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Batch Activate Confirmation Modal -->
<div class="modal fade" id="batchActivateModal" tabindex="-1" role="dialog" aria-labelledby="batchActivateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title" id="batchActivateModalLabel">
                        <i class="fa fa-check text-success"></i> Confirm Batch Activation
                    </h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa fa-fw fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content fs-sm">
                    <p>Are you sure you want to activate the selected plans?</p>
                    <div id="batchActivateDetails" class="alert alert-info">
                        <strong>Selected Plans:</strong>
                        <ul id="batchActivateList"></ul>
                    </div>
                    <p class="text-info">
                        <i class="fa fa-info-circle"></i> Activated plans will be available for purchase in the shop.
                    </p>
                </div>
                <div class="block-content block-content-full text-end bg-body">
                    <button type="button" class="btn btn-sm btn-alt-secondary me-1" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-sm btn-success" id="confirmBatchActivate">
                        <i class="fa fa-check"></i> Activate Selected Plans
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Batch Deactivate Confirmation Modal -->
<div class="modal fade" id="batchDeactivateModal" tabindex="-1" role="dialog" aria-labelledby="batchDeactivateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title" id="batchDeactivateModalLabel">
                        <i class="fa fa-pause text-warning"></i> Confirm Batch Deactivation
                    </h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa fa-fw fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content fs-sm">
                    <p>Are you sure you want to deactivate the selected plans?</p>
                    <div id="batchDeactivateDetails" class="alert alert-info">
                        <strong>Selected Plans:</strong>
                        <ul id="batchDeactivateList"></ul>
                    </div>
                    <p class="text-info">
                        <i class="fa fa-info-circle"></i> Deactivated plans will not be available for purchase in the shop.
                    </p>
                </div>
                <div class="block-content block-content-full text-end bg-body">
                    <button type="button" class="btn btn-sm btn-alt-secondary me-1" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-sm btn-warning" id="confirmBatchDeactivate">
                        <i class="fa fa-pause"></i> Deactivate Selected Plans
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyTemplate() {
    const template = document.getElementById('jsonTemplate').textContent;
    navigator.clipboard.writeText(template).then(function() {
        // Show success message
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa fa-check"></i> Copied!';
        btn.classList.add('btn-success');
        btn.classList.remove('btn-info');
        
        setTimeout(function() {
            btn.innerHTML = originalText;
            btn.classList.add('btn-info');
            btn.classList.remove('btn-success');
        }, 2000);
    });
}

// Batch functionality
function updateBatchUI() {
    const checkedBoxes = $('.plan-checkbox:checked');
    const count = checkedBoxes.length;
    
    if (count > 0) {
        $('#batchActionsBar').show();
        $('#selectedCount').text(count);
    } else {
        $('#batchActionsBar').hide();
    }
}

function clearSelection() {
    $('.plan-checkbox').prop('checked', false);
    $('#master-checkbox').prop('checked', false).prop('indeterminate', false);
    updateBatchUI();
}

function batchAction(action) {
    const selectedPlans = $('.plan-checkbox:checked');
    
    if (selectedPlans.length === 0) {
        alert('Please select at least one plan.');
        return;
    }
    
    // Populate modal with selected plan names
    let planList = '';
    selectedPlans.each(function() {
        const planName = $(this).data('name');
        planList += '<li>' + planName + '</li>';
    });
    
    switch(action) {
        case 'activate':
            $('#batchActivateList').html(planList);
            new bootstrap.Modal(document.getElementById('batchActivateModal')).show();
            break;
        case 'deactivate':
            $('#batchDeactivateList').html(planList);
            new bootstrap.Modal(document.getElementById('batchDeactivateModal')).show();
            break;
        case 'delete':
            $('#batchDeleteList').html(planList);
            // Show warning if any plans might have servers
            $('#batchDeleteWarnings').show();
            new bootstrap.Modal(document.getElementById('batchDeleteModal')).show();
            break;
    }
}

function executeBatchAction(action) {
    const selectedPlans = $('.plan-checkbox:checked').map(function() {
        return $(this).val();
    }).get();
    
    // Get the appropriate modal and button
    let modal, button;
    switch(action) {
        case 'activate':
            modal = '#batchActivateModal';
            button = '#confirmBatchActivate';
            break;
        case 'deactivate':
            modal = '#batchDeactivateModal';
            button = '#confirmBatchDeactivate';
            break;
        case 'delete':
            modal = '#batchDeleteModal';
            button = '#confirmBatchDelete';
            break;
    }
    
    // Show loading state
    const actionButton = $(button);
    const originalText = actionButton.html();
    actionButton.html('<i class="fa fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
    
    $.ajax({
        url: '{{ route("admin.shop.plans.batch") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            action: action,
            plans: selectedPlans
        },
        success: function(response) {
            if (response.success) {
                // Show success message
                actionButton.html('<i class="fa fa-check"></i> Success!').removeClass().addClass('btn btn-success');
                setTimeout(function() {
                    bootstrap.Modal.getInstance(document.querySelector(modal)).hide();
                    location.reload();
                }, 1000);
            } else {
                alert('Error: ' + response.message);
                actionButton.html(originalText).prop('disabled', false);
            }
        },
        error: function(xhr) {
            console.error('Batch action failed:', xhr);
            alert('An error occurred while performing the batch action.');
            actionButton.html(originalText).prop('disabled', false);
        }
    });
}
</script>
