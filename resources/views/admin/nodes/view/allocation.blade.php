@extends('layouts.admin')

@section('title')
    {{ $node->name }}: Allocations
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          {{ $node->name }}
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Control allocations available for servers on this node.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.nodes') }}">Nodes</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.nodes.view', $node->id) }}">{{ $node->name }}</a></li>
          <li class="breadcrumb-item" aria-current="page">Allocations</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
@include('admin.nodes.view._navigation')

<div class="row">
    <div class="col-sm-8">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Existing Allocations</h3>
            </div>
            <div class="block-content p-0" style="overflow-x: visible">
                <table class="table table-vcenter" style="margin-bottom:0;">
                    <thead>
                        <tr>
                            <th style="width: 50px;">
                                <input type="checkbox" class="select-all-files d-none d-sm-inline" data-action="selectAll">
                            </th>
                            <th>IP Address <i class="fa fa-fw fa-minus-square" style="font-weight:normal;color:#d9534f;cursor:pointer;" data-bs-toggle="modal" data-bs-target="#allocationModal"></i></th>
                            <th>IP Alias</th>
                            <th>Port</th>
                            <th>Assigned To</th>
                            <th style="width: 120px;">
                                <div class="btn-group d-none d-sm-flex">
                                    <button type="button" id="mass_actions" class="btn btn-sm btn-outline-secondary dropdown-toggle disabled"
                                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Mass Actions
                                    </button>
                                    <ul class="dropdown-menu dropdown-massactions">
                                        <li><a href="#" id="selective-deletion" class="dropdown-item" data-action="selective-deletion">Delete <i class="fa fa-fw fa-trash-o"></i></a></li>
                                    </ul>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($node->allocations as $allocation)
                            <tr>
                                <td data-identifier="type">
                                    @if(is_null($allocation->server_id))
                                    <input type="checkbox" class="select-file d-none d-sm-inline" data-action="addSelection">
                                    @else
                                    <input disabled="disabled" type="checkbox" class="select-file d-none d-sm-inline" data-action="addSelection">
                                    @endif
                                </td>
                                <td data-identifier="ip">{{ $allocation->ip }}</td>
                                <td>
                                    <input class="form-control form-control-sm" type="text" value="{{ $allocation->ip_alias }}" data-action="set-alias" data-id="{{ $allocation->id }}" placeholder="none" />
                                    <span class="input-loader"><i class="fa fa-refresh fa-spin fa-fw"></i></span>
                                </td>
                                <td data-identifier="port">{{ $allocation->port }}</td>
                                <td>
                                    @if(! is_null($allocation->server))
                                        <a href="{{ route('admin.servers.view', $allocation->server_id) }}">{{ $allocation->server->name }}</a>
                                    @endif
                                </td>
                                <td>
                                    @if(is_null($allocation->server_id))
                                        <button data-action="deallocate" data-id="{{ $allocation->id }}" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash-o"></i></button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($node->allocations->hasPages())
                <div class="block-content block-content-full block-content-sm text-center border-top">
                    {{ $node->allocations->render() }}
                </div>
            @endif
        </div>
    </div>
    <div class="col-sm-4">
        <form action="{{ route('admin.nodes.view.allocation', $node->id) }}" method="POST">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Assign New Allocations</h3>
                </div>
                <div class="block-content">
                    <div class="form-group mb-3">
                        <label for="pAllocationIP" class="form-label">IP Address</label>
                        <div>
                            <select class="form-control" name="allocation_ip" id="pAllocationIP" multiple>
                                @foreach($allocations as $allocation)
                                    <option value="{{ $allocation->ip }}">{{ $allocation->ip }}</option>
                                @endforeach
                            </select>
                            <p class="text-muted small">Enter an IP address to assign ports to here.</p>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="pAllocationAlias" class="form-label">IP Alias</label>
                        <div>
                            <input type="text" id="pAllocationAlias" class="form-control" name="allocation_alias" placeholder="alias" />
                            <p class="text-muted small">If you would like to assign a default alias to these allocations enter it here.</p>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="pAllocationPorts" class="form-label">Ports</label>
                        <div>
                            <select class="form-control" name="allocation_ports[]" id="pAllocationPorts" multiple></select>
                            <p class="text-muted small">Enter individual ports or port ranges here separated by commas or spaces.</p>
                        </div>
                    </div>
                </div>
                <div class="block-content block-content-full block-content-sm text-end border-top">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-success btn-sm">Submit</button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="allocationModal" tabindex="-1" role="dialog" aria-labelledby="allocationModal" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
                <form action="{{ route('admin.nodes.view.allocation.removeBlock', $node->id) }}" method="POST">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Delete Allocations for IP Block</h3>
                        <div class="block-options">
                            <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                                <i class="fa fa-fw fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="block-content fs-sm">
                        <div class="row">
                            <div class="col-md-12">
                                <select class="form-control" name="ip">
                                    @foreach($allocations as $allocation)
                                        <option value="{{ $allocation->ip }}">{{ $allocation->ip }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="block-content block-content-full text-end bg-body">
                        {{{ csrf_field() }}}
                        <button type="button" class="btn btn-sm btn-alt-secondary me-1" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-sm btn-danger">Delete Allocations</button>
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
    $('[data-action="addSelection"]').on('click', function () {
        updateMassActions();
    });

    $('[data-action="selectAll"]').on('click', function () {
        $('input.select-file').not(':disabled').prop('checked', function (i, val) {
            return !val;
        });

        updateMassActions();
    });

    $('[data-action="selective-deletion"]').on('mousedown', function () {
        deleteSelected();
    });

    $('#pAllocationIP').select2({
        tags: true,
        maximumSelectionLength: 1,
        selectOnClose: true,
        tokenSeparators: [',', ' '],
    });

    $('#pAllocationPorts').select2({
        tags: true,
        selectOnClose: true,
        tokenSeparators: [',', ' '],
    });

    $('button[data-action="deallocate"]').click(function (event) {
        event.preventDefault();
        var element = $(this);
        var allocation = $(this).data('id');
        swal({
            title: '',
            text: 'Are you sure you want to delete this allocation?',
            type: 'warning',
            showCancelButton: true,
            allowOutsideClick: true,
            closeOnConfirm: false,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d9534f',
            showLoaderOnConfirm: true
        }, function () {
            $.ajax({
                method: 'DELETE',
                url: '/admin/nodes/view/' + {{ $node->id }} + '/allocation/remove/' + allocation,
                headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
            }).done(function (data) {
                element.parent().parent().addClass('warning').delay(100).fadeOut();
                swal({ type: 'success', title: 'Port Deleted!' });
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

    var typingTimer;
    $('input[data-action="set-alias"]').keyup(function () {
        clearTimeout(typingTimer);
        $(this).parent().removeClass('has-error has-success');
        typingTimer = setTimeout(sendAlias, 250, $(this));
    });

    var fadeTimers = [];
    function sendAlias(element) {
        element.parent().find('.input-loader').show();
        clearTimeout(fadeTimers[element.data('id')]);
        $.ajax({
            method: 'POST',
            url: '/admin/nodes/view/' + {{ $node->id }} + '/allocation/alias',
            headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
            data: {
                alias: element.val(),
                allocation_id: element.data('id'),
            }
        }).done(function () {
            element.parent().addClass('has-success');
        }).fail(function (jqXHR) {
            console.error(jqXHR);
            element.parent().addClass('has-error');
        }).always(function () {
            element.parent().find('.input-loader').hide();
            fadeTimers[element.data('id')] = setTimeout(clearHighlight, 2500, element);
        });
    }

    function clearHighlight(element) {
        element.parent().removeClass('has-error has-success');
    }

    function updateMassActions() {
        if ($('input.select-file:checked').length > 0) {
            $('#mass_actions').removeClass('disabled');
        } else {
            $('#mass_actions').addClass('disabled');
        }
    }

    function deleteSelected() {
        var selectedIds = [];
        var selectedItems = [];
        var selectedItemsElements = [];

        $('input.select-file:checked').each(function () {
            var $parent = $($(this).closest('tr'));
            var id = $parent.find('[data-action="deallocate"]').data('id');
            var $ip = $parent.find('td[data-identifier="ip"]');
            var $port = $parent.find('td[data-identifier="port"]');
            var block = `${$ip.text()}:${$port.text()}`;

            selectedIds.push({
                id: id
            });
            selectedItems.push(block);
            selectedItemsElements.push($parent);
        });

        if (selectedItems.length !== 0) {
            var formattedItems = "";
            var i = 0;
            $.each(selectedItems, function (key, value) {
                formattedItems += ("<code>" + value + "</code>, ");
                i++;
                return i < 5;
            });

            formattedItems = formattedItems.slice(0, -2);
            if (selectedItems.length > 5) {
                formattedItems += ', and ' + (selectedItems.length - 5) + ' other(s)';
            }

            swal({
                type: 'warning',
                title: '',
                text: 'Are you sure you want to delete the following allocations: ' + formattedItems + '?',
                html: true,
                showCancelButton: true,
                showConfirmButton: true,
                closeOnConfirm: false,
                showLoaderOnConfirm: true
            }, function () {
                $.ajax({
                    method: 'DELETE',
                    url: '/admin/nodes/view/' + {{ $node->id }} + '/allocations',
                    headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')},
                    data: JSON.stringify({
                        allocations: selectedIds
                    }),
                    contentType: 'application/json',
                    processData: false
                }).done(function () {
                    $('#file_listing input:checked').each(function () {
                        $(this).prop('checked', false);
                    });

                    $.each(selectedItemsElements, function () {
                        $(this).addClass('warning').delay(200).fadeOut();
                    });

                    swal({
                        type: 'success',
                        title: 'Allocations Deleted'
                    });
                }).fail(function (jqXHR) {
                    console.error(jqXHR);
                    swal({
                        type: 'error',
                        title: 'Whoops!',
                        html: true,
                        text: 'An error occurred while attempting to delete these allocations. Please try again.',
                    });
                });
            });
        } else {
            swal({
                type: 'warning',
                title: '',
                text: 'Please select allocation(s) to delete.',
            });
        }
    }
    </script>
@endsection
