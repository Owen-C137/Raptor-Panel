@extends('layouts.admin')

@section('title')
    Egg &rarr; {{ $egg->name }} &rarr; Variables
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
          Managing variables for this Egg.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.nests') }}">Nests</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.nests.view', $egg->nest->id) }}">{{ $egg->nest->name }}</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.nests.egg.view', $egg->id) }}">{{ $egg->name }}</a></li>
          <li class="breadcrumb-item" aria-current="page">Variables</li>
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
<div class="row">
    <div class="col-12">
        <div class="block block-rounded">
            <div class="block-content text-end">
                <button type="button" class="btn btn-alt-success" data-bs-toggle="modal" data-bs-target="#newVariableModal">
                    <i class="fa fa-plus me-1"></i> Create New Variable
                </button>
            </div>
        </div>
    </div>
</div>
<div class="row">
    @foreach($egg->variables as $variable)
        <div class="col-lg-6">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">{{ $variable->name }}</h3>
                </div>
                <form action="{{ route('admin.nests.egg.variables.edit', ['egg' => $egg->id, 'variable' => $variable->id]) }}" method="POST">
                    <div class="block-content">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" value="{{ $variable->name }}" class="form-control" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ $variable->description }}</textarea>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Environment Variable</label>
                                    <input type="text" name="env_variable" value="{{ $variable->env_variable }}" class="form-control" />
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Default Value</label>
                                    <input type="text" name="default_value" value="{{ $variable->default_value }}" class="form-control" />
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-text text-muted small">This variable can be accessed in the startup command by using <code>{{ $variable->env_variable }}</code>.</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Permissions</label>
                            <select name="options[]" class="pOptions form-control" multiple>
                                <option value="user_viewable" {{ (! $variable->user_viewable) ?: 'selected' }}>Users Can View</option>
                                <option value="user_editable" {{ (! $variable->user_editable) ?: 'selected' }}>Users Can Edit</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Input Rules</label>
                            <input type="text" name="rules" class="form-control" value="{{ $variable->rules }}" />
                            <div class="form-text text-muted small">These rules are defined using standard <a href="https://laravel.com/docs/5.7/validation#available-validation-rules" target="_blank">Laravel Framework validation rules</a>.</div>
                        </div>
                    </div>
                    <div class="block-content block-content-full text-end bg-body-light">
                        {!! csrf_field() !!}
                        <button class="btn btn-alt-primary" name="_method" value="PATCH" type="submit">Save</button>
                        <button class="btn btn-danger" data-action="delete" name="_method" value="DELETE" type="submit"><i class="fa fa-trash-o"></i></button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
</div>
<div class="modal fade" id="newVariableModal" tabindex="-1" aria-labelledby="newVariableModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
            <div class="block-header block-header-default">
                <h5 class="modal-title" id="newVariableModalLabel">
                    <i class="fa fa-plus me-2"></i>Create New Egg Variable
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.nests.egg.variables', $egg->id) }}" method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}"/>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Environment Variable <span class="text-danger">*</span></label>
                                <input type="text" name="env_variable" class="form-control" value="{{ old('env_variable') }}" />
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                        <div class="form-text text-muted small">A brief description of what this variable controls.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Default Value</label>
                        <input type="text" name="default_value" class="form-control" value="{{ old('default_value') }}" />
                        <div class="form-text text-muted small">The default value for this variable. Leave blank if no default is needed.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Input Rules <span class="text-danger">*</span></label>
                        <input type="text" name="rules" class="form-control" value="{{ old('rules', 'required|string|max:20') }}" placeholder="required|string|max:20" />
                        <div class="form-text text-muted small">These rules are defined using standard <a href="https://laravel.com/docs/5.7/validation#available-validation-rules" target="_blank">Laravel Framework validation rules</a>.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Permissions</label>
                        <select name="options[]" class="pOptions form-control" multiple>
                            <option value="user_viewable">Users Can View</option>
                            <option value="user_editable">Users Can Edit</option>
                        </select>
                        <div class="form-text text-muted small">Select what permissions users should have for this variable.</div>
                    </div>
                    <div class="alert alert-info d-flex">
                        <div class="flex-shrink-0">
                            <i class="fa fa-fw fa-info-circle"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="mb-0">This variable can be accessed in the startup command by entering <code>@{{environment variable value}}</code>.</p>
                        </div>
                    </div>
                </div>
               <div class="block-content block-content-full text-end bg-body">
                    {!! csrf_field() !!}
                    <button type="button" class="btn btn-alt-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-alt-primary">
                        <i class="fa fa-check me-1"></i> Create Variable
                    </button>
                </div>
            </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('.pOptions').select2();
        $('[data-action="delete"]').on('mouseenter', function (event) {
            $(this).find('i').html(' Delete Variable');
        }).on('mouseleave', function (event) {
            $(this).find('i').html('');
        });
    </script>
@endsection
