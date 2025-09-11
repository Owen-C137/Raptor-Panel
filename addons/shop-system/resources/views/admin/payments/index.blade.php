@extends('layouts.admin')

@section('title')
    Payments
@endsection

@section('content-header')
    <h1>Payments <small>Manage payment transactions</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
        <li class="active">Payments</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Payment Transactions</h3>
                <div class="box-tools">
                    <a href="{{ route('admin.shop.settings.payment-gateways') }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-cog"></i> Gateway Settings
                    </a>
                </div>
            </div>
            
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            <input type="text" id="searchPayments" class="form-control" placeholder="Search payments...">
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            <select id="filterStatus" class="form-control">
                                <option value="">All Status</option>
                                <option value="completed">Completed</option>
                                <option value="pending">Pending</option>
                                <option value="failed">Failed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="refunded">Refunded</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            <select id="filterGateway" class="form-control">
                                <option value="">All Gateways</option>
                                <option value="paypal">PayPal</option>
                                <option value="stripe">Stripe</option>
                                <option value="credits">Credits</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            <input type="date" id="filterDate" class="form-control" placeholder="Filter by date">
                        </div>
                    </div>
                </div>
                
                <table class="table table-bordered table-hover" id="paymentsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Gateway</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr data-status="{{ $payment->status }}" data-gateway="{{ $payment->gateway }}" 
                                data-date="{{ $payment->created_at->format('Y-m-d') }}">
                                <td>
                                    <strong>#{{ $payment->id }}</strong>
                                    @if($payment->transaction_id)
                                        <br><small class="text-muted">{{ $payment->transaction_id }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($payment->order)
                                        <a href="{{ route('admin.shop.orders.show', $payment->order_id) }}">
                                            Order #{{ $payment->order_id }}
                                        </a>
                                    @else
                                        <span class="text-muted">No Order</span>
                                    @endif
                                </td>
                                <td>
                                    @if($payment->user)
                                        <a href="{{ route('admin.users.view', $payment->user_id) }}">
                                            {{ $payment->user->username }}
                                        </a>
                                        <br><small class="text-muted">{{ $payment->user->email }}</small>
                                    @else
                                        <span class="text-muted">Unknown User</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>${{ number_format($payment->amount, 2) }}</strong>
                                    @if($payment->fee > 0)
                                        <br><small class="text-muted">Fee: ${{ number_format($payment->fee, 2) }}</small>
                                    @endif
                                </td>
                                <td>
                                    @switch($payment->gateway)
                                        @case('paypal')
                                            <span class="label" style="background-color: #0070ba; color: white;">PayPal</span>
                                            @break
                                        @case('stripe')
                                            <span class="label" style="background-color: #635bff; color: white;">Stripe</span>
                                            @break
                                        @case('credits')
                                            <span class="label label-info">Credits</span>
                                            @break
                                        @default
                                            <span class="label label-default">{{ ucfirst($payment->gateway) }}</span>
                                    @endswitch
                                </td>
                                <td>
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
                                        @case('refunded')
                                            <span class="label label-info">Refunded</span>
                                            @break
                                        @default
                                            <span class="label label-default">{{ ucfirst($payment->status) }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    {{ $payment->created_at->format('M d, Y') }}
                                    <br><small class="text-muted">{{ $payment->created_at->format('g:i A') }}</small>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.shop.payments.show', $payment->id) }}" 
                                           class="btn btn-xs btn-primary" title="View Details">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        
                                        @if($payment->status === 'completed' && !$payment->refunded_at)
                                            <button class="btn btn-xs btn-warning" 
                                                    onclick="refundPayment({{ $payment->id }})" title="Refund">
                                                <i class="fa fa-undo"></i>
                                            </button>
                                        @endif
                                        
                                        @if($payment->status === 'pending')
                                            <button class="btn btn-xs btn-success" 
                                                    onclick="completePayment({{ $payment->id }})" title="Complete">
                                                <i class="fa fa-check"></i>
                                            </button>
                                            <button class="btn btn-xs btn-danger" 
                                                    onclick="cancelPayment({{ $payment->id }})" title="Cancel">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">
                                    <p class="text-muted">No payments found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if(method_exists($payments, 'links'))
                <div class="box-footer">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Payment Statistics -->
<div class="row">
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-green">
            <div class="inner">
                <h3>${{ number_format($stats['total_revenue'] ?? 0, 0) }}</h3>
                <p>Total Revenue</p>
            </div>
            <div class="icon">
                <i class="fa fa-money"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-aqua">
            <div class="inner">
                <h3>{{ $stats['total_payments'] ?? $payments->count() }}</h3>
                <p>Total Payments</p>
            </div>
            <div class="icon">
                <i class="fa fa-credit-card"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-yellow">
            <div class="inner">
                <h3>{{ $stats['pending_payments'] ?? 0 }}</h3>
                <p>Pending Payments</p>
            </div>
            <div class="icon">
                <i class="fa fa-clock-o"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-red">
            <div class="inner">
                <h3>{{ $stats['failed_payments'] ?? 0 }}</h3>
                <p>Failed Payments</p>
            </div>
            <div class="icon">
                <i class="fa fa-exclamation-triangle"></i>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $(document).ready(function() {
            // Search functionality
            $('#searchPayments').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $("#paymentsTable tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
            
            // Status filter
            $('#filterStatus').on('change', function() {
                var status = $(this).val();
                if (status === '') {
                    $("#paymentsTable tbody tr").show();
                } else {
                    $("#paymentsTable tbody tr").each(function() {
                        var rowStatus = $(this).data('status');
                        if (rowStatus === status) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                }
            });
            
            // Gateway filter
            $('#filterGateway').on('change', function() {
                var gateway = $(this).val();
                if (gateway === '') {
                    $("#paymentsTable tbody tr").show();
                } else {
                    $("#paymentsTable tbody tr").each(function() {
                        var rowGateway = $(this).data('gateway');
                        if (rowGateway === gateway) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                }
            });
            
            // Date filter
            $('#filterDate').on('change', function() {
                var date = $(this).val();
                if (date === '') {
                    $("#paymentsTable tbody tr").show();
                } else {
                    $("#paymentsTable tbody tr").each(function() {
                        var rowDate = $(this).data('date');
                        if (rowDate === date) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                }
            });
        });
        
        function completePayment(paymentId) {
            if (confirm('Are you sure you want to mark this payment as completed?')) {
                updatePaymentStatus(paymentId, 'completed');
            }
        }
        
        function cancelPayment(paymentId) {
            if (confirm('Are you sure you want to cancel this payment?')) {
                updatePaymentStatus(paymentId, 'cancelled');
            }
        }
        
        function refundPayment(paymentId) {
            if (confirm('Are you sure you want to refund this payment? This action may not be reversible.')) {
                $.ajax({
                    url: '/admin/shop/payments/' + paymentId + '/refund',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred while processing the refund.');
                    }
                });
            }
        }
        
        function updatePaymentStatus(paymentId, status) {
            $.ajax({
                url: '{{ url('admin/shop/payments') }}/' + paymentId + '/update-status',
                type: 'POST',
                data: {
                    status: status,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while updating the payment status.');
                }
            });
        }
    </script>
@endsection
