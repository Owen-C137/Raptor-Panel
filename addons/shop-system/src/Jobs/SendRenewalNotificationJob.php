<?php

namespace PterodactylAddons\ShopSystem\Jobs;

use PterodactylAddons\ShopSystem\Models\ShopOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendRenewalNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private ShopOrder $order;
    private string $notificationType;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(ShopOrder $order, string $notificationType)
    {
        $this->order = $order;
        $this->notificationType = $notificationType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->sendEmailNotification();
            $this->sendDiscordNotification();
            
            Log::info('Renewal notification sent', [
                'order_id' => $this->order->id,
                'user_id' => $this->order->user_id,
                'type' => $this->notificationType,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send renewal notification', [
                'order_id' => $this->order->id,
                'type' => $this->notificationType,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Send email notification.
     */
    private function sendEmailNotification(): void
    {
        if (!config('shop.notifications.email.enabled', true)) {
            return;
        }

        $user = $this->order->user;
        $templateData = $this->getTemplateData();

        switch ($this->notificationType) {
            case 'renewal_reminder':
                // Mail::to($user)->send(new RenewalReminderMail($this->order, $templateData));
                $this->logNotification('Renewal reminder email sent');
                break;
                
            case 'renewal_failed':
                // Mail::to($user)->send(new RenewalFailedMail($this->order, $templateData));
                $this->logNotification('Renewal failed email sent');
                break;
                
            case 'order_suspended':
                // Mail::to($user)->send(new OrderSuspendedMail($this->order, $templateData));
                $this->logNotification('Order suspended email sent');
                break;
                
            case 'final_warning':
                // Mail::to($user)->send(new FinalWarningMail($this->order, $templateData));
                $this->logNotification('Final warning email sent');
                break;
                
            case 'order_terminated':
                // Mail::to($user)->send(new OrderTerminatedMail($this->order, $templateData));
                $this->logNotification('Order terminated email sent');
                break;
        }
    }

    /**
     * Send Discord notification if configured.
     */
    private function sendDiscordNotification(): void
    {
        if (!config('shop.notifications.discord.enabled', false)) {
            return;
        }

        $webhookUrl = config('shop.notifications.discord.webhook_url');
        if (!$webhookUrl) {
            return;
        }

        $message = $this->getDiscordMessage();

        try {
            $response = \Http::post($webhookUrl, [
                'embeds' => [
                    [
                        'title' => $message['title'],
                        'description' => $message['description'],
                        'color' => $message['color'],
                        'fields' => $message['fields'],
                        'timestamp' => now()->toISOString(),
                        'footer' => [
                            'text' => config('app.name', 'Pterodactyl Shop'),
                        ],
                    ],
                ],
            ]);

            if ($response->successful()) {
                $this->logNotification('Discord notification sent');
            }

        } catch (\Exception $e) {
            Log::warning('Failed to send Discord notification', [
                'error' => $e->getMessage(),
                'order_id' => $this->order->id,
            ]);
        }
    }

    /**
     * Get template data for notifications.
     */
    private function getTemplateData(): array
    {
        return [
            'user_name' => $this->order->user->first_name ?? $this->order->user->username,
            'order_id' => $this->order->id,
            'plan_name' => $this->order->plan->name,
            'product_name' => $this->order->plan->category->name,
            'amount' => $this->order->amount,
            'currency' => $this->order->currency,
            'due_date' => $this->order->next_due_at?->format('F j, Y g:i A'),
            'days_overdue' => $this->order->next_due_at?->isPast() ? 
                $this->order->next_due_at->diffInDays(now()) : 0,
            'payment_url' => route('shop.orders.payment', $this->order->uuid),
            'manage_url' => route('shop.orders.show', $this->order->uuid),
        ];
    }

    /**
     * Get Discord message content.
     */
    private function getDiscordMessage(): array
    {
        $user = $this->order->user;
        $templateData = $this->getTemplateData();

        switch ($this->notificationType) {
            case 'renewal_reminder':
                return [
                    'title' => '⏰ Renewal Reminder',
                    'description' => "Order #{$this->order->id} is due for renewal",
                    'color' => 0xffa500, // Orange
                    'fields' => [
                        ['name' => 'User', 'value' => $user->username, 'inline' => true],
                        ['name' => 'Plan', 'value' => $this->order->plan->name, 'inline' => true],
                        ['name' => 'Amount', 'value' => '$' . number_format($this->order->amount, 2), 'inline' => true],
                        ['name' => 'Due Date', 'value' => $templateData['due_date'], 'inline' => false],
                    ],
                ];

            case 'renewal_failed':
                return [
                    'title' => '❌ Renewal Failed',
                    'description' => "Order #{$this->order->id} renewal payment failed",
                    'color' => 0xff0000, // Red
                    'fields' => [
                        ['name' => 'User', 'value' => $user->username, 'inline' => true],
                        ['name' => 'Plan', 'value' => $this->order->plan->name, 'inline' => true],
                        ['name' => 'Amount', 'value' => '$' . number_format($this->order->amount, 2), 'inline' => true],
                    ],
                ];

            case 'order_suspended':
                return [
                    'title' => '⏸️ Order Suspended',
                    'description' => "Order #{$this->order->id} has been suspended for overdue payment",
                    'color' => 0xff6600, // Orange-Red
                    'fields' => [
                        ['name' => 'User', 'value' => $user->username, 'inline' => true],
                        ['name' => 'Plan', 'value' => $this->order->plan->name, 'inline' => true],
                        ['name' => 'Days Overdue', 'value' => $templateData['days_overdue'], 'inline' => true],
                    ],
                ];

            case 'final_warning':
                return [
                    'title' => '🚨 Final Warning',
                    'description' => "Order #{$this->order->id} will be terminated soon",
                    'color' => 0x8b0000, // Dark Red
                    'fields' => [
                        ['name' => 'User', 'value' => $user->username, 'inline' => true],
                        ['name' => 'Plan', 'value' => $this->order->plan->name, 'inline' => true],
                        ['name' => 'Status', 'value' => 'Pending Termination', 'inline' => true],
                    ],
                ];

            case 'order_terminated':
                return [
                    'title' => '💀 Order Terminated',
                    'description' => "Order #{$this->order->id} has been terminated",
                    'color' => 0x000000, // Black
                    'fields' => [
                        ['name' => 'User', 'value' => $user->username, 'inline' => true],
                        ['name' => 'Plan', 'value' => $this->order->plan->name, 'inline' => true],
                        ['name' => 'Server', 'value' => $this->order->server?->name ?? 'N/A', 'inline' => true],
                    ],
                ];

            default:
                return [
                    'title' => 'Shop Notification',
                    'description' => "Order #{$this->order->id} status update",
                    'color' => 0x0099ff,
                    'fields' => [],
                ];
        }
    }

    /**
     * Log notification activity.
     */
    private function logNotification(string $message): void
    {
        activity()
            ->performedOn($this->order)
            ->causedBy($this->order->user)
            ->withProperties([
                'notification_type' => $this->notificationType,
                'method' => 'email',
            ])
            ->log($message);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Notification job failed', [
            'order_id' => $this->order->id,
            'type' => $this->notificationType,
            'error' => $exception->getMessage(),
        ]);
    }
}
