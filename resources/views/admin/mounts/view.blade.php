
@extends('layouts.admin')

@section('title')
    Mounts &rarr; View &rarr; {{ $mount->id }}
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          {{ $mount->name }}
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          {{ str_limit($mount->description, 75) }}
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.mounts') }}">Mounts</a></li>
          <li class="breadcrumb-item" aria-current="page">{{ $mount->name }}</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-6">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Mount Details</h3>
                </div>

                <form action="{{ route('admin.mounts.view', $mount->id) }}" method="POST">
                    <div class="block-content">
                        <div class="form-group">
                            <label for="PUniqueID" class="form-label">Unique ID</label>
                            <input type="text" id="PUniqueID" class="form-control" value="{{ $mount->uuid }}" disabled />
                        </div>

                        <div class="form-group">
                            <label for="pName" class="form-label">Name</label>
                            <input type="text" id="pName" name="name" class="form-control" value="{{ $mount->name }}" />
                        </div>

                        <div class="form-group">
                            <label for="pDescription" class="form-label">Description</label>
                            <textarea id="pDescription" name="description" class="form-control" rows="4">{{ $mount->description }}</textarea>
                        </div>

                        <div class="row">
                            <div class="form-group col-12 col-md-6">
                                <label for="pSource" class="form-label">Source</label>
                                <input type="text" id="pSource" name="source" class="form-control" value="{{ $mount->source }}" />
                            </div>

                            <div class="form-group col-12 col-md-6">
                                <label for="pTarget" class="form-label">Target</label>
                                <input type="text" id="pTarget" name="target" class="form-control" value="{{ $mount->target }}" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group col-12 col-md-6">
                                <label class="form-label">Read Only</label>

                                <div>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" id="pReadOnlyFalse" name="read_only" value="0" @if(!$mount->read_only) checked @endif>
                                        <label class="form-check-label" for="pReadOnlyFalse">False</label>
                                    </div>

                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" id="pReadOnly" name="read_only" value="1" @if($mount->read_only) checked @endif>
                                        <label class="form-check-label" for="pReadOnly">True</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group col-12 col-md-6">
                                <label class="form-label">User Mountable</label>

                                <div>
                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" id="pUserMountableFalse" name="user_mountable" value="0" @if(!$mount->user_mountable) checked @endif>
                                        <label class="form-check-label" for="pUserMountableFalse">False</label>
                                    </div>

                                    <div class="form-check form-check-inline">
                                        <input type="radio" class="form-check-input" id="pUserMountable" name="user_mountable" value="1" @if($mount->user_mountable) checked @endif>
                                        <label class="form-check-label" for="pUserMountable">True</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="block-content block-content-full block-content-sm bg-body-light">
                        {!! csrf_field() !!}
                        {!! method_field('PATCH') !!}

                        <button name="action" value="edit" class="btn btn-primary">Save</button>
                        <button name="action" value="delete" class="btn btn-danger"><i class="fa fa-trash-o"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Eggs</h3>

                    <div class="block-options">
                        <button class="btn btn-sm btn-alt-primary" data-bs-toggle="modal" data-bs-target="#addEggsModal">Add Eggs</button>
                    </div>
                </div>

                <div class="block-content">
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th></th>
                            </tr>

                        @foreach ($mount->eggs as $egg)
                            <tr>
                                <td class="w-15"><code>{{ $egg->id }}</code></td>
                                <td><a href="{{ route('admin.nests.egg.view', $egg->id) }}">{{ $egg->name }}</a></td>
                                <td class="text-center" style="width: 100px;">
                                    <button data-action="detach-egg" data-id="{{ $egg->id }}" class="btn btn-sm btn-danger"><i class="fa fa-trash-o"></i></button>
                                </td>
                            </tr>
                        @endforeach
                        </table>
                    </div>
                </div>
            </div>

            <div class="block block-rounded mt-4">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Nodes</h3>

                    <div class="block-options">
                        <button class="btn btn-sm btn-alt-primary" data-bs-toggle="modal" data-bs-target="#addNodesModal">Add Nodes</button>
                    </div>
                </div>

                <div class="block-content">
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>FQDN</th>
                            <th></th>
                        </tr>

                        @foreach ($mount->nodes as $node)
                            <tr>
                                <td class="w-15"><code>{{ $node->id }}</code></td>
                                <td><a href="{{ route('admin.nodes.view', $node->id) }}">{{ $node->name }}</a></td>
                                <td><code>{{ $node->fqdn }}</code></td>
                                <td class="text-center" style="width: 100px;">
                                    <button data-action="detach-node" data-id="{{ $node->id }}" class="btn btn-sm btn-danger"><i class="fa fa-trash-o"></i></button>
                                </td>
                            </tr>
                        @endforeach
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addEggsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.mounts.eggs', $mount->id) }}" method="POST">
                    <div class="modal-header">
                        <h4 class="modal-title">Add Eggs</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="form-group col-12">
                                <label for="pEggs">Eggs</label>
                                <select id="pEggs" name="eggs[]" class="form-control" multiple>
                                    @foreach ($nests as $nest)
                                        <optgroup label="{{ $nest->name }}">
                                            @foreach ($nest->eggs as $egg)

                                                @if (! in_array($egg->id, $mount->eggs->pluck('id')->toArray()))
                                                    <option value="{{ $egg->id }}">{{ $egg->name }}</option>
                                                @endif

                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        {!! csrf_field() !!}

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addNodesModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.mounts.nodes', $mount->id) }}" method="POST">
                    <div class="modal-header">
                        <h4 class="modal-title">Add Nodes</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="form-group col-12">
                                <label for="pNodes">Nodes</label>
                                <select id="pNodes" name="nodes[]" class="form-control" multiple>
                                    @foreach ($locations as $location)
                                        <optgroup label="{{ $location->long }} ({{ $location->short }})">
                                            @foreach ($location->nodes as $node)

                                                @if (! in_array($node->id, $mount->nodes->pluck('id')->toArray()))
                                                    <option value="{{ $node->id }}">{{ $node->name }}</option>
                                                @endif

                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        {!! csrf_field() !!}

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent

    <script>
        $(document).ready(function() {
            $('#pEggs').select2({
                placeholder: 'Select eggs..',
            });

            $('#pNodes').select2({
                placeholder: 'Select nodes..',
            });

            $('button[data-action="detach-egg"]').click(function (event) {
                event.preventDefault();

                const element = $(this);
                const eggId = $(this).data('id');

                $.ajax({
                    method: 'DELETE',
                    url: '/admin/mounts/' + {{ $mount->id }} + '/eggs/' + eggId,
                    headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
                }).done(function () {
                    element.parent().parent().addClass('warning').delay(100).fadeOut();
                    swal({ type: 'success', title: 'Egg detached.' });
                }).fail(function (jqXHR) {
                    console.error(jqXHR);
                    swal({
                        title: 'Whoops!',
                        text: jqXHR.responseJSON.error,
                        type: 'error'
                    });
                });
            });

            $('button[data-action="detach-node"]').click(function (event) {
                event.preventDefault();

                const element = $(this);
                const nodeId = $(this).data('id');

                $.ajax({
                    method: 'DELETE',
                    url: '/admin/mounts/' + {{ $mount->id }} + '/nodes/' + nodeId,
                    headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
                }).done(function () {
                    element.parent().parent().addClass('warning').delay(100).fadeOut();
                    swal({ type: 'success', title: 'Node detached.' });
                }).fail(function (jqXHR) {
                    console.error(jqXHR);
                    swal({
                        title: 'Whoops!',
                        text: jqXHR.responseJSON.error,
                        type: 'error'
                    });
                });
            });
        });
    </script>
@endsection
