@extends('shop::layout')

@section('title', 'Renew Server Plan')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <div class="text-center mb-6">
                <div class="text-4xl mb-4">🔄</div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Renew Server Plan</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">
                    Server: <strong>{{ $server->uuidShort }}</strong>
                </p>
            </div>

            @if($timeRemaining !== null && $timeRemaining <= 0)
                <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-300 px-4 py-3 rounded mb-6">
                    <div class="flex items-center">
                        <span class="text-xl mr-2">⚠️</span>
                        <div>
                            <strong>Critical: Server Expired</strong>
                            <p class="text-sm mt-1">This server will be deleted soon. Renew immediately to prevent data loss.</p>
                        </div>
                    </div>
                </div>
            @elseif($timeRemaining !== null)
                <div class="bg-yellow-100 dark:bg-yellow-900 border border-yellow-400 text-yellow-700 dark:text-yellow-300 px-4 py-3 rounded mb-6">
                    <div class="flex items-center">
                        <span class="text-xl mr-2">⏰</span>
                        <div>
                            <strong>Auto-Deletion Warning</strong>
                            <p class="text-sm mt-1">
                                This server will be permanently deleted in {{ $timeRemaining }} day(s) if not renewed.
                                All data will be lost and cannot be recovered.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Plan Details</h2>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Plan Name</span>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $plan->name }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Current Status</span>
                            <p class="font-medium">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                    Cancelled
                                </span>
                            </p>
                        </div>
                    </div>
                    @if($plan->description)
                        <div class="mt-4">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Description</span>
                            <p class="text-gray-900 dark:text-white">{{ $plan->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <form id="renewal-form" method="POST" action="{{ route('shop.renewal.process') }}">
                @csrf
                <input type="hidden" name="order_id" value="{{ $order->id }}">
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Select Renewal Period
                    </label>
                    <div class="space-y-3">
                        <label class="flex items-center justify-between p-4 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                            <div class="flex items-center">
                                <input type="radio" name="billing_cycle" value="monthly" class="text-blue-600" checked>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">1 Month</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Billed monthly</div>
                                </div>
                            </div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                ${{ number_format($plan->price, 2) }}
                            </div>
                        </label>

                        <label class="flex items-center justify-between p-4 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                            <div class="flex items-center">
                                <input type="radio" name="billing_cycle" value="quarterly" class="text-blue-600">
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">3 Months</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Save on longer commitment</div>
                                </div>
                            </div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                ${{ number_format($plan->price * 3, 2) }}
                                <span class="text-sm text-green-600 dark:text-green-400 ml-1">(Best Value)</span>
                            </div>
                        </label>

                        <label class="flex items-center justify-between p-4 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                            <div class="flex items-center">
                                <input type="radio" name="billing_cycle" value="annually" class="text-blue-600">
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">12 Months</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Maximum savings</div>
                                </div>
                            </div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                ${{ number_format($plan->price * 12, 2) }}
                                <span class="text-sm text-green-600 dark:text-green-400 ml-1">(Most Savings)</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-6 rounded-lg transition-colors">
                        <span class="flex items-center justify-center">
                            <span class="mr-2">🔄</span>
                            Renew Plan
                        </span>
                    </button>
                    <a href="{{ route('shop.index') }}" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-6 rounded-lg transition-colors text-center">
                        <span class="flex items-center justify-center">
                            <span class="mr-2">🛒</span>
                            Buy New Plan
                        </span>
                    </a>
                </div>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Questions about renewal? <a href="#" class="text-blue-600 hover:text-blue-500">Contact Support</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('renewal-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<span class="flex items-center justify-center"><span class="animate-spin mr-2">⏳</span>Processing...</span>';
    submitBtn.disabled = true;
    
    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect_url;
        } else {
            alert('Error: ' + (data.message || 'Something went wrong'));
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});
</script>
@endsection