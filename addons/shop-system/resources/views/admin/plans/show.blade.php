@extends('layouts.admin')

@section('title')
    Plan: {{ $plan->name }}
@endsection

@section('content-header')
    <h1>Plan Details <small>{{ $plan->name }}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
        <li><a href="{{ route('admin.shop.plans.index') }}">Plans</a></li>
        <li class="active">{{ $plan->name }}</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Plan Information</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('admin.shop.plans.edit', $plan->id) }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-edit"></i> Edit Plan
                    </a>
                    <button type="button" class="btn btn-sm btn-info" onclick="duplicatePlan()">
                        <i class="fa fa-copy"></i> Duplicate
                    </button>
                    <button type="button" class="btn btn-sm btn-{{ $plan->visible ? 'warning' : 'success' }}" 
                            onclick="toggleStatus()">
                        <i class="fa fa-{{ $plan->visible ? 'eye-slash' : 'eye' }}"></i>
                        {{ $plan->visible ? 'Hide' : 'Show' }}
                    </button>
                </div>
            </div>
            
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-striped">
                            <tr>
                                <th width="30%">Name</th>
                                <td>{{ $plan->name }}</td>
                            </tr>
                            <tr>
                                <th>Category</th>
                                <td>{{ $plan->category->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span class="label label-{{ $plan->visible ? 'success' : 'default' }}">
                                        {{ $plan->visible ? 'Visible' : 'Hidden' }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Sort Order</th>
                                <td>{{ $plan->sort_order ?? 'Not Set' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-striped">
                            <tr>
                                <th width="30%">Created</th>
                                <td>{{ $plan->created_at->format('M j, Y g:i A') }}</td>
                            </tr>
                            <tr>
                                <th>Updated</th>
                                <td>{{ $plan->updated_at->diffForHumans() }}</td>
                            </tr>
                            <tr>
                                <th>Egg</th>
                                <td>
                                    @if($plan->egg)
                                        {{ $plan->egg->nest->name }} - {{ $plan->egg->name }}
                                    @else
                                        <span class="text-muted">Any Egg</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                @if($plan->description)
                <div class="row">
                    <div class="col-md-12">
                        <h4>Description</h4>
                        <div class="well">
                            {!! nl2br(e($plan->description)) !!}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Pricing & Billing</h3>
            </div>
            
            <div class="box-body">
                @if(!empty($plan->billing_cycles))
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Billing Cycle</th>
                                <th>Price</th>
                                <th>Setup Fee</th>
                                <th>Per Period</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($plan->billing_cycles as $cycle)
                            <tr>
                                <td>
                                    <span class="label label-info">
                                        {{ ucfirst(str_replace('_', ' ', $cycle['cycle'])) }}
                                    </span>
                                </td>
                                <td>${{ number_format($cycle['price'], 2) }}</td>
                                <td>
                                    @if($cycle['setup_fee'] > 0)
                                        ${{ number_format($cycle['setup_fee'], 2) }}
                                    @else
                                        <span class="text-success">Free</span>
                                    @endif
                                </td>
                                <td>
                                    @switch($cycle['cycle'])
                                        @case('monthly')
                                            ${{ number_format($cycle['price'], 2) }}/month
                                            @break
                                        @case('quarterly')
                                            ${{ number_format($cycle['price'] / 3, 2) }}/month
                                            @break
                                        @case('semi_annually')
                                            ${{ number_format($cycle['price'] / 6, 2) }}/month
                                            @break
                                        @case('annually')
                                            ${{ number_format($cycle['price'] / 12, 2) }}/month
                                            @break
                                        @case('one_time')
                                            One-time payment
                                            @break
                                    @endswitch
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="callout callout-warning">
                    <h4><i class="fa fa-warning"></i> No Pricing Set</h4>
                    <p>This plan has no billing cycles configured. Customers won't be able to purchase this plan.</p>
                </div>
                @endif
            </div>
        </div>
        
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Server Resources</h3>
            </div>
            
            <div class="box-body">
                @if(!empty($plan->server_limits))
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-striped">
                            <tr>
                                <th width="40%">CPU</th>
                                <td>
                                    {{ $plan->server_limits['cpu'] ?? 0 }}%
                                    <small class="text-muted">
                                        ({{ number_format(($plan->server_limits['cpu'] ?? 0) / 100, 1) }} cores)
                                    </small>
                                </td>
                            </tr>
                            <tr>
                                <th>Memory</th>
                                <td>
                                    {{ number_format($plan->server_limits['memory'] ?? 0) }} MiB
                                    <small class="text-muted">
                                        ({{ number_format(($plan->server_limits['memory'] ?? 0) / 953.674, 2) }} GB)
                                    </small>
                                </td>
                            </tr>
                            <tr>
                                <th>Disk Space</th>
                                <td>
                                    {{ number_format($plan->server_limits['disk'] ?? 0) }} MB
                                    <small class="text-muted">
                                        ({{ number_format(($plan->server_limits['disk'] ?? 0) / 1024, 2) }} GB)
                                    </small>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-striped">
                            <tr>
                                <th width="40%">Swap</th>
                                <td>
                                    @if(($plan->server_limits['swap'] ?? 0) == -1)
                                        <span class="label label-success">Unlimited</span>
                                    @else
                                        {{ number_format($plan->server_limits['swap'] ?? 0) }} MB
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>IO Priority</th>
                                <td>{{ $plan->server_limits['io'] ?? 500 }}</td>
                            </tr>
                            <tr>
                                <th>OOM Killer</th>
                                <td>
                                    <span class="label label-{{ ($plan->server_limits['oom_disabled'] ?? 0) ? 'success' : 'warning' }}">
                                        {{ ($plan->server_limits['oom_disabled'] ?? 0) ? 'Disabled' : 'Enabled' }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                @else
                <div class="callout callout-warning">
                    <h4><i class="fa fa-warning"></i> No Resource Limits Set</h4>
                    <p>This plan has no server resource limits configured.</p>
                </div>
                @endif
            </div>
        </div>
        
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Feature Limits</h3>
            </div>
            
            <div class="box-body">
                @if(!empty($plan->server_feature_limits))
                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-striped">
                            <tr>
                                <th width="30%">Databases</th>
                                <td>
                                    @if(($plan->server_feature_limits['databases'] ?? 0) == 0)
                                        <span class="label label-danger">Unlimited</span>
                                    @else
                                        {{ $plan->server_feature_limits['databases'] }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Allocations</th>
                                <td>
                                    @if(($plan->server_feature_limits['allocations'] ?? 0) == 0)
                                        <span class="label label-danger">Unlimited</span>
                                    @else
                                        {{ $plan->server_feature_limits['allocations'] }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Backups</th>
                                <td>
                                    @if(($plan->server_feature_limits['backups'] ?? 0) == 0)
                                        <span class="label label-danger">Unlimited</span>
                                    @else
                                        {{ $plan->server_feature_limits['backups'] }}
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                @else
                <div class="callout callout-warning">
                    <h4><i class="fa fa-warning"></i> No Feature Limits Set</h4>
                    <p>This plan has no server feature limits configured.</p>
                </div>
                @endif
            </div>
        </div>
        
        @if(!empty($plan->allowed_locations) || !empty($plan->allowed_nodes))
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Server Configuration</h3>
            </div>
            
            <div class="box-body">
                @if(!empty($plan->allowed_locations))
                <div class="form-group">
                    <label>Allowed Locations</label>
                    <div>
                        @foreach($plan->allowedLocationModels as $location)
                            <span class="label label-info">{{ $location->long }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                
                @if(!empty($plan->allowed_nodes))
                <div class="form-group">
                    <label>Allowed Nodes</label>
                    <div>
                        @foreach($plan->allowedNodeModels as $node)
                            <span class="label label-info">{{ $node->name }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
    
    <div class="col-md-4">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Quick Actions</h3>
            </div>
            <div class="box-body">
                <a href="{{ route('admin.shop.plans.edit', $plan->id) }}" class="btn btn-primary btn-block">
                    <i class="fa fa-edit"></i> Edit Plan
                </a>
                <button type="button" class="btn btn-info btn-block" onclick="duplicatePlan()">
                    <i class="fa fa-copy"></i> Duplicate Plan
                </button>
                <button type="button" class="btn btn-{{ $plan->visible ? 'warning' : 'success' }} btn-block" 
                        onclick="toggleStatus()">
                    <i class="fa fa-{{ $plan->visible ? 'eye-slash' : 'eye' }}"></i>
                    {{ $plan->visible ? 'Hide Plan' : 'Show Plan' }}
                </button>
                <hr>
                <button type="button" class="btn btn-danger btn-block" onclick="deletePlan()">
                    <i class="fa fa-trash"></i> Delete Plan
                </button>
            </div>
        </div>
        
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Plan Statistics</h3>
            </div>
            <div class="box-body">
                <div class="info-box">
                    <span class="info-box-icon bg-blue"><i class="fa fa-shopping-cart"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Orders</span>
                        <span class="info-box-number">{{ $plan->orders()->count() }}</span>
                    </div>
                </div>
                
                <div class="info-box">
                    <span class="info-box-icon bg-green"><i class="fa fa-dollar"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Revenue</span>
                        <span class="info-box-number">
                            ${{ number_format($plan->orders()->sum('amount'), 2) }}
                        </span>
                    </div>
                </div>
                
                <div class="info-box">
                    <span class="info-box-icon bg-yellow"><i class="fa fa-server"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Active Servers</span>
                        <span class="info-box-number">
                            {{ $plan->orders()->where('status', 'active')->count() }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Resource Summary</h3>
            </div>
            <div class="box-body">
                <div class="callout callout-info">
                    @if(!empty($plan->server_limits))
                    <h4><i class="fa fa-info-circle"></i> Resource Overview</h4>
                    <ul>
                        <li><strong>CPU:</strong> {{ $plan->server_limits['cpu'] ?? 0 }}% ({{ number_format(($plan->server_limits['cpu'] ?? 0) / 100, 1) }} cores)</li>
                        <li><strong>RAM:</strong> {{ number_format(($plan->server_limits['memory'] ?? 0) / 953.674, 2) }} GB</li>
                        <li><strong>Disk:</strong> {{ number_format(($plan->server_limits['disk'] ?? 0) / 1024, 2) }} GB</li>
                        @if(!empty($plan->server_feature_limits))
                        <li><strong>Databases:</strong> {{ $plan->server_feature_limits['databases'] ?? 'Unlimited' }}</li>
                        <li><strong>Backups:</strong> {{ $plan->server_feature_limits['backups'] ?? 'Unlimited' }}</li>
                        @endif
                    </ul>
                    @else
                    <h4><i class="fa fa-warning"></i> No Resources</h4>
                    <p>This plan has no resource limits configured.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Forms for actions -->
<form id="toggle-status-form" method="POST" action="{{ route('admin.shop.plans.toggle-status', $plan->id) }}" style="display: none;">
    @csrf
    @method('PATCH')
</form>

<form id="duplicate-form" method="POST" action="{{ route('admin.shop.plans.duplicate', $plan->id) }}" style="display: none;">
    @csrf
</form>

<form id="delete-form" method="POST" action="{{ route('admin.shop.plans.destroy', $plan->id) }}" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@endsection

@push('footer-scripts')
<script>
    function toggleStatus() {
        swal({
            title: 'Are you sure?',
            text: 'This will {{ $plan->visible ? "hide" : "show" }} the plan {{ $plan->visible ? "from" : "in" }} the shop.',
            type: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '{{ $plan->visible ? "Hide" : "Show" }} Plan'
        }).then(function(result) {
            if (result.value) {
                document.getElementById('toggle-status-form').submit();
            }
        });
    }

    function duplicatePlan() {
        swal({
            title: 'Duplicate Plan',
            text: 'This will create a copy of this plan with the name "Copy of {{ $plan->name }}"',
            type: 'info',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Duplicate Plan'
        }).then(function(result) {
            if (result.value) {
                document.getElementById('duplicate-form').submit();
            }
        });
    }

    function deletePlan() {
        swal({
            title: 'Are you sure?',
            text: 'This will permanently delete the plan. This action cannot be undone!',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then(function(result) {
            if (result.value) {
                document.getElementById('delete-form').submit();
            }
        });
    }
</script>
@endpush
