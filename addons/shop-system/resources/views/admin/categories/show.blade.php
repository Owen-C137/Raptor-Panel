@extends('layouts.admin')

@section('title')
    Category: {{ $category->name }}
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Category Details {{ $category->name }}
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
          <li class="breadcrumb-item" aria-current="page">{{ $category->name }}</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Category Information</h3>
                <div class="block-options">
                    <a href="{{ route('admin.shop.categories.edit', $category->id) }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-edit"></i> Edit Category
                    </a>
                </div>
            </div>
            
            <div class="block-content">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Name:</strong> {{ $category->name }}<br>
                        <strong>Slug:</strong> <code>{{ $category->slug }}</code><br>
                        <strong>Status:</strong> 
                        <span class="badge bg-{{ $category->active ? 'success' : 'warning' }}">
                            {{ $category->active ? 'Active' : 'Inactive' }}
                        </span><br>
                        <strong>Sort Order:</strong> {{ $category->sort_order ?? 'Not set' }}<br>
                        <strong>Plans Count:</strong> <span class="badge bg-primary">{{ $category->plans->count() }}</span><br>
                        @if($category->parent_id)
                            <strong>Parent Category:</strong> {{ $category->parent->name ?? 'Unknown' }}<br>
                        @endif
                    </div>
                </div>
                
                @if($category->description)
                <div class="row mt-3">
                    <div class="col-md-12">
                        <strong>Description:</strong><br>
                        <p class="text-muted">{{ $category->description }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        @if($category->plans->count() > 0)
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Plans in this Category</h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($category->plans as $plan)
                            <tr>
                                <td><strong>{{ $plan->name }}</strong></td>
                                <td>
                                    @if($plan->price > 0)
                                        <strong>${{ number_format($plan->price, 2) }}</strong>
                                    @else
                                        <span class="badge bg-success">Free</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $plan->status === 'active' ? 'success' : 'warning' }}">
                                        {{ ucfirst($plan->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.shop.plans.show', $plan->id) }}" class="btn btn-sm btn-info">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                        <a href="{{ route('admin.shop.plans.edit', $plan->id) }}" class="btn btn-sm btn-primary">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if($category->children->count() > 0)
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Subcategories</h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Plans Count</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($category->children as $subcategory)
                            <tr>
                                <td><strong>{{ $subcategory->name }}</strong></td>
                                <td>
                                    <span class="badge bg-{{ $subcategory->active ? 'success' : 'warning' }}">
                                        {{ $subcategory->active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td><span class="badge bg-primary">{{ $subcategory->plans->count() }}</span></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.shop.categories.show', $subcategory->id) }}" class="btn btn-sm btn-info">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                        <a href="{{ route('admin.shop.categories.edit', $subcategory->id) }}" class="btn btn-sm btn-primary">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Quick Actions</h3>
            </div>
            <div class="block-content">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.shop.categories.edit', $category->id) }}" class="btn btn-primary">
                        <i class="fa fa-edit"></i> Edit Category
                    </a>
                    <a href="{{ route('admin.shop.categories.index') }}" class="btn btn-alt-secondary">
                        <i class="fa fa-arrow-left"></i> Back to Categories
                    </a>
                    <a href="{{ route('admin.shop.category.plans.index', $category->id) }}" class="btn btn-info">
                        <i class="fa fa-list"></i> Manage Plans
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
