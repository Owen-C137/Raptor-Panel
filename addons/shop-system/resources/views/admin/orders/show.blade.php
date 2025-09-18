@extends('layouts.admin')

@section('title')
    Order #{{ $order->id }}
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Order #{{ $order->id }}
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          {{ $order->user->username }}
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
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.shop.orders.index') }}">Orders</a>
          </li>
          <li class="breadcrumb-item" aria-current="page">
            #{{ $order->id }}
          </li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Order Details</h3>
            </div>
            
            <div class="box-body">
                <dl class="dl-horizontal">
                    <dt>Order ID:</dt>
                    <dd>#{{ $order->id }}</dd>
                    
                    <dt>UUID:</dt>
                    <dd><code>{{ $order->uuid }}</code></dd>
                    
                    <dt>Status:</dt>
                    <dd>
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
                    </dd>
                    
                    <dt>Customer:</dt>
                    <dd>
                        <a href="{{ route('admin.users.view', $order->user_id) }}">
                            {{ $order->user->username }} ({{ $order->user->email }})
                        </a>
                    </dd>
                    
                    <dt>Plan:</dt>
                    <dd>
                        {{ $order->plan->name ?? 'N/A' }}
                        @if($order->plan && $order->plan->category)
                            <br><small class="text-muted">Category: {{ $order->plan->category->name }}</small>
                        @endif
                    </dd>
                    
                    <dt>Amount:</dt>
                    <dd>
                        <strong>{{ $currencySymbol }}{{ number_format($order->amount, 2) }}</strong>
                        @if($order->setup_fee > 0)
                            <br><small>Setup Fee: {{ $currencySymbol }}{{ number_format($order->setup_fee, 2) }}</small>
                        @endif
                    </dd>
                    
                    <dt>Currency:</dt>
                    <dd>{{ strtoupper($order->currency) }}</dd>
                    
                    <dt>Billing Cycle:</dt>
                    <dd>{{ ucfirst(str_replace('_', ' ', $order->billing_cycle)) }}</dd>
                    
                    <dt>Created:</dt>
                    <dd>{{ $order->created_at->format('F d, Y \a\t g:i A') }}</dd>
                    
                    @if($order->next_due_at)
                        <dt>Next Due:</dt>
                        <dd>
                            {{ $order->next_due_at->format('F d, Y') }}
                            @if($order->next_due_at->isPast())
                                <span class="text-danger">(Overdue)</span>
                            @endif
                        </dd>
                    @endif
                    
                    @if($order->last_renewed_at)
                        <dt>Last Renewed:</dt>
                        <dd>{{ $order->last_renewed_at->format('F d, Y \a\t g:i A') }}</dd>
                    @endif
                    
                    @if($order->expires_at)
                        <dt>Expires:</dt>
                        <dd>{{ $order->expires_at->format('F d, Y \a\t g:i A') }}</dd>
                    @endif
                    
                    @if($order->server_id)
                        <dt>Server:</dt>
                        <dd>
                            <a href="{{ route('admin.servers.view', $order->server_id) }}">
                                Server #{{ $order->server_id }}
                            </a>
                        </dd>
                    @endif
                </dl>
            </div>
        </div>
        
        @if($order->server_config)
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Server Configuration</h3>
                </div>
                <div class="box-body">
                    <pre><code>{{ json_encode($order->server_config, JSON_PRETTY_PRINT) }}</code></pre>
                </div>
            </div>
        @endif

        @if($order->hasBillingDetails())
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Billing Information</h3>
                </div>
                <div class="box-body">
                    <dl class="dl-horizontal">
                        @if($order->getCustomerName())
                            <dt>Customer Name:</dt>
                            <dd>{{ $order->getCustomerName() }}</dd>
                        @endif
                        
                        @if($order->getCustomerEmail())
                            <dt>Email:</dt>
                            <dd>{{ $order->getCustomerEmail() }}</dd>
                        @endif
                        
                        @if(!empty($order->billing_details['company']))
                            <dt>Company:</dt>
                            <dd>{{ $order->billing_details['company'] }}</dd>
                        @endif
                        
                        @if($order->getBillingAddress())
                            <dt>Billing Address:</dt>
                            <dd>
                                <address style="white-space: pre-line;">{{ $order->getBillingAddress() }}</address>
                            </dd>
                        @endif
                        
                        @if($order->payment_method)
                            <dt>Payment Method:</dt>
                            <dd>{{ ucfirst($order->payment_method) }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        @endif
    </div>
    
    <div class="col-md-4">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Actions</h3>
            </div>
            
            <div class="box-body">
                @if($order->status === 'pending')
                    <button class="btn btn-success btn-block" onclick="updateOrderStatus('processing')">
                        <i class="fa fa-play"></i> Start Processing
                    </button>
                @endif
                
                @if($order->status === 'processing')
                    <button class="btn btn-success btn-block" onclick="updateOrderStatus('active')">
                        <i class="fa fa-check"></i> Activate Order
                    </button>
                @endif
                
                @if($order->status === 'active')
                    <button class="btn btn-warning btn-block" onclick="updateOrderStatus('suspended')">
                        <i class="fa fa-pause"></i> Suspend Order
                    </button>
                @endif
                
                @if($order->status === 'suspended')
                    <button class="btn btn-success btn-block" onclick="updateOrderStatus('active')">
                        <i class="fa fa-play"></i> Unsuspend Order
                    </button>
                @endif
                
                @if(in_array($order->status, ['active', 'suspended']))
                    <button class="btn btn-danger btn-block" onclick="updateOrderStatus('terminated')">
                        <i class="fa fa-stop"></i> Terminate Order
                    </button>
                @endif
                
                <hr>
                
                <a href="{{ route('admin.shop.orders.index') }}" class="btn btn-default btn-block">
                    <i class="fa fa-arrow-left"></i> Back to Orders
                </a>
            </div>
        </div>
        
        @if($order->payments && $order->payments->count() > 0)
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Payment History</h3>
                </div>
                
                <div class="box-body">
                    @foreach($order->payments->take(5) as $payment)
                        <div class="media">
                            <div class="media-body">
                                <h5 class="media-heading">
                                    {{ $currencySymbol }}{{ number_format($payment->amount, 2) }}
                                    <small class="text-muted">{{ $payment->created_at->format('M d, Y') }}</small>
                                </h5>
                                <p class="margin-bottom-5">
                                    @switch($payment->status)
                                        @case('completed')
                                            <span class="label label-success">Completed</span>
                                            @break
                                        @case('pending')
                                            <span class="label label-warning">Pending</span>
                                            @break
                                        @case('failed')
                                            <span class="label label-danger">Failed</span>
                                            @break
                                        @case('cancelled')
                                            <span class="label label-default">Cancelled</span>
                                            @break
                                        @default
                                            <span class="label label-default">{{ ucfirst($payment->status) }}</span>
                                    @endswitch
                                </p>
                            </div>
                        </div>
                        @if(!$loop->last)<hr>@endif
                    @endforeach
                    
                    @if($order->payments->count() > 5)
                        <div class="text-center">
                            <a href="{{ route('admin.shop.payments.index', ['order' => $order->id]) }}" class="btn btn-sm btn-default">
                                View All Payments
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        function updateOrderStatus(newStatus) {
            if (confirm('Are you sure you want to change the order status to ' + newStatus + '?')) {
                $.ajax({
                    url: '{{ route('admin.shop.orders.update-status', $order->id) }}',
                    type: 'POST',
                    data: {
                        status: newStatus,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error updating status: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred while updating the order status.');
                    }
                });
            }
        }
    </script>
@endsection
