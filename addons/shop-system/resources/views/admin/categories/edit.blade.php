@extends('layouts.admin')

@section('title')
    Edit Category: {{ $category->name }}
@endsection

@section('content-header')
    <h1>Edit Category <small>{{ $category->name }}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
        <li><a href="{{ route('admin.shop.categories.index') }}">Categories</a></li>
        <li class="active">Edit</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <form method="POST" action="{{ route('admin.shop.categories.update', $category->id) }}">
            @csrf
            @method('PUT')
            
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Category Information</h3>
                </div>
                
                <div class="box-body">
                    <div class="form-group">
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" 
                               value="{{ old('name', $category->name) }}" required>
                        @error('name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="slug">Slug <span class="text-danger">*</span></label>
                        <input type="text" name="slug" id="slug" class="form-control" 
                               value="{{ old('slug', $category->slug) }}" required>
                        <small class="form-text text-muted">
                            URL-friendly version of the name.
                        </small>
                        @error('slug')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="4">{{ old('description', $category->description) }}</textarea>
                        @error('description')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="parent_id">Parent Category</label>
                        <select name="parent_id" id="parent_id" class="form-control">
                            <option value="">None (Top Level)</option>
                            @foreach($parentCategories as $parentCategory)
                                <option value="{{ $parentCategory->id }}" 
                                        {{ old('parent_id', $category->parent_id) == $parentCategory->id ? 'selected' : '' }}>
                                    {{ $parentCategory->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('parent_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Display Options</h3>
                </div>
                
                <div class="box-body">
                    <div class="form-group">
                        <label for="sort_order">Sort Order</label>
                        <input type="number" name="sort_order" id="sort_order" class="form-control" 
                               value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0">
                        <small class="form-text text-muted">
                            Categories with lower numbers appear first.
                        </small>
                        @error('sort_order')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="is_active" value="1" 
                                       {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                                Active
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            Inactive categories are hidden from customers.
                        </small>
                        @error('is_active')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="is_featured" value="1" 
                                       {{ old('is_featured', $category->is_featured) ? 'checked' : '' }}>
                                Featured Category
                            </label>
                        </div>
                        <small class="form-text text-muted">
                            Featured categories appear prominently in the shop.
                        </small>
                        @error('is_featured')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">SEO Options</h3>
                </div>
                
                <div class="box-body">
                    <div class="form-group">
                        <label for="meta_title">Meta Title</label>
                        <input type="text" name="meta_title" id="meta_title" class="form-control" 
                               value="{{ old('meta_title', $category->meta_title) }}" maxlength="60">
                        <small class="form-text text-muted">
                            SEO title for search engines (recommended: under 60 characters).
                        </small>
                        @error('meta_title')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="meta_description">Meta Description</label>
                        <textarea name="meta_description" id="meta_description" class="form-control" 
                                  rows="3" maxlength="160">{{ old('meta_description', $category->meta_description) }}</textarea>
                        <small class="form-text text-muted">
                            SEO description for search engines (recommended: under 160 characters).
                        </small>
                        @error('meta_description')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="meta_keywords">Meta Keywords</label>
                        <input type="text" name="meta_keywords" id="meta_keywords" class="form-control" 
                               value="{{ old('meta_keywords', $category->meta_keywords) }}" placeholder="keyword1, keyword2, keyword3">
                        <small class="form-text text-muted">
                            Comma-separated keywords for SEO.
                        </small>
                        @error('meta_keywords')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="box">
                <div class="box-footer">
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i> Update Category
                    </button>
                    <a href="{{ route('admin.shop.categories.show', $category->id) }}" class="btn btn-primary">
                        <i class="fa fa-eye"></i> View Category
                    </a>
                    <a href="{{ route('admin.shop.categories.index') }}" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> Back to Categories
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="col-md-4">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Category Info</h3>
            </div>
            
            <div class="box-body">
                <dl class="dl-horizontal">
                    <dt>ID:</dt>
                    <dd>{{ $category->id }}</dd>
                    
                    <dt>Created:</dt>
                    <dd>{{ $category->created_at->format('M d, Y g:i A') }}</dd>
                    
                    <dt>Updated:</dt>
                    <dd>{{ $category->updated_at->format('M d, Y g:i A') }}</dd>
                    
                    <dt>Plans:</dt>
                    <dd>{{ $category->plans->count() }}</dd>
                    
                    @if($category->children && $category->children->count() > 0)
                        <dt>Subcategories:</dt>
                        <dd>{{ $category->children->count() }}</dd>
                    @endif
                </dl>
            </div>
        </div>
        
        @if($category->plans && $category->plans->count() > 0)
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Plans in Category</h3>
                </div>
                
                <div class="box-body">
                    @foreach($category->plans->take(5) as $plan)
                        <div class="media">
                            <div class="media-body">
                                <h5 class="media-heading">{{ $plan->name }}</h5>
                                <p class="margin-bottom-5">
                                    <strong>${{ number_format($plan->price, 2) }}</strong>
                                    @if($plan->status === 'active')
                                        <span class="label label-success">Active</span>
                                    @else
                                        <span class="label label-danger">Inactive</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        @if(!$loop->last)<hr>@endif
                    @endforeach
                    
                    @if($category->plans->count() > 5)
                        <div class="text-center">
                            <a href="{{ route('admin.shop.plans.index', ['category' => $category->id]) }}" 
                               class="btn btn-sm btn-default">
                                View All Plans ({{ $category->plans->count() }})
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endif
        
        @if($category->children && $category->children->count() > 0)
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Subcategories</h3>
                </div>
                
                <div class="box-body">
                    @foreach($category->children as $subcategory)
                        <div class="media">
                            <div class="media-body">
                                <h5 class="media-heading">
                                    <a href="{{ route('admin.shop.categories.show', $subcategory->id) }}">
                                        {{ $subcategory->name }}
                                    </a>
                                </h5>
                                <p class="margin-bottom-5">
                                    <span class="badge bg-blue">{{ $subcategory->plans->count() }} plans</span>
                                    @if($subcategory->is_active)
                                        <span class="label label-success">Active</span>
                                    @else
                                        <span class="label label-danger">Inactive</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        @if(!$loop->last)<hr>@endif
                    @endforeach
                </div>
            </div>
        @endif
        
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Dangerous Actions</h3>
            </div>
            
            <div class="box-body">
                <button class="btn btn-danger btn-block" onclick="deleteCategory()">
                    <i class="fa fa-trash"></i> Delete Category
                </button>
                <small class="form-text text-muted">
                    This action cannot be undone. All plans will be moved to the default category.
                </small>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
            // Character counters
            $('#meta_title').on('input', function() {
                var length = $(this).val().length;
                var color = length > 60 ? 'text-danger' : (length > 50 ? 'text-warning' : 'text-muted');
                $(this).next('.form-text').removeClass('text-muted text-warning text-danger').addClass(color);
            });
            
            $('#meta_description').on('input', function() {
                var length = $(this).val().length;
                var color = length > 160 ? 'text-danger' : (length > 140 ? 'text-warning' : 'text-muted');
                $(this).next('.form-text').removeClass('text-muted text-warning text-danger').addClass(color);
            });
        });
        
        function deleteCategory() {
            if (confirm('Are you sure you want to delete this category? This action cannot be undone.\n\nAll plans in this category will be moved to the default category.')) {
                $.ajax({
                    url: '{{ route('admin.shop.categories.destroy', $category->id) }}',
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.href = '{{ route('admin.shop.categories.index') }}';
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            alert('Cannot delete category: It has subcategories. Delete subcategories first.');
                        } else {
                            alert('An error occurred while deleting the category.');
                        }
                    }
                });
            }
        }
    </script>
@endsection
