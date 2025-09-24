@extends('layouts.admin')

@section('title')
    {{ $node->name }}: Settings
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
          Configure your node settings.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.nodes') }}">Nodes</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.nodes.view', $node->id) }}">{{ $node->name }}</a></li>
          <li class="breadcrumb-item" aria-current="page">Settings</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
@include('admin.nodes.view._navigation')

<form action="{{ route('admin.nodes.view.settings', $node->id) }}" method="POST">
    <div class="row">
        <div class="col-sm-6">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Settings</h3>
                </div>
                <div class="block-content">
                    <div class="form-group mb-3">
                        <label for="name" class="form-label">Node Name</label>
                        <div>
                            <input type="text" autocomplete="off" name="name" class="form-control" value="{{ old('name', $node->name) }}" />
                            <p class="text-muted"><small>Character limits: <code>a-zA-Z0-9_.-</code> and <code>[Space]</code> (min 1, max 100 characters).</small></p>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="description" class="form-label">Description</label>
                        <div>
                            <textarea name="description" id="description" rows="4" class="form-control">{{ $node->description }}</textarea>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="name" class="form-label">Location</label>
                        <div>
                            <select name="location_id" class="form-control">
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" {{ (old('location_id', $node->location_id) === $location->id) ? 'selected' : '' }}>{{ $location->long }} ({{ $location->short }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="public" class="form-label">Allow Automatic Allocation <sup><a data-bs-toggle="tooltip" data-bs-placement="top" title="Allow automatic allocation to this Node?">?</a></sup></label>
                        <div class="space-y-2">
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="public" value="1" {{ (old('public', $node->public)) ? 'checked' : '' }} id="public_1">
                                <label for="public_1" class="form-check-label">Yes</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" name="public" value="0" {{ (old('public', $node->public)) ? '' : 'checked' }} id="public_0">
                                <label for="public_0" class="form-check-label">No</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="fqdn" class="form-label">Fully Qualified Domain Name</label>
                        <div>
                            <input type="text" autocomplete="off" name="fqdn" class="form-control" value="{{ old('fqdn', $node->fqdn) }}" />
                        </div>
                        <p class="text-muted"><small>Please enter domain name (e.g <code>node.example.com</code>) to be used for connecting to the daemon. An IP address may only be used if you are not using SSL for this node.</small></p>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label"><span class="badge bg-warning"><i class="fa fa-power-off"></i></span> Communicate Over SSL</label>
                        <div class="space-y-2">
                            <div class="form-check">
                                <input type="radio" class="form-check-input" id="pSSLTrue" value="https" name="scheme" {{ (old('scheme', $node->scheme) === 'https') ? 'checked' : '' }}>
                                <label for="pSSLTrue" class="form-check-label">Use SSL Connection</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" id="pSSLFalse" value="http" name="scheme" {{ (old('scheme', $node->scheme) !== 'https') ? 'checked' : '' }}>
                                <label for="pSSLFalse" class="form-check-label">Use HTTP Connection</label>
                            </div>
                        </div>
                        <p class="text-muted small">In most cases you should select to use a SSL connection. If using an IP Address or you do not wish to use SSL at all, select a HTTP connection.</p>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label"><span class="badge bg-warning"><i class="fa fa-power-off"></i></span> Behind Proxy</label>
                        <div class="space-y-2">
                            <div class="form-check">
                                <input type="radio" class="form-check-input" id="pProxyFalse" value="0" name="behind_proxy" {{ (old('behind_proxy', $node->behind_proxy) == false) ? 'checked' : '' }}>
                                <label for="pProxyFalse" class="form-check-label">Not Behind Proxy</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" id="pProxyTrue" value="1" name="behind_proxy" {{ (old('behind_proxy', $node->behind_proxy) == true) ? 'checked' : '' }}>
                                <label for="pProxyTrue" class="form-check-label">Behind Proxy</label>
                            </div>
                        </div>
                        <p class="text-muted small">If you are running the daemon behind a proxy such as Cloudflare, select this to have the daemon skip looking for certificates on boot.</p>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label"><span class="badge bg-warning"><i class="fa fa-wrench"></i></span> Maintenance Mode</label>
                        <div class="space-y-2">
                            <div class="form-check">
                                <input type="radio" class="form-check-input" id="pMaintenanceFalse" value="0" name="maintenance_mode" {{ (old('maintenance_mode', $node->maintenance_mode) == false) ? 'checked' : '' }}>
                                <label for="pMaintenanceFalse" class="form-check-label">Disabled</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" id="pMaintenanceTrue" value="1" name="maintenance_mode" {{ (old('maintenance_mode', $node->maintenance_mode) == true) ? 'checked' : '' }}>
                                <label for="pMaintenanceTrue" class="form-check-label">Enabled</label>
                            </div>
                        </div>
                        <p class="text-muted small">If the node is marked as 'Under Maintenance' users won't be able to access servers that are on this node.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Allocation Limits</h3>
                </div>
                <div class="block-content">
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <label for="memory" class="form-label">Total Memory</label>
                            <div class="input-group">
                                <input type="text" name="memory" class="form-control" data-multiplicator="true" value="{{ old('memory', $node->memory) }}"/>
                                <span class="input-group-text">MiB</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label for="memory_overallocate" class="form-label">Overallocate</label>
                            <div class="input-group">
                                <input type="text" name="memory_overallocate" class="form-control" value="{{ old('memory_overallocate', $node->memory_overallocate) }}"/>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    <p class="text-muted small mb-3">Enter the total amount of memory available on this node for allocation to servers. You may also provide a percentage that can allow allocation of more than the defined memory.</p>
                    
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <label for="disk" class="form-label">Disk Space</label>
                            <div class="input-group">
                                <input type="text" name="disk" class="form-control" data-multiplicator="true" value="{{ old('disk', $node->disk) }}"/>
                                <span class="input-group-text">MiB</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label for="disk_overallocate" class="form-label">Overallocate</label>
                            <div class="input-group">
                                <input type="text" name="disk_overallocate" class="form-control" value="{{ old('disk_overallocate', $node->disk_overallocate) }}"/>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                                        <p class="text-muted small">Enter the total amount of disk space available on this node for server allocation. You may also provide a percentage that will determine the amount of disk space over the set limit to allow.</p>
                </div>
            </div>
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">General Configuration</h3>
                </div>
                <div class="block-content">
                    <div class="form-group mb-3">
                        <label for="disk_overallocate" class="form-label">Maximum Web Upload Filesize</label>
                        <div class="input-group">
                            <input type="text" name="upload_size" class="form-control" value="{{ old('upload_size', $node->upload_size) }}"/>
                            <span class="input-group-text">MiB</span>
                        </div>
                        <p class="text-muted small">Enter the maximum size of files that can be uploaded through the web-based file manager.</p>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="daemonListen" class="form-label"><span class="badge bg-warning"><i class="fa fa-power-off"></i></span> Daemon Port</label>
                            <div>
                                <input type="text" name="daemonListen" class="form-control" value="{{ old('daemonListen', $node->daemonListen) }}"/>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="daemonSFTP" class="form-label"><span class="badge bg-warning"><i class="fa fa-power-off"></i></span> Daemon SFTP Port</label>
                            <div>
                                <input type="text" name="daemonSFTP" class="form-control" value="{{ old('daemonSFTP', $node->daemonSFTP) }}"/>
                            </div>
                        </div>
                    </div>
                    <p class="text-muted small">The daemon runs its own SFTP management container and does not use the SSHd process on the main physical server. <Strong>Do not use the same port that you have assigned for your physical server's SSH process.</strong></p>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Save Settings</h3>
                </div>
                <div class="block-content">
                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="reset_secret" id="reset_secret" class="form-check-input" />
                            <label for="reset_secret" class="form-check-label">Reset Daemon Master Key</label>
                        </div>
                        <p class="text-muted small">Resetting the daemon master key will void any request coming from the old key. This key is used for all sensitive operations on the daemon including server creation and deletion. We suggest changing this key regularly for security.</p>
                    </div>
                </div>
                <div class="block-content block-content-full text-end bg-body-light">
                    {!! method_field('PATCH') !!}
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    <script>
    $('[data-bs-toggle="tooltip"]').tooltip({
        placement: 'auto'
    });
    $('select[name="location_id"]').select2();
    </script>
@endsection
