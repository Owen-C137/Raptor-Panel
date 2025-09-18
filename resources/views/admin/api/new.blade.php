@extends('layouts.admin')

@section('title')
    Application API
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Application API
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Create a new application API key.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.api.index') }}">Application API</a></li>
          <li class="breadcrumb-item" aria-current="page">New Credentials</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.api.new') }}">
        <div class="row">
            <div class="col-8">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Select Permissions</h3>
                    </div>
                    <div class="block-content p-0">
                        <table class="table table-hover">
                            @foreach($resources as $resource)
                                <tr>
                                    <td class="col-sm-3 strong">{{ str_replace('_', ' ', title_case($resource)) }}</td>
                                    <td class="col-sm-3 text-center">
                                        <div class="form-check form-check-inline">
                                            <input type="radio" class="form-check-input" id="r_{{ $resource }}" name="r_{{ $resource }}" value="{{ $permissions['r'] }}">
                                            <label class="form-check-label" for="r_{{ $resource }}">Read</label>
                                        </div>
                                    </td>
                                    <td class="col-sm-3 text-center">
                                        <div class="form-check form-check-inline">
                                            <input type="radio" class="form-check-input" id="rw_{{ $resource }}" name="r_{{ $resource }}" value="{{ $permissions['rw'] }}">
                                            <label class="form-check-label" for="rw_{{ $resource }}">Read &amp; Write</label>
                                        </div>
                                    </td>
                                    <td class="col-sm-3 text-center">
                                        <div class="form-check form-check-inline">
                                            <input type="radio" class="form-check-input" id="n_{{ $resource }}" name="r_{{ $resource }}" value="{{ $permissions['n'] }}" checked>
                                            <label class="form-check-label" for="n_{{ $resource }}">None</label>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="block block-rounded">
                    <div class="block-content">
                        <div class="form-group">
                            <label class="form-label" for="memoField">Description <span class="field-required"></span></label>
                            <input id="memoField" type="text" name="memo" class="form-control">
                        </div>
                        <p class="text-muted">Once you have assigned permissions and created this set of credentials you will be unable to come back and edit it. If you need to make changes down the road you will need to create a new set of credentials.</p>
                    </div>
                    <div class="block-content block-content-full block-content-sm text-end border-top">
                        {{ csrf_field() }}
                        <button type="submit" class="btn btn-success btn-sm">Create Credentials</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('footer-scripts')
    @parent
    <script>
    </script>
@endsection
