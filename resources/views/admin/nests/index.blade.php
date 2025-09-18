@extends('layouts.admin')

@section('title')
    Nests
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Nests
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          All nests currently available on this system.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item" aria-current="page">Nests</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="alert alert-danger">
            Eggs are a powerful feature of Pterodactyl Panel that allow for extreme flexibility and configuration. Please note that while powerful, modifying an egg wrongly can very easily brick your servers and cause more problems. Please avoid editing our default eggs — those provided by <code>support@pterodactyl.io</code> — unless you are absolutely sure of what you are doing.
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xs-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Configured Nests</h3>
                <div class="block-options">
                    <a href="#" class="btn btn-sm btn-alt-primary me-2" data-bs-toggle="modal" data-bs-target="#importServiceOptionModal" role="button"><i class="fa fa-upload"></i> Import Egg</a>
                    <a href="{{ route('admin.nests.new') }}" class="btn btn-sm btn-primary">Create New</a>
                </div>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th class="text-center">Eggs</th>
                            <th class="text-center">Servers</th>
                        </tr>
                        @foreach($nests as $nest)
                            <tr>
                                <td><code>{{ $nest->id }}</code></td>
                                <td><a href="{{ route('admin.nests.view', $nest->id) }}" data-bs-toggle="tooltip" data-bs-placement="right" title="{{ $nest->author }}">{{ $nest->name }}</a></td>
                                <td>{{ $nest->description }}</td>
                                <td class="text-center">{{ $nest->eggs_count }}</td>
                                <td class="text-center">{{ $nest->servers_count }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Large Block Modal -->
<div class="modal fade" id="importServiceOptionModal" tabindex="-1" role="dialog" aria-labelledby="importServiceOptionModal" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
                <form action="{{ route('admin.nests.egg.import') }}" enctype="multipart/form-data" method="POST">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Import an Egg</h3>
                        <div class="block-options">
                            <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                                <i class="fa fa-fw fa-times"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="block-content fs-sm">
                        <div class="form-group">
                            <label class="form-label" for="pImportFile">Egg File <span class="text-danger">*</span></label>
                            <input id="pImportFile" type="file" name="import_file" class="form-control" accept="application/json" />
                            <p class="small text-muted">Select the <code>.json</code> file for the new egg that you wish to import.</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="pImportToNest">Associated Nest <span class="text-danger">*</span></label>
                            <select id="pImportToNest" name="import_to_nest" class="form-control">
                                @foreach($nests as $nest)
                                   <option value="{{ $nest->id }}">{{ $nest->name }} &lt;{{ $nest->author }}&gt;</option>
                                @endforeach
                            </select>
                            <p class="small text-muted">Select the nest that this egg will be associated with from the dropdown. If you wish to associate it with a new nest you will need to create that nest before continuing.</p>
                        </div>
                    </div>
                    
                    <div class="block-content block-content-full text-end bg-body">
                        {{ csrf_field() }}
                        <button type="button" class="btn btn-sm btn-alt-secondary me-1" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- END Large Block Modal -->
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
            $('#pImportToNest').select2();
        });
    </script>
@endsection
