@extends('layouts.admin')

@section('title')
    AI Templates Management
@endsection

@section('content-header')
    <h1>AI Templates Management</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.ai.index') }}">AI Management</a></li>
        <li class="active">Templates</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">AI Code Templates</h3>
                <div class="box-tools">
                    <a href="{{ route('admin.ai.templates.create') }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-plus"></i> Create Template
                    </a>
                    <button type="button" class="btn btn-sm btn-danger" id="bulk-delete" disabled>
                        <i class="fa fa-trash-o"></i> Delete Selected
                    </button>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover" id="templates-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all"></th>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Language</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Uses</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Template Categories -->
<div class="row">
    <div class="col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-aqua"><i class="fa fa-code"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Code Templates</span>
                <span class="info-box-number" id="code-templates-count">0</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-red"><i class="fa fa-bug"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Debug Templates</span>
                <span class="info-box-number" id="debug-templates-count">0</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-green"><i class="fa fa-cog"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Config Templates</span>
                <span class="info-box-number" id="config-templates-count">0</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-yellow"><i class="fa fa-file-text"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Documentation</span>
                <span class="info-box-number" id="doc-templates-count">0</span>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
            var table = $('#templates-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route("admin.ai.templates.data") }}',
                columns: [
                    { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
                    { data: 'id', name: 'id' },
                    { data: 'name', name: 'name' },
                    { data: 'type', name: 'type' },
                    { data: 'language', name: 'language' },
                    { data: 'category', name: 'category' },
                    { data: 'is_active', name: 'is_active' },
                    { data: 'usage_count', name: 'usage_count' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                order: [[ 1, 'desc' ]],
                pageLength: 25
            });

            // Load template counts
            loadTemplateCounts();

            // Select all checkbox
            $('#select-all').on('click', function() {
                var checked = this.checked;
                $('#templates-table tbody input[type="checkbox"]').prop('checked', checked);
                updateBulkDeleteButton();
            });

            // Individual checkboxes
            $(document).on('change', '#templates-table tbody input[type="checkbox"]', function() {
                updateBulkDeleteButton();
            });

            function updateBulkDeleteButton() {
                var checked = $('#templates-table tbody input[type="checkbox"]:checked').length;
                $('#bulk-delete').prop('disabled', checked === 0);
            }

            // Bulk delete
            $('#bulk-delete').on('click', function() {
                var ids = [];
                $('#templates-table tbody input[type="checkbox"]:checked').each(function() {
                    ids.push($(this).val());
                });

                if (ids.length === 0) return;

                if (confirm('Are you sure you want to delete ' + ids.length + ' templates?')) {
                    $.post('{{ route("admin.ai.templates.bulk-destroy") }}', {
                        _token: '{{ csrf_token() }}',
                        ids: ids
                    }).done(function() {
                        table.draw();
                        loadTemplateCounts();
                        toastr.success('Templates deleted successfully');
                    }).fail(function() {
                        toastr.error('Failed to delete templates');
                    });
                }
            });

            function loadTemplateCounts() {
                // This would normally load from an API endpoint
                // For now, using placeholder values
                $('#code-templates-count').text('0');
                $('#debug-templates-count').text('0');
                $('#config-templates-count').text('0');
                $('#doc-templates-count').text('0');
            }
        });
    </script>
@endsection