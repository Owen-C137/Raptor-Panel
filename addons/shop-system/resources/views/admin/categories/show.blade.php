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
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Category Information</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('admin.shop.categories.edit', $category->id) }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-edit"></i> Edit Category
                    </a>
                </div>
            </div>
            
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Name:</strong> {{ $category->name }}<br>
                        <strong>Slug:</strong> {{ $category->slug }}<br>
                        <strong>Status:</strong> 
                        <span class="label label-{{ $category->active ? 'success' : 'warning' }}">
                            {{ $category->active ? 'Active' : 'Inactive' }}
                        </span><br>
                        <strong>Sort Order:</strong> {{ $category->sort_order ?? 'Not set' }}<br>
                        <strong>Plans Count:</strong> {{ $category->plans->count() }}<br>
                        @if($category->parent_id)
                            <strong>Parent Category:</strong> {{ $category->parent->name ?? 'Unknown' }}<br>
                        @endif
                    </div>
                </div>
                
                @if($category->description)
                <div class="row">
                    <div class="col-md-12">
                        <strong>Description:</strong><br>
                        <p>{{ $category->description }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        @if($category->plans->count() > 0)
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Plans in this Category</h3>
            </div>
            <div class="box-body">
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
                                <td>{{ $plan->name }}</td>
                                <td>
                                    @if($plan->price > 0)
                                        ${{ number_format($plan->price, 2) }}
                                    @else
                                        Free
                                    @endif
                                </td>
                                <td>
                                    <span class="label label-{{ $plan->status === 'active' ? 'success' : 'warning' }}">
                                        {{ ucfirst($plan->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.shop.plans.show', $plan->id) }}" class="btn btn-xs btn-info">
                                        <i class="fa fa-eye"></i> View
                                    </a>
                                    <a href="{{ route('admin.shop.plans.edit', $plan->id) }}" class="btn btn-xs btn-primary">
                                        <i class="fa fa-edit"></i> Edit
                                    </a>
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
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Subcategories</h3>
            </div>
            <div class="box-body">
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
                                <td>{{ $subcategory->name }}</td>
                                <td>
                                    <span class="label label-{{ $subcategory->active ? 'success' : 'warning' }}">
                                        {{ $subcategory->active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>{{ $subcategory->plans->count() }}</td>
                                <td>
                                    <a href="{{ route('admin.shop.categories.show', $subcategory->id) }}" class="btn btn-xs btn-info">
                                        <i class="fa fa-eye"></i> View
                                    </a>
                                    <a href="{{ route('admin.shop.categories.edit', $subcategory->id) }}" class="btn btn-xs btn-primary">
                                        <i class="fa fa-edit"></i> Edit
                                    </a>
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
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Quick Actions</h3>
            </div>
            <div class="box-body">
                <a href="{{ route('admin.shop.categories.edit', $category->id) }}" class="btn btn-primary btn-block">
                    <i class="fa fa-edit"></i> Edit Category
                </a>
                <a href="{{ route('admin.shop.categories.index') }}" class="btn btn-default btn-block">
                    <i class="fa fa-arrow-left"></i> Back to Categories
                </a>
                <a href="{{ route('admin.shop.category.plans.index', $category->id) }}" class="btn btn-info btn-block">
                    <i class="fa fa-list"></i> Manage Plans
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
