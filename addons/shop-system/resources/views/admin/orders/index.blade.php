@extends('layouts.admin')

@section('title')
    Orders Management
@endsection

@section('content-header')
    <h1>
        Orders
        <small>Manage shop orders and subscriptions</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
        <li class="active">Orders</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Order List</h3>
            </div>
            
            <div class="box-body">
                <!-- Search and Filter Form -->
                <div class="row">
                    <div class="col-md-4">
                        <form method="GET" action="{{ route('admin.shop.orders.index') }}">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Search orders..." 
                                       value="{{ $search }}" autocomplete="off">
                                <div class="input-group-btn">
                                    <button type="submit" class="btn btn-default">
                                        <i class="fa fa-search"></i>
                                    </button>
                                    @if($search)
                                        <a href="{{ route('admin.shop.orders.index') }}" class="btn btn-default">
                                            <i class="fa fa-times"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" onchange="window.location.href=this.value">
                            <option value="{{ route('admin.shop.orders.index') }}">All Status</option>
                            @foreach($statuses as $key => $label)
                                <option value="{{ route('admin.shop.orders.index', ['status' => $key]) }}"
                                        {{ $status == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" onchange="window.location.href=this.value">
                            <option value="{{ route('admin.shop.orders.index') }}">All Users</option>
                            @foreach($users as $u)
                                <option value="{{ route('admin.shop.orders.index', ['user' => $u->id]) }}"
                                        {{ $user == $u->id ? 'selected' : '' }}>
                                    {{ $u->username }} ({{ $u->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            
            @if($orders->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>User</th>
                                <th>Plan</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Billing Cycle</th>
                                <th>Next Due</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                <tr>
                                    <td>
                                        <strong>#{{ $order->id }}</strong>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.users.view', $order->user_id) }}">
                                            {{ $order->user->username }}
                                        </a>
                                        <br><small class="text-muted">{{ $order->user->email }}</small>
                                    </td>
                                    <td>
                                        {{ $order->plan->name ?? 'N/A' }}
                                        @if($order->plan && $order->plan->product)
                                            <br><small class="text-muted">{{ $order->plan->product->name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>${{ number_format($order->amount, 2) }}</strong>
                                        @if($order->setup_fee > 0)
                                            <br><small class="text-muted">+${{ number_format($order->setup_fee, 2) }} setup</small>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($order->status)
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
                                                <span class="label label-default">{{ ucfirst($order->status) }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <span class="label label-default">{{ ucfirst(str_replace('_', ' ', $order->billing_cycle)) }}</span>
                                    </td>
                                    <td>
                                        @if($order->next_due_at)
                                            {{ $order->next_due_at->format('M d, Y') }}
                                            @if($order->next_due_at->isPast())
                                                <br><small class="text-danger">Overdue</small>
                                            @endif
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $order->created_at->format('M d, Y') }}
                                        <br><small class="text-muted">{{ $order->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.shop.orders.show', $order->id) }}" 
                                           class="btn btn-xs btn-primary">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                        @if($order->server_id)
                                            <a href="{{ route('admin.servers.view', $order->server_id) }}" 
                                               class="btn btn-xs btn-info">
                                                <i class="fa fa-server"></i> Server
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="box-footer">
                    <div class="row">
                        <div class="col-md-12 text-center">
                            {{ $orders->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            @else
                <div class="box-body text-center">
                    <p>No orders found matching your criteria.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
