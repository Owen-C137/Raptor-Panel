<?php

namespace PterodactylAddons\ShopSystem\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use PterodactylAddons\ShopSystem\Models\ShopOrder;

class PurchaseConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public ShopOrder $order;

    /**
     * Create a new message instance.
     */
    public function __construct(ShopOrder $order)
    {
        $this->order = $order;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->from(
                config('mail.from.address'),
                config('mail.from.name')
            )
            ->subject('Purchase Confirmation - Order #' . ($this->order->order_number ?? $this->order->id))
            ->view('shop::emails.purchase-confirmation')
            ->with([
                'order' => $this->order,
                'user' => $this->order->user,
                'server' => $this->order->server,
                'payment' => $this->order->payments()->latest()->first(),
            ]);
    }
}
