@extends('layouts.admin')

@section('title')
    New Server
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Create Server
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Add a new server to the panel.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.servers') }}">Servers</a></li>
          <li class="breadcrumb-item" aria-current="page">Create Server</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<form action="{{ route('admin.servers.new') }}" method="POST">
    <div class="row">
        <div class="col-12">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Core Details</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="pName" class="form-label">Server Name</label>
                                <input type="text" class="form-control" id="pName" name="name" value="{{ old('name') }}" placeholder="Server Name">
                                <p class="small text-muted">Character limits: <code>a-z A-Z 0-9 _ - .</code> and <code>[Space]</code>.</p>
                            </div>

                            <div class="form-group mb-3">
                                <label for="pUserId" class="form-label">Server Owner</label>
                                <select id="pUserId" name="owner_id" class="form-control" style="padding-left:0;"></select>
                                <p class="small text-muted">Email address of the Server Owner.</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="pDescription" class="form-label">Server Description</label>
                                <textarea id="pDescription" name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                                <p class="text-muted small">A brief description of this server.</p>
                            </div>

                            <div class="form-group mb-3">
                                <div class="form-check">
                                    <input id="pStartOnCreation" name="start_on_completion" type="checkbox" class="form-check-input" {{ \Pterodactyl\Helpers\Utilities::checked('start_on_completion', 1) }} />
                                    <label for="pStartOnCreation" class="form-check-label fw-semibold">Start Server when Installed</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="block block-rounded">
                <div class="overlay" id="allocationLoader" style="display:none;"><i class="fa fa-refresh fa-spin"></i></div>
                <div class="block-header block-header-default">
                    <h3 class="block-title">Allocation Management</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-group mb-3">
                                <label for="pNodeId" class="form-label">Node</label>
                                <select name="node_id" id="pNodeId" class="form-control">
                                    @foreach($locations as $location)
                                        <optgroup label="{{ $location->long }} ({{ $location->short }})">
                                        @foreach($location->nodes as $node)

                                        <option value="{{ $node->id }}"
                                            @if($location->id === old('location_id')) selected @endif
                                        >{{ $node->name }}</option>

                                        @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <p class="small text-muted">The node which this server will be deployed to.</p>
                            </div>
                        </div>

                        <div class="col-sm-4">
                            <div class="form-group mb-3">
                                <label for="pAllocation" class="form-label">Default Allocation</label>
                                <select id="pAllocation" name="allocation_id" class="form-control"></select>
                                <p class="small text-muted">The main allocation that will be assigned to this server.</p>
                            </div>
                        </div>

                        <div class="col-sm-4">
                            <div class="form-group mb-3">
                                <label for="pAllocationAdditional" class="form-label">Additional Allocation(s)</label>
                                <select id="pAllocationAdditional" name="allocation_additional[]" class="form-control" multiple></select>
                                <p class="small text-muted">Additional allocations to assign to this server on creation.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Application Feature Limits</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group mb-3">
                                <label for="pDatabaseLimit" class="form-label">Database Limit</label>
                                <input type="text" id="pDatabaseLimit" name="database_limit" class="form-control" value="{{ old('database_limit', 0) }}"/>
                                <p class="text-muted small">The total number of databases a user is allowed to create for this server.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group mb-3">
                                <label for="pAllocationLimit" class="form-label">Allocation Limit</label>
                                <input type="text" id="pAllocationLimit" name="allocation_limit" class="form-control" value="{{ old('allocation_limit', 0) }}"/>
                                <p class="text-muted small">The total number of allocations a user is allowed to create for this server.</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group mb-3">
                                <label for="pBackupLimit" class="form-label">Backup Limit</label>
                                <input type="text" id="pBackupLimit" name="backup_limit" class="form-control" value="{{ old('backup_limit', 0) }}"/>
                                <p class="text-muted small">The total number of backups that can be created for this server.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Resource Management</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group mb-3">
                                <label for="pCPU" class="form-label">CPU Limit</label>
                                <div class="input-group">
                                    <input type="text" id="pCPU" name="cpu" class="form-control" value="{{ old('cpu', 0) }}" />
                                    <span class="input-group-text">%</span>
                                </div>
                                <p class="text-muted small">If you do not want to limit CPU usage, set the value to <code>0</code>. To determine a value, take the number of threads and multiply it by 100. For example, on a quad core system without hyperthreading <code>(4 * 100 = 400)</code> there is <code>400%</code> available. To limit a server to using half of a single thread, you would set the value to <code>50</code>. To allow a server to use up to two threads, set the value to <code>200</code>.</p>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="form-group mb-3">
                                <label for="pThreads" class="form-label">CPU Pinning</label>
                                <input type="text" id="pThreads" name="threads" class="form-control" value="{{ old('threads') }}" />
                                <p class="text-muted small"><strong>Advanced:</strong> Enter the specific CPU threads that this process can run on, or leave blank to allow all threads. This can be a single number, or a comma separated list. Example: <code>0</code>, <code>0-1,3</code>, or <code>0,1,3,4</code>.</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group mb-3">
                                <label for="pMemory" class="form-label">Memory</label>
                                <div class="input-group">
                                    <input type="text" id="pMemory" name="memory" class="form-control" value="{{ old('memory') }}" />
                                    <span class="input-group-text">MiB</span>
                                </div>
                                <p class="text-muted small">The maximum amount of memory allowed for this container. Setting this to <code>0</code> will allow unlimited memory in a container.</p>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="form-group mb-3">
                                <label for="pSwap" class="form-label">Swap</label>
                                <div class="input-group">
                                    <input type="text" id="pSwap" name="swap" class="form-control" value="{{ old('swap', 0) }}" />
                                    <span class="input-group-text">MiB</span>
                                </div>
                                <p class="text-muted small">Setting this to <code>0</code> will disable swap space on this server. Setting to <code>-1</code> will allow unlimited swap.</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group mb-3">
                                <label for="pDisk" class="form-label">Disk Space</label>
                                <div class="input-group">
                                    <input type="text" id="pDisk" name="disk" class="form-control" value="{{ old('disk') }}" />
                                    <span class="input-group-text">MiB</span>
                                </div>
                                <p class="text-muted small">This server will not be allowed to boot if it is using more than this amount of space. If a server goes over this limit while running it will be safely stopped and locked until enough space is available. Set to <code>0</code> to allow unlimited disk usage.</p>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="form-group mb-3">
                                <label for="pIO" class="form-label">Block IO Weight</label>
                                <input type="text" id="pIO" name="io" class="form-control" value="{{ old('io', 500) }}" />
                                <p class="text-muted small"><strong>Advanced</strong>: The IO performance of this server relative to other <em>running</em> containers on the system. Value should be between <code>10</code> and <code>1000</code>. Please see <a href="https://docs.docker.com/engine/reference/run/#block-io-bandwidth-blkio-constraint" target="_blank">this documentation</a> for more information about it.</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input type="checkbox" id="pOomDisabled" name="oom_disabled" value="0" class="form-check-input" {{ \Pterodactyl\Helpers\Utilities::checked('oom_disabled', 0) }} />
                            <label for="pOomDisabled" class="form-check-label fw-semibold">Enable OOM Killer</label>
                        </div>
                        <p class="small text-muted">Terminates the server if it breaches the memory limits. Enabling OOM killer may cause server processes to exit unexpectedly.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Nest Configuration</h3>
                </div>
                <div class="block-content">
                    <div class="form-group mb-3">
                        <label for="pNestId" class="form-label">Nest</label>
                        <select id="pNestId" name="nest_id" class="form-control">
                            @foreach($nests as $nest)
                                <option value="{{ $nest->id }}"
                                    @if($nest->id === old('nest_id'))
                                        selected="selected"
                                    @endif
                                >{{ $nest->name }}</option>
                            @endforeach
                        </select>
                        <p class="small text-muted">Select the Nest that this server will be grouped under.</p>
                    </div>

                    <div class="form-group mb-3">
                        <label for="pEggId" class="form-label">Egg</label>
                        <select id="pEggId" name="egg_id" class="form-control"></select>
                        <p class="small text-muted">Select the Egg that will define how this server should operate.</p>
                    </div>
                    
                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input type="checkbox" id="pSkipScripting" name="skip_scripts" value="1" class="form-check-input" {{ \Pterodactyl\Helpers\Utilities::checked('skip_scripts', 0) }} />
                            <label for="pSkipScripting" class="form-check-label fw-semibold">Skip Egg Install Script</label>
                        </div>
                        <p class="small text-muted">If the selected Egg has an install script attached to it, the script will run during the install. If you would like to skip this step, check this box.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Docker Configuration</h3>
                </div>
                <div class="block-content">
                    <div class="form-group mb-3">
                        <label for="pDefaultContainer" class="form-label">Docker Image</label>
                        <select id="pDefaultContainer" name="image" class="form-control"></select>
                        <input id="pDefaultContainerCustom" name="custom_image" value="{{ old('custom_image') }}" class="form-control mt-3" placeholder="Or enter a custom image..." />
                        <p class="small text-muted">This is the default Docker image that will be used to run this server. Select an image from the dropdown above, or enter a custom image in the text field above.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Startup Configuration</h3>
                </div>
                <div class="block-content">
                    <div class="form-group mb-3">
                        <label for="pStartup" class="form-label">Startup Command</label>
                        <input type="text" id="pStartup" name="startup" value="{{ old('startup') }}" class="form-control" />
                        <p class="small text-muted">The following data substitutes are available for the startup command: <code>@{{SERVER_MEMORY}}</code>, <code>@{{SERVER_IP}}</code>, and <code>@{{SERVER_PORT}}</code>. They will be replaced with the allocated memory, server IP, and server port respectively.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Service Variables</h3>
                </div>
                <div class="block-content">
                    <div class="row" id="appendVariablesTo"></div>
                </div>
                <div class="block-content block-content-full text-end bg-body-light">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-plus me-1"></i> Create Server
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('vendor/lodash/lodash.js') !!}

    <script type="application/javascript">
        // Persist 'Service Variables'
        function serviceVariablesUpdated(eggId, ids) {
            @if (old('egg_id'))
                // Check if the egg id matches.
                if (eggId != '{{ old('egg_id') }}') {
                    return;
                }

                @if (old('environment'))
                    @foreach (old('environment') as $key => $value)
                        $('#' + ids['{{ $key }}']).val('{{ $value }}');
                    @endforeach
                @endif
            @endif
            @if(old('image'))
                $('#pDefaultContainer').val('{{ old('image') }}');
            @endif
        }
        // END Persist 'Service Variables'
    </script>

    {!! Theme::js('js/admin/new-server.js?v=20220530') !!}

    <script type="application/javascript">
        $(document).ready(function() {
            // Persist 'Server Owner' select2
            @if (old('owner_id'))
                $.ajax({
                    url: '/admin/users/accounts.json?user_id={{ old('owner_id') }}',
                    dataType: 'json',
                }).then(function (data) {
                    initUserIdSelect([ data ]);
                });
            @else
                initUserIdSelect();
            @endif
            // END Persist 'Server Owner' select2

            // Persist 'Node' select2
            @if (old('node_id'))
                $('#pNodeId').val('{{ old('node_id') }}').change();

                // Persist 'Default Allocation' select2
                @if (old('allocation_id'))
                    $('#pAllocation').val('{{ old('allocation_id') }}').change();
                @endif
                // END Persist 'Default Allocation' select2

                // Persist 'Additional Allocations' select2
                @if (old('allocation_additional'))
                    const additional_allocations = [];

                    @for ($i = 0; $i < count(old('allocation_additional')); $i++)
                        additional_allocations.push('{{ old('allocation_additional.'.$i)}}');
                    @endfor

                    $('#pAllocationAdditional').val(additional_allocations).change();
                @endif
                // END Persist 'Additional Allocations' select2
            @endif
            // END Persist 'Node' select2

            // Persist 'Nest' select2
            @if (old('nest_id'))
                $('#pNestId').val('{{ old('nest_id') }}').change();

                // Persist 'Egg' select2
                @if (old('egg_id'))
                    $('#pEggId').val('{{ old('egg_id') }}').change();
                @endif
                // END Persist 'Egg' select2
            @endif
            // END Persist 'Nest' select2
        });
    </script>
@endsection
