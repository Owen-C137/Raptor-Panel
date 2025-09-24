
@extends('layouts.admin')

@section('title')
    Mounts
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Mounts
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Configure and manage additional mount points for servers.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item" aria-current="page">Mounts</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Mount List</h3>

                    <div class="block-options">
                        <button class="btn btn-sm btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMountModal">Create New</button>
                    </div>
                </div>

                <div class="block-content">
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <tbody>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Source</th>
                                    <th>Target</th>
                                    <th class="text-center">Eggs</th>
                                    <th class="text-center">Nodes</th>
                                    <th class="text-center">Servers</th>
                                </tr>

                            @foreach ($mounts as $mount)
                                <tr>
                                    <td><code>{{ $mount->id }}</code></td>
                                    <td><a href="{{ route('admin.mounts.view', $mount->id) }}">{{ $mount->name }}</a></td>
                                    <td><code>{{ $mount->source }}</code></td>
                                    <td><code>{{ $mount->target }}</code></td>
                                    <td class="text-center">{{ $mount->eggs_count }}</td>
                                    <td class="text-center">{{ $mount->nodes_count }}</td>
                                    <td class="text-center">{{ $mount->servers_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Large Block Modal -->
    <div class="modal fade" id="newMountModal" tabindex="-1" role="dialog" aria-labelledby="newMountModal" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="block block-rounded block-transparent mb-0">
                    <form action="{{ route('admin.mounts') }}" method="POST">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">Create Mount</h3>
                            <div class="block-options">
                                <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                                    <i class="fa fa-fw fa-times"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="block-content fs-sm">
                            <div class="row">
                                <div class="col-md-12">
                                    <label for="pName" class="form-label">Name</label>
                                    <input type="text" id="pName" name="name" class="form-control" />
                                    <p class="text-muted small">Unique name used to separate this mount from another.</p>
                                </div>

                                <div class="col-md-12">
                                    <label for="pDescription" class="form-label">Description</label>
                                    <textarea id="pDescription" name="description" class="form-control" rows="4"></textarea>
                                    <p class="text-muted small">A longer description for this mount, must be less than 191 characters.</p>
                                </div>

                                <div class="col-md-6">
                                    <label for="pSource" class="form-label">Source</label>
                                    <input type="text" id="pSource" name="source" class="form-control" />
                                    <p class="text-muted small">File path on the host system to mount to a container.</p>
                                </div>

                                <div class="col-md-6">
                                    <label for="pTarget" class="form-label">Target</label>
                                    <input type="text" id="pTarget" name="target" class="form-control" />
                                    <p class="text-muted small">Where the mount will be accessible inside a container.</p>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Read Only</label>

                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input type="radio" class="form-check-input" id="pReadOnlyFalse" name="read_only" value="0" checked>
                                            <label class="form-check-label" for="pReadOnlyFalse">False</label>
                                        </div>

                                        <div class="form-check form-check-inline">
                                            <input type="radio" class="form-check-input" id="pReadOnly" name="read_only" value="1">
                                            <label class="form-check-label" for="pReadOnly">True</label>
                                        </div>
                                    </div>

                                    <p class="text-muted small">Is the mount read only inside the container?</p>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">User Mountable</label>

                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input type="radio" class="form-check-input" id="pUserMountableFalse" name="user_mountable" value="0" checked>
                                            <label class="form-check-label" for="pUserMountableFalse">False</label>
                                        </div>

                                        <div class="form-check form-check-inline">
                                            <input type="radio" class="form-check-input" id="pUserMountable" name="user_mountable" value="1">
                                            <label class="form-check-label" for="pUserMountable">True</label>
                                        </div>
                                    </div>

                                    <p class="text-muted small">Should users be able to mount this themselves?</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="block-content block-content-full text-end bg-body">
                            {!! csrf_field() !!}
                            <button type="button" class="btn btn-sm btn-alt-secondary me-1" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-sm btn-primary">Create</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- END Large Block Modal -->
@endsection
