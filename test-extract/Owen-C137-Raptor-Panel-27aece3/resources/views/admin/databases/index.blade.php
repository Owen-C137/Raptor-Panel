@extends('layouts.admin')

@section('title')
    Database Hosts
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Database Hosts
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Database hosts that servers can have databases created on.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.index') }}">Admin</a>
          </li>
          <li class="breadcrumb-item" aria-current="page">
            Database Hosts
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
                <h3 class="block-title">Host List</h3>
                <div class="block-options">
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newHostModal">Create New</button>
                </div>
            </div>
            <div class="block-content p-0">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Host</th>
                            <th>Port</th>
                            <th>Username</th>
                            <th class="text-center">Databases</th>
                            <th class="text-center">Node</th>
                        </tr>
                        @foreach ($hosts as $host)
                            <tr>
                                <td><code>{{ $host->id }}</code></td>
                                <td><a href="{{ route('admin.databases.view', $host->id) }}">{{ $host->name }}</a></td>
                                <td><code>{{ $host->host }}</code></td>
                                <td><code>{{ $host->port }}</code></td>
                                <td>{{ $host->username }}</td>
                                <td class="text-center">{{ $host->databases_count }}</td>
                                <td class="text-center">
                                    @if(! is_null($host->node))
                                        <a href="{{ route('admin.nodes.view', $host->node->id) }}">{{ $host->node->name }}</a>
                                    @else
                                        <span class="badge bg-secondary">None</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="newHostModal" tabindex="-1" role="dialog" aria-labelledby="newHostModal" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
                <form action="{{ route('admin.databases') }}" method="POST">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Create New Database Host</h3>
                        <div class="block-options">
                            <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                                <i class="fa fa-fw fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="block-content fs-sm">
                        <div class="form-group mb-3">
                            <label for="pName" class="form-label">Name</label>
                            <input type="text" name="name" id="pName" class="form-control" />
                            <p class="text-muted small">A short identifier used to distinguish this location from others. Must be between 1 and 60 characters, for example, <code>us.nyc.lvl3</code>.</p>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="pHost" class="form-label">Host</label>
                                    <input type="text" name="host" id="pHost" class="form-control" />
                                    <p class="text-muted small">The IP address or FQDN that should be used when attempting to connect to this MySQL host <em>from the panel</em> to add new databases.</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="pPort" class="form-label">Port</label>
                                    <input type="text" name="port" id="pPort" class="form-control" value="3306"/>
                                    <p class="text-muted small">The port that MySQL is running on for this host.</p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="pUsername" class="form-label">Username</label>
                                    <input type="text" name="username" id="pUsername" class="form-control" />
                                    <p class="text-muted small">The username of an account that has enough permissions to create new users and databases on the system.</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="pPassword" class="form-label">Password</label>
                                    <input type="password" name="password" id="pPassword" class="form-control" />
                                    <p class="text-muted small">The password to the account defined.</p>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="pNodeId" class="form-label">Linked Node</label>
                            <select name="node_id" id="pNodeId" class="form-control">
                                <option value="">None</option>
                                @foreach($locations as $location)
                                    <optgroup label="{{ $location->short }}">
                                        @foreach($location->nodes as $node)
                                            <option value="{{ $node->id }}">{{ $node->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <p class="text-muted small">This setting does nothing other than default to this database host when adding a database to a server on the selected node.</p>
                        </div>
                        <div class="alert alert-danger d-flex">
                            <div class="flex-shrink-0">
                                <i class="fa fa-fw fa-exclamation-circle"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="mb-0">The account defined for this database host <strong>must</strong> have the <code>WITH GRANT OPTION</code> permission. If the defined account does not have this permission requests to create databases <em>will</em> fail. <strong>Do not use the same account details for MySQL that you have defined for this panel.</strong></p>
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

@section('footer-scripts')
    @parent
    <script>
        $('#pNodeId').select2();
    </script>
@endsection
