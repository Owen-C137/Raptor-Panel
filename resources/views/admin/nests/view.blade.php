@extends('layouts.admin')

@section('title')
    Nests &rarr; {{ $nest->name }}
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          {{ $nest->name }}
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          {{ str_limit($nest->description, 50) }}
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.nests') }}">Nests</a></li>
          <li class="breadcrumb-item" aria-current="page">{{ $nest->name }}</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-6">
        <form action="{{ route('admin.nests.view', $nest->id) }}" method="POST">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Nest Details</h3>
                </div>
                <div class="block-content">
                    <div class="form-group">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ $nest->name }}" />
                        <p class="text-muted small">This should be a descriptive category name that encompasses all of the options within the service.</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="7">{{ $nest->description }}</textarea>
                    </div>
                </div>
                <div class="block-content block-content-full block-content-sm bg-body-light">
                    {!! csrf_field() !!}
                    <button type="submit" name="_method" value="PATCH" class="btn btn-primary">Save</button>
                    <button id="deleteButton" type="submit" name="_method" value="DELETE" class="btn btn-danger"><i class="fa fa-trash-o"></i></button>
                </div>
            </div>
        </form>
    </div>
    
    <div class="col-lg-6">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Nest Information</h3>
            </div>
            <div class="block-content">
                <div class="form-group">
                    <label class="form-label">Nest ID</label>
                    <input type="text" readonly class="form-control" value="{{ $nest->id }}" />
                    <p class="text-muted small">A unique ID used for identification of this nest internally and through the API.</p>
                </div>
                <div class="form-group">
                    <label class="form-label">Author</label>
                    <input type="text" readonly class="form-control" value="{{ $nest->author }}" />
                    <p class="text-muted small">The author of this service option. Please direct questions and issues to them unless this is an official option authored by <code>support@pterodactyl.io</code>.</p>
                </div>
                <div class="form-group">
                    <label class="form-label">UUID</label>
                    <input type="text" readonly class="form-control" value="{{ $nest->uuid }}" />
                    <p class="text-muted small">A UUID that all servers using this option are assigned for identification purposes.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xs-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Nest Eggs</h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th class="text-center">Servers</th>
                            <th class="text-center"></th>
                        </tr>
                        @foreach($nest->eggs as $egg)
                            <tr>
                                <td><code>{{ $egg->id }}</code></td>
                                <td><a href="{{ route('admin.nests.egg.view', $egg->id) }}" data-bs-toggle="tooltip" data-bs-placement="right" title="{{ $egg->author }}">{{ $egg->name }}</a></td>
                                <td>{{ $egg->description }}</td>
                                <td class="text-center"><code>{{ $egg->servers->count() }}</code></td>
                                <td class="text-center">
                                    <a href="{{ route('admin.nests.egg.export', ['egg' => $egg->id]) }}"><i class="fa fa-download"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
            <div class="block-content block-content-full block-content-sm bg-body-light">
                <a href="{{ route('admin.nests.egg.new') }}" class="btn btn-primary">New Egg</a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('#deleteButton').on('mouseenter', function (event) {
            $(this).find('i').html(' Delete Nest');
        }).on('mouseleave', function (event) {
            $(this).find('i').html('');
        });
    </script>
@endsection
