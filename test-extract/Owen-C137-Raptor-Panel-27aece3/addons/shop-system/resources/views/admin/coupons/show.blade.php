@extends('layouts.admin')

@section('title')
    Coupon Details: {{ $coupon->code }}
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Coupon Details
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          {{ $coupon->code }}
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.index') }}">Admin</a>
          </li>
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.shop.index') }}">Shop</a>
          </li>
          <li class="breadcrumb-item">
            <a class="link-fx" href="{{ route('admin.shop.coupons.index') }}">Coupons</a>
          </li>
          <li class="breadcrumb-item" aria-current="page">
            {{ $coupon->code }}
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
                    <i class="fa fa-info-circle me-1"></i>Coupon Information
                </h3>
            </div>
            <div class="block-content">
                <div class="row">
                    <div class="col-md-6">
                        <div class="table-responsive">
                            <table class="table table-borderless table-vcenter">
                                <tbody>
                                    <tr>
                                        <td class="fw-semibold text-muted" style="width: 35%">Code</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <code class="bg-body-light px-2 py-1 rounded">{{ $coupon->code }}</code>
                                                <button class="btn btn-sm btn-alt-primary ms-2" onclick="copyToClipboard('{{ $coupon->code }}')" title="Copy Code">
                                                    <i class="fa fa-copy"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-muted">Name</td>
                                        <td>{{ $coupon->name ?: 'No name' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-muted">Description</td>
                                        <td>{{ $coupon->description ?: 'No description' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-muted">Type</td>
                                        <td>
                                            @if($coupon->type === 'percentage')
                                                <span class="badge bg-info">Percentage</span>
                                            @else
                                                <span class="badge bg-primary">Fixed Amount</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-muted">Value</td>
                                        <td class="fw-semibold">
                                            @if($coupon->type === 'percentage')
                                                {{ $coupon->value }}%
                                            @else
                                                ${{ number_format($coupon->value, 2) }}
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-muted">Status</td>
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
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="table-responsive">
                            <table class="table table-borderless table-vcenter">
                                <tbody>
                                    <tr>
                                        <td class="fw-semibold text-muted" style="width: 35%">Usage Limit</td>
                                        <td>{{ $coupon->usage_limit ?? 'Unlimited' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-muted">Per User Limit</td>
                                        <td>{{ $coupon->usage_limit_per_user ?? 'Unlimited' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-muted">Times Used</td>
                                        <td class="fw-semibold">
                                            {{ $coupon->used_count }}
                                            @if($coupon->usage_limit)
                                                / {{ $coupon->usage_limit }}
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-muted">Minimum Amount</td>
                                        <td>{{ $coupon->minimum_amount ? '$' . number_format($coupon->minimum_amount, 2) : 'No minimum' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-muted">First Order Only</td>
                                        <td>
                                            @if($coupon->first_order_only)
                                                <span class="badge bg-info">Yes</span>
                                            @else
                                                <span class="badge bg-secondary">No</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-muted">Valid From</td>
                                        <td>{{ $coupon->valid_from ? $coupon->valid_from->format('M d, Y g:i A') : 'No start date' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-muted">Valid Until</td>
                                        <td>{{ $coupon->valid_until ? $coupon->valid_until->format('M d, Y g:i A') : 'No expiration' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold text-muted">Created</td>
                                        <td>{{ $coupon->created_at->format('M d, Y g:i A') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="block-content bg-body-light text-center">
                <a href="{{ route('admin.shop.coupons.edit', $coupon) }}" class="btn btn-warning">
                    <i class="fa fa-pencil-alt me-1"></i> Edit Coupon
                </a>
                <a href="{{ route('admin.shop.coupons.duplicate', $coupon) }}" class="btn btn-info ms-2">
                    <i class="fa fa-copy me-1"></i> Duplicate
                </a>
                <a href="{{ route('admin.shop.coupons.index') }}" class="btn btn-secondary ms-2">
                    <i class="fa fa-arrow-left me-1"></i> Back to List
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-chart-bar me-1"></i>Usage Statistics
                </h3>
            </div>
            <div class="block-content">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-center">
                            <div class="fs-3 fw-semibold text-primary">{{ $coupon->used_count }}</div>
                            <div class="fw-semibold fs-sm text-muted text-uppercase tracking-wider">Total Uses</div>
                        </div>
                    </div>
                    
                    @if($coupon->usage_limit)
                    <div class="col-6">
                        <div class="text-center">
                            <div class="fs-3 fw-semibold text-success">{{ max(0, $coupon->usage_limit - $coupon->used_count) }}</div>
                            <div class="fw-semibold fs-sm text-muted text-uppercase tracking-wider">Remaining</div>
                        </div>
                    </div>
                    @endif
                    
                    <div class="col-{{ $coupon->usage_limit ? '12' : '6' }}">
                        <div class="text-center">
                            <div class="fs-3 fw-semibold text-warning">{{ $coupon->usages->unique('user_id')->count() }}</div>
                            <div class="fw-semibold fs-sm text-muted text-uppercase tracking-wider">Unique Users</div>
                        </div>
                    </div>
                </div>
                
                @if($coupon->usage_limit)
                <div class="mt-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fs-sm fw-medium">Usage Progress</span>
                        <span class="fs-sm text-muted">{{ number_format(($coupon->used_count / $coupon->usage_limit) * 100, 1) }}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar" role="progressbar" 
                             style="width: {{ ($coupon->used_count / $coupon->usage_limit) * 100 }}%"></div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        
        @if($coupon->usages->count() > 0)
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-history me-1"></i>Recent Usage
                </h3>
            </div>
            <div class="block-content block-content-full">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover table-vcenter">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Date</th>
                                <th>Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($coupon->usages->take(10) as $usage)
                            <tr>
                                <td>
                                    <div class="fw-semibold">
                                        @if($usage->user)
                                            {{ $usage->user->email }}
                                        @else
                                            <em class="text-muted">Unknown User</em>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="fs-sm">{{ $usage->created_at->format('M d, Y') }}</div>
                                    <div class="fs-sm text-muted">{{ $usage->created_at->format('g:i A') }}</div>
                                </td>
                                <td>
                                    @if($usage->order)
                                        <a href="{{ route('admin.shop.orders.show', $usage->order) }}" class="link-fx">
                                            Order #{{ $usage->order->id }}
                                        </a>
                                    @else
                                        <em class="text-muted">No order</em>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($coupon->usages->count() > 10)
                <div class="block-content bg-body-light text-center">
                    <small class="text-muted">Showing 10 of {{ $coupon->usages->count() }} uses</small>
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
        function copyToClipboard(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    // Check if OneUI notification system is available
                    if (typeof One !== 'undefined' && One.helpers && One.helpers.jqGrowl) {
                        One.helpers.jqGrowl('success', 'Coupon code copied to clipboard!');
                    } else if (typeof toastr !== 'undefined') {
                        toastr.success('Coupon code copied to clipboard!');
                    } else {
                        alert('Coupon code copied to clipboard!');
                    }
                }, function(err) {
                    console.error('Failed to copy text: ', err);
                    if (typeof One !== 'undefined' && One.helpers && One.helpers.jqGrowl) {
                        One.helpers.jqGrowl('error', 'Failed to copy code');
                    } else if (typeof toastr !== 'undefined') {
                        toastr.error('Failed to copy code');
                    } else {
                        alert('Failed to copy code');
                    }
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.opacity = '0';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                try {
                    const successful = document.execCommand('copy');
                    if (successful) {
                        if (typeof One !== 'undefined' && One.helpers && One.helpers.jqGrowl) {
                            One.helpers.jqGrowl('success', 'Coupon code copied to clipboard!');
                        } else if (typeof toastr !== 'undefined') {
                            toastr.success('Coupon code copied to clipboard!');
                        } else {
                            alert('Coupon code copied to clipboard!');
                        }
                    } else {
                        throw new Error('Copy command failed');
                    }
                } catch (err) {
                    console.error('Fallback copy failed: ', err);
                    if (typeof One !== 'undefined' && One.helpers && One.helpers.jqGrowl) {
                        One.helpers.jqGrowl('error', 'Failed to copy code');
                    } else if (typeof toastr !== 'undefined') {
                        toastr.error('Failed to copy code');
                    } else {
                        alert('Failed to copy code');
                    }
                }
                
                document.body.removeChild(textArea);
            }
        }
    </script>
@endsection
