@extends('layouts.admin')

@section('title')
    Database Hosts &rarr; View &rarr; {{ $host->name }}
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          {{ $host->name }}
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Viewing associated databases and details for this database host.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.databases') }}">Database Hosts</a></li>
          <li class="breadcrumb-item" aria-current="page">{{ $host->name }}</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<form action="{{ route('admin.databases.view', $host->id) }}" method="POST">
    <div class="row">
        <div class="col-sm-6">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Host Details</h3>
                </div>
                <div class="block-content">
                    <div class="form-group mb-3">
                        <label for="pName" class="form-label">Name</label>
                        <input type="text" id="pName" name="name" class="form-control" value="{{ old('name', $host->name) }}" />
                    </div>
                    <div class="form-group mb-3">
                        <label for="pHost" class="form-label">Host</label>
                        <input type="text" id="pHost" name="host" class="form-control" value="{{ old('host', $host->host) }}" />
                        <p class="text-muted small">The IP address or FQDN that should be used when attempting to connect to this MySQL host <em>from the panel</em> to add new databases.</p>
                    </div>
                    <div class="form-group mb-3">
                        <label for="pPort" class="form-label">Port</label>
                        <input type="text" id="pPort" name="port" class="form-control" value="{{ old('port', $host->port) }}" />
                        <p class="text-muted small">The port that MySQL is running on for this host.</p>
                    </div>
                    <div class="form-group mb-3">
                        <label for="pNodeId" class="form-label">Linked Node</label>
                        <select name="node_id" id="pNodeId" class="form-control">
                            <option value="">None</option>
                            @foreach($locations as $location)
                                <optgroup label="{{ $location->short }}">
                                    @foreach($location->nodes as $node)
                                        <option value="{{ $node->id }}" {{ $host->node_id !== $node->id ?: 'selected' }}>{{ $node->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="text-muted small">This setting does nothing other than default to this database host when adding a database to a server on the selected node.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">User Details</h3>
                </div>
                <div class="block-content">
                    <div class="form-group mb-3">
                        <label for="pUsername" class="form-label">Username</label>
                        <input type="text" name="username" id="pUsername" class="form-control" value="{{ old('username', $host->username) }}" />
                        <p class="text-muted small">The username of an account that has enough permissions to create new users and databases on the system.</p>
                    </div>
                    <div class="form-group mb-3">
                        <label for="pPassword" class="form-label">Password</label>
                        <input type="password" name="password" id="pPassword" class="form-control" />
                        <p class="text-muted small">The password to the account defined. Leave blank to continue using the assigned password.</p>
                    </div>
                    <hr />
                    <p class="text-danger small text-start">The account defined for this database host <strong>must</strong> have the <code>WITH GRANT OPTION</code> permission. If the defined account does not have this permission requests to create databases <em>will</em> fail. <strong>Do not use the same account details for MySQL that you have defined for this panel.</strong></p>
                </div>
                <div class="block-content block-content-full block-content-sm text-end border-top">
                    {!! csrf_field() !!}
                    <button name="_method" value="DELETE" class="btn btn-sm btn-outline-danger me-1"><i class="fa fa-trash-o"></i></button>
                    <button name="_method" value="PATCH" class="btn btn-sm btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>
</form>
<div class="row">
    <div class="col-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Databases</h3>
            </div>
            <div class="block-content p-0">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>Server</th>
                            <th>Database Name</th>
                            <th>Username</th>
                            <th>Connections From</th>
                            <th>Max Connections</th>
                            <th class="text-center" style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($databases as $database)
                            <tr>
                                <td><a href="{{ route('admin.servers.view', $database->getRelation('server')->id) }}">{{ $database->getRelation('server')->name }}</a></td>
                                <td>{{ $database->database }}</td>
                                <td>{{ $database->username }}</td>
                                <td>{{ $database->remote }}</td>
                                @if($database->max_connections != null)
                                    <td>{{ $database->max_connections }}</td>
                                @else
                                    <td>Unlimited</td>
                                @endif
                                <td class="text-center">
                                    <a href="{{ route('admin.servers.view.database', $database->getRelation('server')->id) }}">
                                        <button class="btn btn-sm btn-primary">Manage</button>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($databases->hasPages())
                <div class="block-content block-content-full block-content-sm text-center border-top">
                    {!! $databases->render() !!}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('#pNodeId').select2();
    </script>
@endsection
