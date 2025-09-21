@extends('layouts.admin')

@section('title')
    {{ $node->name }}
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          {{ $node->name }}
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          A quick overview of your node.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.nodes') }}">Nodes</a></li>
          <li class="breadcrumb-item" aria-current="page">{{ $node->name }}</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
@include('admin.nodes.view._navigation')

<div class="row">
    <div class="col-sm-8">
        <div class="row">
            <div class="col-12">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Information</h3>
                    </div>
                    <div class="block-content p-0">
                        <table class="table table-vcenter">
                            <tbody>
                                <tr>
                                    <td>Daemon Version</td>
                                    <td><code data-attr="info-version"><i class="fa fa-refresh fa-fw fa-spin"></i></code> (Latest: <code>{{ $version->getDaemon() }}</code>)</td>
                                </tr>
                                <tr>
                                    <td>System Information</td>
                                    <td data-attr="info-system"><i class="fa fa-refresh fa-fw fa-spin"></i></td>
                                </tr>
                                <tr>
                                    <td>Total CPU Threads</td>
                                    <td data-attr="info-cpus"><i class="fa fa-refresh fa-fw fa-spin"></i></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @if ($node->description)
                <div class="col-12">
                    <div class="block block-rounded">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">Description</h3>
                        </div>
                        <div class="block-content">
                            <pre>{{ $node->description }}</pre>
                        </div>
                    </div>
                </div>
            @endif
            <div class="col-12">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Delete Node</h3>
                    </div>
                    <div class="block-content">
                        <p class="mb-0">Deleting a node is a irreversible action and will immediately remove this node from the panel. There must be no servers associated with this node in order to continue.</p>
                    </div>
                    <div class="block-content block-content-full block-content-sm text-end border-top">
                        <form action="{{ route('admin.nodes.view.delete', $node->id) }}" method="POST">
                            {!! csrf_field() !!}
                            {!! method_field('DELETE') !!}
                            <button type="submit" class="btn btn-danger btn-sm" {{ ($node->servers_count < 1) ?: 'disabled' }}>Yes, Delete This Node</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">At-a-Glance</h3>
            </div>
            <div class="block-content">
                <div class="row">
                    @if($node->maintenance_mode)
                    <div class="col-12 mb-3">
                        <div class="alert alert-warning d-flex">
                            <div class="flex-shrink-0">
                                <i class="fa fa-wrench fa-fw"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="mb-0">
                                    <strong>This node is under maintenance</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="col-12 mb-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <p class="mb-1 fw-medium">Disk Space Allocated</p>
                                <p class="fs-sm text-muted mb-0">{{ $stats['disk']['value'] }} / {{ $stats['disk']['max'] }} MiB</p>
                            </div>
                            <div class="text-end">
                                <i class="fa fa-hdd fa-2x text-{{ $stats['disk']['css'] === 'green' ? 'success' : ($stats['disk']['css'] === 'yellow' ? 'warning' : 'danger') }}"></i>
                            </div>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-{{ $stats['disk']['css'] === 'green' ? 'success' : ($stats['disk']['css'] === 'yellow' ? 'warning' : 'danger') }}" style="width: {{ $stats['disk']['percent'] }}%"></div>
                        </div>
                    </div>
                    <div class="col-12 mb-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                <p class="mb-1 fw-medium">Memory Allocated</p>
                                <p class="fs-sm text-muted mb-0">{{ $stats['memory']['value'] }} / {{ $stats['memory']['max'] }} MiB</p>
                            </div>
                            <div class="text-end">
                                <i class="fa fa-memory fa-2x text-{{ $stats['memory']['css'] === 'green' ? 'success' : ($stats['memory']['css'] === 'yellow' ? 'warning' : 'danger') }}"></i>
                            </div>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-{{ $stats['memory']['css'] === 'green' ? 'success' : ($stats['memory']['css'] === 'yellow' ? 'warning' : 'danger') }}" style="width: {{ $stats['memory']['percent'] }}%"></div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between">
                            <div>
                                <p class="mb-1 fw-medium">Total Servers</p>
                                <p class="fs-sm text-muted mb-0">Active servers on this node</p>
                            </div>
                            <div class="text-end">
                                <span class="fs-3 fw-bold text-primary">{{ $node->servers_count }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    (function getInformation() {
        $.ajax({
            method: 'GET',
            url: '/admin/nodes/view/{{ $node->id }}/system-information',
            timeout: 5000,
        }).done(function (data) {
            $('[data-attr="info-version"]').html(escapeHtml(data.version));
            $('[data-attr="info-system"]').html(escapeHtml(data.system.type) + ' (' + escapeHtml(data.system.arch) + ') <code>' + escapeHtml(data.system.release) + '</code>');
            $('[data-attr="info-cpus"]').html(data.system.cpus);
        }).fail(function (jqXHR) {

        }).always(function() {
            setTimeout(getInformation, 10000);
        });
    })();
    </script>
@endsection
