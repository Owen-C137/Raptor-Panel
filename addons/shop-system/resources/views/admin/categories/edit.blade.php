@extends('layouts.admin')

@section('title')
    Edit Category: {{ $category->name }}
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Edit Category {{ $category->name }}
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          {{ $category->name }}
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.categories.index') }}">Categories</a></li>
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
        <form method="POST" action="{{ route('admin.shop.categories.update', $category->id) }}">
            @csrf
            @method('PUT')
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Category Information</h3>
                </div>
                
                <div class="block-content">
                    <div class="mb-4">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" 
                               value="{{ old('name', $category->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label for="slug" class="form-label">Slug <span class="text-danger">*</span></label>
                        <input type="text" name="slug" id="slug" class="form-control" 
                               value="{{ old('slug', $category->slug) }}" required>
                        <small class="form-text text-muted">
                            URL-friendly version of the name.
                        </small>
                        @error('slug')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="4">{{ old('description', $category->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label for="parent_id" class="form-label">Parent Category</label>
                        <select name="parent_id" id="parent_id" class="form-select">
                            <option value="">None (Top Level)</option>
                            @foreach($parentCategories as $parentCategory)
                                <option value="{{ $parentCategory->id }}" 
                                        {{ old('parent_id', $category->parent_id) == $parentCategory->id ? 'selected' : '' }}>
                                    {{ $parentCategory->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('parent_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Display Options</h3>
                </div>
                
                <div class="block-content">
                    <div class="mb-4">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" id="sort_order" class="form-control" 
                               value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0">
                        <small class="form-text text-muted">
                            Categories with lower numbers appear first.
                        </small>
                        @error('sort_order')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label for="active" class="form-label">Category Status</label>
                        <div class="form-check">
                            <input type="checkbox" name="active" id="active" value="1" class="form-check-input"
                                   {{ old('active', $category->active) ? 'checked' : '' }}>
                            <label for="active" class="form-check-label fw-medium">Active</label>
                        </div>
                        <small class="form-text text-muted">
                            Inactive categories are hidden from customers.
                        </small>
                        @error('active')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">SEO Options</h3>
                </div>
                
                <div class="block-content">
                    <div class="mb-4">
                        <label for="meta_title" class="form-label">Meta Title</label>
                        <input type="text" name="meta_title" id="meta_title" class="form-control" 
                               value="{{ old('meta_title', $category->meta_title) }}" maxlength="60">
                        <small class="form-text text-muted">
                            SEO title for search engines (recommended: under 60 characters).
                        </small>
                        @error('meta_title')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label for="meta_description" class="form-label">Meta Description</label>
                        <textarea name="meta_description" id="meta_description" class="form-control" 
                                  rows="3" maxlength="160">{{ old('meta_description', $category->meta_description) }}</textarea>
                        <small class="form-text text-muted">
                            SEO description for search engines (recommended: under 160 characters).
                        </small>
                        @error('meta_description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label for="meta_keywords" class="form-label">Meta Keywords</label>
                        <input type="text" name="meta_keywords" id="meta_keywords" class="form-control" 
                               value="{{ old('meta_keywords', $category->meta_keywords) }}" placeholder="keyword1, keyword2, keyword3">
                        <small class="form-text text-muted">
                            Comma-separated keywords for SEO.
                        </small>
                        @error('meta_keywords')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="block block-rounded">
                <div class="block-content">
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-save"></i> Update Category
                    </button>
                    <a href="{{ route('admin.shop.categories.show', $category->id) }}" class="btn btn-primary">
                        <i class="fa fa-eye"></i> View Category
                    </a>
                    <a href="{{ route('admin.shop.categories.index') }}" class="btn btn-alt-secondary">
                        <i class="fa fa-arrow-left"></i> Back to Categories
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="col-md-4">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Category Info</h3>
            </div>
            
            <div class="block-content">
                <div class="row">
                    <div class="col-6">
                        <dt class="fw-semibold">ID:</dt>
                        <dd class="fs-sm text-muted">{{ $category->id }}</dd>
                        
                        <dt class="fw-semibold">Created:</dt>
                        <dd class="fs-sm text-muted">{{ $category->created_at->format('M d, Y g:i A') }}</dd>
                        
                        <dt class="fw-semibold">Updated:</dt>
                        <dd class="fs-sm text-muted">{{ $category->updated_at->format('M d, Y g:i A') }}</dd>
                    </div>
                    <div class="col-6">
                        <dt class="fw-semibold">Plans:</dt>
                        <dd class="fs-sm text-muted">{{ $category->plans->count() }}</dd>
                        
                        @if($category->children && $category->children->count() > 0)
                            <dt class="fw-semibold">Subcategories:</dt>
                            <dd class="fs-sm text-muted">{{ $category->children->count() }}</dd>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        @if($category->plans && $category->plans->count() > 0)
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Plans in Category</h3>
                </div>
                
                <div class="block-content">
                    @foreach($category->plans->take(5) as $plan)
                        <div class="d-flex justify-content-between align-items-center py-2">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ $plan->name }}</h6>
                                <div class="d-flex align-items-center">
                                    <strong class="me-2">${{ number_format($plan->price, 2) }}</strong>
                                    @if($plan->status === 'active')
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if(!$loop->last)<hr class="my-1">@endif
                    @endforeach
                    
                    @if($category->plans->count() > 5)
                        <div class="text-center mt-3">
                            <a href="{{ route('admin.shop.plans.index', ['category' => $category->id]) }}" 
                               class="btn btn-sm btn-alt-secondary">
                                View All Plans ({{ $category->plans->count() }})
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endif
        
        @if($category->children && $category->children->count() > 0)
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Subcategories</h3>
                </div>
                
                <div class="block-content">
                    @foreach($category->children as $subcategory)
                        <div class="d-flex justify-content-between align-items-center py-2">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <a href="{{ route('admin.shop.categories.show', $subcategory->id) }}" class="link-fx">
                                        {{ $subcategory->name }}
                                    </a>
                                </h6>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-primary me-2">{{ $subcategory->plans->count() }} plans</span>
                                    @if($subcategory->active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if(!$loop->last)<hr class="my-1">@endif
                    @endforeach
                </div>
            </div>
        @endif
        
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Dangerous Actions</h3>
            </div>
            
            <div class="block-content">
                <button class="btn btn-danger w-100" onclick="deleteCategory()">
                    <i class="fa fa-trash"></i> Delete Category
                </button>
                <small class="form-text text-muted mt-2">
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
