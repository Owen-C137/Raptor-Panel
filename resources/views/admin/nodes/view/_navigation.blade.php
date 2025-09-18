{{-- Node View Navigation --}}
<div class="row">
    <div class="col-12">
        <div class="block block-rounded">
            <div class="block-content">
                <ul class="nav nav-pills push">
                    <li class="nav-item me-1">
                        <a class="nav-link {{ Route::currentRouteName() === 'admin.nodes.view' ? 'active' : '' }}" href="{{ route('admin.nodes.view', $node->id) }}">
                            <i class="fa fa-fw fa-home me-1"></i> About
                        </a>
                    </li>
                    <li class="nav-item me-1">
                        <a class="nav-link {{ Route::currentRouteName() === 'admin.nodes.view.settings' ? 'active' : '' }}" href="{{ route('admin.nodes.view.settings', $node->id) }}">
                            <i class="fa fa-fw fa-cog me-1"></i> Settings
                        </a>
                    </li>
                    <li class="nav-item me-1">
                        <a class="nav-link {{ Route::currentRouteName() === 'admin.nodes.view.configuration' ? 'active' : '' }}" href="{{ route('admin.nodes.view.configuration', $node->id) }}">
                            <i class="fa fa-fw fa-file-code me-1"></i> Configuration
                        </a>
                    </li>
                    <li class="nav-item me-1">
                        <a class="nav-link {{ Route::currentRouteName() === 'admin.nodes.view.allocation' ? 'active' : '' }}" href="{{ route('admin.nodes.view.allocation', $node->id) }}">
                            <i class="fas fa-fw fa-network-wired me-1"></i> Allocation
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ Route::currentRouteName() === 'admin.nodes.view.servers' ? 'active' : '' }}" href="{{ route('admin.nodes.view.servers', $node->id) }}">
                            <i class="fa fa-fw fa-server me-1"></i> Servers
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>