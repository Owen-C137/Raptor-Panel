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
                            <th>ID</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Description</th>
                            <th>Plans</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
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
                                    @if($category->is_active)
                                        <span class="label label-success">Active</span>
                                    @else
                                        <span class="label label-danger">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $category->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.shop.categories.edit', $category->id) }}" 
                                           class="btn btn-xs btn-warning" title="Edit">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                        <button class="btn btn-xs btn-danger" 
                                                onclick="deleteCategory({{ $category->id }})" title="Delete">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">
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
                <h3>{{ $stats['active_categories'] ?? $categories->where('is_active', true)->count() }}</h3>
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
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
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
        
        function deleteCategory(categoryId) {
            if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
                $.ajax({
                    url: '/admin/shop/categories/' + categoryId,
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
            }
        }
    </script>
@endsection
