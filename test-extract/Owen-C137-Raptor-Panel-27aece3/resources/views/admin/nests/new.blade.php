@extends('layouts.admin')

@section('title')
    New Nest
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          New Nest
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Configure a new nest to deploy to all nodes.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.nests') }}">Nests</a></li>
          <li class="breadcrumb-item" aria-current="page">New</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<form action="{{ route('admin.nests.new') }}" method="POST">
    <div class="row">
        <div class="col-md-12">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">New Nest</h3>
                </div>
                <div class="block-content">
                    <div class="form-group">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" />
                        <p class="text-muted small">This should be a descriptive category name that encompasses all of the eggs within the nest.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="6">{{ old('description') }}</textarea>
                    </div>
                </div>
                <div class="block-content block-content-full block-content-sm bg-body-light">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
