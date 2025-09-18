@extends('layouts.admin')

@section('title')
    Manager User: {{ $user->username }}
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          {{ $user->name_first }} {{ $user->name_last}}
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          {{ $user->username }}
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.users') }}">Users</a></li>
          <li class="breadcrumb-item" aria-current="page">{{ $user->username }}</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<form action="{{ route('admin.users.view', $user->id) }}" method="post">
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
                        <input type="email" name="email" value="{{ $user->email }}" class="form-control form-autocomplete-stop">
                    </div>
                    <div class="form-group mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" name="username" value="{{ $user->username }}" class="form-control form-autocomplete-stop">
                    </div>
                    <div class="form-group mb-3">
                        <label for="name_first" class="form-label">Client First Name</label>
                        <input type="text" name="name_first" value="{{ $user->name_first }}" class="form-control form-autocomplete-stop">
                    </div>
                    <div class="form-group mb-3">
                        <label for="name_last" class="form-label">Client Last Name</label>
                        <input type="text" name="name_last" value="{{ $user->name_last }}" class="form-control form-autocomplete-stop">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Default Language</label>
                        <select name="language" class="form-control">
                            @foreach($languages as $key => $value)
                                <option value="{{ $key }}" @if($user->language === $key) selected @endif>{{ $value }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">The default language to use when rendering the Panel for this user.</small>
                    </div>
                </div>
                <div class="block-content block-content-full text-end bg-body-light">
                    {!! csrf_field() !!}
                    {!! method_field('PATCH') !!}
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save me-1"></i> Update User
                    </button>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="block block-rounded">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">
                                <i class="fa fa-lock me-2"></i>Password
                            </h3>
                        </div>
                        <div class="block-content">
                            <div class="alert alert-success" style="display:none;" id="gen_pass"></div>
                            <div class="form-group mb-3">
                                <label for="password" class="form-label">Password <span class="text-muted">(optional)</span></label>
                                <input type="password" id="password" name="password" class="form-control form-autocomplete-stop">
                                <small class="text-muted">Leave blank to keep this user's password the same. User will not receive any notification if password is changed.</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
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
                                    <option value="1" {{ $user->root_admin ? 'selected="selected"' : '' }}>@lang('strings.yes')</option>
                                </select>
                                <small class="text-muted">Setting this to 'Yes' gives a user full administrative access.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<div class="row">
    <div class="col-12">
        <div class="block block-rounded border-danger">
            <div class="block-header block-header-default bg-danger">
                <h3 class="block-title text-white">
                    <i class="fa fa-trash text-white me-2"></i>Delete User
                </h3>
            </div>
            <div class="block-content">
                <p class="mb-0">There must be no servers associated with this account in order for it to be deleted.</p>
            </div>
            <div class="block-content block-content-full text-end bg-body-light">
                <form action="{{ route('admin.users.view', $user->id) }}" method="POST">
                    {!! csrf_field() !!}
                    {!! method_field('DELETE') !!}
                    <button id="delete" type="submit" class="btn btn-danger" {{ $user->servers->count() < 1 ?: 'disabled' }}>
                        <i class="fa fa-trash me-1"></i> Delete User
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
