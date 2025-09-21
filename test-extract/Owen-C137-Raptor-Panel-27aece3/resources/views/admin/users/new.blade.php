@extends('layouts.admin')

@section('title')
    Create User
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Create User
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Add a new user to the system.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.users') }}">Users</a></li>
          <li class="breadcrumb-item" aria-current="page">Create</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<form method="post">
    <div class="row">
        <div class="col-md-6">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-user me-2"></i>Identity
                    </h3>
                </div>
                <div class="block-content">
                    <div class="form-group mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="text" autocomplete="off" name="email" value="{{ old('email') }}" class="form-control" />
                    </div>
                    <div class="form-group mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" autocomplete="off" name="username" value="{{ old('username') }}" class="form-control" />
                    </div>
                    <div class="form-group mb-3">
                        <label for="name_first" class="form-label">Client First Name</label>
                        <input type="text" autocomplete="off" name="name_first" value="{{ old('name_first') }}" class="form-control" />
                    </div>
                    <div class="form-group mb-3">
                        <label for="name_last" class="form-label">Client Last Name</label>
                        <input type="text" autocomplete="off" name="name_last" value="{{ old('name_last') }}" class="form-control" />
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Default Language</label>
                        <select name="language" class="form-control">
                            @foreach($languages as $key => $value)
                                <option value="{{ $key }}" @if(config('app.locale') === $key) selected @endif>{{ $value }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">The default language to use when rendering the Panel for this user.</small>
                    </div>
                </div>
                <div class="block-content block-content-full text-end bg-body-light">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-plus me-1"></i> Create User
                    </button>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-key me-2"></i>Permissions
                    </h3>
                </div>
                <div class="block-content">
                    <div class="form-group mb-3">
                        <label for="root_admin" class="form-label">Administrator</label>
                        <select name="root_admin" class="form-control">
                            <option value="0">@lang('strings.no')</option>
                            <option value="1">@lang('strings.yes')</option>
                        </select>
                        <small class="text-muted">Setting this to 'Yes' gives a user full administrative access.</small>
                    </div>
                </div>
            </div>
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-lock me-2"></i>Password
                    </h3>
                </div>
                <div class="block-content">
                    <div class="alert alert-info d-flex">
                        <div class="flex-shrink-0">
                            <i class="fa fa-info-circle"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="mb-0">Providing a user password is optional. New user emails prompt users to create a password the first time they login. If a password is provided here you will need to find a different method of providing it to the user.</p>
                        </div>
                    </div>
                    <div id="gen_pass" class="alert alert-success" style="display:none;"></div>
                    <div class="form-group mb-3">
                        <label for="pass" class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    <script>$("#gen_pass_bttn").click(function (event) {
            event.preventDefault();
            $.ajax({
                type: "GET",
                url: "/password-gen/12",
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
               },
                success: function(data) {
                    $("#gen_pass").html('<strong>Generated Password:</strong> ' + data).slideDown();
                    $('input[name="password"], input[name="password_confirmation"]').val(data);
                    return false;
                }
            });
            return false;
        });
    </script>
@endsection
