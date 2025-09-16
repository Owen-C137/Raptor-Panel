@extends('shop::layouts.server')

@section('content')
    <!-- Page Content -->
    <div class="flex items-center justify-center min-h-[60vh]">
        <div class="max-w-2xl w-full mx-4">
            <!-- Main Card -->
            <div class="bg-neutral-800/50 backdrop-blur-sm border border-neutral-700/50 rounded-xl p-8 text-center">
                <!-- Icon -->
                <div class="w-20 h-20 bg-blue-600/20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                
                <!-- Title -->
                <h1 class="text-3xl font-bold text-white mb-4">No Active Plan</h1>
                <p class="text-neutral-300 text-lg mb-8 leading-relaxed">
                    This server doesn't have an active shop plan associated with it. Don't worry - this is completely normal and your server is working perfectly!
                </p>

                <!-- Information Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                    <div class="bg-neutral-900/50 border border-neutral-700/50 rounded-lg p-4">
                        <div class="w-12 h-12 bg-green-600/20 rounded-lg flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-white font-semibold mb-2">Server Created Manually</h3>
                        <p class="text-neutral-400 text-sm">Your server was created by an administrator outside of our shop system</p>
                    </div>

                    <div class="bg-neutral-900/50 border border-neutral-700/50 rounded-lg p-4">
                        <div class="w-12 h-12 bg-yellow-600/20 rounded-lg flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-white font-semibold mb-2">Plan Expired</h3>
                        <p class="text-neutral-400 text-sm">A previous shop plan may have expired or been cancelled</p>
                    </div>

                    <div class="bg-neutral-900/50 border border-neutral-700/50 rounded-lg p-4">
                        <div class="w-12 h-12 bg-purple-600/20 rounded-lg flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-white font-semibold mb-2">Custom Configuration</h3>
                        <p class="text-neutral-400 text-sm">This server has a custom setup that doesn't require shop management</p>
                    </div>
                </div>

                <!-- Current Server Info -->
                <div class="bg-neutral-900/30 border border-neutral-700/50 rounded-lg p-6 mb-8">
                    <h3 class="text-white font-semibold mb-4 flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12l5 5L20 7"/>
                        </svg>
                        Your Server is Active & Working
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                        <div class="text-center">
                            <p class="text-neutral-400">Server ID</p>
                            <p class="text-white font-mono">{{ $server->uuidShort }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-neutral-400">Memory</p>
                            <p class="text-white font-semibold">{{ number_format($server->memory / 1024, 1) }} GB</p>
                        </div>
                        <div class="text-center">
                            <p class="text-neutral-400">Storage</p>
                            <p class="text-white font-semibold">{{ number_format($server->disk / 1024, 1) }} GB</p>
                        </div>
                        <div class="text-center">
                            <p class="text-neutral-400">CPU</p>
                            <p class="text-white font-semibold">{{ $server->cpu }}%</p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a 
                        href="/shop" 
                        class="inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 text-white font-semibold rounded-lg transition-all duration-200 transform hover:scale-[1.02] shadow-lg hover:shadow-cyan-500/25"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        Explore Available Plans
                    </a>
                    
                    <a 
                        href="/server/{{ $server->uuidShort }}" 
                        class="inline-flex items-center justify-center px-8 py-4 bg-neutral-700 hover:bg-neutral-600 border border-neutral-600 hover:border-neutral-500 text-neutral-200 hover:text-white font-semibold rounded-lg transition-all duration-200"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back to Server Console
                    </a>
                </div>

                <!-- Help Text -->
                <div class="mt-8 pt-6 border-t border-neutral-700/50">
                    <p class="text-neutral-400 text-sm">
                        Need help? If you believe this server should have an active plan, please contact support with your server ID: 
                        <span class="text-white font-mono">{{ $server->uuidShort }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection