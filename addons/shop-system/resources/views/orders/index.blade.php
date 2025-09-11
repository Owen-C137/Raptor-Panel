@extends('shop::layout')

@section('shop-title', 'My Orders')

@section('shop-content')
<div class="orders-container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h1>
                    <i class="fas fa-shopping-bag"></i>
                    My Orders
                </h1>
                
                <div class="order-filters">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary active" data-filter="all">
                            All Orders
                        </button>
                        <button type="button" class="btn btn-outline-success" data-filter="active">
                            Active
                        </button>
                        <button type="button" class="btn btn-outline-warning" data-filter="pending">
                            Pending
                        </button>
                        <button type="button" class="btn btn-outline-danger" data-filter="cancelled">
                            Cancelled
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Orders List --}}
    <div class="row">
        <div class="col-12">
            @if($orders->count() > 0)
                <div id="orders-list">
                    @foreach($orders as $order)
                    <div class="card mb-3 order-card" data-status="{{ $order->status }}">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <div class="order-header-info">
                                        <h6 class="mb-1">
                                            <a href="{{ route('shop.orders.show', $order) }}" class="text-decoration-none">
                                                Order #{{ $order->order_number }}
                                            </a>
                                        </h6>
                                        <small class="text-muted">
                                            {{ $order->created_at->format('M d, Y \a\t g:i A') }}
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 text-center">
                                    <div class="order-status">
                                        <span class="badge badge-lg bg-{{ $order->getStatusColor() }}">
                                            {{ $order->getStatusLabel() }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="col-md-3 text-center">
                                    <div class="order-total">
                                        <strong class="text-success">
                                            {{ config('shop.currency.symbol', '$') }}{{ number_format($order->amount, 2) }}
                                        </strong>
                                    </div>
                                </div>
                                
                                <div class="col-md-2 text-end">
                                    <div class="order-actions">
                                        <div class="dropdown">
                                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                                                    type="button" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('shop.orders.show', $order) }}">
                                                        <i class="fas fa-eye"></i>
                                                        View Details
                                                    </a>
                                                </li>
                                                @if($order->canDownloadInvoice())
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('shop.orders.invoice', $order) }}">
                                                        <i class="fas fa-file-pdf"></i>
                                                        Download Invoice
                                                    </a>
                                                </li>
                                                @endif
                                                @if($order->canCancel())
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button class="dropdown-item text-danger cancel-order-btn" 
                                                            data-order-id="{{ $order->id }}">
                                                        <i class="fas fa-times"></i>
                                                        Cancel Order
                                                    </button>
                                                </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <div class="row">
                                {{-- Order Items --}}
                                <div class="col-md-8">
                                    <div class="order-items">
                                        @foreach($order->items->take(3) as $item)
                                        <div class="order-item {{ !$loop->first ? 'border-top pt-2 mt-2' : '' }}">
                                            <div class="row align-items-center">
                                                <div class="col-md-6">
                                                    <div class="item-info">
                                                        <h6 class="mb-1">{{ $item->plan->product->name }}</h6>
                                                        <p class="mb-1 text-primary">{{ $item->plan->name }}</p>
                                                        <div class="item-meta">
                                                            <span class="badge bg-secondary me-2">{{ $item->plan->billing_cycle }}</span>
                                                            @if($item->quantity > 1)
                                                                <span class="badge bg-info">Qty: {{ $item->quantity }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-3">
                                                    <div class="item-status">
                                                        @if($item->server_id)
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-server"></i>
                                                                Server Active
                                                            </span>
                                                        @elseif($item->status === 'provisioning')
                                                            <span class="badge bg-warning">
                                                                <i class="fas fa-clock"></i>
                                                                Provisioning
                                                            </span>
                                                        @elseif($item->status === 'suspended')
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-pause"></i>
                                                                Suspended
                                                            </span>
                                                        @else
                                                            <span class="badge bg-secondary">{{ ucfirst($item->status) }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-3 text-end">
                                                    @if($item->server_id)
                                                        <a href="{{ route('server.index', $item->server_id) }}" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-external-link-alt"></i>
                                                            Manage Server
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                        
                                        @if($order->items->count() > 3)
                                        <div class="more-items text-center mt-2">
                                            <small class="text-muted">
                                                +{{ $order->items->count() - 3 }} more items
                                            </small>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                
                                {{-- Renewal Information --}}
                                <div class="col-md-4">
                                    @if($order->hasActiveSubscriptions())
                                    <div class="renewal-info">
                                        <h6 class="text-muted">
                                            <i class="fas fa-sync-alt"></i>
                                            Next Renewal
                                        </h6>
                                        
                                        @php
                                            $nextRenewal = $order->getNextRenewalDate();
                                        @endphp
                                        
                                        @if($nextRenewal)
                                        <div class="renewal-date">
                                            <strong>{{ $nextRenewal->format('M d, Y') }}</strong>
                                            <small class="d-block text-muted">
                                                {{ $nextRenewal->diffForHumans() }}
                                            </small>
                                        </div>
                                        
                                        <div class="renewal-amount mt-2">
                                            <span class="badge bg-info">
                                                {{ config('shop.currency.symbol', '$') }}{{ number_format($order->getMonthlyAmount(), 2) }}/month
                                            </span>
                                        </div>
                                        @endif
                                        
                                        @if($order->canCancelRenewal())
                                        <div class="renewal-actions mt-2">
                                            <button class="btn btn-sm btn-outline-danger cancel-renewal-btn" 
                                                    data-order-id="{{ $order->id }}">
                                                <i class="fas fa-stop"></i>
                                                Cancel Auto-Renewal
                                            </button>
                                        </div>
                                        @endif
                                    </div>
                                    @elseif($order->status === 'completed' && !$order->hasActiveSubscriptions())
                                    <div class="order-complete-info text-center">
                                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                        <small class="text-muted">One-time order completed</small>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                {{-- Pagination --}}
                @if($orders->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $orders->links() }}
                </div>
                @endif
            @else
                {{-- Empty State --}}
                <div class="empty-orders text-center py-5">
                    <i class="fas fa-shopping-bag fa-4x text-muted mb-4"></i>
                    <h3>No Orders Yet</h3>
                    <p class="text-muted mb-4">You haven't placed any orders. Browse our products to get started.</p>
                    <a href="{{ route('shop.index') }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-store"></i>
                        Start Shopping
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Cancel Order Modal --}}
<div class="modal fade" id="cancelOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                    Cancel Order
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel this order?</p>
                <div class="alert alert-warning">
                    <strong>Warning:</strong> Cancelling this order will:
                    <ul class="mb-0 mt-2">
                        <li>Stop all services associated with this order</li>
                        <li>Cancel future renewals</li>
                        <li>May result in data loss</li>
                    </ul>
                </div>
                <div class="mb-3">
                    <label for="cancel-reason" class="form-label">Reason for cancellation (optional):</label>
                    <textarea class="form-control" id="cancel-reason" rows="3" 
                              placeholder="Please let us know why you're cancelling..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Keep Order
                </button>
                <button type="button" class="btn btn-danger" id="confirm-cancel-order">
                    <i class="fas fa-times"></i>
                    Cancel Order
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Cancel Renewal Modal --}}
<div class="modal fade" id="cancelRenewalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-stop-circle text-warning"></i>
                    Cancel Auto-Renewal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel automatic renewals for this order?</p>
                <div class="alert alert-info">
                    <strong>Note:</strong> Your services will remain active until the current billing period ends, 
                    but they will not automatically renew.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Keep Auto-Renewal
                </button>
                <button type="button" class="btn btn-warning" id="confirm-cancel-renewal">
                    <i class="fas fa-stop"></i>
                    Cancel Auto-Renewal
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentOrderId = null;
    
    // Filter functionality
    document.querySelectorAll('[data-filter]').forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.dataset.filter;
            
            // Update active button
            document.querySelectorAll('[data-filter]').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            // Filter orders
            document.querySelectorAll('.order-card').forEach(card => {
                const status = card.dataset.status;
                let show = false;
                
                if (filter === 'all') {
                    show = true;
                } else if (filter === 'active') {
                    show = ['active', 'completed'].includes(status);
                } else if (filter === 'pending') {
                    show = ['pending', 'processing'].includes(status);
                } else if (filter === 'cancelled') {
                    show = ['cancelled', 'refunded', 'failed'].includes(status);
                } else {
                    show = status === filter;
                }
                
                card.style.display = show ? 'block' : 'none';
            });
        });
    });
    
    // Cancel order functionality
    document.querySelectorAll('.cancel-order-btn').forEach(button => {
        button.addEventListener('click', function() {
            currentOrderId = this.dataset.orderId;
            const modal = new bootstrap.Modal(document.getElementById('cancelOrderModal'));
            modal.show();
        });
    });
    
    document.getElementById('confirm-cancel-order').addEventListener('click', function() {
        if (!currentOrderId) return;
        
        const reason = document.getElementById('cancel-reason').value;
        const formData = new FormData();
        formData.append('reason', reason);
        
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
        
        fetch(`/shop/orders/${currentOrderId}/cancel`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Shop.showNotification('success', 'Order cancelled successfully.');
                location.reload();
            } else {
                Shop.showNotification('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error cancelling order:', error);
            Shop.showNotification('error', 'Failed to cancel order.');
        })
        .finally(() => {
            bootstrap.Modal.getInstance(document.getElementById('cancelOrderModal')).hide();
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-times"></i> Cancel Order';
        });
    });
    
    // Cancel renewal functionality
    document.querySelectorAll('.cancel-renewal-btn').forEach(button => {
        button.addEventListener('click', function() {
            currentOrderId = this.dataset.orderId;
            const modal = new bootstrap.Modal(document.getElementById('cancelRenewalModal'));
            modal.show();
        });
    });
    
    document.getElementById('confirm-cancel-renewal').addEventListener('click', function() {
        if (!currentOrderId) return;
        
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
        
        fetch(`/shop/orders/${currentOrderId}/cancel-renewal`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Shop.showNotification('success', 'Auto-renewal cancelled successfully.');
                location.reload();
            } else {
                Shop.showNotification('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error cancelling renewal:', error);
            Shop.showNotification('error', 'Failed to cancel auto-renewal.');
        })
        .finally(() => {
            bootstrap.Modal.getInstance(document.getElementById('cancelRenewalModal')).hide();
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-stop"></i> Cancel Auto-Renewal';
        });
    });
});
</script>
@endpush

@push('styles')
<style>
.order-card {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.order-card[data-status="active"] {
    border-left-color: #28a745;
}

.order-card[data-status="pending"] {
    border-left-color: #ffc107;
}

.order-card[data-status="cancelled"] {
    border-left-color: #dc3545;
}

.order-card[data-status="completed"] {
    border-left-color: #17a2b8;
}

.order-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.badge-lg {
    padding: 6px 12px;
    font-size: 0.85em;
}

.order-header-info h6 a {
    color: #495057;
    font-weight: 600;
}

.order-header-info h6 a:hover {
    color: #007bff;
}

.order-item {
    padding: 8px 0;
}

.item-info h6 {
    color: #495057;
    font-size: 0.9em;
}

.item-info .text-primary {
    font-weight: 500;
}

.renewal-info {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
}

.renewal-date strong {
    color: #495057;
    font-size: 1.1em;
}

.order-complete-info {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
}

.empty-orders {
    background: #f8f9fa;
    border-radius: 12px;
    margin: 40px 0;
}

.btn-group .btn.active {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

@media (max-width: 768px) {
    .order-card .row > div {
        margin-bottom: 15px;
        text-align: center;
    }
    
    .order-filters {
        width: 100%;
        margin-top: 15px;
    }
    
    .btn-group {
        width: 100%;
        display: flex;
    }
    
    .btn-group .btn {
        flex: 1;
        font-size: 0.8em;
        padding: 8px 4px;
    }
    
    .order-actions .dropdown-toggle {
        width: 100%;
    }
}
</style>
@endpush
