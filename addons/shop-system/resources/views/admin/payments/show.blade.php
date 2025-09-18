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
    <div class="col-md-8">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Payment Details</h3>
            </div>
            
            <div class="box-body">
                <dl class="dl-horizontal">
                    <dt>Payment ID:</dt>
                    <dd>#{{ $payment->id }}</dd>
                    
                    <dt>Transaction ID:</dt>
                    <dd><code>{{ $payment->gateway_transaction_id ?? 'N/A' }}</code></dd>
                    
                    <dt>Gateway:</dt>
                    <dd>{{ ucfirst($payment->gateway) }}</dd>
                    
                    <dt>Status:</dt>
                    <dd>
                        @switch($payment->status)
                            @case('pending')
                                <span class="label label-warning">Pending</span>
                                @break
                            @case('completed')
                                <span class="label label-success">Completed</span>
                                @break
                            @case('failed')
                                <span class="label label-danger">Failed</span>
                                @break
                            @case('cancelled')
                                <span class="label label-default">Cancelled</span>
                                @break
                            @case('refunded')
                                <span class="label label-info">Refunded</span>
                                @break
                            @default
                                <span class="label label-default">{{ ucfirst($payment->status) }}</span>
                        @endswitch
                    </dd>
                    
                    <dt>Amount:</dt>
                    <dd>{{ $currencySymbol }}{{ number_format($payment->amount, 2) }}</dd>
                    
                    @if($payment->fee_amount > 0)
                    <dt>Gateway Fee:</dt>
                    <dd>{{ $currencySymbol }}{{ number_format($payment->fee_amount, 2) }}</dd>
                    @endif
                    
                    <dt>Currency:</dt>
                    <dd>{{ strtoupper($payment->currency) }}</dd>
                    
                    <dt>Created:</dt>
                    <dd>{{ $payment->created_at->format('M j, Y \a\t g:i A') }}</dd>
                    
                    @if($payment->completed_at)
                    <dt>Completed:</dt>
                    <dd>{{ $payment->completed_at->format('M j, Y \a\t g:i A') }}</dd>
                    @endif
                </dl>
                
                @if($payment->gateway_metadata)
                <hr>
                <h4>Gateway Information</h4>
                <pre class="small">{{ json_encode($payment->gateway_metadata, JSON_PRETTY_PRINT) }}</pre>
                @endif
            </div>
        </div>
        
        @if($payment->order)
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Related Order</h3>
            </div>
            
            <div class="box-body">
                <dl class="dl-horizontal">
                    <dt>Order ID:</dt>
                    <dd>
                        <a href="{{ route('admin.shop.orders.show', $payment->order->id) }}">
                            #{{ $payment->order->id }}
                        </a>
                    </dd>
                    
                    <dt>Customer:</dt>
                    <dd>
                        @if($payment->order->user)
                            <a href="{{ route('admin.users.view', $payment->order->user->id) }}">
                                {{ $payment->order->user->username }}
                            </a>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </dd>
                    
                    <dt>Plan:</dt>
                    <dd>
                        @if($payment->order->plan)
                            {{ $payment->order->plan->name }}
                            @if($payment->order->plan->category)
                                <small class="text-muted">({{ $payment->order->plan->category->name }})</small>
                            @endif
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </dd>
                    
                    <dt>Order Status:</dt>
                    <dd>
                        @switch($payment->order->status)
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
                                <span class="label label-default">{{ ucfirst($payment->order->status) }}</span>
                        @endswitch
                    </dd>
                    
                    <dt>Order Amount:</dt>
                    <dd>{{ $currencySymbol }}{{ number_format($payment->order->amount, 2) }}</dd>
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
                @if($payment->status === 'completed' && !$payment->refunded_at)
                    <form action="{{ route('admin.shop.payments.refund', $payment->id) }}" method="POST" class="form-refund">
                        @csrf
                        <div class="form-group">
                            <label for="refund_amount">Refund Amount</label>
                            <div class="input-group">
                                <span class="input-group-addon">$</span>
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
                        
                        <div class="form-group">
                            <label for="refund_reason">Reason (Optional)</label>
                            <textarea name="refund_reason" 
                                      id="refund_reason" 
                                      class="form-control" 
                                      rows="3" 
                                      placeholder="Reason for refund..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-warning btn-sm">
                            <i class="fa fa-undo"></i> Process Refund
                        </button>
                    </form>
                @elseif($payment->refunded_at)
                    <p class="text-info">
                        <i class="fa fa-info-circle"></i>
                        This payment was refunded on {{ $payment->refunded_at->format('M j, Y') }}.
                    </p>
                @else
                    <p class="text-muted">
                        <i class="fa fa-info-circle"></i>
                        No actions available for {{ $payment->status }} payments.
                    </p>
                @endif
                
                <hr>
                
                <a href="{{ route('admin.shop.payments.index') }}" class="btn btn-default btn-sm">
                    <i class="fa fa-arrow-left"></i> Back to Payments
                </a>
            </div>
        </div>
        
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Payment Timeline</h3>
            </div>
            
            <div class="box-body">
                <ul class="list-unstyled timeline">
                    <li>
                        <i class="fa fa-plus text-green"></i>
                        <strong>Payment Created</strong><br>
                        <small class="text-muted">{{ $payment->created_at->format('M j, Y \a\t g:i A') }}</small>
                    </li>
                    
                    @if($payment->completed_at)
                    <li>
                        <i class="fa fa-check text-green"></i>
                        <strong>Payment Completed</strong><br>
                        <small class="text-muted">{{ $payment->completed_at->format('M j, Y \a\t g:i A') }}</small>
                    </li>
                    @endif
                    
                    @if($payment->refunded_at)
                    <li>
                        <i class="fa fa-undo text-yellow"></i>
                        <strong>Payment Refunded</strong><br>
                        <small class="text-muted">{{ $payment->refunded_at->format('M j, Y \a\t g:i A') }}</small>
                    </li>
                    @endif
                </ul>
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
