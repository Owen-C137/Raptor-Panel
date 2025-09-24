@extends('layouts.admin')

@section('title')
    Locations &rarr; View &rarr; {{ $location->short }}
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          {{ $location->short }}
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          {{ str_limit($location->long, 75) }}
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.locations') }}">Locations</a></li>
          <li class="breadcrumb-item" aria-current="page">{{ $location->short }}</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-sm-6">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Location Details</h3>
            </div>
            <form action="{{ route('admin.locations.view', $location->id) }}" method="POST">
                <div class="block-content">
                    <div class="form-group mb-3">
                        <label for="pShort" class="form-label">Short Code</label>
                        <input type="text" id="pShort" name="short" class="form-control" value="{{ $location->short }}" />
                    </div>
                    <div class="form-group mb-3">
                        <label for="pLong" class="form-label">Description</label>
                        <textarea id="pLong" name="long" class="form-control" rows="4">{{ $location->long }}</textarea>
                    </div>
                </div>
                <div class="block-content block-content-full block-content-sm text-end border-top">
                    {!! csrf_field() !!}
                    {!! method_field('PATCH') !!}
                    <button name="action" value="delete" class="btn btn-sm btn-outline-danger me-1"><i class="fa fa-trash-o"></i></button>
                    <button name="action" value="edit" class="btn btn-sm btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Nodes</h3>
            </div>
            <div class="block-content p-0">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>FQDN</th>
                            <th>Servers</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($location->nodes as $node)
                            <tr>
                                <td><code>{{ $node->id }}</code></td>
                                <td><a href="{{ route('admin.nodes.view', $node->id) }}">{{ $node->name }}</a></td>
                                <td><code>{{ $node->fqdn }}</code></td>
                                <td>{{ $node->servers->count() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
