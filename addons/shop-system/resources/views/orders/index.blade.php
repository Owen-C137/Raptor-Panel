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
                <div class="orders-grid" id="orders-list">
                    @foreach($orders as $order)
                    {{-- Completely Redesigned Order Card --}}
                    <div class="order-card-redesigned" data-status="{{ $order->status }}">
                        {{-- Order Header Strip --}}
                        <div class="order-header-strip">
                            <div class="order-id-section">
                                <span class="order-hash">#{{ $order->order_number ?? str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</span>
                                <span class="order-date-simple">{{ $order->created_at->format('M d, Y') }}</span>
                            </div>
                            
                            <div class="order-status-pill">
                                @php
                                    $statusConfig = match($order->status) {
                                        'active' => ['class' => 'success', 'icon' => 'check-circle', 'label' => 'Active'],
                                        'pending' => ['class' => 'warning', 'icon' => 'clock', 'label' => 'Pending'],
                                        'cancelled' => ['class' => 'danger', 'icon' => 'times-circle', 'label' => 'Cancelled'],
                                        'suspended' => ['class' => 'secondary', 'icon' => 'pause-circle', 'label' => 'Suspended'],
                                        'completed' => ['class' => 'info', 'icon' => 'check', 'label' => 'Completed'],
                                        default => ['class' => 'secondary', 'icon' => 'question-circle', 'label' => ucfirst($order->status)]
                                    };
                                @endphp
                                <span class="status-indicator status-{{ $statusConfig['class'] }}">
                                    <i class="fas fa-{{ $statusConfig['icon'] }}"></i>
                                    {{ $statusConfig['label'] }}
                                </span>
                            </div>
                            
                            <div class="order-total-display">
                                <span class="total-amount">${{ number_format($order->total ?? 0, 2) }}</span>
                            </div>
                            
                            <div class="order-actions-minimal">
                                <div class="dropdown">
                                    <button class="btn-action-dots" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('shop.orders.show', $order) }}">
                                                <i class="fas fa-eye"></i>
                                                View Details
                                            </a>
                                        </li>
                                        @if($order->status === 'active')
                                            <li>
                                                <a class="dropdown-item" href="{{ route('shop.orders.invoice', $order) }}">
                                                    <i class="fas fa-file-pdf"></i>
                                                    Download Invoice
                                                </a>
                                            </li>
                                        @endif
                                        @if(in_array($order->status, ['pending', 'active']))
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger cancel-order-btn" 
                                                   href="#" data-order-id="{{ $order->id }}">
                                                    <i class="fas fa-times"></i>
                                                    Cancel Order
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Order Content --}}
                        <div class="order-content-main">
                            {{-- Service Details --}}
                            <div class="service-details-section">
                                @if($order->items && $order->items->count() > 0)
                                    @foreach($order->items as $item)
                                    <div class="service-item-card">
                                        <div class="service-icon">
                                            @php
                                                $categoryIcon = match(strtolower($item->plan->category->name ?? 'general')) {
                                                    'minecraft' => 'fas fa-cube',
                                                    'ark' => 'fas fa-dragon',
                                                    'rust' => 'fas fa-tools',
                                                    'cs2', 'csgo', 'counter-strike' => 'fas fa-crosshairs',
                                                    'gmod', 'garry\'s mod' => 'fas fa-wrench',
                                                    'terraria' => 'fas fa-mountain',
                                                    'valheim' => 'fas fa-hammer',
                                                    'fivem' => 'fas fa-car',
                                                    default => 'fas fa-server'
                                                };
                                            @endphp
                                            <i class="{{ $categoryIcon }}"></i>
                                        </div>
                                        <div class="service-info">
                                            <h4 class="service-name">{{ $item->plan->name ?? 'Custom Plan' }}</h4>
                                            <p class="service-category">{{ $item->plan->category->name ?? 'General' }}</p>
                                            <div class="service-meta">
                                                <span class="billing-cycle">{{ ucfirst($order->billing_cycle ?? 'monthly') }} billing</span>
                                                @if($item->quantity > 1)
                                                    <span class="quantity">x{{ $item->quantity }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="service-status">
                                            @if($order->status === 'active')
                                                <span class="status-badge active">
                                                    <i class="fas fa-play"></i>
                                                    Running
                                                </span>
                                            @elseif($order->status === 'pending')
                                                <span class="status-badge pending">
                                                    <i class="fas fa-clock"></i>
                                                    Setting Up
                                                </span>
                                            @else
                                                <span class="status-badge inactive">
                                                    <i class="fas fa-stop"></i>
                                                    {{ ucfirst($order->status) }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="service-actions">
                                            @if($order->status === 'active' && $order->server_id)
                                                <a href="/server/{{ $order->server_id }}" class="btn-manage">
                                                    <i class="fas fa-cog"></i>
                                                    Manage
                                                </a>
                                            @elseif($order->status === 'pending')
                                                <button class="btn-manage disabled" disabled>
                                                    <i class="fas fa-hourglass-half"></i>
                                                    Pending
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach
                                @else
                                    <div class="service-item-card">
                                        <div class="service-icon">
                                            <i class="fas fa-server"></i>
                                        </div>
                                        <div class="service-info">
                                            <h4 class="service-name">{{ $order->plan->name ?? 'Custom Order' }}</h4>
                                            <p class="service-category">{{ $order->plan->category->name ?? 'General' }}</p>
                                            <div class="service-meta">
                                                <span class="billing-cycle">{{ ucfirst($order->billing_cycle ?? 'monthly') }} billing</span>
                                            </div>
                                        </div>
                                        <div class="service-status">
                                            <span class="status-badge {{ $order->status === 'active' ? 'active' : ($order->status === 'pending' ? 'pending' : 'inactive') }}">
                                                <i class="fas fa-{{ $statusConfig['icon'] }}"></i>
                                                {{ $statusConfig['label'] }}
                                            </span>
                                        </div>
                                        <div class="service-actions">
                                            @if($order->status === 'active' && $order->server_id)
                                                <a href="/server/{{ $order->server_id }}" class="btn-manage">
                                                    <i class="fas fa-cog"></i>
                                                    Manage
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
                            {{-- Quick Info Panel --}}
                            <div class="quick-info-panel">
                                @if(in_array($order->status, ['active', 'pending']))
                                    <div class="info-item">
                                        <span class="info-label">Next Renewal</span>
                                        @if($order->expires_at)
                                            <span class="info-value primary">{{ $order->expires_at->format('M d, Y') }}</span>
                                            <span class="info-note">{{ $order->expires_at->diffForHumans() }}</span>
                                        @else
                                            <span class="info-value muted">Not set</span>
                                        @endif
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Renewal Amount</span>
                                        <span class="info-value success">${{ number_format($order->total ?? 0, 2) }}</span>
                                        <span class="info-note">{{ $order->billing_cycle ?? 'monthly' }}</span>
                                    </div>
                                @elseif($order->status === 'completed')
                                    <div class="info-item">
                                        <span class="info-label">Completed</span>
                                        <span class="info-value success">{{ $order->updated_at->format('M d, Y') }}</span>
                                        <span class="info-note">{{ $order->updated_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Total Paid</span>
                                        <span class="info-value success">${{ number_format($order->total ?? 0, 2) }}</span>
                                    </div>
                                @else
                                    <div class="info-item">
                                        <span class="info-label">Status</span>
                                        <span class="info-value">{{ ucfirst($order->status) }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Total</span>
                                        <span class="info-value">${{ number_format($order->total ?? 0, 2) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                {{-- Pagination --}}
                @if($orders->hasPages())
                <div class="pagination-wrapper">
                    {{ $orders->links() }}
                </div>
                @endif
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

