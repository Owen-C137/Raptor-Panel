@extends('layouts.admin')

@section('title')
    Categories
@endsection

@section('content-header')
    <h1>Categories <small>Manage hosting plan categories</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
        <li class="active">Categories</li>
    </ol>
@endsection

@section('content')
<!-- Quick Stats -->
<div class="row">
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-aqua">
            <div class="inner">
                <h3>{{ $stats['total_categories'] ?? $categories->count() }}</h3>
                <p>Total Categories</p>
            </div>
            <div class="icon">
                <i class="fa fa-tags"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-green">
            <div class="inner">
                <h3>{{ $stats['active_categories'] ?? $categories->where('active', true)->count() }}</h3>
                <p>Active Categories</p>
            </div>
            <div class="icon">
                <i class="fa fa-check"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-yellow">
            <div class="inner">
                <h3>{{ $stats['categories_with_plans'] ?? $categories->filter(function($c) { return $c->plans->count() > 0; })->count() }}</h3>
                <p>With Plans</p>
            </div>
            <div class="icon">
                <i class="fa fa-cube"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-red">
            <div class="inner">
                <h3>{{ $stats['empty_categories'] ?? $categories->filter(function($c) { return $c->plans->count() === 0; })->count() }}</h3>
                <p>Empty Categories</p>
            </div>
            <div class="icon">
                <i class="fa fa-exclamation-triangle"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Category Management</h3>
                <div class="box-tools">
                    <a href="{{ route('admin.shop.categories.create') }}" class="btn btn-success btn-sm">
                        <i class="fa fa-plus"></i> Create Category
                    </a>
                </div>
            </div>
            
            <div class="box-body">
                <!-- Batch Actions Bar -->
                <div class="row" id="batchActionsBar" style="display: none;">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <div class="row">
                                <div class="col-sm-6">
                                    <span id="selectedCount">0</span> categories selected
                                </div>
                                <div class="col-sm-6 text-right">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-warning btn-sm" 
                                                onclick="batchToggleStatus()"
                                                data-toggle="tooltip" 
                                                data-placement="top" 
                                                title="Toggle Status of Selected Categories">
                                            <i class="fa fa-toggle-on"></i> Toggle Status
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" 
                                                onclick="batchDelete()"
                                                data-toggle="tooltip" 
                                                data-placement="top" 
                                                title="Delete Selected Categories">
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
                
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <input type="text" id="searchCategories" class="form-control" placeholder="Search categories...">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <select id="filterStatus" class="form-control">
                                <option value="">All Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <table class="table table-bordered table-hover" id="categoriesTable">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" class="master-checkbox"
                                       data-toggle="tooltip" 
                                       data-placement="top" 
                                       title="Select All Categories">
                            </th>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Description</th>
                            <th>Plans</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td>
                                    <input type="checkbox" class="category-checkbox" value="{{ $category->id }}" 
                                           data-name="{{ $category->name }}"
                                           data-toggle="tooltip" 
                                           data-placement="right" 
                                           title="Select {{ $category->name }}">
                                </td>
                                <td>{{ $category->id }}</td>
                                <td>
                                    <strong>{{ $category->name }}</strong>
                                    @if($category->parent)
                                        <br><small class="text-muted">Parent: {{ $category->parent->name }}</small>
                                    @endif
                                </td>
                                <td><code>{{ $category->slug }}</code></td>
                                <td>
                                    @if($category->description)
                                        {{ Str::limit($category->description, 50) }}
                                    @else
                                        <span class="text-muted">No description</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-blue">{{ $category->plans_count ?? $category->plans->count() }}</span>
                                </td>
                                <td>{{ $category->sort_order ?? 0 }}</td>
                                <td>
                                    @if($category->active)
                                        <span class="label label-success">Active</span>
                                    @else
                                        <span class="label label-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $category->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.shop.categories.edit', $category->id) }}" 
                                           class="btn btn-xs btn-warning" 
                                           data-toggle="tooltip" 
                                           data-placement="top" 
                                           title="Edit Category">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                        <button class="btn btn-xs btn-{{ $category->active ? 'info' : 'success' }}" 
                                                onclick="toggleCategoryStatus({{ $category->id }})" 
                                                data-toggle="tooltip" 
                                                data-placement="top" 
                                                title="{{ $category->active ? 'Deactivate Category' : 'Activate Category' }}"
                                                id="toggleBtn-{{ $category->id }}">
                                            <i class="fa fa-{{ $category->active ? 'toggle-off' : 'toggle-on' }}" id="toggleIcon-{{ $category->id }}"></i>
                                        </button>
                                        <button class="btn btn-xs btn-danger" 
                                                onclick="deleteCategory({{ $category->id }})" 
                                                data-toggle="tooltip" 
                                                data-placement="top" 
                                                title="Delete Category">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center">
                                    <p class="text-muted">No categories found.</p>
                                    <a href="{{ route('admin.shop.categories.create') }}" class="btn btn-success">
                                        <i class="fa fa-plus"></i> Create First Category
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if(method_exists($categories, 'links'))
                <div class="box-footer">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="deleteModalLabel">
                    <i class="fa fa-exclamation-triangle text-danger"></i> Confirm Deletion
                </h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this category?</p>
                <p class="text-danger">
                    <strong>This action cannot be undone.</strong>
                </p>
                <div id="categoryDetails" class="well well-sm">
                    <strong>Category:</strong> <span id="categoryName"></span><br>
                    <strong>Plans:</strong> <span id="categoryPlans"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="fa fa-trash"></i> Delete Category
                </button>
            </div>
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
                <p>Are you sure you want to delete the selected categories?</p>
                <p class="text-danger">
                    <strong>This action cannot be undone.</strong>
                </p>
                <div id="batchDeleteDetails" class="well well-sm">
                    <strong>Selected Categories:</strong>
                    <ul id="batchDeleteList"></ul>
                </div>
                <div id="batchDeleteWarnings" class="alert alert-warning" style="display: none;">
                    <strong>Warning:</strong> Categories with plans cannot be deleted and will be skipped.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmBatchDelete">
                    <i class="fa fa-trash"></i> Delete Selected Categories
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Batch Toggle Status Confirmation Modal -->
<div class="modal fade" id="batchToggleModal" tabindex="-1" role="dialog" aria-labelledby="batchToggleModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="batchToggleModalLabel">
                    <i class="fa fa-toggle-on text-info"></i> Confirm Batch Status Toggle
                </h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to toggle the status of the selected categories?</p>
                <div id="batchToggleDetails" class="well well-sm">
                    <strong>Selected Categories:</strong>
                    <ul id="batchToggleList"></ul>
                </div>
                <p class="text-info">
                    <i class="fa fa-info-circle"></i> Active categories will become inactive, and inactive categories will become active.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-warning" id="confirmBatchToggle">
                    <i class="fa fa-toggle-on"></i> Toggle Status
                </button>
            </div>
        </div>
    </div>
</div>

@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
            // Master checkbox functionality
            $('#selectAll').on('change', function() {
                $('.category-checkbox').prop('checked', $(this).is(':checked'));
                updateBatchActionsVisibility();
            });
            
            // Individual checkbox functionality
            $(document).on('change', '.category-checkbox', function() {
                updateBatchActionsVisibility();
                updateMasterCheckbox();
            });
            
            // Update master checkbox state
            function updateMasterCheckbox() {
                const totalCheckboxes = $('.category-checkbox').length;
                const checkedCheckboxes = $('.category-checkbox:checked').length;
                
                if (checkedCheckboxes === 0) {
                    $('#selectAll').prop('indeterminate', false).prop('checked', false);
                } else if (checkedCheckboxes === totalCheckboxes) {
                    $('#selectAll').prop('indeterminate', false).prop('checked', true);
                } else {
                    $('#selectAll').prop('indeterminate', true);
                }
            }
            
            // Initialize tooltips for dynamic content
            function initializeTooltips() {
                $('[data-toggle="tooltip"]').tooltip();
            }
            
            // Initialize tooltips on page load
            initializeTooltips();
            
            // Show/hide batch actions bar
            function updateBatchActionsVisibility() {
                const checkedCount = $('.category-checkbox:checked').length;
                $('#selectedCount').text(checkedCount);
                
                if (checkedCount > 0) {
                    $('#batchActionsBar').show();
                } else {
                    $('#batchActionsBar').hide();
                }
            }
            
            // Clear selection
            window.clearSelection = function() {
                $('.category-checkbox').prop('checked', false);
                $('#selectAll').prop('checked', false).prop('indeterminate', false);
                updateBatchActionsVisibility();
            };
            
            // Batch delete functionality
            window.batchDelete = function() {
                const selectedCategories = getSelectedCategories();
                if (selectedCategories.length === 0) {
                    alert('Please select categories to delete.');
                    return;
                }
                
                // Populate modal with selected categories
                const deleteList = $('#batchDeleteList');
                deleteList.empty();
                selectedCategories.forEach(function(category) {
                    deleteList.append('<li>' + category.name + '</li>');
                });
                
                // Check if any selected categories have plans
                const hasPlans = selectedCategories.some(category => {
                    const plansCount = parseInt($(`input[value="${category.id}"]`).closest('tr').find('.badge').text());
                    return plansCount > 0;
                });
                
                if (hasPlans) {
                    $('#batchDeleteWarnings').show();
                } else {
                    $('#batchDeleteWarnings').hide();
                }
                
                $('#batchDeleteModal').modal('show');
            };
            
            // Batch toggle status functionality
            window.batchToggleStatus = function() {
                const selectedCategories = getSelectedCategories();
                if (selectedCategories.length === 0) {
                    alert('Please select categories to toggle status.');
                    return;
                }
                
                // Populate modal with selected categories
                const toggleList = $('#batchToggleList');
                toggleList.empty();
                selectedCategories.forEach(function(category) {
                    const status = $(`input[value="${category.id}"]`).closest('tr').find('.label').hasClass('label-success') ? 'Active' : 'Inactive';
                    toggleList.append('<li>' + category.name + ' (Currently: ' + status + ')</li>');
                });
                
                $('#batchToggleModal').modal('show');
            };
            
            // Get selected categories
            function getSelectedCategories() {
                const selected = [];
                $('.category-checkbox:checked').each(function() {
                    selected.push({
                        id: $(this).val(),
                        name: $(this).data('name')
                    });
                });
                return selected;
            }
            
            // Confirm batch delete
            $('#confirmBatchDelete').on('click', function() {
                const selectedIds = $('.category-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();
                
                performBatchAction('delete', selectedIds);
                $('#batchDeleteModal').modal('hide');
            });
            
            // Confirm batch toggle
            $('#confirmBatchToggle').on('click', function() {
                const selectedIds = $('.category-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();
                
                performBatchAction('toggle_status', selectedIds);
                $('#batchToggleModal').modal('hide');
            });
            
            // Perform batch action
            function performBatchAction(action, categoryIds) {
                $.ajax({
                    url: '{{ route("admin.shop.categories.batch-action") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        action: action,
                        category_ids: categoryIds
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(xhr) {
                        alert('An error occurred while performing the batch action.');
                    }
                });
            }
            
            // Individual toggle status
            window.toggleCategoryStatus = function(categoryId) {
                $.ajax({
                    url: '{{ route("admin.shop.categories.toggle-status", ":id") }}'.replace(':id', categoryId),
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update the status label
                            const row = $(`input[value="${categoryId}"]`).closest('tr');
                            const statusCell = row.find('.label');
                            const toggleBtn = $(`#toggleBtn-${categoryId}`);
                            const toggleIcon = $(`#toggleIcon-${categoryId}`);
                            
                            if (response.active) {
                                statusCell.removeClass('label-danger').addClass('label-success').text('Active');
                                toggleBtn.removeClass('btn-success').addClass('btn-info')
                                        .attr('data-original-title', 'Deactivate Category');
                                toggleIcon.removeClass('fa-toggle-on').addClass('fa-toggle-off');
                            } else {
                                statusCell.removeClass('label-success').addClass('label-danger').text('Inactive');
                                toggleBtn.removeClass('btn-info').addClass('btn-success')
                                        .attr('data-original-title', 'Activate Category');
                                toggleIcon.removeClass('fa-toggle-off').addClass('fa-toggle-on');
                            }
                            
                            // Update tooltip
                            toggleBtn.tooltip('fixTitle');
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(xhr) {
                        alert('An error occurred while toggling the category status.');
                    }
                });
            };
            
            // Search functionality
            $('#searchCategories').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $("#categoriesTable tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
            
            // Status filter
            $('#filterStatus').on('change', function() {
                var status = $(this).val();
                if (status === '') {
                    $("#categoriesTable tbody tr").show();
                } else {
                    $("#categoriesTable tbody tr").each(function() {
                        var rowStatus = $(this).find('.label').hasClass('label-success') ? '1' : '0';
                        if (rowStatus === status) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                }
            });
        });
        
        let categoryToDelete = null;
        
        function deleteCategory(categoryId) {
            categoryToDelete = categoryId;
            
            // Get category details from the table row
            const row = $(`button[onclick="deleteCategory(${categoryId})"]`).closest('tr');
            const categoryName = row.find('td:nth-child(2) strong').text();
            const categoryPlans = row.find('td:nth-child(5) .badge').text();
            
            // Populate modal with category details
            $('#categoryName').text(categoryName);
            $('#categoryPlans').text(categoryPlans + ' plans');
            
            // Show the modal
            $('#deleteModal').modal('show');
        }
        
        // Handle confirm delete button click
        $('#confirmDelete').on('click', function() {
            if (categoryToDelete) {
                $('#deleteModal').modal('hide');
                
                $.ajax({
                    url: '{{ route("admin.shop.categories.destroy", ":id") }}'.replace(':id', categoryToDelete),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            alert('Cannot delete category: It contains plans or has subcategories.');
                        } else {
                            alert('An error occurred while deleting the category.');
                        }
                    }
                });
                
                categoryToDelete = null;
            }
        });
        
        // Reset when modal is hidden
        $('#deleteModal').on('hidden.bs.modal', function() {
            categoryToDelete = null;
        });
    </script>
@endsection
