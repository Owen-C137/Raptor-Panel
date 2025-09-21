@extends('layouts.admin')

@section('title')
    Payments
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Payments
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Manage payment transactions
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
          <li class="breadcrumb-item" aria-current="page">Payments</li>
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
                    <i class="fa fa-credit-card me-1"></i>Payment Transactions
                </h3>
                <div class="block-options">
                    <a href="{{ route('admin.shop.settings.payment-gateways') }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-cog me-1"></i> Gateway Settings
                    </a>
                </div>
            </div>
            
            <div class="block-content">
                <div class="row">
                    <div class="col-sm-3">
                        <div class="mb-3">
                            <input type="text" id="searchPayments" class="form-control" placeholder="Search payments...">
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="mb-3">
                            <select id="filterStatus" class="form-select">
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
                        <div class="mb-3">
                            <select id="filterGateway" class="form-select">
                                <option value="">All Gateways</option>
                                <option value="paypal">PayPal</option>
                                <option value="stripe">Stripe</option>
                                <option value="credits">Credits</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="mb-3">
                            <input type="date" id="filterDate" class="form-control" placeholder="Filter by date">
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover table-vcenter" id="paymentsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Gateway</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th class="text-center">Actions</th>
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
                                    <strong>{{ $currencySymbol }}{{ number_format($payment->amount, 2) }}</strong>
                                    @if($payment->fee > 0)
                                        <br><small class="text-muted">Fee: {{ $currencySymbol }}{{ number_format($payment->fee, 2) }}</small>
                                    @endif
                                </td>
                                <td>
                                    @switch($payment->gateway)
                                        @case('paypal')
                                            <span class="badge" style="background-color: #0070ba; color: white;">PayPal</span>
                                            @break
                                        @case('stripe')
                                            <span class="badge" style="background-color: #635bff; color: white;">Stripe</span>
                                            @break
                                        @case('credits')
                                            <span class="badge bg-info">Credits</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ ucfirst($payment->gateway) }}</span>
                                    @endswitch
                                </td>
                                <td>
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
                                        @case('refunded')
                                            <span class="badge bg-info">Refunded</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ ucfirst($payment->status) }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    {{ $payment->created_at->format('M d, Y') }}
                                    <br><small class="text-muted">{{ $payment->created_at->format('g:i A') }}</small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.shop.payments.show', $payment->id) }}" 
                                           class="btn btn-sm btn-primary" title="View Details">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        
                                        @if($payment->status === 'completed' && !$payment->refunded_at)
                                            <button class="btn btn-sm btn-warning" 
                                                    onclick="refundPayment({{ $payment->id }})" title="Refund">
                                                <i class="fa fa-undo"></i>
                                            </button>
                                        @endif
                                        
                                        @if($payment->status === 'pending')
                                            <button class="btn btn-sm btn-success" 
                                                    onclick="completePayment({{ $payment->id }})" title="Complete">
                                                <i class="fa fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
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
                                    <div class="py-4">
                                        <i class="fa fa-credit-card fa-2x text-muted mb-3"></i>
                                        <h4 class="text-muted">No payments found</h4>
                                        <p class="text-muted">No payments match your current criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if(method_exists($payments, 'links'))
                <div class="block-content block-content-full">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Payment Statistics -->
<div class="row">
    <div class="col-6 col-lg-3">
        <div class="block block-rounded text-center">
            <div class="block-content">
                <div class="fs-2 fw-bold text-success">{{ $currencySymbol }}{{ number_format($stats['total_revenue'] ?? 0, 0) }}</div>
                <div class="fs-sm fw-semibold text-uppercase text-muted">Total Revenue</div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3">
        <div class="block block-rounded text-center">
            <div class="block-content">
                <div class="fs-2 fw-bold text-primary">{{ $stats['total_payments'] ?? $payments->count() }}</div>
                <div class="fs-sm fw-semibold text-uppercase text-muted">Total Payments</div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3">
        <div class="block block-rounded text-center">
            <div class="block-content">
                <div class="fs-2 fw-bold text-warning">{{ $stats['pending_payments'] ?? 0 }}</div>
                <div class="fs-sm fw-semibold text-uppercase text-muted">Pending Payments</div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-lg-3">
        <div class="block block-rounded text-center">
            <div class="block-content">
                <div class="fs-2 fw-bold text-danger">{{ $stats['failed_payments'] ?? 0 }}</div>
                <div class="fs-sm fw-semibold text-uppercase text-muted">Failed Payments</div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Search functionality
            document.getElementById('searchPayments').addEventListener('keyup', function() {
                const value = this.value.toLowerCase();
                const rows = document.querySelectorAll('#paymentsTable tbody tr');
                rows.forEach(function(row) {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.indexOf(value) > -1 ? '' : 'none';
                });
            });
            
            // Status filter
            document.getElementById('filterStatus').addEventListener('change', function() {
                const status = this.value;
                const rows = document.querySelectorAll('#paymentsTable tbody tr');
                rows.forEach(function(row) {
                    if (status === '') {
                        row.style.display = '';
                    } else {
                        const rowStatus = row.dataset.status;
                        row.style.display = rowStatus === status ? '' : 'none';
                    }
                });
            });
            
            // Gateway filter
            document.getElementById('filterGateway').addEventListener('change', function() {
                const gateway = this.value;
                const rows = document.querySelectorAll('#paymentsTable tbody tr');
                rows.forEach(function(row) {
                    if (gateway === '') {
                        row.style.display = '';
                    } else {
                        const rowGateway = row.dataset.gateway;
                        row.style.display = rowGateway === gateway ? '' : 'none';
                    }
                });
            });
            
            // Date filter
            document.getElementById('filterDate').addEventListener('change', function() {
                const date = this.value;
                const rows = document.querySelectorAll('#paymentsTable tbody tr');
                rows.forEach(function(row) {
                    if (date === '') {
                        row.style.display = '';
                    } else {
                        const rowDate = row.dataset.date;
                        row.style.display = rowDate === date ? '' : 'none';
                    }
                });
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
                fetch('/admin/shop/payments/' + paymentId + '/refund', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while processing the refund.');
                });
            }
        }
        
        function updatePaymentStatus(paymentId, status) {
            fetch('{{ url('admin/shop/payments') }}/' + paymentId + '/update-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the payment status.');
            });
        }
    </script>
@endsection
