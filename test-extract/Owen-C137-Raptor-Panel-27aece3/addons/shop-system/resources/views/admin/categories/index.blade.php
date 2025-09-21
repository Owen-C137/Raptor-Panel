@extends('layouts.admin')

@section('title')
    Categories
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Categories
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Manage hosting plan categories.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.index') }}">Admin</a>
          </li>
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.shop.dashboard') }}">Shop</a>
          </li>
          <li class="breadcrumb-item" aria-current="page">
            Categories
          </li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<!-- Quick Stats -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="block block-rounded text-center">
            <div class="block-content ribbon ribbon-left ribbon-modern ribbon-primary">
                <div class="py-4">
                    <div class="item item-2x mx-auto push">
                        <i class="fa fa-tags text-primary"></i>
                    </div>
                    <h1 class="h3 fw-bold mb-1">{{ $stats['total_categories'] ?? $categories->count() }}</h1>
                    <p class="fw-medium text-muted mb-0">Total Categories</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="block block-rounded text-center">
            <div class="block-content ribbon ribbon-left ribbon-modern ribbon-success">
                <div class="py-4">
                    <div class="item item-2x mx-auto push">
                        <i class="fa fa-check text-success"></i>
                    </div>
                    <h1 class="h3 fw-bold mb-1">{{ $stats['active_categories'] ?? $categories->where('active', true)->count() }}</h1>
                    <p class="fw-medium text-muted mb-0">Active Categories</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="block block-rounded text-center">
            <div class="block-content ribbon ribbon-left ribbon-modern ribbon-warning">
                <div class="py-4">
                    <div class="item item-2x mx-auto push">
                        <i class="fa fa-cube text-warning"></i>
                    </div>
                    <h1 class="h3 fw-bold mb-1">{{ $stats['categories_with_plans'] ?? $categories->filter(function($c) { return $c->plans->count() > 0; })->count() }}</h1>
                    <p class="fw-medium text-muted mb-0">With Plans</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="block block-rounded text-center">
            <div class="block-content ribbon ribbon-left ribbon-modern ribbon-danger">
                <div class="py-4">
                    <div class="item item-2x mx-auto push">
                        <i class="fa fa-exclamation-triangle text-danger"></i>
                    </div>
                    <h1 class="h3 fw-bold mb-1">{{ $stats['empty_categories'] ?? $categories->filter(function($c) { return $c->plans->count() === 0; })->count() }}</h1>
                    <p class="fw-medium text-muted mb-0">Empty Categories</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Category Management</h3>
                <div class="block-options">
                    <a href="{{ route('admin.shop.categories.create') }}" class="btn btn-success btn-sm">
                        <i class="fa fa-plus"></i> Create Category
                    </a>
                </div>
            </div>
            
            <div class="block-content">
                <!-- Batch Actions Bar -->
                <div class="row" id="batchActionsBar" style="display: none;">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <div class="row">
                                <div class="col-sm-6">
                                    <span id="selectedCount">0</span> categories selected
                                </div>
                                <div class="col-sm-6 text-end">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-warning btn-sm" 
                                                onclick="batchToggleStatus()"
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="top" 
                                                title="Toggle Status of Selected Categories">
                                            <i class="fa fa-toggle-on"></i> Toggle Status
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" 
                                                onclick="batchDelete()"
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="top" 
                                                title="Delete Selected Categories">
                                            <i class="fa fa-trash"></i> Delete Selected
                                        </button>
                                        <button type="button" class="btn btn-alt-secondary btn-sm" 
                                                onclick="clearSelection()"
                                                data-bs-toggle="tooltip" 
                                                data-bs-placement="top" 
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
                        <div class="mb-4">
                            <input type="text" id="searchCategories" class="form-control" placeholder="Search categories...">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="mb-4">
                            <select id="filterStatus" class="form-select">
                                <option value="">All Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="categoriesTable">
                        <thead>
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="selectAll" class="master-checkbox form-check-input"
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top" 
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
                                        <input type="checkbox" class="category-checkbox form-check-input" value="{{ $category->id }}" 
                                               data-name="{{ $category->name }}"
                                               data-bs-toggle="tooltip" 
                                               data-bs-placement="right" 
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
                                        <span class="badge bg-primary">{{ $category->plans_count ?? $category->plans->count() }}</span>
                                    </td>
                                    <td>{{ $category->sort_order ?? 0 }}</td>
                                    <td>
                                        @if($category->active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ $category->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.shop.categories.edit', $category->id) }}" 
                                               class="btn btn-sm btn-warning" 
                                               data-bs-toggle="tooltip" 
                                               data-bs-placement="top" 
                                               title="Edit Category">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                            <button class="btn btn-sm btn-{{ $category->active ? 'info' : 'success' }}" 
                                                    onclick="toggleCategoryStatus({{ $category->id }})" 
                                                    data-bs-toggle="tooltip" 
                                                    data-bs-placement="top" 
                                                    title="{{ $category->active ? 'Deactivate Category' : 'Activate Category' }}"
                                                    id="toggleBtn-{{ $category->id }}">
                                                <i class="fa fa-{{ $category->active ? 'toggle-off' : 'toggle-on' }}" id="toggleIcon-{{ $category->id }}"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="deleteCategory({{ $category->id }})" 
                                                    data-bs-toggle="tooltip" 
                                                    data-bs-placement="top" 
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
            </div>
            
            @if(method_exists($categories, 'links'))
                <div class="block-content block-content-full">
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
            <div class="block block-rounded shadow-none mb-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-exclamation-triangle text-danger"></i> Confirm Deletion
                    </h3>
                    <div class="block-options">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="block-content">
                    <p>Are you sure you want to delete this category?</p>
                    <p class="text-danger">
                        <strong>This action cannot be undone.</strong>
                    </p>
                    <div id="categoryDetails" class="alert alert-info">
                        <strong>Category:</strong> <span id="categoryName"></span><br>
                        <strong>Plans:</strong> <span id="categoryPlans"></span>
                    </div>
                </div>
                <div class="block-content block-content-full text-end">
                    <button type="button" class="btn btn-alt-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">
                        <i class="fa fa-trash"></i> Delete Category
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Batch Delete Confirmation Modal -->
<div class="modal fade" id="batchDeleteModal" tabindex="-1" role="dialog" aria-labelledby="batchDeleteModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="block block-rounded shadow-none mb-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-exclamation-triangle text-danger"></i> Confirm Batch Deletion
                    </h3>
                    <div class="block-options">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="block-content">
                    <p>Are you sure you want to delete the selected categories?</p>
                    <p class="text-danger">
                        <strong>This action cannot be undone.</strong>
                    </p>
                    <div id="batchDeleteDetails" class="alert alert-info">
                        <strong>Selected Categories:</strong>
                        <ul id="batchDeleteList"></ul>
                    </div>
                    <div id="batchDeleteWarnings" class="alert alert-warning" style="display: none;">
                        <strong>Warning:</strong> Categories with plans cannot be deleted and will be skipped.
                    </div>
                </div>
                <div class="block-content block-content-full text-end">
                    <button type="button" class="btn btn-alt-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmBatchDelete">
                        <i class="fa fa-trash"></i> Delete Selected Categories
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Batch Toggle Status Confirmation Modal -->
<div class="modal fade" id="batchToggleModal" tabindex="-1" role="dialog" aria-labelledby="batchToggleModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="block block-rounded shadow-none mb-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-toggle-on text-info"></i> Confirm Batch Status Toggle
                    </h3>
                    <div class="block-options">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="block-content">
                    <p>Are you sure you want to toggle the status of the selected categories?</p>
                    <div id="batchToggleDetails" class="alert alert-info">
                        <strong>Selected Categories:</strong>
                        <ul id="batchToggleList"></ul>
                    </div>
                    <p class="text-info">
                        <i class="fa fa-info-circle"></i> Active categories will become inactive, and inactive categories will become active.
                    </p>
                </div>
                <div class="block-content block-content-full text-end">
                    <button type="button" class="btn btn-alt-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-warning" id="confirmBatchToggle">
                        <i class="fa fa-toggle-on"></i> Toggle Status
                    </button>
                </div>
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
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
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
                    const status = $(`input[value="${category.id}"]`).closest('tr').find('.badge').hasClass('bg-success') ? 'Active' : 'Inactive';
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
                            // Update the status badge
                            const row = $(`input[value="${categoryId}"]`).closest('tr');
                            const statusCell = row.find('.badge');
                            const toggleBtn = $(`#toggleBtn-${categoryId}`);
                            const toggleIcon = $(`#toggleIcon-${categoryId}`);
                            
                            if (response.active) {
                                statusCell.removeClass('bg-danger').addClass('bg-success').text('Active');
                                toggleBtn.removeClass('btn-success').addClass('btn-info')
                                        .attr('data-bs-original-title', 'Deactivate Category');
                                toggleIcon.removeClass('fa-toggle-on').addClass('fa-toggle-off');
                            } else {
                                statusCell.removeClass('bg-success').addClass('bg-danger').text('Inactive');
                                toggleBtn.removeClass('btn-info').addClass('btn-success')
                                        .attr('data-bs-original-title', 'Activate Category');
                                toggleIcon.removeClass('fa-toggle-off').addClass('fa-toggle-on');
                            }
                            
                            // Update tooltip (Bootstrap 5 method)
                            const tooltip = bootstrap.Tooltip.getInstance(toggleBtn[0]);
                            if (tooltip) {
                                tooltip.dispose();
                                new bootstrap.Tooltip(toggleBtn[0]);
                            }
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
                        var rowStatus = $(this).find('.badge').hasClass('bg-success') ? '1' : '0';
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
