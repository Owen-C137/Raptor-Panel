@extends('layouts.admin')

@section('title')
    Nests &rarr; Egg: {{ $egg->name }} &rarr; Install Script
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
          Manage the install script for this Egg.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.nests') }}">Nests</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.nests.view', $egg->nest->id) }}">{{ $egg->nest->name }}</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.nests.egg.view', $egg->id) }}">{{ $egg->name }}</a></li>
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
<form action="{{ route('admin.nests.egg.scripts', $egg->id) }}" method="POST">
    <div class="row">
        <div class="col-12">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Install Script</h3>
                </div>
                @if(! is_null($egg->copyFrom))
                    <div class="block-content">
                        <div class="alert alert-warning">
                            This service option is copying installation scripts and container options from <a href="{{ route('admin.nests.egg.view', $egg->copyFrom->id) }}">{{ $egg->copyFrom->name }}</a>. Any changes you make to this script will not apply unless you select "None" from the dropdown box below.
                        </div>
                    </div>
                @endif
                <div class="block-content p-0">
                    <div id="editor_install" style="height:300px">{{ $egg->script_install }}</div>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label class="form-label">Copy Script From</label>
                                <select id="pCopyScriptFrom" name="copy_script_from" class="form-control">
                                    <option value="">None</option>
                                    @foreach($copyFromOptions as $opt)
                                        <option value="{{ $opt->id }}" {{ $egg->copy_script_from !== $opt->id ?: 'selected' }}>{{ $opt->name }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text text-muted small">If selected, script above will be ignored and script from selected option will be used in place.</div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label class="form-label">Script Container</label>
                                <input type="text" name="script_container" class="form-control" value="{{ $egg->script_container }}" />
                                <div class="form-text text-muted small">Docker container to use when running this script for the server.</div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label class="form-label">Script Entrypoint Command</label>
                                <input type="text" name="script_entry" class="form-control" value="{{ $egg->script_entry }}" />
                                <div class="form-text text-muted small">The entrypoint command to use for this script.</div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 text-muted">
                            The following service options rely on this script:
                            @if(count($relyOnScript) > 0)
                                @foreach($relyOnScript as $rely)
                                    <a href="{{ route('admin.nests.egg.view', $rely->id) }}">
                                        <code>{{ $rely->name }}</code>@if(!$loop->last),&nbsp;@endif
                                    </a>
                                @endforeach
                            @else
                                <em>none</em>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="block-content block-content-full text-end bg-body-light">
                    {!! csrf_field() !!}
                    <textarea name="script_install" class="d-none"></textarea>
                    <button type="submit" name="_method" value="PATCH" class="btn btn-alt-primary">Save</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('vendor/ace/ace.js') !!}
    {!! Theme::js('vendor/ace/ext-modelist.js') !!}
    <script>
    $(document).ready(function () {
        $('#pCopyScriptFrom').select2();

        const InstallEditor = ace.edit('editor_install');
        const Modelist = ace.require('ace/ext/modelist')

        InstallEditor.setTheme('ace/theme/chrome');
        InstallEditor.getSession().setMode('ace/mode/sh');
        InstallEditor.getSession().setUseWrapMode(true);
        InstallEditor.setShowPrintMargin(false);

        $('form').on('submit', function (e) {
            $('textarea[name="script_install"]').val(InstallEditor.getValue());
        });
    });
    </script>

@endsection
