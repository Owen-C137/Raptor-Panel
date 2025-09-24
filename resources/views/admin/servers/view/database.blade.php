@extends('layouts.admin')

@section('title')
    Server — {{ $server->name }}: Databases
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
          Manage server databases.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.servers') }}">Servers</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></li>
          <li class="breadcrumb-item" aria-current="page">Databases</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
@include('admin.servers.partials.navigation')
<div class="row">
    <div class="col-sm-7">
        <div class="alert alert-info">
            <i class="fa fa-info-circle me-2"></i>
            Database passwords can be viewed when <a href="/server/{{ $server->uuidShort }}/databases">visiting this server</a> on the front-end.
        </div>
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Active Databases</h3>
            </div>
            <div class="block-content block-content-full">
                <div class="table-responsive">
                    <table class="table table-hover table-vcenter">
                        <thead>
                            <tr>
                                <th>Database</th>
                                <th>Username</th>
                                <th>Connections From</th>
                                <th>Host</th>
                                <th>Max Connections</th>
                                <th class="text-center" style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($server->databases as $database)
                                <tr>
                                    <td class="fw-semibold">{{ $database->database }}</td>
                                    <td>{{ $database->username }}</td>
                                    <td>{{ $database->remote }}</td>
                                    <td><code>{{ $database->host->host }}:{{ $database->host->port }}</code></td>
                                    <td>
                                        @if($database->max_connections != null)
                                            {{ $database->max_connections }}
                                        @else
                                            <span class="text-muted">Unlimited</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group" aria-label="Database Actions">
                                            <button data-action="reset-password" data-id="{{ $database->id }}" class="btn btn-sm btn-alt-primary" title="Reset Password">
                                                <i class="fa fa-refresh"></i>
                                            </button>
                                            <button data-action="remove" data-id="{{ $database->id }}" class="btn btn-sm btn-alt-danger" title="Delete Database">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fa fa-database fa-2x mb-2"></i>
                                        <br>No databases configured for this server.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-plus text-success me-2"></i>Create New Database
                </h3>
            </div>
            <form action="{{ route('admin.servers.view.database', $server->id) }}" method="POST">
                <div class="block-content">
                    <div class="form-group mb-3">
                        <label for="pDatabaseHostId" class="form-label">Database Host</label>
                        <select id="pDatabaseHostId" name="database_host_id" class="form-control">
                            @foreach($hosts as $host)
                                <option value="{{ $host->id }}">{{ $host->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Select the host database server that this database should be created on.</small>
                    </div>
                    <div class="form-group mb-3">
                        <label for="pDatabaseName" class="form-label">Database</label>
                        <div class="input-group">
                            <span class="input-group-text">s{{ $server->id }}_</span>
                            <input id="pDatabaseName" type="text" name="database" class="form-control" placeholder="database" />
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="pRemote" class="form-label">Connections</label>
                        <input id="pRemote" type="text" name="remote" class="form-control" value="%" />
                        <small class="text-muted">This should reflect the IP address that connections are allowed from. Uses standard MySQL notation. If unsure leave as <code>%</code>.</small>
                    </div>
                    <div class="form-group mb-3">
                        <label for="pmax_connections" class="form-label">Concurrent Connections</label>
                        <input id="pmax_connections" type="text" name="max_connections" class="form-control"/>
                        <small class="text-muted">This should reflect the max number of concurrent connections from this user to the database. Leave empty for unlimited.</small>
                    </div>
                </div>
                <div class="block-content block-content-full text-end bg-body-light">
                    {!! csrf_field() !!}
                    <div class="mb-2">
                        <small class="text-muted">A username and password for this database will be randomly generated after form submission.</small>
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-plus me-1"></i> Create Database
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
    $('#pDatabaseHost').select2();
    $('[data-action="remove"]').click(function (event) {
        event.preventDefault();
        var self = $(this);
        swal({
            title: '',
            type: 'warning',
            text: 'Are you sure that you want to delete this database? There is no going back, all data will immediately be removed.',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d9534f',
            closeOnConfirm: false,
            showLoaderOnConfirm: true,
        }, function () {
            $.ajax({
                method: 'DELETE',
                url: '/admin/servers/view/{{ $server->id }}/database/' + self.data('id') + '/delete',
                headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
            }).done(function () {
                self.parent().parent().slideUp();
                swal.close();
            }).fail(function (jqXHR) {
                console.error(jqXHR);
                swal({
                    type: 'error',
                    title: 'Whoops!',
                    text: (typeof jqXHR.responseJSON.error !== 'undefined') ? jqXHR.responseJSON.error : 'An error occurred while processing this request.'
                });
            });
        });
    });
    $('[data-action="reset-password"]').click(function (e) {
        e.preventDefault();
        var block = $(this);
        $(this).addClass('disabled').find('i').addClass('fa-spin');
        $.ajax({
            type: 'PATCH',
            url: '/admin/servers/view/{{ $server->id }}/database',
            headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
            data: { database: $(this).data('id') },
        }).done(function (data) {
            swal({
                type: 'success',
                title: '',
                text: 'The password for this database has been reset.',
            });
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error(jqXHR);
            var error = 'An error occurred while trying to process this request.';
            if (typeof jqXHR.responseJSON !== 'undefined' && typeof jqXHR.responseJSON.error !== 'undefined') {
                error = jqXHR.responseJSON.error;
            }
            swal({
                type: 'error',
                title: 'Whoops!',
                text: error
            });
        }).always(function () {
            block.removeClass('disabled').find('i').removeClass('fa-spin');
        });
    });
    </script>
@endsection
