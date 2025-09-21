<div class="row">
    <div class="col-12">
        <div class="block block-rounded">
            <div class="block-content">
                <ul class="nav nav-pills push">
                    <li class="nav-item me-1">
                        <a class="nav-link @if(request()->routeIs('admin.nests.egg.view')) active @endif" href="{{ route('admin.nests.egg.view', $egg->id) }}">
                            <i class="fa fa-fw fa-cog me-1"></i> Configuration
                        </a>
                    </li>
                    <li class="nav-item me-1">
                        <a class="nav-link @if(request()->routeIs('admin.nests.egg.variables')) active @endif" href="{{ route('admin.nests.egg.variables', $egg->id) }}">
                            <i class="fa fa-fw fa-list me-1"></i> Variables
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('admin.nests.egg.scripts')) active @endif" href="{{ route('admin.nests.egg.scripts', $egg->id) }}">
                            <i class="fa fa-fw fa-code me-1"></i> Install Script
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>