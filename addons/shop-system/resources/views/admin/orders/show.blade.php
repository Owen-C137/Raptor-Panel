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
    <div class="col-lg-8">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-info-circle me-1"></i>Order Details
                </h3>
            </div>
            
            <div class="block-content">
                <div class="row">
                    <div class="col-sm-4">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Order ID</div>
                            <div class="fs-lg fw-semibold">#{{ $order->id }}</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">UUID</div>
                            <div class="fs-sm"><code>{{ $order->uuid }}</code></div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Status</div>
                            <div>
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
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-sm-6">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Customer</div>
                            <div>
                                <a href="{{ route('admin.users.view', $order->user_id) }}" class="link-fx">
                                    {{ $order->user->username }}
                                </a>
                                <br><small class="text-muted">{{ $order->user->email }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Plan</div>
                            <div>
                                {{ $order->plan->name ?? 'N/A' }}
                                @if($order->plan && $order->plan->category)
                                    <br><small class="text-muted">Category: {{ $order->plan->category->name }}</small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-sm-4">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Amount</div>
                            <div>
                                <strong class="fs-lg">{{ $currencySymbol }}{{ number_format($order->amount, 2) }}</strong>
                                @if($order->setup_fee > 0)
                                    <br><small class="text-muted">Setup Fee: {{ $currencySymbol }}{{ number_format($order->setup_fee, 2) }}</small>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Currency</div>
                            <div>{{ strtoupper($order->currency) }}</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Billing Cycle</div>
                            <div>{{ ucfirst(str_replace('_', ' ', $order->billing_cycle)) }}</div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-sm-6">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Created</div>
                            <div>{{ $order->created_at->format('F d, Y \a\t g:i A') }}</div>
                        </div>
                    </div>
                    @if($order->next_due_at)
                        <div class="col-sm-6">
                            <div class="p-3">
                                <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Next Due</div>
                                <div>
                                    {{ $order->next_due_at->format('F d, Y') }}
                                    @if($order->next_due_at->isPast())
                                        <span class="text-danger fw-semibold">(Overdue)</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                
                @if($order->last_renewed_at || $order->expires_at || $order->server_id)
                <div class="row">
                    @if($order->last_renewed_at)
                        <div class="col-sm-4">
                            <div class="p-3">
                                <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Last Renewed</div>
                                <div>{{ $order->last_renewed_at->format('F d, Y \a\t g:i A') }}</div>
                            </div>
                        </div>
                    @endif
                    
                    @if($order->expires_at)
                        <div class="col-sm-4">
                            <div class="p-3">
                                <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Expires</div>
                                <div>{{ $order->expires_at->format('F d, Y \a\t g:i A') }}</div>
                            </div>
                        </div>
                    @endif
                    
                    @if($order->server_id)
                        <div class="col-sm-4">
                            <div class="p-3">
                                <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Server</div>
                                <div>
                                    <a href="{{ route('admin.servers.view', $order->server_id) }}" class="link-fx">
                                        Server #{{ $order->server_id }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
        
        @if($order->server_config)
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-server me-1"></i>Server Configuration
                    </h3>
                </div>
                <div class="block-content">
                    <pre class="language-json"><code>{{ json_encode($order->server_config, JSON_PRETTY_PRINT) }}</code></pre>
                </div>
            </div>
        @endif

        @if($order->hasBillingDetails())
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-credit-card me-1"></i>Billing Information
                    </h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        @if($order->getCustomerName())
                            <div class="col-sm-6">
                                <div class="p-3">
                                    <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Customer Name</div>
                                    <div>{{ $order->getCustomerName() }}</div>
                                </div>
                            </div>
                        @endif
                        
                        @if($order->getCustomerEmail())
                            <div class="col-sm-6">
                                <div class="p-3">
                                    <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Email</div>
                                    <div>{{ $order->getCustomerEmail() }}</div>
                                </div>
                            </div>
                        @endif
                        
                        @if(!empty($order->billing_details['company']))
                            <div class="col-sm-6">
                                <div class="p-3">
                                    <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Company</div>
                                    <div>{{ $order->billing_details['company'] }}</div>
                                </div>
                            </div>
                        @endif
                        
                        @if($order->payment_method)
                            <div class="col-sm-6">
                                <div class="p-3">
                                    <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Payment Method</div>
                                    <div>{{ ucfirst($order->payment_method) }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                        
                    @if($order->getBillingAddress())
                        <div class="row">
                            <div class="col-12">
                                <div class="p-3">
                                    <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Billing Address</div>
                                    <address class="mb-0" style="white-space: pre-line;">{{ $order->getBillingAddress() }}</address>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
    
    <div class="col-lg-4">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-cogs me-1"></i>Actions
                </h3>
            </div>
            
            <div class="block-content">
                @if($order->status === 'pending')
                    <button class="btn btn-success w-100 mb-2" onclick="updateOrderStatus('processing')">
                        <i class="fa fa-play me-1"></i> Start Processing
                    </button>
                @endif
                
                @if($order->status === 'processing')
                    <button class="btn btn-success w-100 mb-2" onclick="updateOrderStatus('active')">
                        <i class="fa fa-check me-1"></i> Activate Order
                    </button>
                @endif
                
                @if($order->status === 'active')
                    <button class="btn btn-warning w-100 mb-2" onclick="updateOrderStatus('suspended')">
                        <i class="fa fa-pause me-1"></i> Suspend Order
                    </button>
                @endif
                
                @if($order->status === 'suspended')
                    <button class="btn btn-success w-100 mb-2" onclick="updateOrderStatus('active')">
                        <i class="fa fa-play me-1"></i> Unsuspend Order
                    </button>
                @endif
                
                @if(in_array($order->status, ['active', 'suspended']))
                    <button class="btn btn-danger w-100 mb-2" onclick="updateOrderStatus('terminated')">
                        <i class="fa fa-stop me-1"></i> Terminate Order
                    </button>
                @endif
                
                <hr>
                
                <a href="{{ route('admin.shop.orders.index') }}" class="btn btn-outline-secondary w-100">
                    <i class="fa fa-arrow-left me-1"></i> Back to Orders
                </a>
            </div>
        </div>
        
        @if($order->payments && $order->payments->count() > 0)
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-history me-1"></i>Payment History
                    </h3>
                </div>
                
                <div class="block-content">
                    @foreach($order->payments->take(5) as $payment)
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <div class="fs-sm fw-semibold">
                                    {{ $currencySymbol }}{{ number_format($payment->amount, 2) }}
                                    <small class="text-muted ms-2">{{ $payment->created_at->format('M d, Y') }}</small>
                                </div>
                                <div class="mt-1">
                                    @switch($payment->status)
                                        @case('completed')
                                            <span class="badge bg-success">Completed</span>
                                            @break
                                        @case('pending')
                                            <span class="badge bg-warning">Pending</span>
                                            @break
                                        @case('failed')
                                            <span class="badge bg-danger">Failed</span>
                                            @break
                                        @case('cancelled')
                                            <span class="badge bg-secondary">Cancelled</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ ucfirst($payment->status) }}</span>
                                    @endswitch
                                </div>
                            </div>
                        </div>
                        @if(!$loop->last)<hr class="my-3">@endif
                    @endforeach
                    
                    @if($order->payments->count() > 5)
                        <div class="text-center mt-3">
                            <a href="{{ route('admin.shop.payments.index', ['order' => $order->id]) }}" class="btn btn-sm btn-outline-primary">
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
                fetch('{{ route('admin.shop.orders.update-status', $order->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        status: newStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error updating status: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the order status.');
                });
            }
        }
    </script>
@endsection
