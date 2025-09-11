@extends('shop::layout')

@section('shop-title', 'Order #' . $order->id)

@section('shop-content')
<div class="row">
    <div class="col-12">
        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('shop.index') }}">Shop</a></li>
                <li class="breadcrumb-item"><a href="{{ route('shop.orders.index') }}">Orders</a></li>
                <li class="breadcrumb-item active">#{{ $order->id }}</li>
            </ol>
        </nav>
        
        {{-- Order Details --}}
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-shopping-bag"></i>
                        Order #{{ $order->id }}
                    </h3>
                    <span class="badge {{ $order->status_class }}">
                        {{ $order->display_status }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Order Information</h5>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Order Date:</strong></td>
                                <td>{{ $order->created_at->format('M j, Y g:i A') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge {{ $order->status_class }}">
                                        {{ $order->display_status }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Total:</strong></td>
                                <td><strong>${{ number_format($order->total_amount, 2) }}</strong></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Payment Information</h5>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Payment Method:</strong></td>
                                <td>{{ $order->payments->isNotEmpty() ? ucfirst($order->payments->first()->gateway) : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Payment Status:</strong></td>
                                <td>{{ $order->isActive() ? 'Paid' : 'Pending' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                {{-- Order Items --}}
                <h5 class="mt-4">Order Items</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong>{{ $order->plan->name }}</strong>
                                    @if($order->plan->description)
                                        <br><small class="text-muted">{{ $order->plan->description }}</small>
                                    @endif
                                    <br><small class="text-muted">Billing: {{ ucfirst(str_replace('_', ' ', $order->billing_cycle)) }}</small>
                                </td>
                                <td>1</td>
                                <td>${{ number_format($order->amount, 2) }}</td>
                                <td>${{ number_format($order->amount, 2) }}</td>
                            </tr>
                            @if($order->setup_fee > 0)
                            <tr>
                                <td>
                                    <strong>Setup Fee</strong>
                                    <br><small class="text-muted">One-time setup charge</small>
                                </td>
                                <td>1</td>
                                <td>${{ number_format($order->setup_fee, 2) }}</td>
                                <td>${{ number_format($order->setup_fee, 2) }}</td>
                            </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total:</th>
                                <th>${{ number_format($order->total_amount, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                @if($order->hasBillingDetails())
                    <hr>
                    <h5>Billing Information</h5>
                    <div class="row">
                        <div class="col-md-6">
                            @if($order->getCustomerName())
                                <p><strong>Customer:</strong> {{ $order->getCustomerName() }}</p>
                            @endif
                            
                            @if($order->getCustomerEmail())
                                <p><strong>Email:</strong> {{ $order->getCustomerEmail() }}</p>
                            @endif
                            
                            @if(!empty($order->billing_details['company']))
                                <p><strong>Company:</strong> {{ $order->billing_details['company'] }}</p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            @if($order->getBillingAddress())
                                <p><strong>Billing Address:</strong></p>
                                <address style="white-space: pre-line;">{{ $order->getBillingAddress() }}</address>
                            @endif
                            
                            @if($order->payment_method)
                                <p><strong>Payment Method:</strong> {{ ucfirst($order->payment_method) }}</p>
                            @endif
                        </div>
                    </div>
                @endif
                
                {{-- Server Variable Input Section --}}
                @if($order->status === 'processing' && $order->requiresVariableInput())
                    <hr>
                    <div class="alert alert-info">
                        <h5><i class="fas fa-cogs"></i> Server Configuration Required</h5>
                        <p class="mb-0">Your order requires some additional configuration before we can create your server. Please provide the required information below:</p>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Server Variables</h5>
                        </div>
                        <div class="card-body">
                            <form id="server-variables-form" method="POST" action="{{ route('shop.orders.create-server', $order) }}">
                                @csrf
                                
                                @foreach($order->getRequiredVariables() as $variable)
                                    <div class="mb-3">
                                        <label for="var_{{ $variable['env_variable'] }}" class="form-label">
                                            <strong>{{ $variable['user_friendly_name'] }}</strong>
                                            @if($variable['type'] === 'steam_token')
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>
                                        
                                        @if($variable['type'] === 'password')
                                            <input type="password" 
                                                   class="form-control" 
                                                   name="variables[{{ $variable['env_variable'] }}]" 
                                                   id="var_{{ $variable['env_variable'] }}"
                                                   placeholder="Enter {{ strtolower($variable['user_friendly_name']) }}"
                                                   {{ $variable['type'] === 'steam_token' ? 'required' : '' }}>
                                        @elseif($variable['type'] === 'boolean')
                                            <select class="form-control" 
                                                    name="variables[{{ $variable['env_variable'] }}]" 
                                                    id="var_{{ $variable['env_variable'] }}">
                                                <option value="1">Yes</option>
                                                <option value="0">No</option>
                                            </select>
                                        @elseif($variable['type'] === 'number')
                                            <input type="number" 
                                                   class="form-control" 
                                                   name="variables[{{ $variable['env_variable'] }}]" 
                                                   id="var_{{ $variable['env_variable'] }}"
                                                   placeholder="Enter {{ strtolower($variable['user_friendly_name']) }}"
                                                   {{ $variable['type'] === 'steam_token' ? 'required' : '' }}>
                                        @else
                                            <input type="text" 
                                                   class="form-control" 
                                                   name="variables[{{ $variable['env_variable'] }}]" 
                                                   id="var_{{ $variable['env_variable'] }}"
                                                   placeholder="Enter {{ strtolower($variable['user_friendly_name']) }}"
                                                   {{ $variable['type'] === 'steam_token' ? 'required' : '' }}>
                                        @endif
                                        
                                        @if($variable['help_text'])
                                            <div class="form-text">{{ $variable['help_text'] }}</div>
                                        @endif
                                    </div>
                                @endforeach
                                
                                <div class="text-end">
                                    <button type="submit" class="btn btn-success" id="create-server-btn">
                                        <i class="fas fa-server"></i>
                                        Create Server
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
                
                {{-- Server Information (if server exists) --}}
                @if($order->server_id)
                    <hr>
                    <div class="alert alert-success">
                        <h5><i class="fas fa-server"></i> Server Created</h5>
                        <p class="mb-0">Your server has been created successfully!</p>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Server Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Server ID:</strong> {{ $order->server_id }}</p>
                                    <p><strong>Status:</strong> 
                                        <span class="badge badge-info">{{ $order->server->status ?? 'Installing' }}</span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    @if($order->server)
                                        <p><strong>Name:</strong> {{ $order->server->name }}</p>
                                        @if($order->server->allocation)
                                            <p><strong>Connection:</strong> {{ $order->server->allocation->ip }}:{{ $order->server->allocation->port }}</p>
                                        @endif
                                    @endif
                                </div>
                            </div>
                            
                            @if($order->server)
                                <div class="text-end">
                                    <a href="{{ route('server.index', $order->server->uuidShort) }}" class="btn btn-primary">
                                        <i class="fas fa-external-link-alt"></i>
                                        Manage Server
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
                
                <div class="text-end mt-3">
                    <a href="{{ route('shop.orders.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Orders
                    </a>
                    @if(isset($paymentMethods) && count($paymentMethods) > 0 && $order->status === 'pending')
                        <button class="btn btn-success">
                            <i class="fas fa-credit-card"></i>
                            Pay Now
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
