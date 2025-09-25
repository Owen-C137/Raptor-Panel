@extends('layouts.admin')

@section('title')
    List Servers
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Servers
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          All servers available on the system.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.index') }}">Admin</a>
          </li>
          <li class="breadcrumb-item" aria-current="page">
            Servers
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
                <h3 class="block-title">Server List</h3>
                <div class="block-options">
                    <form action="{{ route('admin.servers') }}" method="GET" class="d-flex">
                        <div class="input-group input-group-sm me-2">
                            <input type="text" name="filter[*]" class="form-control" value="{{ request()->input()['filter']['*'] ?? '' }}" placeholder="Search Servers">
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-success me-2" data-bs-toggle="modal" data-bs-target="#quickServerModal">
                            <i class="fa fa-bolt me-1"></i> Quick Create
                        </button>
                        <a href="{{ route('admin.servers.new') }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-plus me-1"></i> Create New
                        </a>
                    </form>
                </div>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-hover table-vcenter">
                        <thead>
                            <tr>
                                <th>Server Name</th>
                                <th>UUID</th>
                                <th>Owner</th>
                                <th>Node</th>
                                <th>Connection</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($servers as $server)
                                <tr data-server="{{ $server->uuidShort }}">
                                    <td>
                                        <a href="{{ route('admin.servers.view', $server->id) }}" class="fw-semibold">
                                            {{ $server->name }}
                                        </a>
                                    </td>
                                    <td>
                                        <code title="{{ $server->uuid }}">{{ $server->uuid }}</code>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.users.view', $server->user->id) }}">
                                            {{ $server->user->username }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.nodes.view', $server->node->id) }}">
                                            {{ $server->node->name }}
                                        </a>
                                    </td>
                                    <td>
                                        <code>{{ $server->allocation->alias }}:{{ $server->allocation->port }}</code>
                                    </td>
                                    <td class="text-center">
                                        @if($server->isSuspended())
                                            <span class="badge bg-danger">Suspended</span>
                                        @elseif(! $server->isInstalled())
                                            <span class="badge bg-warning">Installing</span>
                                        @else
                                            <span class="badge bg-success">Active</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a class="btn btn-sm btn-outline-primary" href="/server/{{ $server->uuidShort }}">
                                            <i class="fa fa-wrench"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if($servers->hasPages())
                <div class="block-content block-content-full bg-body-light">
                    <div class="d-flex justify-content-center">
                        {!! $servers->appends(['filter' => Request::input('filter')])->render() !!}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Quick Server Creation Modal -->
<div class="modal fade" id="quickServerModal" tabindex="-1" aria-labelledby="quickServerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="quickServerModalLabel">
                    <i class="fa fa-bolt me-2"></i>Quick Server Creation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="quickCreateLoading" class="text-center py-3" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading available options...</p>
                </div>

                <form id="quickServerForm">
                    @csrf
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle me-2"></i>
                        <strong>Quick Create</strong> is perfect for rapid egg testing with smart defaults and auto-selection.
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="quickNest" class="form-label">Nest</label>
                                <select class="form-select" id="quickNest" name="nest_id" required>
                                    <option value="">Select a nest...</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="quickEgg" class="form-label">Egg</label>
                                <select class="form-select" id="quickEgg" name="egg_id" required disabled>
                                    <option value="">Select an egg...</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="quickPreset" class="form-label">Resource Preset</label>
                                <select class="form-select" id="quickPreset" name="preset" required>
                                    <option value="low">Low (512MB RAM, 1GB Disk) - Testing</option>
                                    <option value="medium" selected>Medium (2GB RAM, 4GB Disk) - Development</option>
                                    <option value="high">High (4GB RAM, 8GB Disk) - Production</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Server Options</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="quickAutoStart" name="auto_start" value="1">
                                    <label class="form-check-label" for="quickAutoStart">
                                        Auto-start after creation
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="quickRandomName" name="random_name" value="1" checked>
                                    <label class="form-check-label" for="quickRandomName">
                                        Use random server name
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3" id="customNameField" style="display: none;">
                        <label for="quickCustomName" class="form-label">Custom Server Name</label>
                        <input type="text" class="form-control" id="quickCustomName" name="custom_name" maxlength="255" placeholder="Enter server name...">
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-secondary">
                                <h6><i class="fa fa-magic me-1"></i> Smart Defaults Include:</h6>
                                <ul class="mb-0 small">
                                    <li><strong>Node & Allocation:</strong> Auto-selected first available</li>
                                    <li><strong>Environment Variables:</strong> Smart defaults based on egg type</li>
                                    <li><strong>Docker Image:</strong> Latest or Java 17 preferred</li>
                                    <li><strong>Naming:</strong> Random descriptive names for easy identification</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Cancel
                </button>
                <button type="submit" form="quickServerForm" class="btn btn-success" id="quickCreateBtn">
                    <i class="fa fa-bolt me-1"></i> Create Quick Server
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('.console-popout').on('click', function (event) {
            event.preventDefault();
            window.open($(this).attr('href'), 'Pterodactyl Console', 'width=800,height=400');
        });

        // Quick Server Creation Modal Functionality
        let quickServerData = null;

        // Load data when modal opens
        $('#quickServerModal').on('show.bs.modal', function () {
            if (!quickServerData) {
                loadQuickServerData();
            }
        });

        // Load available nests and eggs
        function loadQuickServerData() {
            $('#quickCreateLoading').show();
            $('#quickServerForm').hide();

            fetch('{{ route('admin.servers.quick.data') }}')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    quickServerData = data;
                    populateNests(data.nests);
                    displayWarnings(data.warnings || []);
                    $('#quickCreateLoading').hide();
                    $('#quickServerForm').show();
                })
                .catch(error => {
                    console.error('Error loading quick server data:', error);
                    showAlert('error', 'Failed to load server creation data. Please try again.');
                    $('#quickCreateLoading').hide();
                });
        }

        // Display system warnings
        function displayWarnings(warnings) {
            const warningContainer = $('#quickServerForm .alert-info').after('<div id="systemWarnings"></div>').next();
            
            warnings.forEach(warning => {
                const alertClass = warning.type === 'danger' ? 'alert-danger' : 
                                  warning.type === 'warning' ? 'alert-warning' : 'alert-info';
                const icon = warning.type === 'danger' ? 'fa-exclamation-triangle' : 'fa-info-circle';
                
                warningContainer.append(`
                    <div class="alert ${alertClass} small">
                        <i class="fa ${icon} me-2"></i>${warning.message}
                    </div>
                `);
            });
        }

        // Populate nest dropdown
        function populateNests(nests) {
            const nestSelect = $('#quickNest');
            nestSelect.empty().append('<option value="">Select a nest...</option>');
            
            nests.forEach(nest => {
                nestSelect.append(`<option value="${nest.id}">${nest.name}</option>`);
            });
        }

        // Handle nest selection change
        $('#quickNest').on('change', function() {
            const nestId = $(this).val();
            const eggSelect = $('#quickEgg');
            
            eggSelect.empty().append('<option value="">Select an egg...</option>');
            
            if (nestId && quickServerData) {
                const nest = quickServerData.nests.find(n => n.id == nestId);
                if (nest && nest.eggs) {
                    nest.eggs.forEach(egg => {
                        eggSelect.append(`<option value="${egg.id}">${egg.name}</option>`);
                    });
                    eggSelect.prop('disabled', false);
                }
            } else {
                eggSelect.prop('disabled', true);
            }
        });

        // Handle random name checkbox
        $('#quickRandomName').on('change', function() {
            if ($(this).is(':checked')) {
                $('#customNameField').hide();
                $('#quickCustomName').val('');
            } else {
                $('#customNameField').show();
            }
        });

        // Handle form submission
        $('#quickServerForm').on('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = $('#quickCreateBtn');
            const originalText = submitBtn.html();
            
            // Disable button and show loading
            submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Creating Server...');
            
            // Collect form data
            const formData = new FormData(this);
            
            // Submit request
            fetch('{{ route('admin.servers.quick.create') }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="_token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showAlert('success', data.message, data.server);
                    
                    // Close modal and reset form
                    $('#quickServerModal').modal('hide');
                    resetQuickForm();
                    
                    // Optionally redirect to server view
                    setTimeout(() => {
                        if (data.server && data.server.view_url) {
                            window.location.href = data.server.view_url;
                        } else {
                            // Refresh page to show new server
                            window.location.reload();
                        }
                    }, 2000);
                    
                } else {
                    showAlert('error', data.error || 'Failed to create server. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error creating server:', error);
                showAlert('error', 'Failed to create server. Please check the console for details.');
            })
            .finally(() => {
                // Re-enable button
                submitBtn.prop('disabled', false).html(originalText);
            });
        });

        // Reset form when modal is hidden
        $('#quickServerModal').on('hidden.bs.modal', function () {
            resetQuickForm();
        });

        function resetQuickForm() {
            $('#quickServerForm')[0].reset();
            $('#quickEgg').prop('disabled', true).empty().append('<option value="">Select an egg...</option>');
            $('#customNameField').hide();
            $('#quickPreset').val('medium');
        }

        // Show alert messages
        function showAlert(type, message, serverData = null) {
            let alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            let icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            let alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <i class="fa ${icon} me-2"></i>
                    <strong>${message}</strong>
            `;
            
            if (serverData) {
                alertHtml += `
                    <div class="mt-2">
                        <div class="row small">
                            <div class="col-md-6">
                                <strong>Server:</strong> ${serverData.name}<br>
                                <strong>Node:</strong> ${serverData.node}<br>
                                <strong>Allocation:</strong> ${serverData.allocation}
                            </div>
                            <div class="col-md-6">
                                <strong>Preset:</strong> ${serverData.preset}<br>
                                <strong>Memory:</strong> ${serverData.memory}<br>
                                <strong>Disk:</strong> ${serverData.disk}
                            </div>
                        </div>
                    </div>
                `;
            }
            
            alertHtml += `
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // Find container for alerts (add after page header)
            let alertContainer = $('.bg-body-light').after('<div id="alertContainer" class="container-fluid py-2"></div>');
            if ($('#alertContainer').length === 0) {
                $('.bg-body-light').after('<div id="alertContainer" class="container-fluid py-2"></div>');
            }
            
            $('#alertContainer').html(alertHtml);
            
            // Auto-remove after 10 seconds for success, 15 for error
            setTimeout(() => {
                $('#alertContainer .alert').fadeOut();
            }, type === 'success' ? 10000 : 15000);
        }
    </script>
@endsection
