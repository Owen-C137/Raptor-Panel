@extends('layouts.admin')

@section('title')
    Server — {{ $server->name }}: Mounts
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
          Manage server mounts.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.servers') }}">Servers</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></li>
          <li class="breadcrumb-item" aria-current="page">Mounts</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
    @include('admin.servers.partials.navigation')

    <div class="row">
        <div class="col-sm-12">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-hdd-o me-2"></i>Available Mounts
                    </h3>
                </div>

                <div class="block-content block-content-full">
                    <div class="table-responsive">
                        <table class="table table-hover table-vcenter">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">ID</th>
                                    <th>Name</th>
                                    <th>Source</th>
                                    <th>Target</th>
                                    <th style="width: 120px;">Status</th>
                                    <th class="text-center" style="width: 80px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($mounts as $mount)
                                    <tr>
                                        <td><code>{{ $mount->id }}</code></td>
                                        <td class="fw-semibold">
                                            <a class="link-fx" href="{{ route('admin.mounts.view', $mount->id) }}">{{ $mount->name }}</a>
                                        </td>
                                        <td><code>{{ $mount->source }}</code></td>
                                        <td><code>{{ $mount->target }}</code></td>

                                        @if (! in_array($mount->id, $server->mounts->pluck('id')->toArray()))
                                            <td>
                                                <span class="badge bg-secondary">Unmounted</span>
                                            </td>

                                            <td class="text-center">
                                                <form action="{{ route('admin.servers.view.mounts.store', [ 'server' => $server->id ]) }}" method="POST">
                                                    {!! csrf_field() !!}
                                                    <input type="hidden" value="{{ $mount->id }}" name="mount_id" />
                                                    <button type="submit" class="btn btn-sm btn-alt-success" title="Mount">
                                                        <i class="fa fa-plus"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        @else
                                            <td>
                                                <span class="badge bg-success">Mounted</span>
                                            </td>

                                            <td class="text-center">
                                                <form action="{{ route('admin.servers.view.mounts.delete', [ 'server' => $server->id, 'mount' => $mount->id ]) }}" method="POST">
                                                    @method('DELETE')
                                                    {!! csrf_field() !!}
                                                    <button type="submit" class="btn btn-sm btn-alt-danger" title="Unmount">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="fa fa-hdd-o fa-2x mb-2"></i>
                                            <br>No mounts available for this server.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
