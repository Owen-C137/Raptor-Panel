@extends('layouts.admin')

@section('title')
    Server — {{ $server->name }}: Delete
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
          Delete this server from the panel.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.servers') }}">Servers</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></li>
          <li class="breadcrumb-item" aria-current="page">Delete</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
@include('admin.servers.partials.navigation')
<div class="row">
    <div class="col-md-6">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-trash-o text-danger me-2"></i>Safely Delete Server
                </h3>
            </div>
            <div class="block-content">
                <p class="mb-3">This action will attempt to delete the server from both the panel and daemon. If either one reports an error the action will be cancelled.</p>
                <div class="alert alert-danger d-flex">
                    <div class="flex-shrink-0">
                        <i class="fa fa-fw fa-exclamation-circle"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="mb-0">
                            Deleting a server is an <strong>irreversible action</strong>. All server data (including files and users) will be removed from the system.
                        </p>
                    </div>
                </div>
            </div>
            <div class="block-content block-content-full text-end bg-body-light">
                <form id="deleteform" action="{{ route('admin.servers.view.delete', $server->id) }}" method="POST">
                    {!! csrf_field() !!}
                    <button id="deletebtn" type="button" class="btn btn-danger">
                        <i class="fa fa-trash me-1"></i> Safely Delete This Server
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="block block-rounded border-danger">
            <div class="block-header block-header-default bg-danger">
                <h3 class="block-title text-white">
                    <i class="fa fa-warning text-white me-2"></i>Force Delete Server
                </h3>
            </div>
            <div class="block-content">
                <p class="mb-3">This action will attempt to delete the server from both the panel and daemon. If the daemon does not respond, or reports an error the deletion will continue.</p>
                <div class="alert alert-warning d-flex">
                    <div class="flex-shrink-0">
                        <i class="fa fa-fw fa-exclamation-triangle"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="mb-0">
                            Deleting a server is an <strong>irreversible action</strong>. All server data (including files and users) will be removed from the system. This method may leave dangling files on your daemon if it reports an error.
                        </p>
                    </div>
                </div>
            </div>
            <div class="block-content block-content-full text-end bg-body-light">
                <form id="forcedeleteform" action="{{ route('admin.servers.view.delete', $server->id) }}" method="POST">
                    {!! csrf_field() !!}
                    <input type="hidden" name="force_delete" value="1" />
                    <button id="forcedeletebtn" type="button" class="btn btn-danger">
                        <i class="fa fa-bolt me-1"></i> Forcibly Delete This Server
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
    $('#deletebtn').click(function (event) {
        event.preventDefault();
        swal({
            title: '',
            type: 'warning',
            text: 'Are you sure that you want to delete this server? There is no going back, all data will immediately be removed.',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d9534f',
            closeOnConfirm: false
        }, function () {
            $('#deleteform').submit()
        });
    });

    $('#forcedeletebtn').click(function (event) {
        event.preventDefault();
        swal({
            title: '',
            type: 'warning',
            text: 'Are you sure that you want to delete this server? There is no going back, all data will immediately be removed.',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d9534f',
            closeOnConfirm: false
        }, function () {
            $('#forcedeleteform').submit()
        });
    });
    </script>
@endsection
