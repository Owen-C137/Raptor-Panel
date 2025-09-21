@php
    /** @var \Pterodactyl\Models\Server $server */
    $router = app('router');
@endphp
<div class="row">
    <div class="col-12">
        <div class="block block-rounded">
            <div class="block-content">
                <ul class="nav nav-pills push">
                    <li class="nav-item me-1">
                        <a class="nav-link {{ $router->currentRouteNamed('admin.servers.view') ? 'active' : '' }}" href="{{ route('admin.servers.view', $server->id) }}">
                            <i class="fa fa-fw fa-info-circle me-1"></i> About
                        </a>
                    </li>
                    @if($server->isInstalled())
                        <li class="nav-item me-1">
                            <a class="nav-link {{ $router->currentRouteNamed('admin.servers.view.details') ? 'active' : '' }}" href="{{ route('admin.servers.view.details', $server->id) }}">
                                <i class="fa fa-fw fa-list me-1"></i> Details
                            </a>
                        </li>
                        <li class="nav-item me-1">
                            <a class="nav-link {{ $router->currentRouteNamed('admin.servers.view.build') ? 'active' : '' }}" href="{{ route('admin.servers.view.build', $server->id) }}">
                                <i class="fa fa-fw fa-wrench me-1"></i> Build Configuration
                            </a>
                        </li>
                        <li class="nav-item me-1">
                            <a class="nav-link {{ $router->currentRouteNamed('admin.servers.view.startup') ? 'active' : '' }}" href="{{ route('admin.servers.view.startup', $server->id) }}">
                                <i class="fa fa-fw fa-play me-1"></i> Startup
                            </a>
                        </li>
                        <li class="nav-item me-1">
                            <a class="nav-link {{ $router->currentRouteNamed('admin.servers.view.database') ? 'active' : '' }}" href="{{ route('admin.servers.view.database', $server->id) }}">
                                <i class="fa fa-fw fa-database me-1"></i> Database
                            </a>
                        </li>
                        <li class="nav-item me-1">
                            <a class="nav-link {{ $router->currentRouteNamed('admin.servers.view.mounts') ? 'active' : '' }}" href="{{ route('admin.servers.view.mounts', $server->id) }}">
                                <i class="fa fa-fw fa-magic me-1"></i> Mounts
                            </a>
                        </li>
                    @endif
                    <li class="nav-item me-1">
                        <a class="nav-link {{ $router->currentRouteNamed('admin.servers.view.manage') ? 'active' : '' }}" href="{{ route('admin.servers.view.manage', $server->id) }}">
                            <i class="fa fa-fw fa-cogs me-1"></i> Manage
                        </a>
                    </li>
                    <li class="nav-item me-1">
                        <a class="nav-link text-danger {{ $router->currentRouteNamed('admin.servers.view.delete') ? 'active' : '' }}" href="{{ route('admin.servers.view.delete', $server->id) }}">
                            <i class="fa fa-fw fa-trash me-1"></i> Delete
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/server/{{ $server->uuidShort }}" target="_blank">
                            <i class="fa fa-fw fa-external-link"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
