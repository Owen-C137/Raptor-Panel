@extends('layouts.admin')

@section('title')
    Coupons
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Coupons
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Manage discount coupons
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
          <li class="breadcrumb-item" aria-current="page">Coupons</li>
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
                    <i class="fa fa-tags me-1"></i>Coupon Management
                </h3>
                <div class="block-options">
                    <a href="{{ route('admin.shop.coupons.create') }}" class="btn btn-success btn-sm">
                        <i class="fa fa-plus me-1"></i> Create Coupon
                    </a>
                </div>
            </div>
            
            <div class="block-content">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" id="searchCoupons" class="form-control" placeholder="Search coupons...">
                            <label for="searchCoupons">Search coupons...</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <select id="filterStatus" class="form-select">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="expired">Expired</option>
                                <option value="inactive">Inactive</option>
                                <option value="used_up">Used Up</option>
                            </select>
                            <label for="filterStatus">Filter by Status</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating">
                            <select id="filterType" class="form-select">
                                <option value="">All Types</option>
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed Amount</option>
                            </select>
                            <label for="filterType">Filter by Type</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary w-100 h-100" onclick="resetFilters()">
                            <i class="fa fa-refresh me-1"></i> Reset
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover table-vcenter" id="couponsTable">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Usage</th>
                                <th>Valid Period</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-center" style="width: 180px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($coupons as $coupon)
                                <tr data-status="{{ $coupon->getStatus() }}" data-type="{{ $coupon->type }}">
                                    <td>
                                        <div class="fw-semibold">{{ $coupon->code }}</div>
                                        @if($coupon->name)
                                            <div class="fs-sm text-muted">{{ $coupon->name }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($coupon->type === 'percentage')
                                            <span class="badge bg-info">Percentage</span>
                                        @else
                                            <span class="badge bg-primary">Fixed Amount</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            @if($coupon->type === 'percentage')
                                                {{ $coupon->value }}%
                                            @else
                                                ${{ number_format($coupon->value, 2) }}
                                            @endif
                                        </div>
                                        
                                        @if($coupon->minimum_amount > 0)
                                            <div class="fs-sm text-muted">Min: ${{ number_format($coupon->minimum_amount, 2) }}</div>
                                        @endif
                                        
                                        @if($coupon->maximum_discount > 0 && $coupon->type === 'percentage')
                                            <div class="fs-sm text-muted">Max: ${{ number_format($coupon->maximum_discount, 2) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $coupon->used_count ?? 0 }}
                                            @if($coupon->usage_limit)
                                                / {{ $coupon->usage_limit }}
                                            @else
                                                / ∞
                                            @endif
                                        </div>
                                        
                                        @if($coupon->usage_limit_per_user)
                                            <div class="fs-sm text-muted">{{ $coupon->usage_limit_per_user }}/user</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($coupon->valid_from)
                                            <div class="fs-sm">From: {{ $coupon->valid_from->format('M d, Y') }}</div>
                                        @endif
                                        @if($coupon->valid_until)
                                            <div class="fs-sm">Until: {{ $coupon->valid_until->format('M d, Y') }}</div>
                                        @else
                                            <div class="fs-sm text-muted">No expiration</div>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $status = $coupon->getStatus();
                                        @endphp
                                        
                                        @switch($status)
                                            @case('active')
                                                <span class="badge bg-success">Active</span>
                                                @break
                                            @case('expired')
                                                <span class="badge bg-warning">Expired</span>
                                                @break
                                            @case('inactive')
                                                <span class="badge bg-danger">Inactive</span>
                                                @break
                                            @case('used_up')
                                                <span class="badge bg-secondary">Used Up</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ ucfirst($status) }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <div class="fs-sm">{{ $coupon->created_at->format('M d, Y') }}</div>
                                        <div class="fs-sm text-muted">{{ $coupon->created_at->format('g:i A') }}</div>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.shop.coupons.show', $coupon->id) }}" 
                                               class="btn btn-sm btn-alt-primary" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.shop.coupons.edit', $coupon->id) }}" 
                                               class="btn btn-sm btn-alt-warning" title="Edit">
                                                <i class="fa fa-pencil-alt"></i>
                                            </a>
                                            @if($coupon->active)
                                                <button class="btn btn-sm btn-alt-danger" 
                                                        onclick="toggleCoupon({{ $coupon->id }}, false)" title="Deactivate">
                                                    <i class="fa fa-pause"></i>
                                                </button>
                                            @else
                                                <button class="btn btn-sm btn-alt-success" 
                                                        onclick="toggleCoupon({{ $coupon->id }}, true)" title="Activate">
                                                    <i class="fa fa-play"></i>
                                                </button>
                                            @endif
                                            <button class="btn btn-sm btn-alt-danger" 
                                                    onclick="deleteCoupon({{ $coupon->id }})" title="Delete">
                                                <i class="fa fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="py-4">
                                            <i class="fa fa-tags fa-3x text-muted mb-3"></i>
                                            <h4 class="fw-normal text-muted">No coupons found</h4>
                                            <p class="fs-sm text-muted mb-3">Get started by creating your first coupon.</p>
                                            <a href="{{ route('admin.shop.coupons.create') }}" class="btn btn-success">
                                                <i class="fa fa-plus me-1"></i> Create First Coupon
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            @if(method_exists($coupons, 'links'))
                <div class="block-content block-content-full bg-body-light">
                    {{ $coupons->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row">
    <div class="col-lg-3 col-sm-6">
        <div class="block block-rounded text-center">
            <div class="block-content">
                <div class="py-3">
                    <div class="fs-3 fw-semibold text-primary">{{ $stats['total_coupons'] ?? $coupons->count() }}</div>
                    <div class="fw-semibold fs-sm text-muted text-uppercase tracking-wider">Total Coupons</div>
                </div>
            </div>
            <div class="block-content block-content-full bg-primary-light">
                <div class="fs-6 fw-semibold text-primary">
                    <i class="fa fa-tags me-1"></i> All Coupons
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-sm-6">
        <div class="block block-rounded text-center">
            <div class="block-content">
                <div class="py-3">
                    <div class="fs-3 fw-semibold text-success">{{ $stats['active_coupons'] ?? $coupons->where('active', true)->count() }}</div>
                    <div class="fw-semibold fs-sm text-muted text-uppercase tracking-wider">Active Coupons</div>
                </div>
            </div>
            <div class="block-content block-content-full bg-success-light">
                <div class="fs-6 fw-semibold text-success">
                    <i class="fa fa-check me-1"></i> Currently Active
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-sm-6">
        <div class="block block-rounded text-center">
            <div class="block-content">
                <div class="py-3">
                    <div class="fs-3 fw-semibold text-warning">{{ $stats['used_coupons'] ?? 0 }}</div>
                    <div class="fw-semibold fs-sm text-muted text-uppercase tracking-wider">Times Used</div>
                </div>
            </div>
            <div class="block-content block-content-full bg-warning-light">
                <div class="fs-6 fw-semibold text-warning">
                    <i class="fa fa-shopping-cart me-1"></i> Usage Count
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-sm-6">
        <div class="block block-rounded text-center">
            <div class="block-content">
                <div class="py-3">
                    <div class="fs-3 fw-semibold text-danger">${{ number_format($stats['total_discount'] ?? 0, 2) }}</div>
                    <div class="fw-semibold fs-sm text-muted text-uppercase tracking-wider">Total Discounts</div>
                </div>
            </div>
            <div class="block-content block-content-full bg-danger-light">
                <div class="fs-6 fw-semibold text-danger">
                    <i class="fa fa-dollar-sign me-1"></i> Savings Given
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchCoupons');
            const statusFilter = document.getElementById('filterStatus');
            const typeFilter = document.getElementById('filterType');
            const tableRows = document.querySelectorAll('#couponsTable tbody tr');
            
            // Search functionality
            searchInput.addEventListener('keyup', function() {
                const searchValue = this.value.toLowerCase();
                tableRows.forEach(function(row) {
                    const text = row.textContent.toLowerCase();
                    if (text.indexOf(searchValue) > -1) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
            
            // Status filter
            statusFilter.addEventListener('change', function() {
                const selectedStatus = this.value;
                tableRows.forEach(function(row) {
                    const rowStatus = row.getAttribute('data-status');
                    if (selectedStatus === '' || rowStatus === selectedStatus) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
            
            // Type filter
            typeFilter.addEventListener('change', function() {
                const selectedType = this.value;
                tableRows.forEach(function(row) {
                    const rowType = row.getAttribute('data-type');
                    if (selectedType === '' || rowType === selectedType) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
        
        function resetFilters() {
            document.getElementById('searchCoupons').value = '';
            document.getElementById('filterStatus').value = '';
            document.getElementById('filterType').value = '';
            document.querySelectorAll('#couponsTable tbody tr').forEach(function(row) {
                row.style.display = '';
            });
        }
        
        function toggleCoupon(couponId, activate) {
            const action = activate ? 'activate' : 'deactivate';
            if (confirm('Are you sure you want to ' + action + ' this coupon?')) {
                fetch('{{ url('admin/shop/coupons') }}/' + couponId + '/toggle-status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        active: activate
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
                    alert('An error occurred while updating the coupon.');
                });
            }
        }
        
        function deleteCoupon(couponId) {
            if (confirm('Are you sure you want to delete this coupon? This action cannot be undone.')) {
                fetch('/admin/shop/coupons/' + couponId, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
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
                    alert('An error occurred while deleting the coupon.');
                });
            }
        }
    </script>
@endsection
