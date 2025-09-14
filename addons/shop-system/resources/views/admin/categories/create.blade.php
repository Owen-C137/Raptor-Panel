@extends('layouts.admin')

@section('title')
    Create Category
@endsection

@section('content-header')
    <h1>Create Category <small>Add a new product category</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
        <li><a href="{{ route('admin.shop.categories.index') }}">Categories</a></li>
        <li class="active">Create</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <form method="POST" action="{{ route('admin.shop.categories.store') }}">
            @csrf
            
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Category Information</h3>
                </div>
                
                <div class="box-body">
                    <div class="form-group">
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" 
                               value="{{ old('name') }}" required>
                        @error('name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="slug">Slug</label>
                        <input type="text" name="slug" id="slug" class="form-control" 
                               value="{{ old('slug') }}" placeholder="Auto-generated from name">
                        <small class="form-text text-muted">
                            URL-friendly version of the name. Leave blank to auto-generate.
                        </small>
                        @error('slug')
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
                    
                    <div class="form-group">
                        <label for="parent_id">Parent Category</label>
                        <select name="parent_id" id="parent_id" class="form-control">
                            <option value="">None (Top Level)</option>
                            @foreach($parentCategories as $parentCategory)
                                <option value="{{ $parentCategory->id }}" 
                                        {{ old('parent_id') == $parentCategory->id ? 'selected' : '' }}>
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
                               value="{{ old('sort_order', 0) }}" min="0">
                        <small class="form-text text-muted">
                            Categories with lower numbers appear first.
                        </small>
                        @error('sort_order')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="active">Category Status</label>
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input type="checkbox" name="active" id="active" value="1" 
                                   {{ old('active', true) ? 'checked' : '' }}>
                            <label for="active" class="strong">Active</label>
                        </div>
                        <small class="form-text text-muted">
                            Inactive categories are hidden from customers.
                        </small>
                        @error('active')
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
                               value="{{ old('meta_title') }}" maxlength="60">
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
                                  rows="3" maxlength="160">{{ old('meta_description') }}</textarea>
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
                               value="{{ old('meta_keywords') }}" placeholder="keyword1, keyword2, keyword3">
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
                        <i class="fa fa-save"></i> Create Category
                    </button>
                    <a href="{{ route('admin.shop.categories.index') }}" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="col-md-4">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Category Guidelines</h3>
            </div>
            
            <div class="box-body">
                <div class="callout callout-info">
                    <h4><i class="fa fa-info-circle"></i> Tip!</h4>
                    <p>Use clear, descriptive names for your categories to help customers find products easily.</p>
                </div>
                
                <h5>Best Practices:</h5>
                <ul>
                    <li>Keep names short and descriptive</li>
                    <li>Use proper capitalization</li>
                    <li>Avoid special characters in slugs</li>
                    <li>Set appropriate sort orders</li>
                    <li>Write helpful descriptions</li>
                </ul>
                
                <h5>Hierarchy:</h5>
                <p>Categories can be nested under parent categories to create a hierarchy. This helps organize products logically.</p>
                
                <h5>SEO:</h5>
                <p>Fill out meta fields to improve search engine visibility and click-through rates.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
            // Auto-generate slug from name
            $('#name').on('input', function() {
                var name = $(this).val();
                var slug = name.toLowerCase()
                    .replace(/[^\w\s-]/g, '') // Remove special characters
                    .replace(/[\s_-]+/g, '-') // Replace spaces and underscores with hyphens
                    .replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens
                
                if ($('#slug').val() === '' || $('#slug').data('auto-generated')) {
                    $('#slug').val(slug).data('auto-generated', true);
                }
            });
            
            // Mark slug as manually edited if user types in it
            $('#slug').on('input', function() {
                $(this).data('auto-generated', false);
            });
            
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
    </script>
@endsection
