<!DOCTYPE htm    <!-- TailwindCSS - Use CDN for compatibility -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'neutral': {
                            50: '#fafafa',
                            100: '#f5f5f5',
                            200: '#e5e5e5',
                            300: '#d4d4d8',
                            400: '#a1a1aa',
                            500: '#71717a',
                            600: '#52525b',
                            700: '#3f3f46',
                            800: '#27272a',
                            900: '#18181b',
                        }
                    }
                }
            }
        }
    </script>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Pterodactyl') }} - Server Plan Management</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Rubik:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    
    <!-- TailwindCSS - Use CDN for compatibility -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'neutral': {
                            50: '#fafafa',
                            100: '#f5f5f5',
                            200: '#e5e5e5',
                            300: '#d4d4d8',
                            400: '#a1a1aa',
                            500: '#71717a',
                            600: '#52525b',
                            700: '#3f3f46',
                            800: '#27272a',
                            900: '#18181b',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Rubik', sans-serif;
        }
        
        .font-mono {
            font-family: 'IBM Plex Mono', monospace;
        }
    </style>
    
    @yield('head')
</head>
<body class="bg-neutral-900 min-h-screen">>
    <!-- Navigation Bar -->
    <div class="w-full bg-neutral-900 shadow-md">
        <div class="mx-auto w-full flex items-center h-[3.5rem] max-w-[1200px]">
            <div class="flex-1">
                <a href="/" class="text-2xl font-header px-4 no-underline text-neutral-200 hover:text-neutral-100 transition-colors duration-150">
                    {{ config('app.name', 'Pterodactyl') }}
                </a>
            </div>
            <div class="flex h-full items-center justify-center">
                <a href="/server/{{ $server->uuidShort }}" class="flex items-center h-full no-underline text-neutral-300 px-6 cursor-pointer transition-all duration-150 hover:text-neutral-100 hover:bg-black">
                    <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="arrow-left" class="svg-inline--fa fa-arrow-left" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" style="width: 1em; height: 1em;">
                        <path fill="currentColor" d="M257.5 445.1l-22.2 22.2c-9.4 9.4-24.6 9.4-33.9 0L7 273c-9.4-9.4-9.4-24.6 0-33.9L201.4 44.7c9.4-9.4 24.6-9.4 33.9 0l22.2 22.2c9.3 9.4 9.3 24.5-.1 33.9L136.5 216H424c13.3 0 24 10.7 24 24v32c0 13.3-10.7 24-24 24H136.5l120.9 115.2c9.4 9.4 9.4 24.5.1 33.9z"></path>
                    </svg>
                    <span class="ml-2">Back to Server</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Server Information Bar -->
    <div class="w-full bg-neutral-800">
        <div class="mx-auto w-full max-w-[1200px] py-4 px-4">
            <div class="flex items-center">
                <h1 class="text-2xl text-neutral-50 font-header font-normal">
                    {{ $server->name }}
                </h1>
                <span class="ml-4 px-2 py-1 bg-neutral-600 text-neutral-200 text-xs rounded uppercase">
                    {{ $server->uuidShort }}
                </span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="w-full min-h-screen">
        <div class="mx-auto w-full max-w-[1200px] py-6 px-4">
            @yield('content')
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    @yield('scripts')
</body>
</html>