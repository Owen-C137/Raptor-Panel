@extends('layouts.admin')

@section('title')
    Payment #{{ $payment->id }}
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Payment #{{ $payment->id }}
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          {{ $payment->gateway }}
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
            <a class="link-fx" href="{{ route('admin.shop.payments.index') }}">Payments</a>
          </li>
          <li class="breadcrumb-item" aria-current="page">
            #{{ $payment->id }}
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
                    <i class="fa fa-credit-card me-1"></i>Payment Details
                </h3>
            </div>
            
            <div class="block-content">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Payment ID</div>
                            <div class="fs-lg fw-semibold">#{{ $payment->id }}</div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Transaction ID</div>
                            <div class="fs-sm"><code>{{ $payment->gateway_transaction_id ?? 'N/A' }}</code></div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-sm-4">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Gateway</div>
                            <div>{{ ucfirst($payment->gateway) }}</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Status</div>
                            <div>
                                @switch($payment->status)
                                    @case('pending')
                                        <span class="badge bg-warning">Pending</span>
                                        @break
                                    @case('completed')
                                        <span class="badge bg-success">Completed</span>
                                        @break
                                    @case('failed')
                                        <span class="badge bg-danger">Failed</span>
                                        @break
                                    @case('cancelled')
                                        <span class="badge bg-secondary">Cancelled</span>
                                        @break
                                    @case('refunded')
                                        <span class="badge bg-info">Refunded</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">{{ ucfirst($payment->status) }}</span>
                                @endswitch
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Currency</div>
                            <div>{{ strtoupper($payment->currency) }}</div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-sm-6">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Amount</div>
                            <div class="fs-lg fw-bold text-success">{{ $currencySymbol }}{{ number_format($payment->amount, 2) }}</div>
                        </div>
                    </div>
                    @if($payment->fee_amount > 0)
                    <div class="col-sm-6">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Gateway Fee</div>
                            <div class="text-muted">{{ $currencySymbol }}{{ number_format($payment->fee_amount, 2) }}</div>
                        </div>
                    </div>
                    @endif
                </div>
                
                <div class="row">
                    <div class="col-sm-6">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Created</div>
                            <div>{{ $payment->created_at->format('M j, Y \a\t g:i A') }}</div>
                        </div>
                    </div>
                    @if($payment->completed_at)
                    <div class="col-sm-6">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Completed</div>
                            <div>{{ $payment->completed_at->format('M j, Y \a\t g:i A') }}</div>
                        </div>
                    </div>
                    @endif
                </div>
                
                @if($payment->gateway_metadata)
                <hr>
                <div class="p-3">
                    <div class="fs-sm fw-semibold text-uppercase text-muted mb-2">Gateway Information</div>
                    <pre class="language-json fs-sm"><code>{{ json_encode($payment->gateway_metadata, JSON_PRETTY_PRINT) }}</code></pre>
                </div>
                @endif
            </div>
        </div>
        
        @if($payment->order)
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-shopping-cart me-1"></i>Related Order
                </h3>
            </div>
            
            <div class="block-content">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Order ID</div>
                            <div>
                                <a href="{{ route('admin.shop.orders.show', $payment->order->id) }}" class="link-fx">
                                    #{{ $payment->order->id }}
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Customer</div>
                            <div>
                                @if($payment->order->user)
                                    <a href="{{ route('admin.users.view', $payment->order->user->id) }}" class="link-fx">
                                        {{ $payment->order->user->username }}
                                    </a>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-sm-6">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Plan</div>
                            <div>
                                @if($payment->order->plan)
                                    {{ $payment->order->plan->name }}
                                    @if($payment->order->plan->category)
                                        <br><small class="text-muted">({{ $payment->order->plan->category->name }})</small>
                                    @endif
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Order Status</div>
                            <div>
                                @switch($payment->order->status)
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
                                        <span class="badge bg-secondary">{{ ucfirst($payment->order->status) }}</span>
                                @endswitch
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-sm-6">
                        <div class="p-3">
                            <div class="fs-sm fw-semibold text-uppercase text-muted mb-1">Order Amount</div>
                            <div class="fs-lg fw-semibold">{{ $currencySymbol }}{{ number_format($payment->order->amount, 2) }}</div>
                        </div>
                    </div>
                </div>
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
                @if($payment->status === 'completed' && !$payment->refunded_at)
                    <form action="{{ route('admin.shop.payments.refund', $payment->id) }}" method="POST" class="form-refund">
                        @csrf
                        <div class="mb-3">
                            <label for="refund_amount" class="form-label">Refund Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" 
                                       name="refund_amount" 
                                       id="refund_amount" 
                                       class="form-control" 
                                       step="0.01" 
                                       max="{{ $payment->amount }}" 
                                       value="{{ $payment->amount }}"
                                       required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="refund_reason" class="form-label">Reason (Optional)</label>
                            <textarea name="refund_reason" 
                                      id="refund_reason" 
                                      class="form-control" 
                                      rows="3" 
                                      placeholder="Reason for refund..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-warning">
                            <i class="fa fa-undo me-1"></i> Process Refund
                        </button>
                    </form>
                @elseif($payment->refunded_at)
                    <div class="alert alert-info d-flex">
                        <div class="flex-shrink-0">
                            <i class="fa fa-info-circle"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            This payment was refunded on {{ $payment->refunded_at->format('M j, Y') }}.
                        </div>
                    </div>
                @else
                    <div class="alert alert-secondary d-flex">
                        <div class="flex-shrink-0">
                            <i class="fa fa-info-circle"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            No actions available for {{ $payment->status }} payments.
                        </div>
                    </div>
                @endif
                
                <hr>
                
                <a href="{{ route('admin.shop.payments.index') }}" class="btn btn-outline-secondary w-100">
                    <i class="fa fa-arrow-left me-1"></i> Back to Payments
                </a>
            </div>
        </div>
        
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-history me-1"></i>Payment Timeline
                </h3>
            </div>
            
            <div class="block-content">
                <div class="timeline timeline-alt">
                    <div class="timeline-item">
                        <div class="timeline-point timeline-point-success">
                            <i class="fa fa-plus"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="fw-semibold">Payment Created</div>
                            <small class="text-muted">{{ $payment->created_at->format('M j, Y \a\t g:i A') }}</small>
                        </div>
                    </div>
                    
                    @if($payment->completed_at)
                    <div class="timeline-item">
                        <div class="timeline-point timeline-point-success">
                            <i class="fa fa-check"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="fw-semibold">Payment Completed</div>
                            <small class="text-muted">{{ $payment->completed_at->format('M j, Y \a\t g:i A') }}</small>
                        </div>
                    </div>
                    @endif
                    
                    @if($payment->refunded_at)
                    <div class="timeline-item">
                        <div class="timeline-point timeline-point-warning">
                            <i class="fa fa-undo"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="fw-semibold">Payment Refunded</div>
                            <small class="text-muted">{{ $payment->refunded_at->format('M j, Y \a\t g:i A') }}</small>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($payment->status === 'completed' && !$payment->refunded_at)
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.form-refund').addEventListener('submit', function(e) {
        const amount = document.getElementById('refund_amount').value;
        if (!confirm('Are you sure you want to refund $' + amount + '? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
});
</script>
@endif
@endsection
