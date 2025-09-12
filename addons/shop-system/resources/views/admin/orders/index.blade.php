@extends('layouts.admin')

@section('title')
    Orders Management
@endsection

@section('content-header')
    <h1>
        Orders
        <small>Manage shop orders and subscriptions</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
        <li class="active">Orders</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Order List</h3>
            </div>
            
            <div class="box-body">
                <!-- Search and Filter Form -->
                <div class="row">
                    <div class="col-md-4">
                        <form method="GET" action="{{ route('admin.shop.orders.index') }}">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search orders..." 
                                       value="{{ $search }}" autocomplete="off">
                                <div class="input-group-btn">
                                    <button type="submit" class="btn btn-default">
                                        <i class="fa fa-search"></i>
                                    </button>
                                    @if($search)
                                        <a href="{{ route('admin.shop.orders.index') }}" class="btn btn-default">
                                            <i class="fa fa-times"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" onchange="window.location.href=this.value">
                            <option value="{{ route('admin.shop.orders.index') }}">All Status</option>
                            @foreach($statuses as $key => $label)
                                <option value="{{ route('admin.shop.orders.index', ['status' => $key]) }}"
                                        {{ $status == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" onchange="window.location.href=this.value">
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
                    <table class="table table-hover">
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
                                                   class="btn btn-xs btn-info server-link" 
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
                                        <strong>${{ number_format($order->amount, 2) }}</strong>
                                        @if($order->setup_fee > 0)
                                            <br><small class="text-muted">+${{ number_format($order->setup_fee, 2) }} setup</small>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($order->status)
                                            @case('pending')
                                                <span class="label label-warning">Pending</span>
                                                @break
                                            @case('processing')
                                                <span class="label label-info">Processing</span>
                                                @break
                                            @case('active')
                                                <span class="label label-success">Active</span>
                                                @break
                                            @case('suspended')
                                                <span class="label label-danger">Suspended</span>
                                                @break
                                            @case('cancelled')
                                                <span class="label label-default">Cancelled</span>
                                                @break
                                            @case('terminated')
                                                <span class="label label-danger">Terminated</span>
                                                @break
                                            @default
                                                <span class="label label-default">{{ ucfirst($order->status) }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <span class="label label-default">{{ ucfirst(str_replace('_', ' ', $order->billing_cycle)) }}</span>
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
                                           class="btn btn-xs btn-primary">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                        <button type="button" 
                                                class="btn btn-xs btn-danger"
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
                
                <div class="box-footer">
                    <div class="row">
                        <div class="col-md-12 text-center">
                            {{ $orders->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            @else
                <div class="box-body text-center">
                    <p>No orders found matching your criteria.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Delete Order Modal -->
<div class="modal fade" id="deleteOrderModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fa fa-trash text-danger"></i> Delete Order
                </h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>Are you sure you want to delete this order?</strong></p>
                <p>Order: <span id="delete-order-details"></span></p>
                
                <div id="server-deletion-section" style="display: none;">
                    <hr>
                    <div class="alert alert-warning">
                        <h5><i class="fa fa-server"></i> Server Connected</h5>
                        <p>This order has an associated server: <strong id="delete-server-name"></strong></p>
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input type="checkbox" id="delete-server-checkbox" name="delete_server" value="1" checked>
                            <label for="delete-server-checkbox" class="strong">Also delete the associated server</label>
                        </div>
                        <small class="text-muted">
                            <i class="fa fa-warning"></i> 
                            If unchecked, the server will remain but will no longer be linked to this order.
                        </small>
                    </div>
                </div>
                
                <div class="alert alert-danger">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>Warning:</strong> This action cannot be undone.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-order-btn">
                    <i class="fa fa-trash"></i> <span id="delete-order-btn-text">Delete Order</span>
                </button>
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
            
            $('#delete-order-details').text('Order #' + orderId + ' for ' + username);
            
            if (hasServer) {
                $('#delete-server-name').text(serverName);
                $('#server-deletion-section').show();
            } else {
                $('#server-deletion-section').hide();
            }
            
            // Update button text based on initial checkbox state
            updateDeleteOrderButtonText();
            
            $('#deleteOrderModal').modal('show');
        }

        function updateDeleteOrderButtonText() {
            const deleteServerChecked = $('#delete-server-checkbox').is(':checked');
            const hasServer = $('#server-deletion-section').is(':visible');
            
            let buttonText = 'Delete Order';
            if (hasServer && deleteServerChecked) {
                buttonText = 'Delete Order & Server';
            }
            
            $('#delete-order-btn-text').text(buttonText);
        }

        $(document).ready(function() {
            // Update button text when checkbox changes
            $('#delete-server-checkbox').on('change', function() {
                updateDeleteOrderButtonText();
            });

            // Handle delete confirmation
            $('#confirm-delete-order-btn').on('click', function() {
                if (!currentOrderId) return;
                
                const deleteServer = $('#delete-server-checkbox').is(':checked');
                
                // Create form and submit
                const form = $('<form>')
                    .attr('method', 'POST')
                    .attr('action', `/admin/shop/orders/${currentOrderId}`)
                    .append($('<input>').attr('type', 'hidden').attr('name', '_token').val('{{ csrf_token() }}'))
                    .append($('<input>').attr('type', 'hidden').attr('name', '_method').val('DELETE'));
                
                if (deleteServer) {
                    form.append($('<input>').attr('type', 'hidden').attr('name', 'delete_server').val('1'));
                }
                
                $('body').append(form);
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
        transition: all 0.2s ease;
    }
    
    .server-links-container .server-link:hover {
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .server-links-container .server-link:focus {
        text-decoration: none;
        color: #fff;
    }
</style>
@endpush
