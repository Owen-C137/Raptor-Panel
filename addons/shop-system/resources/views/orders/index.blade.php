@extends('shop::layout')

@section('shop-title', 'My Orders')

@section('shop-content')
<div class="shop-container orders-page">
    {{-- Enhanced Orders Header --}}
    <div class="orders-header-enhanced">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="page-title-section">
                        <div class="title-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="title-content">
                            <h1 class="page-title">My Orders</h1>
                            <p class="page-subtitle">Manage and track your hosting orders</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="orders-summary">
                        <div class="summary-stats">
                            <div class="stat-item">
                                <span class="stat-value">{{ $orders->where('status', 'active')->count() }}</span>
                                <span class="stat-label">Active</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value">{{ $orders->where('status', 'pending')->count() }}</span>
                                <span class="stat-label">Pending</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value">{{ $orders->count() }}</span>
                                <span class="stat-label">Total</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Enhanced Filter Bar --}}
    <div class="filter-bar-enhanced">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="filter-buttons">
                        <button type="button" class="filter-btn active" data-filter="all">
                            <i class="fas fa-list me-2"></i>
                            <span>All Orders</span>
                            <span class="count">{{ $orders->count() }}</span>
                        </button>
                        <button type="button" class="filter-btn" data-filter="active">
                            <i class="fas fa-check-circle me-2"></i>
                            <span>Active</span>
                            <span class="count">{{ $orders->where('status', 'active')->count() }}</span>
                        </button>
                        <button type="button" class="filter-btn" data-filter="pending">
                            <i class="fas fa-clock me-2"></i>
                            <span>Pending</span>
                            <span class="count">{{ $orders->where('status', 'pending')->count() }}</span>
                        </button>
                        <button type="button" class="filter-btn" data-filter="cancelled">
                            <i class="fas fa-times-circle me-2"></i>
                            <span>Cancelled</span>
                            <span class="count">{{ $orders->where('status', 'cancelled')->count() }}</span>
                        </button>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="search-and-sort">
                        <div class="search-box">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" class="search-input" placeholder="Search orders..." id="order-search">
                        </div>
                        <div class="sort-dropdown">
                            <select class="sort-select" id="order-sort">
                                <option value="newest">Newest First</option>
                                <option value="oldest">Oldest First</option>
                                <option value="amount-high">Highest Amount</option>
                                <option value="amount-low">Lowest Amount</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Enhanced Orders List --}}
    <div class="orders-content">
        <div class="container-fluid">
            @if($orders->count() > 0)
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Orders</h3>
                        <div class="block-options">
                            <div class="dropdown">
                                <button type="button" class="btn-block-option" id="dropdown-order-filters" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Filters <i class="fa fa-angle-down ms-1"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown-order-filters">
                                    <a class="dropdown-item d-flex align-items-center justify-content-between" href="javascript:void(0)" data-filter="all">
                                        All Orders
                                        <span class="badge bg-primary rounded-pill">{{ $orders->count() }}</span>
                                    </a>
                                    <a class="dropdown-item d-flex align-items-center justify-content-between" href="javascript:void(0)" data-filter="active">
                                        Active
                                        <span class="badge bg-success rounded-pill">{{ $orders->where('status', 'active')->count() }}</span>
                                    </a>
                                    <a class="dropdown-item d-flex align-items-center justify-content-between" href="javascript:void(0)" data-filter="pending">
                                        Pending
                                        <span class="badge bg-warning rounded-pill">{{ $orders->where('status', 'pending')->count() }}</span>
                                    </a>
                                    <a class="dropdown-item d-flex align-items-center justify-content-between" href="javascript:void(0)" data-filter="cancelled">
                                        Cancelled
                                        <span class="badge bg-danger rounded-pill">{{ $orders->where('status', 'cancelled')->count() }}</span>
                                    </a>
                                    <a class="dropdown-item d-flex align-items-center justify-content-between" href="javascript:void(0)" data-filter="completed">
                                        Completed
                                        <span class="badge bg-info rounded-pill">{{ $orders->where('status', 'completed')->count() }}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="block-content">
                        <form action="{{ route('shop.orders.index') }}" method="GET" onsubmit="return false;">
                            <div class="mb-4">
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-alt" id="order-search" name="search" placeholder="Search all orders..." value="{{ request('search') }}">
                                    <span class="input-group-text bg-body border-0">
                                        <i class="fa fa-search"></i>
                                    </span>
                                </div>
                            </div>
                        </form>
                        <div class="table-responsive">
                            <table class="table table-borderless table-striped table-vcenter">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 100px;">Order</th>
                                        <th class="d-none d-sm-table-cell text-center">Date</th>
                                        <th>Status</th>
                                        <th class="d-none d-xl-table-cell">Services</th>
                                        <th class="d-none d-xl-table-cell text-center">Items</th>
                                        <th class="d-none d-sm-table-cell text-end">Total</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($orders as $order)
                                    <tr data-status="{{ $order->status }}">
                                        <td class="text-center fs-sm">
                                            <a class="fw-semibold" href="{{ route('shop.orders.show', $order) }}">
                                                <strong>#{{ $order->order_number ?? str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</strong>
                                            </a>
                                        </td>
                                        <td class="d-none d-sm-table-cell text-center fs-sm">{{ $order->created_at->format('d/m/Y') }}</td>
                                        <td>
                                            @php
                                                $statusConfig = match($order->status) {
                                                    'active' => ['class' => 'success', 'label' => 'Active'],
                                                    'pending' => ['class' => 'warning', 'label' => 'Pending'],
                                                    'cancelled' => ['class' => 'danger', 'label' => 'Cancelled'],
                                                    'suspended' => ['class' => 'secondary', 'label' => 'Suspended'],
                                                    'completed' => ['class' => 'info', 'label' => 'Completed'],
                                                    default => ['class' => 'secondary', 'label' => ucfirst($order->status)]
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $statusConfig['class'] }}">{{ $statusConfig['label'] }}</span>
                                        </td>
                                        <td class="d-none d-xl-table-cell fs-sm">
                                            @if($order->items && $order->items->count() > 0)
                                                <div class="fw-semibold">{{ $order->items->first()->plan->name ?? 'Custom Plan' }}</div>
                                                @if($order->items->count() > 1)
                                                    <small class="text-muted">+{{ $order->items->count() - 1 }} more</small>
                                                @endif
                                            @else
                                                <span class="fw-semibold">{{ $order->plan->name ?? 'Custom Order' }}</span>
                                            @endif
                                        </td>
                                        <td class="d-none d-xl-table-cell text-center fs-sm">
                                            <a class="fw-semibold" href="{{ route('shop.orders.show', $order) }}">
                                                {{ $order->items ? $order->items->sum('quantity') : 1 }}
                                            </a>
                                        </td>
                                        <td class="d-none d-sm-table-cell text-end fs-sm">
                                            <strong>${{ number_format($order->total_amount ?? 0, 2) }}</strong>
                                        </td>
                                        <td class="text-center">
                                            <a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" href="{{ route('shop.orders.show', $order) }}" data-bs-toggle="tooltip" aria-label="View" data-bs-original-title="View Order">
                                                <i class="fa fa-fw fa-eye"></i>
                                            </a>
                                            @if($order->status === 'active')
                                                <a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" href="{{ route('shop.orders.invoice', $order) }}" data-bs-toggle="tooltip" aria-label="Invoice" data-bs-original-title="Download Invoice">
                                                    <i class="fa fa-fw fa-file-pdf"></i>
                                                </a>
                                            @endif
                                            @if($order->status === 'active' && $order->server_id && $order->server)
                                                <a class="btn btn-sm btn-alt-secondary js-bs-tooltip-enabled" href="/server/{{ $order->server->uuidShort }}" data-bs-toggle="tooltip" aria-label="Manage" data-bs-original-title="Manage Server">
                                                    <i class="fa fa-fw fa-cog"></i>
                                                </a>
                                            @endif
                                            @if(in_array($order->status, ['pending', 'active']))
                                                <a class="btn btn-sm btn-alt-secondary text-danger js-bs-tooltip-enabled cancel-order-btn" href="#" data-order-id="{{ $order->id }}" data-bs-toggle="tooltip" aria-label="Cancel" data-bs-original-title="Cancel Order">
                                                    <i class="fa fa-fw fa-times"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($orders->hasPages())
                        <nav aria-label="Orders Navigation">
                            <ul class="pagination pagination-sm justify-content-end mt-2">
                                {{-- Previous Page Link --}}
                                @if ($orders->onFirstPage())
                                    <li class="page-item disabled">
                                        <span class="page-link" tabindex="-1" aria-label="Previous">Prev</span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $orders->previousPageUrl() }}" tabindex="-1" aria-label="Previous">Prev</a>
                                    </li>
                                @endif

                                {{-- Pagination Elements --}}
                                @foreach ($orders->getUrlRange(1, $orders->lastPage()) as $page => $url)
                                    @if ($page == $orders->currentPage())
                                        <li class="page-item active">
                                            <span class="page-link">{{ $page }}</span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                        </li>
                                    @endif
                                @endforeach

                                {{-- Next Page Link --}}
                                @if ($orders->hasMorePages())
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $orders->nextPageUrl() }}" aria-label="Next">Next</a>
                                    </li>
                                @else
                                    <li class="page-item disabled">
                                        <span class="page-link" aria-label="Next">Next</span>
                                    </li>
                                @endif
                            </ul>
                        </nav>
                        @endif
                    </div>
                </div>
            @else
                {{-- Enhanced Empty State --}}
                <div class="empty-state-enhanced">
                    <div class="empty-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="empty-content">
                        <h3 class="empty-title">No Orders Yet</h3>
                        <p class="empty-description">You haven't placed any orders. Browse our hosting plans to get started.</p>
                        <a href="{{ route('shop.index') }}" class="btn-empty-action">
                            <i class="fas fa-store me-2"></i>
                            <span>Start Shopping</span>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Enhanced Cancel Order Modal --}}
<div class="modal fade" id="cancelOrderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-enhanced">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Cancel Order
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="modal-description">Are you sure you want to cancel this order?</p>
                <div class="alert alert-warning">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>Warning:</strong> Cancelling this order will:
                        <ul class="warning-list">
                            <li>Stop all services associated with this order</li>
                            <li>Cancel future renewals</li>
                            <li>May result in data loss</li>
                        </ul>
                    </div>
                </div>
                <div class="form-group">
                    <label for="cancel-reason" class="form-label">Reason for cancellation (optional):</label>
                    <textarea class="form-control" id="cancel-reason" rows="3" 
                              placeholder="Please let us know why you're cancelling..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-arrow-left me-2"></i>
                    Keep Order
                </button>
                <button type="button" class="btn btn-danger" id="confirm-cancel-order">
                    <i class="fas fa-times me-2"></i>
                    Cancel Order
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Enhanced JavaScript for Orders Page --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentOrderId = null;
    
    // Enhanced filter functionality
    const filterButtons = document.querySelectorAll('.filter-btn');
    const orderCards = document.querySelectorAll('.order-card-enhanced');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.dataset.filter;
            
            // Update active button with animation
            filterButtons.forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            // Filter orders with fade animation
            orderCards.forEach(card => {
                const status = card.dataset.status;
                let shouldShow = false;
                
                if (filter === 'all') {
                    shouldShow = true;
                } else if (filter === 'active') {
                    shouldShow = ['active', 'completed'].includes(status);
                } else if (filter === 'pending') {
                    shouldShow = ['pending', 'processing', 'provisioning'].includes(status);
                } else if (filter === 'cancelled') {
                    shouldShow = ['cancelled', 'refunded', 'failed', 'suspended'].includes(status);
                } else {
                    shouldShow = status === filter;
                }
                
                if (shouldShow) {
                    card.style.display = 'block';
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    
                    requestAnimationFrame(() => {
                        card.style.transition = 'all 0.3s ease';
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    });
                } else {
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        card.style.display = 'none';
                    }, 300);
                }
            });
        });
    });
    
    // Enhanced search functionality
    const searchInput = document.getElementById('order-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            orderCards.forEach(card => {
                const orderNumber = card.querySelector('.order-link').textContent.toLowerCase();
                const planName = card.querySelector('.plan-name')?.textContent.toLowerCase() || '';
                const categoryName = card.querySelector('.plan-category')?.textContent.toLowerCase() || '';
                
                const matches = orderNumber.includes(searchTerm) || 
                               planName.includes(searchTerm) || 
                               categoryName.includes(searchTerm);
                
                if (matches || searchTerm === '') {
                    card.style.display = 'block';
                    card.style.opacity = '1';
                } else {
                    card.style.opacity = '0.3';
                }
            });
        });
    }
    
    // Enhanced sort functionality
    const sortSelect = document.getElementById('order-sort');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const sortType = this.value;
            const ordersContainer = document.getElementById('orders-list');
            const ordersArray = Array.from(orderCards);
            
            ordersArray.sort((a, b) => {
                switch (sortType) {
                    case 'newest':
                        return new Date(b.querySelector('.order-date').textContent) - 
                               new Date(a.querySelector('.order-date').textContent);
                    case 'oldest':
                        return new Date(a.querySelector('.order-date').textContent) - 
                               new Date(b.querySelector('.order-date').textContent);
                    case 'amount-high':
                        return parseFloat(b.dataset.amount) - parseFloat(a.dataset.amount);
                    case 'amount-low':
                        return parseFloat(a.dataset.amount) - parseFloat(b.dataset.amount);
                    default:
                        return 0;
                }
            });
            
            // Re-append sorted elements
            ordersArray.forEach(card => {
                ordersContainer.appendChild(card);
            });
        });
    }
    
    // Enhanced cancel order functionality
    document.querySelectorAll('.cancel-order-btn').forEach(button => {
        button.addEventListener('click', function() {
            currentOrderId = this.dataset.orderId;
            const modal = new bootstrap.Modal(document.getElementById('cancelOrderModal'));
            modal.show();
        });
    });
    
    const confirmCancelBtn = document.getElementById('confirm-cancel-order');
    if (confirmCancelBtn) {
        confirmCancelBtn.addEventListener('click', function() {
            if (!currentOrderId) return;
            
            const reason = document.getElementById('cancel-reason').value;
            const formData = new FormData();
            formData.append('reason', reason);
            
            // Add loading state
            const originalText = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Cancelling...';
            
            fetch(`/shop/orders/${currentOrderId}/cancel`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Order cancelled successfully.', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showToast(data.message || 'Failed to cancel order.', 'error');
                }
            })
            .catch(error => {
                console.error('Error cancelling order:', error);
                showToast('Failed to cancel order. Please try again.', 'error');
            })
            .finally(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('cancelOrderModal'));
                if (modal) modal.hide();
                this.disabled = false;
                this.innerHTML = originalText;
            });
        });
    }
    
    // Enhanced card hover effects
    orderCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});

// Enhanced toast notification system
function showToast(message, type = 'info') {
    // Use Shop notification system if available
    if (typeof Shop !== 'undefined' && Shop.showNotification) {
        Shop.showNotification(type, message);
        return;
    }
    
    // Fallback toast system
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas fa-${getToastIcon(type)} me-2"></i>
            <span>${message}</span>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }
    }, 5000);
}

function getToastIcon(type) {
    switch(type) {
        case 'success': return 'check-circle';
        case 'error': return 'exclamation-circle';
        case 'warning': return 'exclamation-triangle';
        default: return 'info-circle';
    }
}
</script>
@endpush

