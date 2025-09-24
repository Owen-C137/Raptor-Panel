@extends('layouts.admin')

@section('title')
    Server — {{ $server->name }}: Build Details
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
          Control allocations and system resources for this server.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.servers') }}">Servers</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></li>
          <li class="breadcrumb-item" aria-current="page">Build Configuration</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
@include('admin.servers.partials.navigation')
<form action="{{ route('admin.servers.view.build', $server->id) }}" method="POST">
    <div class="row">
        <div class="col-12">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Resource Management</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="cpu" class="form-label">CPU Limit</label>
                                <div class="input-group">
                                    <input type="text" name="cpu" class="form-control" value="{{ old('cpu', $server->cpu) }}"/>
                                    <span class="input-group-text">%</span>
                                </div>
                                <p class="text-muted small">Each <em>virtual</em> core (thread) on the system is considered to be <code>100%</code>. Setting this value to <code>0</code> will allow a server to use CPU time without restrictions.</p>
                            </div>
                            <div class="form-group mb-3">
                                <label for="threads" class="form-label">CPU Pinning</label>
                                <input type="text" name="threads" class="form-control" value="{{ old('threads', $server->threads) }}"/>
                                <p class="text-muted small"><strong>Advanced:</strong> Enter the specific CPU cores that this process can run on, or leave blank to allow all cores. This can be a single number, or a comma seperated list. Example: <code>0</code>, <code>0-1,3</code>, or <code>0,1,3,4</code>.</p>
                            </div>
                            <div class="form-group mb-3">
                                <label for="memory" class="form-label">Allocated Memory</label>
                                <div class="input-group">
                                    <input type="text" name="memory" data-multiplicator="true" class="form-control" value="{{ old('memory', $server->memory) }}"/>
                                    <span class="input-group-text">MiB</span>
                                </div>
                                <p class="text-muted small">The maximum amount of memory allowed for this container. Setting this to <code>0</code> will allow unlimited memory in a container.</p>
                            </div>
                            <div class="form-group mb-3">
                                <label for="swap" class="form-label">Allocated Swap</label>
                                <div class="input-group">
                                    <input type="text" name="swap" data-multiplicator="true" class="form-control" value="{{ old('swap', $server->swap) }}"/>
                                    <span class="input-group-text">MiB</span>
                                </div>
                                <p class="text-muted small">Setting this to <code>0</code> will disable swap space on this server. Setting to <code>-1</code> will allow unlimited swap.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="disk" class="form-label">Disk Space Limit</label>
                                <div class="input-group">
                                    <input type="text" name="disk" class="form-control" value="{{ old('disk', $server->disk) }}"/>
                                    <span class="input-group-text">MiB</span>
                                </div>
                                <p class="text-muted small">This server will not be allowed to boot if it is using more than this amount of space. If a server goes over this limit while running it will be safely stopped and locked until enough space is available. Set to <code>0</code> to allow unlimited disk usage.</p>
                            </div>
                            <div class="form-group mb-3">
                                <label for="io" class="form-label">Block IO Proportion</label>
                                <input type="text" name="io" class="form-control" value="{{ old('io', $server->io) }}"/>
                                <p class="text-muted small"><strong>Advanced</strong>: The IO performance of this server relative to other <em>running</em> containers on the system. Value should be between <code>10</code> and <code>1000</code>.</p>
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label">OOM Killer</label>
                                <div class="space-y-2">
                                    <div class="form-check">
                                        <input type="radio" id="pOomKillerEnabled" value="0" name="oom_disabled" class="form-check-input" @if(!$server->oom_disabled)checked @endif>
                                        <label for="pOomKillerEnabled" class="form-check-label">Enabled</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" id="pOomKillerDisabled" value="1" name="oom_disabled" class="form-check-input" @if($server->oom_disabled)checked @endif>
                                        <label for="pOomKillerDisabled" class="form-check-label">Disabled</label>
                                    </div>
                                </div>
                                <p class="text-muted small">Enabling OOM killer may cause server processes to exit unexpectedly.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Application Feature Limits</h3>
                </div>
                <div class="block-content">
                    <div class="form-group mb-3">
                        <label for="database_limit" class="form-label">Database Limit</label>
                        <input type="text" name="database_limit" class="form-control" value="{{ old('database_limit', $server->database_limit) }}"/>
                        <p class="text-muted small">The total number of databases a user is allowed to create for this server.</p>
                    </div>
                    <div class="form-group mb-3">
                        <label for="allocation_limit" class="form-label">Allocation Limit</label>
                        <input type="text" name="allocation_limit" class="form-control" value="{{ old('allocation_limit', $server->allocation_limit) }}"/>
                        <p class="text-muted small">The total number of allocations a user is allowed to create for this server.</p>
                    </div>
                    <div class="form-group mb-3">
                        <label for="backup_limit" class="form-label">Backup Limit</label>
                        <input type="text" name="backup_limit" class="form-control" value="{{ old('backup_limit', $server->backup_limit) }}"/>
                        <p class="text-muted small">The total number of backups that can be created for this server.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Allocation Management</h3>
                </div>
                <div class="block-content">
                    <div class="form-group mb-3">
                        <label for="pAllocation" class="form-label">Game Port</label>
                        <select id="pAllocation" name="allocation_id" class="form-control">
                            @foreach ($assigned as $assignment)
                                <option value="{{ $assignment->id }}"
                                    @if($assignment->id === $server->allocation_id)
                                        selected="selected"
                                    @endif
                                >{{ $assignment->alias }}:{{ $assignment->port }}</option>
                            @endforeach
                        </select>
                        <p class="text-muted small">The default connection address that will be used for this game server.</p>
                    </div>
                    <div class="form-group mb-3">
                        <label for="pAddAllocations" class="form-label">Assign Additional Ports</label>
                        <select name="add_allocations[]" class="form-control" multiple id="pAddAllocations">
                            @foreach ($unassigned as $assignment)
                                <option value="{{ $assignment->id }}">{{ $assignment->alias }}:{{ $assignment->port }}</option>
                            @endforeach
                        </select>
                        <p class="text-muted small">Please note that due to software limitations you cannot assign identical ports on different IPs to the same server.</p>
                    </div>
                    <div class="form-group mb-3">
                        <label for="pRemoveAllocations" class="form-label">Remove Additional Ports</label>
                        <select name="remove_allocations[]" class="form-control" multiple id="pRemoveAllocations">
                            @foreach ($assigned as $assignment)
                                <option value="{{ $assignment->id }}">{{ $assignment->alias }}:{{ $assignment->port }}</option>
                            @endforeach
                        </select>
                        <p class="text-muted small">Simply select which ports you would like to remove from the list above. If you want to assign a port on a different IP that is already in use you can select it from the left and delete it here.</p>
                    </div>
                </div>
                <div class="block-content block-content-full text-end bg-body-light">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save me-1"></i> Update Build Configuration
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
    $('#pAddAllocations').select2();
    $('#pRemoveAllocations').select2();
    $('#pAllocation').select2();
    </script>
@endsection
