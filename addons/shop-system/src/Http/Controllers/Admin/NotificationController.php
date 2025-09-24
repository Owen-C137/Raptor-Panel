<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PterodactylAddons\ShopSystem\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Models\ShopPayment;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Get pending notifications for the admin
     */
    public function getPending(Request $request)
    {
        $user = $request->user();
        
        // Get unread notifications
        $notifications = $user->unreadNotifications()
            ->where('type', 'like', '%Shop%')
            ->limit(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'created_at' => $notification->created_at->diffForHumans(),
                    'message' => $this->formatNotificationMessage($notification)
                ];
            });

        // Also get recent system notifications
        $systemNotifications = $this->getSystemNotifications();
        
        return response()->json([
            'notifications' => $notifications,
            'system' => $systemNotifications,
            'total_count' => $notifications->count() + $systemNotifications->count()
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $notificationId)
    {
        $user = $request->user();
        
        $notification = $user->notifications()->find($notificationId);
        
        if (!$notification) {
            return response()->json(['error' => 'Notification not found'], 404);
        }
        
        $notification->markAsRead();
        
        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        
        $user->unreadNotifications()
            ->where('type', 'like', '%Shop%')
            ->update(['read_at' => now()]);
        
        return response()->json(['success' => true]);
    }

    /**
     * Get system notifications (alerts about orders, payments, etc.)
     */
    private function getSystemNotifications()
    {
        $notifications = [];
        
        // Check for pending payments
        $pendingPayments = ShopPayment::where('status', 'pending')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
            
        if ($pendingPayments > 0) {
            $notifications[] = [
                'id' => 'pending_payments',
                'type' => 'warning',
                'message' => "You have {$pendingPayments} pending payment(s) in the last 24 hours",
                'action_url' => route('admin.shop.payments.index', ['status' => 'pending']),
                'action_text' => 'View Payments',
                'created_at' => 'System'
            ];
        }
        
        // Check for failed payments
        $failedPayments = ShopPayment::where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
            
        if ($failedPayments > 0) {
            $notifications[] = [
                'id' => 'failed_payments',
                'type' => 'error',
                'message' => "You have {$failedPayments} failed payment(s) in the last 24 hours",
                'action_url' => route('admin.shop.payments.index', ['status' => 'failed']),
                'action_text' => 'View Payments',
                'created_at' => 'System'
            ];
        }
        
        // Check for orders requiring attention
        $ordersNeedingAttention = ShopOrder::where('status', 'processing')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
            
        if ($ordersNeedingAttention > 0) {
            $notifications[] = [
                'id' => 'orders_processing',
                'type' => 'info',
                'message' => "You have {$ordersNeedingAttention} order(s) in processing status",
                'action_url' => route('admin.shop.orders.index', ['status' => 'processing']),
                'action_text' => 'View Orders',
                'created_at' => 'System'
            ];
        }
        
        // Check for low stock products (if applicable)
        $lowStockProducts = ShopOrder::selectRaw('product_id, COUNT(*) as order_count')
            ->where('status', 'active')
            ->groupBy('product_id')
            ->havingRaw('COUNT(*) > 50') // Assuming 50+ active orders means high usage
            ->count();
            
        if ($lowStockProducts > 0) {
            $notifications[] = [
                'id' => 'high_usage_products',
                'type' => 'warning',
                'message' => "{$lowStockProducts} product(s) have high usage - consider monitoring capacity",
                'action_url' => route('admin.shop.products.index'),
                'action_text' => 'View Products',
                'created_at' => 'System'
            ];
        }
        
        return collect($notifications);
    }

    /**
     * Format notification message based on type
     */
    private function formatNotificationMessage($notification)
    {
        $data = $notification->data;
        
        switch ($notification->type) {
            case 'App\\Notifications\\ShopNewOrder':
                return "New order #{$data['order_id']} from {$data['user_email']}";
                
            case 'App\\Notifications\\ShopPaymentReceived':
                return "Payment received for order #{$data['order_id']} - {$data['amount']}";
                
            case 'App\\Notifications\\ShopPaymentFailed':
                return "Payment failed for order #{$data['order_id']}";
                
            case 'App\\Notifications\\ShopOrderCancelled':
                return "Order #{$data['order_id']} was cancelled";
                
            case 'App\\Notifications\\ShopRefundProcessed':
                return "Refund processed for order #{$data['order_id']} - {$data['amount']}";
                
            default:
                return $data['message'] ?? 'New shop notification';
        }
    }

    /**
     * Get notification statistics
     */
    public function getStats(Request $request)
    {
        $user = $request->user();
        
        $stats = [
            'unread_count' => $user->unreadNotifications()
                ->where('type', 'like', '%Shop%')
                ->count(),
            'total_count' => $user->notifications()
                ->where('type', 'like', '%Shop%')
                ->count(),
            'recent_count' => $user->notifications()
                ->where('type', 'like', '%Shop%')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
        ];
        
        return response()->json($stats);
    }

    /**
     * Clear old notifications
     */
    public function clearOld(Request $request)
    {
        $user = $request->user();
        $days = $request->get('days', 30);
        
        $deleted = $user->notifications()
            ->where('type', 'like', '%Shop%')
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
        
        Log::info("Cleared {$deleted} old shop notifications for user {$user->id}");
        
        return response()->json([
            'success' => true,
            'deleted_count' => $deleted
        ]);
    }

    /**
     * Send test notification
     */
    public function sendTest(Request $request)
    {
        $user = $request->user();
        
        $user->notify(new \Illuminate\Notifications\Messages\DatabaseMessage([
            'title' => 'Test Shop Notification',
            'message' => 'This is a test notification from the shop system',
            'type' => 'test',
            'created_at' => now()
        ]));
        
        return response()->json(['success' => true]);
    }
}
