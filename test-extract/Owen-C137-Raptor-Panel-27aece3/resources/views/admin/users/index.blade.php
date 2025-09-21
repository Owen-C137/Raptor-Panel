@extends('layouts.admin')

@section('title')
    List Users
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Users
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          All registered users on the system.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.index') }}">Admin</a>
          </li>
          <li class="breadcrumb-item" aria-current="page">
            Users
          </li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-users me-2"></i>User List
                </h3>
                <div class="block-options">
                    <form action="{{ route('admin.users') }}" method="GET" class="d-flex">
                        <div class="input-group input-group-sm me-2">
                            <input type="text" name="filter[email]" class="form-control" value="{{ request()->input('filter.email') }}" placeholder="Search users...">
                            <button type="submit" class="btn btn-alt-primary">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>
                        <a href="{{ route('admin.users.new') }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-plus me-1"></i> Create New
                        </a>
                    </form>
                </div>
            </div>
            <div class="block-content block-content-full">
                <div class="table-responsive">
                    <table class="table table-hover table-vcenter">
                        <thead>
                            <tr>
                                <th style="width: 80px;">ID</th>
                                <th>Email</th>
                                <th>Client Name</th>
                                <th>Username</th>
                                <th class="text-center" style="width: 80px;">2FA</th>
                                <th class="text-center" style="width: 120px;">
                                    <span data-bs-toggle="tooltip" data-bs-placement="top" title="Servers that this user is marked as the owner of.">Servers Owned</span>
                                </th>
                                <th class="text-center" style="width: 120px;">
                                    <span data-bs-toggle="tooltip" data-bs-placement="top" title="Servers that this user can access because they are marked as a subuser.">Can Access</span>
                                </th>
                                <th class="text-center" style="width: 60px;">Avatar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td><code>{{ $user->id }}</code></td>
                                    <td>
                                        <a class="fw-semibold link-fx" href="{{ route('admin.users.view', $user->id) }}">{{ $user->email }}</a>
                                        @if($user->root_admin)
                                            <i class="fa fa-star text-warning ms-1" title="Administrator"></i>
                                        @endif
                                    </td>
                                    <td>{{ $user->name_last }}, {{ $user->name_first }}</td>
                                    <td>{{ $user->username }}</td>
                                    <td class="text-center">
                                        @if($user->use_totp)
                                            <i class="fa fa-lock text-success" title="2FA Enabled"></i>
                                        @else
                                            <i class="fa fa-unlock text-danger" title="2FA Disabled"></i>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.servers', ['filter[owner_id]' => $user->id]) }}" class="badge bg-primary">{{ $user->servers_count }}</a>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $user->subuser_of_count }}</span>
                                    </td>
                                    <td class="text-center">
                                        <img src="https://www.gravatar.com/avatar/{{ md5(strtolower($user->email)) }}?s=100" class="rounded-circle" style="height:32px; width:32px;" alt="Avatar" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fa fa-users fa-2x mb-2"></i>
                                        <br>No users found matching your search criteria.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($users->hasPages())
                <div class="block-content block-content-full bg-body-light text-center">
                    {!! $users->appends(['query' => Request::input('query')])->render() !!}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
