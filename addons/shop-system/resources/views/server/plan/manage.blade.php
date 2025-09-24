@extends('shop::layouts.server')

@section('content')
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white">Plan Management</h1>
                <p class="text-neutral-300 mt-2">Manage your server's active plan, renewals, and billing information</p>
            </div>
            @if($isExpired)
                <div class="flex items-center space-x-2 px-4 py-2 bg-red-600/20 border border-red-600/50 rounded-lg">
                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <span class="text-red-300 font-medium">Plan Expired</span>
                </div>
            @elseif($isNearExpiration)
                <div class="flex items-center space-x-2 px-4 py-2 bg-yellow-600/20 border border-yellow-600/50 rounded-lg">
                    <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-yellow-300 font-medium">Expiring Soon</span>
                </div>
            @else
                <div class="flex items-center space-x-2 px-4 py-2 bg-green-600/20 border border-green-600/50 rounded-lg">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="text-green-300 font-medium">Active Plan</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Main Content Layout -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        <!-- Left Column - Plan Details & Resources -->
        <div class="xl:col-span-2 space-y-6">
            <!-- Plan Overview -->
            <div class="bg-neutral-800/50 backdrop-blur-sm border border-neutral-700/50 rounded-xl p-6">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-semibold text-white mb-2">{{ $plan->name }}</h2>
                        <p class="text-neutral-400">{{ $category->name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-neutral-400 text-sm">Order ID</p>
                        <p class="text-white font-mono">#{{ $currentOrder->id }}</p>
                    </div>
                </div>

                @if($plan->description)
                <div class="mb-6">
                    <h3 class="text-neutral-300 text-sm font-medium mb-3">Plan Description</h3>
                    <div class="bg-neutral-900/50 border border-neutral-700/50 rounded-lg p-4">
                        <p class="text-neutral-300 text-sm leading-relaxed">{!! nl2br(e($plan->description)) !!}</p>
                    </div>
                </div>
                @endif

                <!-- Plan Timeline -->
                <div class="border-t border-neutral-700/50 pt-6">
                    <h3 class="text-neutral-300 text-sm font-medium mb-4">Plan Timeline</h3>
                    <div class="relative">
                        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-neutral-700"></div>
                        
                        <div class="relative flex items-center mb-4">
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-white font-medium">Plan Created</p>
                                <p class="text-neutral-400 text-sm">{{ $currentOrder->created_at->format('M d, Y \a\t g:i A') }}</p>
                            </div>
                        </div>

                        @if($expirationDate)
                        <div class="relative flex items-center">
                            <div class="w-8 h-8 {{ $isExpired ? 'bg-red-600' : ($isNearExpiration ? 'bg-yellow-600' : 'bg-green-600') }} rounded-full flex items-center justify-center mr-4">
                                @if($isExpired)
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <p class="text-white font-medium">
                                    @if($isExpired) Plan Expired @else Plan Expires @endif
                                </p>
                                <p class="text-neutral-400 text-sm">{{ $expirationDate->format('M d, Y \a\t g:i A') }}</p>
                                @if(!$isExpired && $expirationDate->diffInDays() <= 7)
                                    <p class="text-yellow-400 text-xs mt-1">{{ $expirationDate->diffForHumans() }}</p>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Server Resources -->
            <div class="bg-neutral-800/50 backdrop-blur-sm border border-neutral-700/50 rounded-xl p-6">
                <h2 class="text-xl font-semibold text-white mb-6">Server Resources</h2>
                
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <div class="bg-neutral-900/50 border border-neutral-700/50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-neutral-400 text-sm">Memory</span>
                            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                            </svg>
                        </div>
                        <p class="text-white font-semibold">{{ number_format($server->memory / 1024, 1) }} GB</p>
                    </div>

                    <div class="bg-neutral-900/50 border border-neutral-700/50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-neutral-400 text-sm">Storage</span>
                            <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                            </svg>
                        </div>
                        <p class="text-white font-semibold">{{ number_format($server->disk / 1024, 1) }} GB</p>
                    </div>

                    <div class="bg-neutral-900/50 border border-neutral-700/50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-neutral-400 text-sm">CPU</span>
                            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="text-white font-semibold">{{ $server->cpu }}%</p>
                    </div>

                    <div class="bg-neutral-900/50 border border-neutral-700/50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-neutral-400 text-sm">Databases</span>
                            <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7"/>
                            </svg>
                        </div>
                        <p class="text-white font-semibold">{{ $server->database_limit ?? '∞' }}</p>
                    </div>

                    <div class="bg-neutral-900/50 border border-neutral-700/50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-neutral-400 text-sm">Allocations</span>
                            <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <p class="text-white font-semibold">{{ $server->allocation_limit ?? '∞' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Actions & Quick Links -->
        <div class="space-y-6">
            @if($isExpired || $isNearExpiration)
            <!-- Renewal Action -->
            <div class="bg-gradient-to-br from-green-600/10 to-emerald-600/10 border border-green-600/30 rounded-xl p-6">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-green-600/20 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white">
                        @if($isExpired) Renew Expired Plan @else Renew Plan @endif
                    </h3>
                </div>
                
                @if($renewalOptions)
                <form id="renewal-form" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-neutral-300 text-sm font-medium mb-2">Select Billing Cycle</label>
                        <select id="billing-cycle" name="billing_cycle" class="w-full bg-neutral-800/80 border border-neutral-600/50 text-white p-3 rounded-lg focus:border-green-500/50 focus:outline-none focus:ring-2 focus:ring-green-500/20 transition-all">
                            @foreach($renewalOptions as $cycle => $option)
                            <option value="{{ $cycle }}">
                                {{ $option['label'] }} - {{ $currencySymbol }}{{ number_format($option['price'], 2) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <button type="submit" class="w-full bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-[1.02] shadow-lg hover:shadow-green-500/25">
                        <span class="flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Renew Plan
                        </span>
                    </button>
                </form>
                @else
                <div class="bg-neutral-800/50 border border-neutral-700/50 rounded-lg p-4">
                    <p class="text-neutral-400 text-sm">No renewal options available for this plan.</p>
                </div>
                @endif
            </div>
            @endif

            <!-- Plan Management Actions -->
            <div class="bg-neutral-800/50 backdrop-blur-sm border border-neutral-700/50 rounded-xl p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-blue-600/20 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white">Plan Management</h3>
                </div>
                
                <div class="space-y-3">
                    @if(!$isExpired)
                    <button 
                        id="cancel-plan-btn" 
                        class="w-full bg-red-600/20 hover:bg-red-600/30 border border-red-600/50 hover:border-red-500/50 text-red-300 hover:text-red-200 font-medium py-3 px-4 rounded-lg transition-all duration-200"
                    >
                        <span class="flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Cancel Plan
                        </span>
                    </button>
                    @endif
                    
                    <a 
                        href="/shop/orders?server={{ $server->uuidShort }}" 
                        class="block w-full bg-blue-600/20 hover:bg-blue-600/30 border border-blue-600/50 hover:border-blue-500/50 text-blue-300 hover:text-blue-200 font-medium py-3 px-4 rounded-lg text-center transition-all duration-200"
                    >
                        <span class="flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            View Order History
                        </span>
                    </a>
                    
                    <a 
                        href="/shop" 
                        class="block w-full bg-cyan-600/20 hover:bg-cyan-600/30 border border-cyan-600/50 hover:border-cyan-500/50 text-cyan-300 hover:text-cyan-200 font-medium py-3 px-4 rounded-lg text-center transition-all duration-200"
                    >
                        <span class="flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            Browse Other Plans
                        </span>
                    </a>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="bg-neutral-800/50 backdrop-blur-sm border border-neutral-700/50 rounded-xl p-6">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-purple-600/20 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white">Quick Stats</h3>
                </div>
                
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-neutral-400">Plan Duration</span>
                        <span class="text-white font-medium">
                            {{ $currentOrder->created_at->diffForHumans($expirationDate ?? now(), true) }}
                        </span>
                    </div>
                    @if($expirationDate && !$isExpired)
                    <div class="flex justify-between items-center">
                        <span class="text-neutral-400">Days Remaining</span>
                        <span class="text-white font-medium">
                            {{ $expirationDate->diffInDays() }} days
                        </span>
                    </div>
                    @endif
                    <div class="flex justify-between items-center">
                        <span class="text-neutral-400">Server Status</span>
                        <span class="px-2 py-1 bg-green-600/20 text-green-300 text-xs rounded-full font-medium">
                            Online
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Plan Modal -->
    <div id="cancel-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-neutral-800 border border-neutral-700 rounded-xl p-6 max-w-md mx-4 w-full">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 bg-red-600/20 rounded-lg flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-white">Cancel Plan</h3>
            </div>
            
            <div class="mb-6">
                <div class="bg-red-600/10 border border-red-600/30 rounded-lg p-4 mb-4">
                    <p class="text-red-300 text-sm">
                        <strong>Warning:</strong> This action cannot be undone. Your server will be deleted at the end of the billing period.
                    </p>
                </div>
                <p class="text-neutral-300">
                    Are you sure you want to cancel this plan? Please provide a reason for cancellation:
                </p>
            </div>

            <form id="cancel-form" class="space-y-4">
                @csrf
                <textarea 
                    name="reason" 
                    class="w-full bg-neutral-900 border border-neutral-700 text-white p-3 rounded-lg focus:border-red-500/50 focus:outline-none focus:ring-2 focus:ring-red-500/20 transition-all"
                    rows="3"
                    placeholder="Optional: Tell us why you're cancelling..."
                ></textarea>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" id="cancel-modal-close" class="px-4 py-2 bg-neutral-700 hover:bg-neutral-600 text-neutral-300 hover:text-white rounded-lg transition-colors">
                        Keep Plan
                    </button>
                    <button type="submit" id="confirm-cancel" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                        Cancel Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    // Notification System
    function showNotification(type, message) {
        // Remove existing notifications
        const existing = document.querySelectorAll('.notification-toast');
        existing.forEach(n => n.remove());
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification-toast fixed top-4 right-4 max-w-sm p-4 rounded-lg shadow-lg z-50 transform translate-x-0 transition-all duration-300 ${
            type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'
        }`;
        
        notification.innerHTML = `
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    ${type === 'success' 
                        ? '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>'
                        : '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>'
                    }
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium">${message}</p>
                </div>
                <div class="ml-4 flex-shrink-0">
                    <button onclick="this.closest('.notification-toast').remove()" class="text-white/80 hover:text-white">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    // Plan Renewal
    document.getElementById('renewal-form')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const billingCycle = document.getElementById('billing-cycle').value;
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="flex items-center justify-center"><svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Processing...</span>';
        
        fetch(window.location.pathname + '/renew', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify({
                billing_cycle: billingCycle
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showNotification('success', 'Renewal successful! Redirecting to checkout...');
                setTimeout(() => {
                    window.location.href = data.checkout_url || '/shop/checkout';
                }, 2000);
            } else {
                throw new Error(data.message || 'Renewal failed');
            }
        })
        .catch(error => {
            console.error('Renewal error:', error);
            showNotification('error', 'Error: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });

    // Cancel Plan Modal
    const cancelBtn = document.getElementById('cancel-plan-btn');
    const cancelModal = document.getElementById('cancel-modal');
    const closeModalBtn = document.getElementById('cancel-modal-close');
    const cancelForm = document.getElementById('cancel-form');

    cancelBtn?.addEventListener('click', () => {
        cancelModal.classList.remove('hidden');
        cancelModal.classList.add('flex');
    });

    closeModalBtn?.addEventListener('click', () => {
        cancelModal.classList.add('hidden');
        cancelModal.classList.remove('flex');
    });

    cancelModal?.addEventListener('click', (e) => {
        if (e.target === cancelModal) {
            cancelModal.classList.add('hidden');
            cancelModal.classList.remove('flex');
        }
    });

    // Handle plan cancellation
    cancelForm?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const reason = this.querySelector('textarea[name="reason"]').value;
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Cancelling...';
        
        fetch(window.location.pathname + '/cancel', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify({
                reason: reason,
                confirm: true
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success notification
                showNotification('success', data.message || 'Plan cancelled successfully.');
                
                // Close modal
                cancelModal.classList.add('hidden');
                cancelModal.classList.remove('flex');
                
                // Redirect after a short delay to show the notification
                setTimeout(() => {
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else {
                        // Fallback: redirect to main panel
                        window.location.href = '/';
                    }
                }, 2000);
            } else {
                throw new Error(data.error || data.message || 'Cancellation failed');
            }
        })
        .catch(error => {
            console.error('Cancellation error:', error);
            showNotification('error', 'Error: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
</script>
@endsection