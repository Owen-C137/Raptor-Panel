@extends('layouts.admin')

@section('title')
    Orders Management
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Orders
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Manage shop orders and subscriptions.
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.index') }}">Admin</a>
          </li>
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.shop.dashboard') }}">Shop</a>
          </li>
          <li class="breadcrumb-item" aria-current="page">
            Orders
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
                    <i class="fa fa-shopping-cart me-1"></i>Order Management
                </h3>
            </div>
            
            <div class="block-content">
                <!-- Search and Filter Form -->
                <div class="row">
                    <div class="col-md-4">
                        <form method="GET" action="{{ route('admin.shop.orders.index') }}">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search orders..." 
                                       value="{{ $search }}" autocomplete="off">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fa fa-search"></i>
                                </button>
                                @if($search)
                                    <a href="{{ route('admin.shop.orders.index') }}" class="btn btn-outline-secondary">
                                        <i class="fa fa-times"></i>
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" onchange="window.location.href=this.value">
                            <option value="{{ route('admin.shop.orders.index') }}">All Status</option>
                            @foreach($statuses as $key => $label)
                                <option value="{{ route('admin.shop.orders.index', ['status' => $key]) }}"
                                        {{ $status == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" onchange="window.location.href=this.value">
                            <option value="{{ route('admin.shop.orders.index') }}">All Users</option>
                            @foreach($users as $u)
                                <option value="{{ route('admin.shop.orders.index', ['user' => $u->id]) }}"
                                        {{ $user == $u->id ? 'selected' : '' }}>
                                    {{ $u->username }} ({{ $u->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            
            @if($orders->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-vcenter">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>User</th>
                                <th>Plan</th>
                                <th>Server</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Billing Cycle</th>
                                <th>Next Due</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                <tr>
                                    <td>
                                        <strong>#{{ $order->id }}</strong>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.users.view', $order->user_id) }}">
                                            {{ $order->user->username }}
                                        </a>
                                        <br><small class="text-muted">{{ $order->user->email }}</small>
                                    </td>
                                    <td>
                                        {{ $order->plan->name ?? 'N/A' }}
                                        @if($order->plan && $order->plan->category)
                                            <br><small class="text-muted">{{ $order->plan->category->name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($order->server)
                                            <div class="server-links-container">
                                                <a href="{{ route('admin.servers.view', $order->server->id) }}" 
                                                   class="btn btn-sm btn-info server-link" 
                                                   title="Manage Server">
                                                    <i class="fa fa-server"></i> {{ $order->server->name }}
                                                </a>
                                                <br><small class="text-muted">{{ $order->server->uuid }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">No server</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $currencySymbol }}{{ number_format($order->amount, 2) }}</strong>
                                        @if($order->setup_fee > 0)
                                            <br><small class="text-muted">+{{ $currencySymbol }}{{ number_format($order->setup_fee, 2) }} setup</small>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($order->status)
                                            @case('pending')
                                                <span class="badge bg-warning">Pending</span>
                                                @break
                                            @case('processing')
                                                <span class="badge bg-info">Processing</span>
                                                @break
                                            @case('active')
                                                <span class="badge bg-success">Active</span>
                                                @break
                                            @case('suspended')
                                                <span class="badge bg-danger">Suspended</span>
                                                @break
                                            @case('cancelled')
                                                <span class="badge bg-secondary">Cancelled</span>
                                                @break
                                            @case('terminated')
                                                <span class="badge bg-danger">Terminated</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ ucfirst($order->status) }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <span class="badge bg-outline-secondary">{{ ucfirst(str_replace('_', ' ', $order->billing_cycle)) }}</span>
                                    </td>
                                    <td>
                                        @if($order->next_due_at)
                                            {{ $order->next_due_at->format('M d, Y') }}
                                            @if($order->next_due_at->isPast())
                                                <br><small class="text-danger">Overdue</small>
                                            @endif
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $order->created_at->format('M d, Y') }}
                                        <br><small class="text-muted">{{ $order->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.shop.orders.show', $order->id) }}" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-danger"
                                                onclick="showDeleteOrderModal({{ $order->id }}, '{{ addslashes($order->user->username) }}', {{ $order->server ? 'true' : 'false' }}, '{{ $order->server ? addslashes($order->server->name) : '' }}')"
                                                data-server-count="{{ $order->server ? 1 : 0 }}">
                                            <i class="fa fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="block-content block-content-full">
                    <div class="row">
                        <div class="col-12 text-center">
                            {{ $orders->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            @else
                <div class="block-content text-center">
                    <div class="py-4">
                        <i class="fa fa-shopping-cart fa-2x text-muted mb-3"></i>
                        <h4 class="text-muted">No orders found</h4>
                        <p class="text-muted">No orders found matching your criteria.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Delete Order Modal -->
<div class="modal fade" id="deleteOrderModal" tabindex="-1" role="dialog" aria-labelledby="deleteOrderModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-transparent mb-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-trash text-danger me-1"></i> Delete Order
                    </h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa fa-fw fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content fs-sm">
                    <p><strong>Are you sure you want to delete this order?</strong></p>
                    <p>Order: <span id="delete-order-details"></span></p>
                    
                    <div id="server-deletion-section" style="display: none;">
                        <hr>
                        <div class="alert alert-warning d-flex">
                            <div class="flex-shrink-0">
                                <i class="fa fa-server"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="alert-heading">Server Connected</h5>
                                <p class="mb-2">This order has an associated server: <strong id="delete-server-name"></strong></p>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="delete-server-checkbox" name="delete_server" value="1" checked>
                                    <label class="form-check-label fw-medium" for="delete-server-checkbox">
                                        Also delete the associated server
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    <i class="fa fa-warning"></i> 
                                    If unchecked, the server will remain but will no longer be linked to this order.
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-danger d-flex">
                        <div class="flex-shrink-0">
                            <i class="fa fa-exclamation-triangle"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <strong>Warning:</strong> This action cannot be undone.
                        </div>
                    </div>
                </div>
                <div class="block-content block-content-full text-end bg-body-light">
                    <button type="button" class="btn btn-sm btn-alt-secondary me-1" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-danger" id="confirm-delete-order-btn">
                        <i class="fa fa-trash"></i> <span id="delete-order-btn-text">Delete Order</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        let currentOrderId = null;

        function showDeleteOrderModal(orderId, username, hasServer, serverName) {
            currentOrderId = orderId;
            
            document.getElementById('delete-order-details').textContent = 'Order #' + orderId + ' for ' + username;
            
            if (hasServer) {
                document.getElementById('delete-server-name').textContent = serverName;
                document.getElementById('server-deletion-section').style.display = 'block';
            } else {
                document.getElementById('server-deletion-section').style.display = 'none';
            }
            
            // Update button text based on initial checkbox state
            updateDeleteOrderButtonText();
            
            const modal = new bootstrap.Modal(document.getElementById('deleteOrderModal'));
            modal.show();
        }

        function updateDeleteOrderButtonText() {
            const deleteServerChecked = document.getElementById('delete-server-checkbox') ? 
                document.getElementById('delete-server-checkbox').checked : false;
            const hasServer = document.getElementById('server-deletion-section').style.display !== 'none';
            
            let buttonText = 'Delete Order';
            if (hasServer && deleteServerChecked) {
                buttonText = 'Delete Order & Server';
            }
            
            document.getElementById('delete-order-btn-text').textContent = buttonText;
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Update button text when checkbox changes
            const deleteServerCheckbox = document.getElementById('delete-server-checkbox');
            if (deleteServerCheckbox) {
                deleteServerCheckbox.addEventListener('change', function() {
                    updateDeleteOrderButtonText();
                });
            }

            // Handle delete confirmation
            document.getElementById('confirm-delete-order-btn').addEventListener('click', function() {
                if (!currentOrderId) return;
                
                const deleteServerCheckbox = document.getElementById('delete-server-checkbox');
                const deleteServer = deleteServerCheckbox ? deleteServerCheckbox.checked : false;
                
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/admin/shop/orders/${currentOrderId}`;
                
                // Add CSRF token
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);
                
                // Add method override
                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';
                form.appendChild(methodInput);
                
                if (deleteServer) {
                    const deleteServerInput = document.createElement('input');
                    deleteServerInput.type = 'hidden';
                    deleteServerInput.name = 'delete_server';
                    deleteServerInput.value = '1';
                    form.appendChild(deleteServerInput);
                }
                
                document.body.appendChild(form);
                form.submit();
            });
        });
    </script>
@endsection

@push('styles')
<style>
    .server-links-container .server-link {
        text-decoration: none;
        color: #fff;
        transition: all 0.15s ease-in-out;
        display: inline-block;
    }
    
    .server-links-container .server-link:hover {
        text-decoration: none;
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.15);
    }
    
    .server-links-container .server-link:focus {
        text-decoration: none;
        color: #fff;
    }

    .table-vcenter td {
        vertical-align: middle;
    }

    .badge {
        font-weight: 600;
        font-size: 0.875em;
    }
</style>
@endpush
