@extends('layouts.admin')

@section('title')
    AI Conversations Management
@endsection

@section('content-header')
    <h1>AI Conversations Management</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.ai.index') }}">AI Management</a></li>
        <li class="active">Conversations</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">AI Conversations</h3>
                <div class="box-tools">
                    <button type="button" class="btn btn-sm btn-danger" id="bulk-delete" disabled>
                        <i class="fa fa-trash-o"></i> Delete Selected
                    </button>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover" id="conversations-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all"></th>
                            <th>ID</th>
                            <th>User</th>
                            <th>Model</th>
                            <th>Messages</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
            var table = $('#conversations-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route("admin.ai.conversations.data") }}',
                columns: [
                    { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
                    { data: 'id', name: 'id' },
                    { data: 'user', name: 'user.username' },
                    { data: 'model', name: 'model' },
                    { data: 'message_count', name: 'message_count' },
                    { data: 'status', name: 'status' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                order: [[ 1, 'desc' ]],
                pageLength: 25
            });

            // Select all checkbox
            $('#select-all').on('click', function() {
                var checked = this.checked;
                $('#conversations-table tbody input[type="checkbox"]').prop('checked', checked);
                updateBulkDeleteButton();
            });

            // Individual checkboxes
            $(document).on('change', '#conversations-table tbody input[type="checkbox"]', function() {
                updateBulkDeleteButton();
            });

            function updateBulkDeleteButton() {
                var checked = $('#conversations-table tbody input[type="checkbox"]:checked').length;
                $('#bulk-delete').prop('disabled', checked === 0);
            }

            // Bulk delete
            $('#bulk-delete').on('click', function() {
                var ids = [];
                $('#conversations-table tbody input[type="checkbox"]:checked').each(function() {
                    ids.push($(this).val());
                });

                if (ids.length === 0) return;

                if (confirm('Are you sure you want to delete ' + ids.length + ' conversations?')) {
                    $.post('{{ route("admin.ai.conversations.bulk-destroy") }}', {
                        _token: '{{ csrf_token() }}',
                        ids: ids
                    }).done(function() {
                        table.draw();
                        toastr.success('Conversations deleted successfully');
                    }).fail(function() {
                        toastr.error('Failed to delete conversations');
                    });
                }
            });
        });
    </script>
@endsection