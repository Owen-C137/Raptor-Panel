@extends('layouts.admin')

@section('title')
    Nests &rarr; New Egg
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          New Egg
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Create a new Egg to assign to servers.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.nests') }}">Nests</a></li>
          <li class="breadcrumb-item" aria-current="page">New Egg</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<form action="{{ route('admin.nests.egg.new') }}" method="POST">
    <div class="row">
        <div class="col-12">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Configuration</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="pNestId" class="form-label">Associated Nest</label>
                                <div>
                                    <select name="nest_id" id="pNestId" class="form-control">
                                        @foreach($nests as $nest)
                                            <option value="{{ $nest->id }}" {{ old('nest_id') != $nest->id ?: 'selected' }}>{{ $nest->name }} &lt;{{ $nest->author }}&gt;</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text text-muted small">Think of a Nest as a category. You can put multiple Eggs in a nest, but consider putting only Eggs that are related to each other in each Nest.</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="pName" class="form-label">Name</label>
                                <input type="text" id="pName" name="name" value="{{ old('name') }}" class="form-control" />
                                <div class="form-text text-muted small">A simple, human-readable name to use as an identifier for this Egg. This is what users will see as their game server type.</div>
                            </div>
                            <div class="mb-3">
                                <label for="pDescription" class="form-label">Description</label>
                                <textarea id="pDescription" name="description" class="form-control" rows="8">{{ old('description') }}</textarea>
                                <div class="form-text text-muted small">A description of this Egg.</div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" id="pForceOutgoingIp" name="force_outgoing_ip" type="checkbox" value="1" {{ \Pterodactyl\Helpers\Utilities::checked('force_outgoing_ip', 0) }} />
                                    <label class="form-check-label fw-bold" for="pForceOutgoingIp">Force Outgoing IP</label>
                                    <div class="form-text text-muted small">
                                        Forces all outgoing network traffic to have its Source IP NATed to the IP of the server's primary allocation IP.
                                        Required for certain games to work properly when the Node has multiple public IP addresses.
                                        <br>
                                        <strong>
                                            Enabling this option will disable internal networking for any servers using this egg,
                                            causing them to be unable to internally access other servers on the same node.
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="pDockerImage" class="form-label">Docker Images</label>
                                <textarea id="pDockerImages" name="docker_images" rows="4" placeholder="quay.io/pterodactyl/service" class="form-control">{{ old('docker_images') }}</textarea>
                                <div class="form-text text-muted small">The docker images available to servers using this egg. Enter one per line. Users will be able to select from this list of images if more than one value is provided.</div>
                            </div>
                            <div class="mb-3">
                                <label for="pStartup" class="form-label">Startup Command</label>
                                <textarea id="pStartup" name="startup" class="form-control" rows="10">{{ old('startup') }}</textarea>
                                <div class="form-text text-muted small">The default startup command that should be used for new servers created with this Egg. You can change this per-server as needed.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Process Management</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <p>All fields are required unless you select a separate option from the 'Copy Settings From' dropdown, in which case fields may be left blank to use the values from that option.</p>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="pConfigFrom" class="form-label">Copy Settings From</label>
                                <select name="config_from" id="pConfigFrom" class="form-control">
                                    <option value="">None</option>
                                </select>
                                <div class="form-text text-muted small">If you would like to default to settings from another Egg select it from the dropdown above.</div>
                            </div>
                            <div class="mb-3">
                                <label for="pConfigStop" class="form-label">Stop Command</label>
                                <input type="text" id="pConfigStop" name="config_stop" class="form-control" value="{{ old('config_stop') }}" />
                                <div class="form-text text-muted small">The command that should be sent to server processes to stop them gracefully. If you need to send a <code>SIGINT</code> you should enter <code>^C</code> here.</div>
                            </div>
                            <div class="mb-3">
                                <label for="pConfigLogs" class="form-label">Log Configuration</label>
                                <textarea data-action="handle-tabs" id="pConfigLogs" name="config_logs" class="form-control" rows="6">{{ old('config_logs') }}</textarea>
                                <div class="form-text text-muted small">This should be a JSON representation of where log files are stored, and whether or not the daemon should be creating custom logs.</div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="pConfigFiles" class="form-label">Configuration Files</label>
                                <textarea data-action="handle-tabs" id="pConfigFiles" name="config_files" class="form-control" rows="6">{{ old('config_files') }}</textarea>
                                <div class="form-text text-muted small">This should be a JSON representation of configuration files to modify and what parts should be changed.</div>
                            </div>
                            <div class="mb-3">
                                <label for="pConfigStartup" class="form-label">Start Configuration</label>
                                <textarea data-action="handle-tabs" id="pConfigStartup" name="config_startup" class="form-control" rows="6">{{ old('config_startup') }}</textarea>
                                <div class="form-text text-muted small">This should be a JSON representation of what values the daemon should be looking for when booting a server to determine completion.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="block-content block-content-full text-end bg-body-light">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-alt-success">Create</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('vendor/lodash/lodash.js') !!}
    <script>
    $(document).ready(function() {
        $('#pNestId').select2().change();
        $('#pConfigFrom').select2();
    });
    $('#pNestId').on('change', function (event) {
        $('#pConfigFrom').html('<option value="">None</option>').select2({
            data: $.map(_.get(Pterodactyl.nests, $(this).val() + '.eggs', []), function (item) {
                return {
                    id: item.id,
                    text: item.name + ' <' + item.author + '>',
                };
            }),
        });
    });
    $('textarea[data-action="handle-tabs"]').on('keydown', function(event) {
        if (event.keyCode === 9) {
            event.preventDefault();

            var curPos = $(this)[0].selectionStart;
            var prepend = $(this).val().substr(0, curPos);
            var append = $(this).val().substr(curPos);

            $(this).val(prepend + '    ' + append);
        }
    });
    </script>
@endsection
