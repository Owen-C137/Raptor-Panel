@extends('layouts.admin')

@section('title')
    Nests &rarr; Egg: {{ $egg->name }}
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          {{ $egg->name }}
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          {{ str_limit($egg->description, 50) }}
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.nests') }}">Nests</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.nests.view', $egg->nest->id) }}">{{ $egg->nest->name }}</a></li>
          <li class="breadcrumb-item" aria-current="page">{{ $egg->name }}</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        @include('admin.eggs.partials.navigation')
    </div>
</div>
<form action="{{ route('admin.nests.egg.view', $egg->id) }}" enctype="multipart/form-data" method="POST">
    <div class="row">
        <div class="col-12">
            <div class="block block-themed block-rounded">
                <div class="block-header bg-danger">
                    <h3 class="block-title text-white">Egg File Import</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="mb-3">
                                <label for="pName" class="form-label">Egg File</label>
                                <div>
                                    <input type="file" name="import_file" class="form-control" />
                                    <div class="form-text text-muted small">If you would like to replace settings for this Egg by uploading a new JSON file, simply select it here and press "Update Egg". This will not change any existing startup strings or Docker images for existing servers.</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            {!! csrf_field() !!}
                            <div class="d-grid">
                                <button type="submit" name="_method" value="PUT" class="btn btn-danger">Update Egg</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<form action="{{ route('admin.nests.egg.view', $egg->id) }}" method="POST">
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
                                <label for="pName" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" id="pName" name="name" value="{{ $egg->name }}" class="form-control" />
                                <div class="form-text text-muted small">A simple, human-readable name to use as an identifier for this Egg.</div>
                            </div>
                            <div class="mb-3">
                                <label for="pUuid" class="form-label">UUID</label>
                                <input type="text" id="pUuid" readonly value="{{ $egg->uuid }}" class="form-control" />
                                <div class="form-text text-muted small">This is the globally unique identifier for this Egg which the Daemon uses as an identifier.</div>
                            </div>
                            <div class="mb-3">
                                <label for="pAuthor" class="form-label">Author</label>
                                <input type="text" id="pAuthor" readonly value="{{ $egg->author }}" class="form-control" />
                                <div class="form-text text-muted small">The author of this version of the Egg. Uploading a new Egg configuration from a different author will change this.</div>
                            </div>
                            <div class="mb-3">
                                <label for="pDockerImage" class="form-label">Docker Images <span class="text-danger">*</span></label>
                                <textarea id="pDockerImages" name="docker_images" class="form-control" rows="4">{{ implode(PHP_EOL, $images) }}</textarea>
                                <div class="form-text text-muted small">
                                    The docker images available to servers using this egg. Enter one per line. Users
                                    will be able to select from this list of images if more than one value is provided.
                                    Optionally, a display name may be provided by prefixing the image with the name
                                    followed by a pipe character, and then the image URL. Example: <code>Display Name|ghcr.io/my/egg</code>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" id="pForceOutgoingIp" name="force_outgoing_ip" type="checkbox" value="1" @if($egg->force_outgoing_ip) checked @endif />
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
                                <label for="pDescription" class="form-label">Description</label>
                                <textarea id="pDescription" name="description" class="form-control" rows="8">{{ $egg->description }}</textarea>
                                <div class="form-text text-muted small">A description of this Egg that will be displayed throughout the Panel as needed.</div>
                            </div>
                            <div class="mb-3">
                                <label for="pStartup" class="form-label">Startup Command <span class="text-danger">*</span></label>
                                <textarea id="pStartup" name="startup" class="form-control" rows="8">{{ $egg->startup }}</textarea>
                                <div class="form-text text-muted small">The default startup command that should be used for new servers using this Egg.</div>
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
                                <p>The following configuration options should not be edited unless you understand how this system works. If wrongly modified it is possible for the daemon to break.</p>
                                <p>All fields are required unless you select a separate option from the 'Copy Settings From' dropdown, in which case fields may be left blank to use the values from that Egg.</p>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="pConfigFrom" class="form-label">Copy Settings From</label>
                                <select name="config_from" id="pConfigFrom" class="form-control">
                                    <option value="">None</option>
                                    @foreach($egg->nest->eggs as $o)
                                        <option value="{{ $o->id }}" {{ ($egg->config_from !== $o->id) ?: 'selected' }}>{{ $o->name }} &lt;{{ $o->author }}&gt;</option>
                                    @endforeach
                                </select>
                                <div class="form-text text-muted small">If you would like to default to settings from another Egg select it from the menu above.</div>
                            </div>
                            <div class="mb-3">
                                <label for="pConfigStop" class="form-label">Stop Command</label>
                                <input type="text" id="pConfigStop" name="config_stop" class="form-control" value="{{ $egg->config_stop }}" />
                                <div class="form-text text-muted small">The command that should be sent to server processes to stop them gracefully. If you need to send a <code>SIGINT</code> you should enter <code>^C</code> here.</div>
                            </div>
                            <div class="mb-3">
                                <label for="pConfigLogs" class="form-label">Log Configuration</label>
                                <textarea data-action="handle-tabs" id="pConfigLogs" name="config_logs" class="form-control" rows="6">{{ ! is_null($egg->config_logs) ? json_encode(json_decode($egg->config_logs), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '' }}</textarea>
                                <div class="form-text text-muted small">This should be a JSON representation of where log files are stored, and whether or not the daemon should be creating custom logs.</div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label for="pConfigFiles" class="form-label">Configuration Files</label>
                                <textarea data-action="handle-tabs" id="pConfigFiles" name="config_files" class="form-control" rows="6">{{ ! is_null($egg->config_files) ? json_encode(json_decode($egg->config_files), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '' }}</textarea>
                                <div class="form-text text-muted small">This should be a JSON representation of configuration files to modify and what parts should be changed.</div>
                            </div>
                            <div class="mb-3">
                                <label for="pConfigStartup" class="form-label">Start Configuration</label>
                                <textarea data-action="handle-tabs" id="pConfigStartup" name="config_startup" class="form-control" rows="6">{{ ! is_null($egg->config_startup) ? json_encode(json_decode($egg->config_startup), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '' }}</textarea>
                                <div class="form-text text-muted small">This should be a JSON representation of what values the daemon should be looking for when booting a server to determine completion.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="block-content block-content-full text-end bg-body-light">
                    {!! csrf_field() !!}
                    <button type="submit" name="_method" value="PATCH" class="btn btn-alt-primary">Save</button>
                    <a href="{{ route('admin.nests.egg.export', $egg->id) }}" class="btn btn-alt-info me-2">Export</a>
                    <button id="deleteButton" type="submit" name="_method" value="DELETE" class="btn btn-danger">
                        <i class="fa fa-trash-o"></i>
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
    $('#pConfigFrom').select2();
    $('#deleteButton').on('mouseenter', function (event) {
        $(this).find('i').html(' Delete Egg');
    }).on('mouseleave', function (event) {
        $(this).find('i').html('');
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
