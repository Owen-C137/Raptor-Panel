@extends('shop::layout')

@section('shop-title', 'Order #' . $order->id)

@section('shop-content')
<div class="row">
    <div class="col-12">
        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('shop.index') }}">Shop</a></li>
                <li class="breadcrumb-item"><a href="{{ route('shop.orders.index') }}">Orders</a></li>
                <li class="breadcrumb-item active">#{{ $order->id }}</li>
            </ol>
        </nav>
        
        {{-- Order Details --}}
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-shopping-bag"></i>
                        Order #{{ $order->id }}
                    </h3>
                    <span class="badge badge-{{ $order->status_color }}">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Order Information</h5>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Order Date:</strong></td>
                                <td>{{ $order->created_at->format('M j, Y g:i A') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge badge-{{ $order->status_color }}">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Total:</strong></td>
                                <td><strong>${{ number_format($order->total, 2) }}</strong></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Payment Information</h5>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Payment Method:</strong></td>
                                <td>{{ $order->payment_method ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Payment Status:</strong></td>
                                <td>{{ ucfirst($order->payment_status ?? 'pending') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                {{-- Order Items --}}
                <h5 class="mt-4">Order Items</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items ?? [] as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item->plan->name ?? 'N/A' }}</strong>
                                    @if($item->plan->description ?? false)
                                        <br><small class="text-muted">{{ $item->plan->description }}</small>
                                    @endif
                                </td>
                                <td>{{ $item->quantity }}</td>
                                <td>${{ number_format($item->price, 2) }}</td>
                                <td>${{ number_format($item->price * $item->quantity, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="text-end">
                    <a href="{{ route('shop.orders.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Orders
                    </a>
                    @if(isset($paymentMethods) && count($paymentMethods) > 0 && $order->status === 'pending')
                        <button class="btn btn-success">
                            <i class="fas fa-credit-card"></i>
                            Pay Now
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
