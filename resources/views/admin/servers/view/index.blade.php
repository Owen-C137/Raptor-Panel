@extends('layouts.admin')

@section('title')
    Server — {{ $server->name }}
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
          {{ str_limit($server->description) }}
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.servers') }}">Servers</a></li>
          <li class="breadcrumb-item" aria-current="page">{{ $server->name }}</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
@include('admin.servers.partials.navigation')
<div class="row">
    <div class="col-sm-8">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Information</h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-hover table-vcenter">
                        <tbody>
                            <tr>
                                <td class="fw-semibold">Internal Identifier</td>
                                <td><code>{{ $server->id }}</code></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">External Identifier</td>
                                @if(is_null($server->external_id))
                                    <td><span class="badge bg-secondary">Not Set</span></td>
                                @else
                                    <td><code>{{ $server->external_id }}</code></td>
                                @endif
                            </tr>
                            <tr>
                                <td class="fw-semibold">UUID / Docker Container ID</td>
                                <td><code>{{ $server->uuid }}</code></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Current Egg</td>
                                <td>
                                    <a href="{{ route('admin.nests.view', $server->nest_id) }}">{{ $server->nest->name }}</a> ::
                                    <a href="{{ route('admin.nests.egg.view', $server->egg_id) }}">{{ $server->egg->name }}</a>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Server Name</td>
                                <td>{{ $server->name }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">CPU Limit</td>
                                <td>
                                    @if($server->cpu === 0)
                                        <code>Unlimited</code>
                                    @else
                                        <code>{{ $server->cpu }}%</code>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">CPU Pinning</td>
                                <td>
                                    @if($server->threads != null)
                                        <code>{{ $server->threads }}</code>
                                    @else
                                        <span class="badge bg-secondary">Not Set</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Memory</td>
                                <td>
                                    @if($server->memory === 0)
                                        <code>Unlimited</code>
                                    @else
                                        <code>{{ $server->memory }}MiB</code>
                                    @endif
                                    /
                                    @if($server->swap === 0)
                                        <code data-bs-toggle="tooltip" data-bs-placement="top" title="Swap Space">Not Set</code>
                                    @elseif($server->swap === -1)
                                        <code data-bs-toggle="tooltip" data-bs-placement="top" title="Swap Space">Unlimited</code>
                                    @else
                                        <code data-bs-toggle="tooltip" data-bs-placement="top" title="Swap Space"> {{ $server->swap }}MiB</code>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Disk Space</td>
                                <td>
                                    @if($server->disk === 0)
                                        <code>Unlimited</code>
                                    @else
                                        <code>{{ $server->disk }}MiB</code>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Block IO Weight</td>
                                <td><code>{{ $server->io }}</code></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Default Connection</td>
                                <td><code>{{ $server->allocation->ip }}:{{ $server->allocation->port }}</code></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Connection Alias</td>
                                <td>
                                    @if($server->allocation->alias !== $server->allocation->ip)
                                        <code>{{ $server->allocation->alias }}:{{ $server->allocation->port }}</code>
                                    @else
                                        <span class="badge bg-secondary">No Alias Assigned</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="row">
            @if($server->isSuspended())
                <div class="col-12 mb-4">
                    <div class="block block-rounded block-themed block-transparent">
                        <div class="block-header bg-warning">
                            <h3 class="block-title text-white">
                                <i class="fa fa-pause me-1"></i> Suspended
                            </h3>
                        </div>
                        <div class="block-content bg-warning-light">
                            <p class="mb-0">This server is currently suspended and cannot be started.</p>
                        </div>
                    </div>
                </div>
            @endif
            @if(!$server->isInstalled())
                <div class="col-12 mb-4">
                    <div class="block block-rounded block-themed block-transparent">
                        <div class="block-header {{ (! $server->isInstalled()) ? 'bg-info' : 'bg-danger' }}">
                            <h3 class="block-title text-white">
                                <i class="fa fa-{{ (! $server->isInstalled()) ? 'download' : 'times' }} me-1"></i> 
                                {{ (! $server->isInstalled()) ? 'Installing' : 'Install Failed' }}
                            </h3>
                        </div>
                        <div class="block-content {{ (! $server->isInstalled()) ? 'bg-info-light' : 'bg-danger-light' }}">
                            <p class="mb-0">{{ (! $server->isInstalled()) ? 'Server is currently being installed.' : 'Server installation has failed.' }}</p>
                        </div>
                    </div>
                </div>
            @endif
            <div class="col-12 mb-4">
                <div class="block block-rounded block-link-shadow">
                    <div class="block-content block-content-full text-center">
                        <div class="py-3">
                            <div class="fs-3 fw-semibold text-primary">
                                <i class="fa fa-user"></i>
                            </div>
                            <div class="fs-sm fw-semibold text-uppercase text-muted pt-1">Server Owner</div>
                            <div class="fw-semibold">{{ str_limit($server->user->username, 16) }}</div>
                        </div>
                    </div>
                    <a class="block-content block-content-full block-content-sm text-center" href="{{ route('admin.users.view', $server->user->id) }}">
                        <span class="fs-sm fw-semibold">
                            View User <i class="fa fa-arrow-right ms-1"></i>
                        </span>
                    </a>
                </div>
            </div>
            <div class="col-12">
                <div class="block block-rounded block-link-shadow">
                    <div class="block-content block-content-full text-center">
                        <div class="py-3">
                            <div class="fs-3 fw-semibold text-primary">
                                <i class="fa fa-server"></i>
                            </div>
                            <div class="fs-sm fw-semibold text-uppercase text-muted pt-1">Server Node</div>
                            <div class="fw-semibold">{{ str_limit($server->node->name, 16) }}</div>
                        </div>
                    </div>
                    <a class="block-content block-content-full block-content-sm text-center" href="{{ route('admin.nodes.view', $server->node->id) }}">
                        <span class="fs-sm fw-semibold">
                            View Node <i class="fa fa-arrow-right ms-1"></i>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
