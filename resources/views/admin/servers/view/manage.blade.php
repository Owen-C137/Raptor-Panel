@extends('layouts.admin')

@section('title')
    Server — {{ $server->name }}: Manage
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          {{ $server->name }}
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Additional actions to control this server.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.servers') }}">Servers</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></li>
          <li class="breadcrumb-item" aria-current="page">Manage</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
    @include('admin.servers.partials.navigation')
    <div class="row">
        <div class="col-sm-4">
            <div class="block block-rounded border-danger">
                <div class="block-header block-header-default bg-danger">
                    <h3 class="block-title text-white">
                        <i class="fa fa-refresh text-white me-2"></i>Reinstall Server
                    </h3>
                </div>
                <div class="block-content">
                    <p class="mb-0">This will reinstall the server with the assigned service scripts.</p>
                    <div class="alert alert-warning d-flex mt-3 mb-0">
                        <div class="flex-shrink-0">
                            <i class="fa fa-fw fa-exclamation-triangle"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <strong>Danger!</strong> This could overwrite server data.
                        </div>
                    </div>
                </div>
                <div class="block-content block-content-full text-end bg-body-light">
                    @if($server->isInstalled())
                        <form action="{{ route('admin.servers.view.manage.reinstall', $server->id) }}" method="POST">
                            {!! csrf_field() !!}
                            <button type="submit" class="btn btn-danger">
                                <i class="fa fa-refresh me-1"></i> Reinstall Server
                            </button>
                        </form>
                    @else
                        <button class="btn btn-secondary" disabled>
                            <i class="fa fa-times me-1"></i> Server Must Install Properly to Reinstall
                        </button>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="block block-rounded border-primary">
                <div class="block-header block-header-default bg-primary">
                    <h3 class="block-title text-white">
                        <i class="fa fa-toggle-on text-white me-2"></i>Install Status
                    </h3>
                </div>
                <div class="block-content">
                    <p class="mb-0">If you need to change the install status from uninstalled to installed, or vice versa, you may do so with the button below.</p>
                </div>
                <div class="block-content block-content-full text-end bg-body-light">
                    <form action="{{ route('admin.servers.view.manage.toggle', $server->id) }}" method="POST">
                        {!! csrf_field() !!}
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-toggle-on me-1"></i> Toggle Install Status
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @if(! $server->isSuspended())
            <div class="col-sm-4">
                <div class="block block-rounded border-warning">
                    <div class="block-header block-header-default bg-warning">
                        <h3 class="block-title text-dark">
                            <i class="fa fa-pause text-dark me-2"></i>Suspend Server
                        </h3>
                    </div>
                    <div class="block-content">
                        <p class="mb-0">This will suspend the server, stop any running processes, and immediately block the user from being able to access their files or otherwise manage the server through the panel or API.</p>
                    </div>
                    <div class="block-content block-content-full text-end bg-body-light">
                        <form action="{{ route('admin.servers.view.manage.suspension', $server->id) }}" method="POST">
                            {!! csrf_field() !!}
                            <input type="hidden" name="action" value="suspend" />
                            <button type="submit" class="btn btn-warning @if(! is_null($server->transfer)) disabled @endif">
                                <i class="fa fa-pause me-1"></i> Suspend Server
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <div class="col-sm-4">
                <div class="block block-rounded border-success">
                    <div class="block-header block-header-default bg-success">
                        <h3 class="block-title text-white">
                            <i class="fa fa-play text-white me-2"></i>Unsuspend Server
                        </h3>
                    </div>
                    <div class="block-content">
                        <p class="mb-0">This will unsuspend the server and restore normal user access.</p>
                    </div>
                    <div class="block-content block-content-full text-end bg-body-light">
                        <form action="{{ route('admin.servers.view.manage.suspension', $server->id) }}" method="POST">
                            {!! csrf_field() !!}
                            <input type="hidden" name="action" value="unsuspend" />
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-play me-1"></i> Unsuspend Server
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if(is_null($server->transfer))
            <div class="col-sm-4">
                <div class="block block-rounded border-success">
                    <div class="block-header block-header-default bg-success">
                        <h3 class="block-title text-white">
                            <i class="fa fa-share text-white me-2"></i>Transfer Server
                        </h3>
                    </div>
                    <div class="block-content">
                        <p class="mb-3">
                            Transfer this server to another node connected to this panel.
                        </p>
                        <div class="alert alert-warning d-flex mb-0">
                            <div class="flex-shrink-0">
                                <i class="fa fa-fw fa-exclamation-triangle"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <strong>Warning!</strong> This feature has not been fully tested and may have bugs.
                            </div>
                        </div>
                    </div>

                    <div class="block-content block-content-full text-end bg-body-light">
                        @if($canTransfer)
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#transferServerModal">
                                <i class="fa fa-share me-1"></i> Transfer Server
                            </button>
                        @else
                            <div class="text-start">
                                <button class="btn btn-secondary" disabled>
                                    <i class="fa fa-times me-1"></i> Transfer Server
                                </button>
                                <small class="text-muted d-block mt-2">Transferring a server requires more than one node to be configured on your panel.</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="col-sm-4">
                <div class="block block-rounded border-info">
                    <div class="block-header block-header-default bg-info">
                        <h3 class="block-title text-white">
                            <i class="fa fa-spinner fa-spin text-white me-2"></i>Transfer Server
                        </h3>
                    </div>
                    <div class="block-content">
                        <p class="mb-0">
                            This server is currently being transferred to another node.
                            Transfer was initiated at <strong>{{ $server->transfer->created_at }}</strong>
                        </p>
                    </div>

                    <div class="block-content block-content-full text-end bg-body-light">
                        <button class="btn btn-secondary" disabled>
                            <i class="fa fa-spinner fa-spin me-1"></i> Transfer in Progress
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="modal fade" id="transferServerModal" tabindex="-1" aria-labelledby="transferServerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.servers.view.manage.transfer', $server->id) }}" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="transferServerModalLabel">
                            <i class="fa fa-share me-2"></i>Transfer Server
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="pNodeId" class="form-label">Node</label>
                                    <select name="node_id" id="pNodeId" class="form-control">
                                        @foreach($locations as $location)
                                            <optgroup label="{{ $location->long }} ({{ $location->short }})">
                                                @foreach($location->nodes as $node)
                                                    @if($node->id != $server->node_id)
                                                        <option value="{{ $node->id }}"
                                                                @if($location->id === old('location_id')) selected @endif
                                                        >{{ $node->name }}</option>
                                                    @endif
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">The node which this server will be transferred to.</small>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="pAllocation" class="form-label">Default Allocation</label>
                                    <select name="allocation_id" id="pAllocation" class="form-control"></select>
                                    <small class="text-muted">The main allocation that will be assigned to this server.</small>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="pAllocationAdditional" class="form-label">Additional Allocation(s)</label>
                                    <select name="allocation_additional[]" id="pAllocationAdditional" class="form-control" multiple></select>
                                    <small class="text-muted">Additional allocations to assign to this server on creation.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        {!! csrf_field() !!}
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fa fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-check me-1"></i> Confirm Transfer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('vendor/lodash/lodash.js') !!}

    @if($canTransfer)
        {!! Theme::js('js/admin/server/transfer.js') !!}
    @endif
@endsection
