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
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Plans</h3>
                <div class="box-tools">
                    <a href="{{ route('admin.shop.plans.create') }}" class="btn btn-sm btn-primary" data-toggle="tooltip" data-placement="top" title="Create New Plan">
                        <i class="fa fa-plus"></i> Create Plan
                    </a>
                    <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#importPlansModal" title="Import Plans from JSON File">
                        <i class="fa fa-upload"></i> Import Plans
                    </button>
                </div>
            </div>
            
            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="fa fa-check"></i> {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="fa fa-exclamation-triangle"></i> {{ session('error') }}
                </div>
            @endif
            
            <!-- Search and Filter Form -->
            <div class="box-body">
                <form method="GET" action="{{ route('admin.shop.plans.index') }}" class="row">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Search plans..." 
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="category" class="form-control">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-search"></i> Search
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('admin.shop.plans.index') }}" class="btn btn-default">
                            <i class="fa fa-refresh"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-body table-responsive no-padding">
                <!-- Batch Actions Bar -->
                <div class="row" id="batchActionsBar" style="display: none; padding: 15px;">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <div class="row">
                                <div class="col-sm-6">
                                    <span id="selectedCount">0</span> plans selected
                                </div>
                                <div class="col-sm-6 text-right">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-success btn-sm" 
                                                onclick="batchAction('activate')"
                                                data-toggle="tooltip" 
                                                data-placement="top" 
                                                title="Activate Selected Plans">
                                            <i class="fa fa-check"></i> Activate Selected
                                        </button>
                                        <button type="button" class="btn btn-warning btn-sm" 
                                                onclick="batchAction('deactivate')"
                                                data-toggle="tooltip" 
                                                data-placement="top" 
                                                title="Deactivate Selected Plans">
                                            <i class="fa fa-pause"></i> Deactivate Selected
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" 
                                                onclick="batchAction('delete')"
                                                data-toggle="tooltip" 
                                                data-placement="top" 
                                                title="Delete Selected Plans">
                                            <i class="fa fa-trash"></i> Delete Selected
                                        </button>
                                        <button type="button" class="btn btn-default btn-sm" 
                                                onclick="clearSelection()"
                                                data-toggle="tooltip" 
                                                data-placement="top" 
                                                title="Clear All Selections">
                                            <i class="fa fa-times"></i> Clear Selection
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @if($plans->count() > 0)
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="30px">
                                    <input type="checkbox" id="master-checkbox" data-toggle="tooltip" data-placement="top" title="Select All Plans">
                                </th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Server(s)</th>
                                <th>Pricing</th>
                                <th>Resources</th>
                                <th>Status</th>
                                <th>Sort</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($plans as $plan)
                                <tr data-plan-id="{{ $plan->id }}">
                                    <td>
                                        <input type="checkbox" class="plan-checkbox" value="{{ $plan->id }}" 
                                               data-name="{{ $plan->name }}"
                                               data-toggle="tooltip" 
                                               data-placement="right" 
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
                                            <span class="label label-primary">{{ $plan->category->name }}</span>
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
                                                       data-toggle="popover" 
                                                       data-placement="top"
                                                       data-html="true"
                                                       data-trigger="click"
                                                       data-content="{!! htmlspecialchars($popoverContent) !!}"
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
                                            <span class="label label-success" data-status="active">Active</span>
                                        @elseif($plan->status === 'inactive')
                                            <span class="label label-warning" data-status="inactive">Inactive</span>
                                        @else
                                            <span class="label label-default" data-status="archived">Archived</span>
                                        @endif
                                    </td>
                                    <td>{{ $plan->sort_order }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.shop.plans.show', $plan->id) }}" 
                                               class="btn btn-xs btn-primary" 
                                               data-toggle="tooltip" 
                                               data-placement="top" 
                                               title="View Plan Details">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.shop.plans.edit', $plan->id) }}" 
                                               class="btn btn-xs btn-warning" 
                                               data-toggle="tooltip" 
                                               data-placement="top" 
                                               title="Edit Plan">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                            @if($plan->status === 'active')
                                                <button class="btn btn-xs btn-danger" 
                                                        onclick="showToggleModal({{ $plan->id }}, 'active', '{{ $plan->name }}')" 
                                                        data-toggle="tooltip" 
                                                        data-placement="top" 
                                                        title="Deactivate Plan">
                                                    <i class="fa fa-pause"></i>
                                                </button>
                                            @else
                                                <button class="btn btn-xs btn-success" 
                                                        onclick="showToggleModal({{ $plan->id }}, 'inactive', '{{ $plan->name }}')" 
                                                        data-toggle="tooltip" 
                                                        data-placement="top" 
                                                        title="Activate Plan">
                                                    <i class="fa fa-play"></i>
                                                </button>
                                            @endif
                                            <button class="btn btn-xs btn-info" 
                                                    onclick="showDuplicateModal({{ $plan->id }}, '{{ $plan->name }}')" 
                                                    data-toggle="tooltip" 
                                                    data-placement="top" 
                                                    title="Duplicate Plan">
                                                <i class="fa fa-copy"></i>
                                            </button>
                                            <button class="btn btn-xs btn-danger" 
                                                    onclick="showDeleteModal({{ $plan->id }}, '{{ $plan->name }}')" 
                                                    data-toggle="tooltip" 
                                                    data-placement="top" 
                                                    title="Delete Plan">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center" style="padding: 50px;">
                        <h4>No Plans Found</h4>
                        <p>Get started by creating your first hosting plan.</p>
                        <a href="{{ route('admin.shop.plans.create') }}" class="btn btn-primary">
                            <i class="fa fa-plus"></i> Create Plan
                        </a>
                    </div>
                @endif
            </div>
            
            @if($plans->hasPages())
                <div class="box-footer">
                    <div class="text-center">
                        {{ $plans->appends(request()->query())->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Toggle Status Modal -->
<div class="modal fade" id="toggleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="toggleModalTitle">Confirm Action</h4>
            </div>
            <div class="modal-body">
                <p id="toggleModalMessage">Are you sure you want to perform this action?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-sm pull-left" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="confirmToggleBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Plan Modal -->
<div class="modal fade" id="deletePlanModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Delete Plan</h4>
            </div>
            <div class="modal-body">
                <p id="deleteModalMessage">Are you sure you want to delete this plan? This action cannot be undone.</p>
                <div class="alert alert-warning">
                    <i class="fa fa-warning"></i> <strong>Warning:</strong> Plans with existing orders cannot be deleted.
                </div>
                
                <!-- Server Deletion Section -->
                <div id="serverDeletionSection" style="display: none;">
                    <hr>
                    <div class="alert alert-info">
                        <i class="fa fa-server"></i> <strong>Connected Servers:</strong>
                        <span id="serverCount">0 servers</span> are currently connected to this plan.
                    </div>
                    
                    <div class="checkbox checkbox-primary no-margin-bottom">
                        <input type="checkbox" id="deleteServers" name="delete_servers" value="1" checked>
                        <label for="deleteServers" class="strong">
                            <i class="fa fa-trash text-danger"></i> Also delete all connected servers
                        </label>
                    </div>
                    <p class="help-block text-muted">
                        <i class="fa fa-exclamation-triangle text-warning"></i> 
                        <strong>Warning:</strong> This will permanently delete all servers and their data. 
                        Server backups will be preserved if configured.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-sm pull-left" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmDelete">
                    <i class="fa fa-trash"></i> Delete Plan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Duplicate Plan Modal -->
<div class="modal fade" id="duplicatePlanModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-copy text-info"></i> Duplicate Plan
                </h4>
            </div>
            <div class="modal-body">
                <p id="duplicateModalMessage">Are you sure you want to duplicate this plan?</p>
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> <strong>Note:</strong> The duplicated plan will be created as "inactive" and you can edit it before making it available.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-sm pull-left" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info btn-sm" id="confirmDuplicate">
                    <i class="fa fa-copy"></i> Duplicate Plan
                </button>
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
            
            $('#toggleModal').modal('show');
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
            
            $('#deletePlanModal').modal('show');
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
            $('#duplicatePlanModal').modal('show');
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
                    $('#toggleModal').modal('hide');
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
                    $('#toggleModal').modal('hide');
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
                    $('#deletePlanModal').modal('hide');
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
                    $('#deletePlanModal').modal('hide');
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
                    $('#duplicatePlanModal').modal('hide');
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
                    $('#duplicatePlanModal').modal('hide');
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
            $statusBadge.removeClass('label-success label-danger label-warning label-default')
                       .attr('data-status', newStatus);
            
            if (newStatus === 'active') {
                $statusBadge.addClass('label-success').text('Active');
            } else if (newStatus === 'inactive') {
                $statusBadge.addClass('label-warning').text('Inactive');
            } else if (newStatus === 'archived') {
                $statusBadge.addClass('label-default').text('Archived');
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
        
        // Initialize pricing popovers
        $(document).ready(function() {
            $('[data-toggle="popover"]').popover({
                container: 'body'
            });
            
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();
            
            // Close popover when clicking elsewhere
            $(document).on('click', function (e) {
                $('[data-toggle="popover"]').each(function () {
                    if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
                        $(this).popover('hide');
                    }
                });
            });
        });
    </script>
@endsection

<!-- Import Plans Modal -->
<div class="modal fade" id="importPlansModal" tabindex="-1" role="dialog" aria-labelledby="importPlansModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="importPlansModalLabel">
                    <i class="fa fa-upload"></i> Import Plans from JSON
                </h4>
            </div>
            <form id="importPlansForm" action="{{ route('admin.shop.plans.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="box box-primary">
                                <div class="box-header with-border">
                                    <h3 class="box-title">Upload JSON File</h3>
                                </div>
                                <div class="box-body">
                                    <div class="form-group">
                                        <label for="import_file">Select JSON File <span class="text-danger">*</span></label>
                                        <input type="file" name="import_file" id="import_file" class="form-control" accept=".json" required>
                                        <small class="help-block">Upload a JSON file containing an array of plans</small>
                                    </div>
                                    
                                        <label>
                                            <input type="checkbox" name="overwrite_existing" value="1">
                                            Overwrite existing plans with same name
                                        </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="box box-info">
                                <div class="box-header with-border">
                                    <h3 class="box-title">JSON Template</h3>
                                </div>
                                <div class="box-body">
                                    <p class="text-muted">Use this template to create your plans JSON file:</p>
                                    <button type="button" class="btn btn-xs btn-info" onclick="copyTemplate()" data-toggle="tooltip" data-placement="top" title="Copy JSON Template to Clipboard">
                                        <i class="fa fa-copy"></i> Copy Template
                                    </button>
                                    <pre id="jsonTemplate" style="max-height: 300px; overflow-y: auto; font-size: 11px;">// Available Categories: {{ implode(', ', $availableCategories) }}
// Available Eggs: {{ implode(', ', $availableEggs) }}
// Available Locations: {{ implode(', ', $availableLocations) }}
// Available Nodes: {{ implode(', ', $availableNodes) }}

[
  {
    "name": "Basic Minecraft",
    "description": "Perfect for small communities",
    "category_name": "{{ $availableCategories[0] ?? 'Minecraft' }}",
    "sort_order": 0,
    "visible": true,
    "billing_cycles": [
      {
        "cycle": "monthly", 
        "price": 9.99,
        "setup_fee": 0
      },
      {
        "cycle": "annually",
        "price": 99.99,
        "setup_fee": 0
      }
    ],
    "server_limits": {
      "cpu": 100,
      "memory": 2048,
      "disk": 5120,
      "swap": 0,
      "io": 500,
      "oom_disabled": false
    },
    "server_feature_limits": {
      "databases": 2,
      "allocations": 1,
      "backups": 3
    },
    "egg_name": "{{ $availableEggs[0] ?? 'Vanilla Minecraft' }}",
    "allowed_location_names": [{{ count($availableLocations) > 0 ? '"' . implode('", "', array_slice($availableLocations, 0, 2)) . '"' : '"US East", "EU West"' }}],
    "allowed_node_names": [{{ count($availableNodes) > 0 ? '"' . implode('", "', array_slice($availableNodes, 0, 2)) . '"' : '"Node-1", "Node-2"' }}]
  }
]</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="callout callout-info">
                                <h4><i class="fa fa-info-circle"></i> Import Notes</h4>
                                <ul class="text-sm">
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-upload"></i> Import Plans
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Batch Delete Confirmation Modal -->
<div class="modal fade" id="batchDeleteModal" tabindex="-1" role="dialog" aria-labelledby="batchDeleteModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="batchDeleteModalLabel">
                    <i class="fa fa-exclamation-triangle text-danger"></i> Confirm Batch Deletion
                </h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the selected plans?</p>
                <p class="text-danger">
                    <strong>This action cannot be undone.</strong>
                </p>
                <div id="batchDeleteDetails" class="well well-sm">
                    <strong>Selected Plans:</strong>
                    <ul id="batchDeleteList"></ul>
                </div>
                <div id="batchDeleteWarnings" class="alert alert-warning" style="display: none;">
                    <strong>Warning:</strong> Plans with associated servers will also have their servers deleted.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmBatchDelete">
                    <i class="fa fa-trash"></i> Delete Selected Plans
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Batch Activate Confirmation Modal -->
<div class="modal fade" id="batchActivateModal" tabindex="-1" role="dialog" aria-labelledby="batchActivateModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="batchActivateModalLabel">
                    <i class="fa fa-check text-success"></i> Confirm Batch Activation
                </h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to activate the selected plans?</p>
                <div id="batchActivateDetails" class="well well-sm">
                    <strong>Selected Plans:</strong>
                    <ul id="batchActivateList"></ul>
                </div>
                <p class="text-info">
                    <i class="fa fa-info-circle"></i> Activated plans will be available for purchase in the shop.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-success" id="confirmBatchActivate">
                    <i class="fa fa-check"></i> Activate Selected Plans
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Batch Deactivate Confirmation Modal -->
<div class="modal fade" id="batchDeactivateModal" tabindex="-1" role="dialog" aria-labelledby="batchDeactivateModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="batchDeactivateModalLabel">
                    <i class="fa fa-pause text-warning"></i> Confirm Batch Deactivation
                </h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to deactivate the selected plans?</p>
                <div id="batchDeactivateDetails" class="well well-sm">
                    <strong>Selected Plans:</strong>
                    <ul id="batchDeactivateList"></ul>
                </div>
                <p class="text-info">
                    <i class="fa fa-info-circle"></i> Deactivated plans will not be available for purchase in the shop.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-warning" id="confirmBatchDeactivate">
                    <i class="fa fa-pause"></i> Deactivate Selected Plans
                </button>
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
            $('#batchActivateModal').modal('show');
            break;
        case 'deactivate':
            $('#batchDeactivateList').html(planList);
            $('#batchDeactivateModal').modal('show');
            break;
        case 'delete':
            $('#batchDeleteList').html(planList);
            // Show warning if any plans might have servers
            $('#batchDeleteWarnings').show();
            $('#batchDeleteModal').modal('show');
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
                    $(modal).modal('hide');
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
