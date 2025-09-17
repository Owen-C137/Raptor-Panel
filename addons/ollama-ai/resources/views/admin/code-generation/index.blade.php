@extends('layouts.admin')

@section('title')
    AI Code Generation Management
@endsection

@section('content-header')
    <h1>AI Code Generation Management</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.ai.index') }}">AI Management</a></li>
        <li class="active">Code Generation</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">AI Code Generation History</h3>
                <div class="box-tools">
                    <button type="button" class="btn btn-sm btn-danger" id="bulk-delete" disabled>
                        <i class="fa fa-trash-o"></i> Delete Selected
                    </button>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover" id="code-generation-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all"></th>
                            <th>ID</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>Language</th>
                            <th>Model</th>
                            <th>Status</th>
                            <th>Execution Time</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Section -->
<div class="row">
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-aqua">
            <div class="inner">
                <h3 id="total-generations">0</h3>
                <p>Total Generations</p>
            </div>
            <div class="icon">
                <i class="fa fa-code"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-green">
            <div class="inner">
                <h3 id="success-rate">0%</h3>
                <p>Success Rate</p>
            </div>
            <div class="icon">
                <i class="fa fa-check-circle"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-yellow">
            <div class="inner">
                <h3 id="avg-time">0s</h3>
                <p>Avg Execution Time</p>
            </div>
            <div class="icon">
                <i class="fa fa-clock-o"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-red">
            <div class="inner">
                <h3 id="today-count">0</h3>
                <p>Today's Generations</p>
            </div>
            <div class="icon">
                <i class="fa fa-calendar"></i>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
            var table = $('#code-generation-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route("admin.ai.code-generation.data") }}',
                columns: [
                    { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
                    { data: 'id', name: 'id' },
                    { data: 'user', name: 'user.username' },
                    { data: 'type', name: 'type' },
                    { data: 'language', name: 'language' },
                    { data: 'model', name: 'model' },
                    { data: 'status', name: 'status' },
                    { data: 'execution_time', name: 'execution_time' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                order: [[ 1, 'desc' ]],
                pageLength: 25
            });

            // Load statistics
            loadStatistics();

            // Select all checkbox
            $('#select-all').on('click', function() {
                var checked = this.checked;
                $('#code-generation-table tbody input[type="checkbox"]').prop('checked', checked);
                updateBulkDeleteButton();
            });

            // Individual checkboxes
            $(document).on('change', '#code-generation-table tbody input[type="checkbox"]', function() {
                updateBulkDeleteButton();
            });

            function updateBulkDeleteButton() {
                var checked = $('#code-generation-table tbody input[type="checkbox"]:checked').length;
                $('#bulk-delete').prop('disabled', checked === 0);
            }

            // Bulk delete
            $('#bulk-delete').on('click', function() {
                var ids = [];
                $('#code-generation-table tbody input[type="checkbox"]:checked').each(function() {
                    ids.push($(this).val());
                });

                if (ids.length === 0) return;

                if (confirm('Are you sure you want to delete ' + ids.length + ' code generation records?')) {
                    $.post('{{ route("admin.ai.code-generation.bulk-destroy") }}', {
                        _token: '{{ csrf_token() }}',
                        ids: ids
                    }).done(function() {
                        table.draw();
                        loadStatistics();
                        toastr.success('Code generation records deleted successfully');
                    }).fail(function() {
                        toastr.error('Failed to delete records');
                    });
                }
            });

            function loadStatistics() {
                // This would normally load from an API endpoint
                // For now, using placeholder values
                $('#total-generations').text('0');
                $('#success-rate').text('0%');
                $('#avg-time').text('0s');
                $('#today-count').text('0');
            }
        });
    </script>
@endsection