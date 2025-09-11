@extends('layouts.admin')

@section('title')
    Plans
@endsection

@section('content-header')
    <h1>Plans <small>manage hosting plans</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
        <li class="active">Plans</li>
    </ol>
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
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Plans</h3>
                <div class="box-tools">
                    <a href="{{ route('admin.shop.plans.create') }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-plus"></i> Create Plan
                    </a>
                </div>
            </div>
            
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
                @if($plans->count() > 0)
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
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
                                    <td>
                                        @php
                                            $cycles = is_array($plan->billing_cycles) ? $plan->billing_cycles : [];
                                            $monthly = collect($cycles)->where('cycle', 'monthly')->first();
                                        @endphp
                                        @if($monthly)
                                            <strong>${{ number_format($monthly['price'], 2) }}/mo</strong>
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
                                                            ? ' (+$' . number_format($cycle['setup_fee'], 2) . ' setup)'
                                                            : '';
                                                        $popoverContent .= "<div><strong>$" . $price . "/{$label}</strong>{$setup}</div>";
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
                                            <a href="{{ route('admin.shop.plans.show', $plan->id) }}" class="btn btn-xs btn-primary" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.shop.plans.edit', $plan->id) }}" class="btn btn-xs btn-warning" title="Edit">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                            @if($plan->status === 'active')
                                                <button class="btn btn-xs btn-danger" 
                                                        onclick="showToggleModal({{ $plan->id }}, 'active', '{{ $plan->name }}')" title="Deactivate">
                                                    <i class="fa fa-pause"></i>
                                                </button>
                                            @else
                                                <button class="btn btn-xs btn-success" 
                                                        onclick="showToggleModal({{ $plan->id }}, 'inactive', '{{ $plan->name }}')" title="Activate">
                                                    <i class="fa fa-play"></i>
                                                </button>
                                            @endif
                                            <button class="btn btn-xs btn-info" 
                                                    onclick="showDuplicateModal({{ $plan->id }}, '{{ $plan->name }}')" title="Duplicate">
                                                <i class="fa fa-copy"></i>
                                            </button>
                                            <button class="btn btn-xs btn-danger" 
                                                    onclick="showDeleteModal({{ $plan->id }}, '{{ $plan->name }}')" title="Delete">
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
            $('#deletePlanModal').modal('show');
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
            
            $.ajax({
                url: '{{ url('admin/shop/plans') }}/' + planId,
                type: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#deletePlanModal').modal('hide');
                    showLoadingState('#confirmDelete', false);
                    
                    if (response.success) {
                        showAlert('success', 'Plan deleted successfully!');
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
