@include('partials/admin.settings.notice')

@section('settings::nav')
    @yield('settings::notice')
    <div class="row">
        <div class="col-12">
            <div class="block block-rounded">
                <div class="block-content">
                    <ul class="nav nav-pills push">
                        <li class="nav-item me-1">
                            <a class="nav-link @if($activeTab === 'basic') active @endif" href="{{ route('admin.settings') }}">
                                <i class="fa fa-fw fa-cog me-1"></i> General
                            </a>
                        </li>
                        <li class="nav-item me-1">
                            <a class="nav-link @if($activeTab === 'mail') active @endif" href="{{ route('admin.settings.mail') }}">
                                <i class="fa fa-fw fa-envelope me-1"></i> Mail
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @if($activeTab === 'advanced') active @endif" href="{{ route('admin.settings.advanced') }}">
                                <i class="fa fa-fw fa-wrench me-1"></i> Advanced
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
