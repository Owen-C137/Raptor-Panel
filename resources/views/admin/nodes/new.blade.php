@extends('layouts.admin')

@section('title')
    Nodes &rarr; New
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          New Node
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Create a new local or remote node for servers to be installed to.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.nodes') }}">Nodes</a></li>
          <li class="breadcrumb-item" aria-current="page">New</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<form action="{{ route('admin.nodes.new') }}" method="POST">
    <div class="row">
        <div class="col-sm-6">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Basic Details</h3>
                </div>
                <div class="block-content">
                    <div class="form-group mb-3">
                        <label for="pName" class="form-label">Name</label>
                        <input type="text" name="name" id="pName" class="form-control" value="{{ old('name') }}"/>
                        <p class="text-muted small">Character limits: <code>a-zA-Z0-9_.-</code> and <code>[Space]</code> (min 1, max 100 characters).</p>
                    </div>
                    <div class="form-group mb-3">
                        <label for="pDescription" class="form-label">Description</label>
                        <textarea name="description" id="pDescription" rows="4" class="form-control">{{ old('description') }}</textarea>
                    </div>
                    <div class="form-group mb-3">
                        <label for="pLocationId" class="form-label">Location</label>
                        <select name="location_id" id="pLocationId" class="form-control">
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ $location->id != old('location_id') ?: 'selected' }}>{{ $location->short }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Node Visibility</label>
                        <div class="space-y-2">
                            <div class="form-check">
                                <input type="radio" class="form-check-input" id="pPublicTrue" value="1" name="public" checked>
                                <label for="pPublicTrue" class="form-check-label">Public</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" id="pPublicFalse" value="0" name="public">
                                <label for="pPublicFalse" class="form-check-label">Private</label>
                            </div>
                        </div>
                        <p class="text-muted small">By setting a node to <code>private</code> you will be denying the ability to auto-deploy to this node.</p>
                    </div>
                    <div class="form-group mb-3">
                        <label for="pFQDN" class="form-label">FQDN</label>
                        <input type="text" name="fqdn" id="pFQDN" class="form-control" value="{{ old('fqdn') }}"/>
                        <p class="text-muted small">Please enter domain name (e.g <code>node.example.com</code>) to be used for connecting to the daemon. An IP address may be used <em>only</em> if you are not using SSL for this node.</p>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Communicate Over SSL</label>
                        <div class="space-y-2">
                            <div class="form-check">
                                <input type="radio" class="form-check-input" id="pSSLTrue" value="https" name="scheme" checked>
                                <label for="pSSLTrue" class="form-check-label">Use SSL Connection</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" id="pSSLFalse" value="http" name="scheme" @if(request()->isSecure()) disabled @endif>
                                <label for="pSSLFalse" class="form-check-label">Use HTTP Connection</label>
                            </div>
                        </div>
                        @if(request()->isSecure())
                            <p class="text-danger small">Your Panel is currently configured to use a secure connection. In order for browsers to connect to your node it <strong>must</strong> use a SSL connection.</p>
                        @else
                            <p class="text-muted small">In most cases you should select to use a SSL connection. If using an IP Address or you do not wish to use SSL at all, select a HTTP connection.</p>
                        @endif
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Behind Proxy</label>
                        <div class="space-y-2">
                            <div class="form-check">
                                <input type="radio" class="form-check-input" id="pProxyFalse" value="0" name="behind_proxy" checked>
                                <label for="pProxyFalse" class="form-check-label">Not Behind Proxy</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" id="pProxyTrue" value="1" name="behind_proxy">
                                <label for="pProxyTrue" class="form-check-label">Behind Proxy</label>
                            </div>
                        </div>
                        <p class="text-muted small">If you are running the daemon behind a proxy such as Cloudflare, select this to have the daemon skip looking for certificates on boot.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Configuration</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="form-group col-md-6 mb-3">
                            <label for="pDaemonBase" class="form-label">Daemon Server File Directory</label>
                            <input type="text" name="daemonBase" id="pDaemonBase" class="form-control" value="/var/lib/pterodactyl/volumes" />
                            <p class="text-muted small">Enter the directory where server files should be stored. <strong>If you use OVH you should check your partition scheme. You may need to use <code>/home/daemon-data</code> to have enough space.</strong></p>
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label for="pMemory" class="form-label">Total Memory</label>
                            <div class="input-group">
                                <input type="text" name="memory" data-multiplicator="true" class="form-control" id="pMemory" value="{{ old('memory') }}"/>
                                <span class="input-group-text">MiB</span>
                            </div>
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label for="pMemoryOverallocate" class="form-label">Memory Over-Allocation</label>
                            <div class="input-group">
                                <input type="text" name="memory_overallocate" class="form-control" id="pMemoryOverallocate" value="{{ old('memory_overallocate') }}"/>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <p class="text-muted small">Enter the total amount of memory available for new servers. If you would like to allow overallocation of memory enter the percentage that you want to allow. To disable checking for overallocation enter <code>-1</code> into the field. Entering <code>0</code> will prevent creating new servers if it would put the node over the limit.</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6 mb-3">
                            <label for="pDisk" class="form-label">Total Disk Space</label>
                            <div class="input-group">
                                <input type="text" name="disk" data-multiplicator="true" class="form-control" id="pDisk" value="{{ old('disk') }}"/>
                                <span class="input-group-text">MiB</span>
                            </div>
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label for="pDiskOverallocate" class="form-label">Disk Over-Allocation</label>
                            <div class="input-group">
                                <input type="text" name="disk_overallocate" class="form-control" id="pDiskOverallocate" value="{{ old('disk_overallocate') }}"/>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <p class="text-muted small">Enter the total amount of disk space available for new servers. If you would like to allow overallocation of disk space enter the percentage that you want to allow. To disable checking for overallocation enter <code>-1</code> into the field. Entering <code>0</code> will prevent creating new servers if it would put the node over the limit.</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6 mb-3">
                            <label for="pDaemonListen" class="form-label">Daemon Port</label>
                            <input type="text" name="daemonListen" class="form-control" id="pDaemonListen" value="8080" />
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label for="pDaemonSFTP" class="form-label">Daemon SFTP Port</label>
                            <input type="text" name="daemonSFTP" class="form-control" id="pDaemonSFTP" value="2022" />
                        </div>
                        <div class="col-md-12 mb-3">
                            <p class="text-muted small">The daemon runs its own SFTP management container and does not use the SSHd process on the main physical server. <Strong>Do not use the same port that you have assigned for your physical server's SSH process.</strong> If you will be running the daemon behind CloudFlare&reg; you should set the daemon port to <code>8443</code> to allow websocket proxying over SSL.</p>
                        </div>
                    </div>
                </div>
                <div class="block-content block-content-full block-content-sm text-end border-top">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-success">Create Node</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('#pLocationId').select2();
    </script>
@endsection
