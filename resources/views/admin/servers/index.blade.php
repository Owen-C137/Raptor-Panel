@extends('layouts.admin')

@section('title')
    List Servers
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Servers
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          All servers available on the system.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.index') }}">Admin</a>
          </li>
          <li class="breadcrumb-item" aria-current="page">
            Servers
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
                <h3 class="block-title">Server List</h3>
                <div class="block-options">
                    <form action="{{ route('admin.servers') }}" method="GET" class="d-flex">
                        <div class="input-group input-group-sm me-2">
                            <input type="text" name="filter[*]" class="form-control" value="{{ request()->input()['filter']['*'] ?? '' }}" placeholder="Search Servers">
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>
                        <a href="{{ route('admin.servers.new') }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-plus me-1"></i> Create New
                        </a>
                    </form>
                </div>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-hover table-vcenter">
                        <thead>
                            <tr>
                                <th>Server Name</th>
                                <th>UUID</th>
                                <th>Owner</th>
                                <th>Node</th>
                                <th>Connection</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($servers as $server)
                                <tr data-server="{{ $server->uuidShort }}">
                                    <td>
                                        <a href="{{ route('admin.servers.view', $server->id) }}" class="fw-semibold">
                                            {{ $server->name }}
                                        </a>
                                    </td>
                                    <td>
                                        <code title="{{ $server->uuid }}">{{ $server->uuid }}</code>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.users.view', $server->user->id) }}">
                                            {{ $server->user->username }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.nodes.view', $server->node->id) }}">
                                            {{ $server->node->name }}
                                        </a>
                                    </td>
                                    <td>
                                        <code>{{ $server->allocation->alias }}:{{ $server->allocation->port }}</code>
                                    </td>
                                    <td class="text-center">
                                        @if($server->isSuspended())
                                            <span class="badge bg-danger">Suspended</span>
                                        @elseif(! $server->isInstalled())
                                            <span class="badge bg-warning">Installing</span>
                                        @else
                                            <span class="badge bg-success">Active</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a class="btn btn-sm btn-outline-primary" href="/server/{{ $server->uuidShort }}">
                                            <i class="fa fa-wrench"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if($servers->hasPages())
                <div class="block-content block-content-full bg-body-light">
                    <div class="d-flex justify-content-center">
                        {!! $servers->appends(['filter' => Request::input('filter')])->render() !!}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('.console-popout').on('click', function (event) {
            event.preventDefault();
            window.open($(this).attr('href'), 'Pterodactyl Console', 'width=800,height=400');
        });
    </script>
@endsection
