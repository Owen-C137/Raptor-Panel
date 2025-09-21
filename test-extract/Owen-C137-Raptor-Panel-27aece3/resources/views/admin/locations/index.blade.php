@extends('layouts.admin')

@section('title')
    Locations
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Locations
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          All locations that nodes can be assigned to for easier categorization.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.index') }}">Admin</a>
          </li>
          <li class="breadcrumb-item" aria-current="page">
            Locations
          </li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Location List</h3>
                <div class="block-options">
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newLocationModal">Create New</button>
                </div>
            </div>
            <div class="block-content p-0">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Short Code</th>
                            <th>Description</th>
                            <th class="text-center">Nodes</th>
                            <th class="text-center">Servers</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($locations as $location)
                            <tr>
                                <td><code>{{ $location->id }}</code></td>
                                <td><a href="{{ route('admin.locations.view', $location->id) }}">{{ $location->short }}</a></td>
                                <td>{{ $location->long }}</td>
                                <td class="text-center">{{ $location->nodes_count }}</td>
                                <td class="text-center">{{ $location->servers_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="newLocationModal" tabindex="-1" role="dialog" aria-labelledby="newLocationModal" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
                <form action="{{ route('admin.locations') }}" method="POST">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Create Location</h3>
                        <div class="block-options">
                            <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                                <i class="fa fa-fw fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="block-content fs-sm">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="pShortModal" class="form-label">Short Code</label>
                                    <input type="text" name="short" id="pShortModal" class="form-control" />
                                    <p class="text-muted small">A short identifier used to distinguish this location from others. Must be between 1 and 60 characters, for example, <code>us.nyc.lvl3</code>.</p>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="pLongModal" class="form-label">Description</label>
                                    <textarea name="long" id="pLongModal" class="form-control" rows="4"></textarea>
                                    <p class="text-muted small">A longer description of this location. Must be less than 191 characters.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="block-content block-content-full text-end bg-body">
                        {!! csrf_field() !!}
                        <button type="button" class="btn btn-sm btn-alt-secondary me-1" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-success">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
