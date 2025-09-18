@extends('layouts.admin')

@section('title')
    Server — {{ $server->name }}: Startup
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
          Control startup command as well as variables.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.servers') }}">Servers</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a></li>
          <li class="breadcrumb-item" aria-current="page">Startup</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
@include('admin.servers.partials.navigation')
<form action="{{ route('admin.servers.view.startup', $server->id) }}" method="POST">
    <div class="row">
        <div class="col-xs-12">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-terminal me-2"></i>Startup Command Modification
                    </h3>
                </div>
                <div class="block-content">
                    <div class="form-group mb-3">
                        <label for="pStartup" class="form-label">Startup Command</label>
                        <input id="pStartup" name="startup" class="form-control" type="text" value="{{ old('startup', $server->startup) }}" />
                        <small class="text-muted">Edit your server's startup command here. The following variables are available by default: <code>@{{SERVER_MEMORY}}</code>, <code>@{{SERVER_IP}}</code>, and <code>@{{SERVER_PORT}}</code>.</small>
                    </div>
                    <div class="form-group mb-3">
                        <label for="pDefaultStartupCommand" class="form-label">Default Service Start Command</label>
                        <input id="pDefaultStartupCommand" class="form-control" type="text" readonly />
                    </div>
                </div>
                <div class="block-content block-content-full text-end bg-body-light">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save me-1"></i> Save Modifications
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-cogs me-2"></i>Service Configuration
                    </h3>
                </div>
                <div class="block-content">
                    <div class="alert alert-danger d-flex mb-4">
                        <div class="flex-shrink-0">
                            <i class="fa fa-fw fa-exclamation-triangle"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="mb-2">
                                Changing any of the below values will result in the server processing a re-install command. The server will be stopped and will then proceed.
                                If you would like the service scripts to not run, ensure the box is checked at the bottom.
                            </p>
                            <p class="mb-0">
                                <strong>This is a destructive operation in many cases. This server will be stopped immediately in order for this action to proceed.</strong>
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="pNestId" class="form-label">Nest</label>
                        <select name="nest_id" id="pNestId" class="form-control">
                            @foreach($nests as $nest)
                                <option value="{{ $nest->id }}"
                                    @if($nest->id === $server->nest_id)
                                        selected
                                    @endif
                                >{{ $nest->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Select the Nest that this server will be grouped into.</small>
                    </div>
                    <div class="form-group mb-3">
                        <label for="pEggId" class="form-label">Egg</label>
                        <select name="egg_id" id="pEggId" class="form-control"></select>
                        <small class="text-muted">Select the Egg that will provide processing data for this server.</small>
                    </div>
                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input id="pSkipScripting" name="skip_scripts" type="checkbox" value="1" class="form-check-input" @if($server->skip_scripts) checked @endif />
                            <label for="pSkipScripting" class="form-check-label fw-semibold">Skip Egg Install Script</label>
                        </div>
                        <small class="text-muted">If the selected Egg has an install script attached to it, the script will run during install. If you would like to skip this step, check this box.</small>
                    </div>
                </div>
            </div>
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fab fa-docker me-2"></i>Docker Image Configuration
                    </h3>
                </div>
                <div class="block-content">
                    <div class="form-group mb-3">
                        <label for="pDockerImage" class="form-label">Image</label>
                        <select id="pDockerImage" name="docker_image" class="form-control mb-2"></select>
                        <input id="pDockerImageCustom" name="custom_docker_image" value="{{ old('custom_docker_image') }}" class="form-control" placeholder="Or enter a custom image..." />
                        <small class="text-muted">This is the Docker image that will be used to run this server. Select an image from the dropdown or enter a custom image in the text field above.</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="row" id="appendVariablesTo"></div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('vendor/lodash/lodash.js') !!}
    <script>
    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    $(document).ready(function () {
        $('#pEggId').select2({placeholder: 'Select a Nest Egg'}).on('change', function () {
            var selectedEgg = _.isNull($(this).val()) ? $(this).find('option').first().val() : $(this).val();
            var parentChain = _.get(Pterodactyl.nests, $("#pNestId").val());
            var objectChain = _.get(parentChain, 'eggs.' + selectedEgg);

            const images = _.get(objectChain, 'docker_images', [])
            $('#pDockerImage').html('');
            const keys = Object.keys(images);
            for (let i = 0; i < keys.length; i++) {
                let opt = document.createElement('option');
                opt.value = images[keys[i]];
                opt.innerText = keys[i] + " (" + images[keys[i]] + ")";
                if (objectChain.id === parseInt(Pterodactyl.server.egg_id) && Pterodactyl.server.image == opt.value) {
                    opt.selected = true
                }
                $('#pDockerImage').append(opt);
            }
            $('#pDockerImage').on('change', function () {
                $('#pDockerImageCustom').val('');
            })

            if (objectChain.id === parseInt(Pterodactyl.server.egg_id)) {
                if ($('#pDockerImage').val() != Pterodactyl.server.image) {
                    $('#pDockerImageCustom').val(Pterodactyl.server.image);
                }
            }

            if (!_.get(objectChain, 'startup', false)) {
                $('#pDefaultStartupCommand').val(_.get(parentChain, 'startup', 'ERROR: Startup Not Defined!'));
            } else {
                $('#pDefaultStartupCommand').val(_.get(objectChain, 'startup'));
            }

            $('#appendVariablesTo').html('');
            $.each(_.get(objectChain, 'variables', []), function (i, item) {
                var setValue = _.get(Pterodactyl.server_variables, item.env_variable, item.default_value);
                var isRequired = (item.required === 1) ? '<span class="badge bg-danger me-1">Required</span>' : '';
                var dataAppend = ' \
                    <div class="col-xs-12"> \
                        <div class="block block-rounded"> \
                            <div class="block-header block-header-default"> \
                                <h3 class="block-title">' + isRequired + escapeHtml(item.name) + '</h3> \
                            </div> \
                            <div class="block-content"> \
                                <div class="form-group mb-3"> \
                                    <input name="environment[' + escapeHtml(item.env_variable) + ']" class="form-control" type="text" id="egg_variable_' + escapeHtml(item.env_variable) + '" /> \
                                    <small class="text-muted">' + escapeHtml(item.description) + '</small> \
                                </div> \
                            </div> \
                            <div class="block-content block-content-full bg-body-light"> \
                                <div class="row"> \
                                    <div class="col-sm-6"> \
                                        <small class="text-muted"><strong>Startup Variable:</strong> <code>' + escapeHtml(item.env_variable) + '</code></small> \
                                    </div> \
                                    <div class="col-sm-6"> \
                                        <small class="text-muted"><strong>Input Rules:</strong> <code>' + escapeHtml(item.rules) + '</code></small> \
                                    </div> \
                                </div> \
                            </div> \
                        </div> \
                    </div>';
                $('#appendVariablesTo').append(dataAppend).find('#egg_variable_' + item.env_variable).val(setValue);
            });
        });

        $('#pNestId').select2({placeholder: 'Select a Nest'}).on('change', function () {
            $('#pEggId').html('').select2({
                data: $.map(_.get(Pterodactyl.nests, $(this).val() + '.eggs', []), function (item) {
                    return {
                        id: item.id,
                        text: item.name,
                    };
                }),
            });

            if (_.isObject(_.get(Pterodactyl.nests, $(this).val() + '.eggs.' + Pterodactyl.server.egg_id))) {
                $('#pEggId').val(Pterodactyl.server.egg_id);
            }

            $('#pEggId').change();
        }).change();
    });
    </script>
@endsection
