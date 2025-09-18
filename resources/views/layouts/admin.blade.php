<!DOCTYPE html>
<html lang="en" class="remember-theme">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>{{ config('app.name', 'Pterodactyl') }} - @yield('title')</title>
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
        <meta name="_token" content="{{ csrf_token() }}">

        <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
        <link rel="icon" type="image/png" href="/favicons/favicon-32x32.png" sizes="32x32">
        <link rel="icon" type="image/png" href="/favicons/favicon-16x16.png" sizes="16x16">
        <link rel="manifest" href="/favicons/manifest.json">
        <link rel="mask-icon" href="/favicons/safari-pinned-tab.svg" color="#bc6e3c">
        <link rel="shortcut icon" href="/favicons/favicon.ico">
        <meta name="msapplication-config" content="/favicons/browserconfig.xml">
        <meta name="theme-color" content="#0e4688">

        @include('layouts.scripts')

        @section('scripts')
            {!! Theme::css('vendor/select2/select2.min.css?t={cache-version}') !!}
            {!! Theme::css('vendor/sweetalert/sweetalert.min.css?t={cache-version}') !!}
            {!! Theme::css('vendor/animate/animate.min.css?t={cache-version}') !!}
            <link rel="stylesheet" href="/themes/one-ui/css/oneui.min.css?t={cache-version}" type="text/css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">

            <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
            <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
            <![endif]-->
            
            <!-- Load theme handler script early to prevent theme flashing -->
            <script src="/themes/one-ui/js/setTheme.js?t={cache-version}" type="application/javascript"></script>
        @show
    </head>
    <body>
        <!-- Page Container -->
        <div id="page-container" class="sidebar-o sidebar-dark enable-page-overlay side-scroll page-header-fixed main-content-narrow">
            <header id="page-header">
                <div class="content-header">
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-sm btn-alt-secondary me-2 d-lg-none" data-toggle="layout" data-action="sidebar_toggle">
                            <i class="fa fa-fw fa-bars"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-alt-secondary d-md-none" data-toggle="layout" data-action="header_search_on">
                            <i class="fa fa-fw fa-search"></i>
                        </button>
                        <form class="d-none d-md-inline-block" action="#" method="POST">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control form-control-alt" placeholder="Search.." id="page-header-search-input2" name="page-header-search-input2">
                                <span class="input-group-text border-0">
                                    <i class="fa fa-fw fa-search"></i>
                                </span>
                            </div>
                        </form>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="dropdown d-inline-block ms-2">
                            <button type="button" class="btn btn-sm btn-alt-secondary d-flex align-items-center" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <img class="rounded-circle" src="https://www.gravatar.com/avatar/{{ md5(strtolower(Auth::user()->email)) }}?s=160" alt="Header Avatar" style="width: 21px;">
                                <span class="d-none d-sm-inline-block ms-2">{{ Auth::user()->name_first }} {{ Auth::user()->name_last }}</span>
                                <i class="fa fa-fw fa-angle-down d-none d-sm-inline-block opacity-50 ms-1 mt-1"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-md dropdown-menu-end p-0 border-0" aria-labelledby="page-header-user-dropdown">
                                <div class="p-3 text-center bg-body-light border-bottom rounded-top">
                                    <img class="img-avatar img-avatar48 img-avatar-thumb" src="https://www.gravatar.com/avatar/{{ md5(strtolower(Auth::user()->email)) }}?s=160" alt="">
                                    <p class="mt-2 mb-0 fw-medium">{{ Auth::user()->name_first }} {{ Auth::user()->name_last }}</p>
                                    <p class="mb-0 text-muted fs-sm fw-medium">Administrator</p>
                                </div>
                                <div class="p-2">
                                    <a class="dropdown-item d-flex align-items-center justify-content-between" href="{{ route('account') }}">
                                        <span class="fs-sm fw-medium">Account</span>
                                    </a>
                                </div>
                                <div role="separator" class="dropdown-divider m-0"></div>
                                <div class="p-2">
                                    <a class="dropdown-item d-flex align-items-center justify-content-between" href="{{ route('index') }}">
                                        <span class="fs-sm fw-medium">Exit Admin Control</span>
                                    </a>
                                    <a class="dropdown-item d-flex align-items-center justify-content-between" href="{{ route('auth.logout') }}" id="logoutButton">
                                        <span class="fs-sm fw-medium">Log Out</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-alt-secondary ms-2" data-toggle="layout" data-action="side_overlay_toggle">
                            <i class="fa fa-fw fa-list-ul fa-flip-horizontal"></i>
                        </button>
                    </div>
                </div>
                <div id="page-header-search" class="overlay-header bg-body-extra-light">
                    <div class="content-header">
                        <form class="w-100" action="#" method="POST">
                            <div class="input-group">
                                <button type="button" class="btn btn-alt-danger" data-toggle="layout" data-action="header_search_off">
                                    <i class="fa fa-fw fa-times-circle"></i>
                                </button>
                                <input type="text" class="form-control" placeholder="Search or hit ESC.." id="page-header-search-input" name="page-header-search-input">
                            </div>
                        </form>
                    </div>
                </div>
                <div id="page-header-loader" class="overlay-header bg-body-extra-light">
                    <div class="content-header">
                        <div class="w-100 text-center">
                            <i class="fa fa-fw fa-circle-notch fa-spin"></i>
                        </div>
                    </div>
                </div>
            </header>
            
            <nav id="sidebar" aria-label="Main Navigation">
                <div class="content-header">
                    <a class="fw-semibold text-dual" href="{{ route('index') }}">
                        <span class="smini-visible">
                            <i class="fa fa-circle-notch text-primary"></i>
                        </span>
                        <span class="smini-hide fs-5 tracking-wider d-inline-flex align-items-center">
                            <img src="{{ asset('themes/one-ui/media/blue_raptor.png') }}" 
                                alt="Logo" 
                                class="me-2" 
                                style="height: 24px; width: auto;">
                            {{ config('app.name', 'Pterodactyl') }}
                        </span>
                    </a>
                    <div class="d-flex align-items-center gap-1">
                        <div class="dropdown">
                            <button type="button" class="btn btn-sm btn-alt-secondary" id="sidebar-dark-mode-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-fw fa-moon" data-dark-mode-icon=""></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end smini-hide border-0" aria-labelledby="sidebar-dark-mode-dropdown">
                                <button type="button" class="dropdown-item d-flex align-items-center gap-2" data-toggle="layout" data-action="dark_mode_off" data-dark-mode="off">
                                    <i class="far fa-sun fa-fw opacity-50"></i>
                                    <span class="fs-sm fw-medium">Light</span>
                                </button>
                                <button type="button" class="dropdown-item d-flex align-items-center gap-2" data-toggle="layout" data-action="dark_mode_on" data-dark-mode="on">
                                    <i class="far fa-moon fa-fw opacity-50"></i>
                                    <span class="fs-sm fw-medium">Dark</span>
                                </button>
                                <button type="button" class="dropdown-item d-flex align-items-center gap-2" data-toggle="layout" data-action="dark_mode_system" data-dark-mode="system">
                                    <i class="fa fa-desktop fa-fw opacity-50"></i>
                                    <span class="fs-sm fw-medium">System</span>
                                </button>
                            </div>
                        </div>
                        <div class="dropdown">
                            <button type="button" class="btn btn-sm btn-alt-secondary" id="sidebar-themes-dropdown" data-bs-auto-close="outside" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-fw fa-brush"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end fs-sm smini-hide border-0" aria-labelledby="sidebar-themes-dropdown">
                                <button class="dropdown-item d-flex align-items-center justify-content-between fw-medium active" data-toggle="theme" data-theme="default">
                                    <span>Default</span>
                                    <i class="fa fa-circle text-default"></i>
                                </button>
                                <button class="dropdown-item d-flex align-items-center justify-content-between fw-medium" data-toggle="theme" data-theme="/themes/one-ui/css/themes/amethyst.min.css">
                                    <span>Amethyst</span>
                                    <i class="fa fa-circle text-amethyst"></i>
                                </button>
                                <button class="dropdown-item d-flex align-items-center justify-content-between fw-medium" data-toggle="theme" data-theme="/themes/one-ui/css/themes/city.min.css">
                                    <span>City</span>
                                    <i class="fa fa-circle text-city"></i>
                                </button>
                                <button class="dropdown-item d-flex align-items-center justify-content-between fw-medium" data-toggle="theme" data-theme="/themes/one-ui/css/themes/flat.min.css">
                                    <span>Flat</span>
                                    <i class="fa fa-circle text-flat"></i>
                                </button>
                                <button class="dropdown-item d-flex align-items-center justify-content-between fw-medium" data-toggle="theme" data-theme="/themes/one-ui/css/themes/modern.min.css">
                                    <span>Modern</span>
                                    <i class="fa fa-circle text-modern"></i>
                                </button>
                                <button class="dropdown-item d-flex align-items-center justify-content-between fw-medium" data-toggle="theme" data-theme="/themes/one-ui/css/themes/smooth.min.css">
                                    <span>Smooth</span>
                                    <i class="fa fa-circle text-smooth"></i>
                                </button>
                                <div class="dropdown-divider d-dark-none"></div>
                                <a class="dropdown-item fw-medium d-dark-none" data-toggle="layout" data-action="sidebar_style_light" href="javascript:void(0)">
                                    <span>Sidebar Light</span>
                                </a>
                                <a class="dropdown-item fw-medium d-dark-none" data-toggle="layout" data-action="sidebar_style_dark" href="javascript:void(0)">
                                    <span>Sidebar Dark</span>
                                </a>
                                <div class="dropdown-divider d-dark-none"></div>
                                <a class="dropdown-item fw-medium d-dark-none" data-toggle="layout" data-action="header_style_light" href="javascript:void(0)">
                                    <span>Header Light</span>
                                </a>
                                <a class="dropdown-item fw-medium d-dark-none" data-toggle="layout" data-action="header_style_dark" href="javascript:void(0)">
                                    <span>Header Dark</span>
                                </a>
                            </div>
                        </div>
                        <a class="d-lg-none btn btn-sm btn-alt-secondary ms-1" data-toggle="layout" data-action="sidebar_close" href="javascript:void(0)">
                            <i class="fa fa-fw fa-times"></i>
                        </a>
                    </div>
                </div>
                <div class="js-sidebar-scroll">
                    <div class="content-side">
                        <ul class="nav-main">
                            <li class="nav-main-heading">BASIC ADMINISTRATION</li>
                            <li class="nav-main-item {{ Route::currentRouteName() !== 'admin.index' ?: 'open' }}">
                                <a class="nav-main-link {{ Route::currentRouteName() !== 'admin.index' ?: 'active' }}" href="{{ route('admin.index') }}">
                                    <i class="nav-main-link-icon fa fa-home"></i>
                                    <span class="nav-main-link-name">Overview</span>
                                </a>
                            </li>
                            <li class="nav-main-item {{ ! starts_with(Route::currentRouteName(), 'admin.settings') ?: 'open' }}">
                                <a class="nav-main-link {{ ! starts_with(Route::currentRouteName(), 'admin.settings') ?: 'active' }}" href="{{ route('admin.settings')}}">
                                    <i class="nav-main-link-icon fa fa-wrench"></i>
                                    <span class="nav-main-link-name">Settings</span>
                                </a>
                            </li>
                            <li class="nav-main-item {{ ! starts_with(Route::currentRouteName(), 'admin.api') ?: 'open' }}">
                                <a class="nav-main-link {{ ! starts_with(Route::currentRouteName(), 'admin.api') ?: 'active' }}" href="{{ route('admin.api.index')}}">
                                    <i class="nav-main-link-icon fa fa-gamepad"></i>
                                    <span class="nav-main-link-name">Application API</span>
                                </a>
                            </li>
                            <li class="nav-main-heading">MANAGEMENT</li>
                            <li class="nav-main-item {{ ! starts_with(Route::currentRouteName(), 'admin.databases') ?: 'open' }}">
                                <a class="nav-main-link {{ ! starts_with(Route::currentRouteName(), 'admin.databases') ?: 'active' }}" href="{{ route('admin.databases') }}">
                                    <i class="nav-main-link-icon fa fa-database"></i>
                                    <span class="nav-main-link-name">Databases</span>
                                </a>
                            </li>
                            <li class="nav-main-item {{ ! starts_with(Route::currentRouteName(), 'admin.locations') ?: 'open' }}">
                                <a class="nav-main-link {{ ! starts_with(Route::currentRouteName(), 'admin.locations') ?: 'active' }}" href="{{ route('admin.locations') }}">
                                    <i class="nav-main-link-icon fa fa-globe"></i>
                                    <span class="nav-main-link-name">Locations</span>
                                </a>
                            </li>
                            <li class="nav-main-item {{ ! starts_with(Route::currentRouteName(), 'admin.nodes') ?: 'open' }}">
                                <a class="nav-main-link {{ ! starts_with(Route::currentRouteName(), 'admin.nodes') ?: 'active' }}" href="{{ route('admin.nodes') }}">
                                    <i class="nav-main-link-icon fa fa-sitemap"></i>
                                    <span class="nav-main-link-name">Nodes</span>
                                </a>
                            </li>
                            <li class="nav-main-item {{ ! starts_with(Route::currentRouteName(), 'admin.servers') ?: 'open' }}">
                                <a class="nav-main-link {{ ! starts_with(Route::currentRouteName(), 'admin.servers') ?: 'active' }}" href="{{ route('admin.servers') }}">
                                    <i class="nav-main-link-icon fa fa-server"></i>
                                    <span class="nav-main-link-name">Servers</span>
                                </a>
                            </li>
                            <li class="nav-main-item {{ ! starts_with(Route::currentRouteName(), 'admin.users') ?: 'open' }}">
                                <a class="nav-main-link {{ ! starts_with(Route::currentRouteName(), 'admin.users') ?: 'active' }}" href="{{ route('admin.users') }}">
                                    <i class="nav-main-link-icon fa fa-users"></i>
                                    <span class="nav-main-link-name">Users</span>
                                </a>
                            </li>
                            <li class="nav-main-heading">SERVICE MANAGEMENT</li>
                            <li class="nav-main-item {{ ! starts_with(Route::currentRouteName(), 'admin.mounts') ?: 'open' }}">
                                <a class="nav-main-link {{ ! starts_with(Route::currentRouteName(), 'admin.mounts') ?: 'active' }}" href="{{ route('admin.mounts') }}">
                                    <i class="nav-main-link-icon fa fa-magic"></i>
                                    <span class="nav-main-link-name">Mounts</span>
                                </a>
                            </li>
                            <li class="nav-main-item {{ ! starts_with(Route::currentRouteName(), 'admin.nests') ?: 'open' }}">
                                <a class="nav-main-link {{ ! starts_with(Route::currentRouteName(), 'admin.nests') ?: 'active' }}" href="{{ route('admin.nests') }}">
                                    <i class="nav-main-link-icon fa fa-th-large"></i>
                                    <span class="nav-main-link-name">Nests</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Main Container -->
            <main id="main-container" class="d-flex flex-column">
                <!-- Page Content Wrapper -->
                <div class="flex-fill">
                    <!-- Page Header -->
                    @yield('content-header')
                    
                    <!-- Page Content -->
                    <div class="content">
                        <div class="row">
                            <div class="col-12">
                                @if (count($errors) > 0)
                                    <div class="alert alert-danger">
                                        There was an error validating the data provided.<br><br>
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                @foreach (Alert::getMessages() as $type => $messages)
                                    @foreach ($messages as $message)
                                        <div class="alert alert-{{ $type }} alert-dismissible" role="alert">
                                            {!! $message !!}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                        @yield('content')
                    </div>
                </div>
                
                <!-- Footer - Sticky at bottom -->
                <footer id="page-footer" class="bg-body-light mt-auto">
                    <div class="content py-3">
                        <div class="row fs-sm">
                            <div class="col-sm-6 order-sm-2 py-1 text-center text-sm-end">
                                <strong><i class="fa fa-fw {{ $appIsGit ? 'fa-git-square' : 'fa-code-fork' }}"></i></strong> {{ $appVersion }}<br />
                                <strong><i class="fa fa-fw fa-clock-o"></i></strong> {{ round(microtime(true) - LARAVEL_START, 3) }}s
                            </div>
                            <div class="col-sm-6 order-sm-1 py-1 text-center text-sm-start">
                                Copyright &copy; 2015 - {{ date('Y') }} <a href="https://pterodactyl.io/">Pterodactyl Software</a>.
                            </div>
                        </div>
                    </div>
                </footer>
            </main>
        </div>
        @section('footer-scripts')
            <script src="/js/keyboard.polyfill.js" type="application/javascript"></script>
            <script>keyboardeventKeyPolyfill.polyfill();</script>


            {!! Theme::js('vendor/jquery/jquery.min.js?t={cache-version}') !!}
            {!! Theme::js('vendor/sweetalert/sweetalert.min.js?t={cache-version}') !!}
            {!! Theme::js('vendor/bootstrap/bootstrap.min.js?t={cache-version}') !!}
            {!! Theme::js('vendor/slimscroll/jquery.slimscroll.min.js?t={cache-version}') !!}
            {!! Theme::js('vendor/adminlte/app.min.js?t={cache-version}') !!}
            {!! Theme::js('vendor/bootstrap-notify/bootstrap-notify.min.js?t={cache-version}') !!}
            {!! Theme::js('vendor/select2/select2.full.min.js?t={cache-version}') !!}
            {!! Theme::js('js/admin/functions.js?t={cache-version}') !!}
            <script src="/themes/one-ui/js/oneui.app.min.js?t={cache-version}" type="application/javascript"></script>
            <script src="/js/autocomplete.js" type="application/javascript"></script>


            @if(Auth::user()->root_admin)
                <script>
                    $('#logoutButton').on('click', function (event) {
                        event.preventDefault();

                        var that = this;
                        swal({
                            title: 'Do you want to log out?',
                            type: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d9534f',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Log out'
                        }, function () {
                             $.ajax({
                                type: 'POST',
                                url: '{{ route('auth.logout') }}',
                                data: {
                                    _token: '{{ csrf_token() }}'
                                },complete: function () {
                                    window.location.href = '{{route('auth.login')}}';
                                }
                        });
                    });
                });
                </script>
            @endif

            <script>
                $(function () {
                    $('[data-toggle="tooltip"]').tooltip();
                })
            </script>
        @show
    </body>
</html>
