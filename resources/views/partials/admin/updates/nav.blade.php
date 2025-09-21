@section('updates::nav')
    <div class="row">
        <div class="col-12">
            <div class="block block-rounded">
                <div class="block-content">
                    <ul class="nav nav-pills push">
                        <li class="nav-item me-1">
                            <a class="nav-link @if($activeTab === 'dashboard') active @endif" href="{{ route('admin.updates.dashboard') }}">
                                <i class="fa fa-fw fa-tachometer-alt me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item me-1">
                            <a class="nav-link @if($activeTab === 'manage') active @endif" href="{{ route('admin.updates.manage') }}">
                                <i class="fa fa-fw fa-download me-1"></i> Manage Updates
                            </a>
                        </li>
                        <li class="nav-item me-1">
                            <a class="nav-link @if($activeTab === 'history') active @endif" href="{{ route('admin.updates.history') }}">
                                <i class="fa fa-fw fa-history me-1"></i> Update History
                            </a>
                        </li>
                        <li class="nav-item me-1">
                            <a class="nav-link @if($activeTab === 'health') active @endif" href="{{ route('admin.updates.health') }}">
                                <i class="fa fa-fw fa-heartbeat me-1"></i> System Health
                            </a>
                        </li>
                        <li class="nav-item me-1">
                            <a class="nav-link @if($activeTab === 'safety') active @endif" href="{{ route('admin.updates.safety') }}">
                                <i class="fa fa-fw fa-shield-alt me-1"></i> Safety Controls
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @if($activeTab === 'configuration') active @endif" href="{{ route('admin.updates.configuration') }}">
                                <i class="fa fa-fw fa-cogs me-1"></i> Configuration
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection