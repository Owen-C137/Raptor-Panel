<?php

namespace PterodactylAddons\ShopSystem\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use PterodactylAddons\ShopSystem\Models\WalletTransaction;

class WalletFundsAddedMail extends Mailable
{
    use Queueable, SerializesModels;

    public WalletTransaction $transaction;

    /**
     * Create a new message instance.
     */
    public function __construct(WalletTransaction $transaction)
    {
        $this->transaction = $transaction;
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
            ->subject('Wallet Funds Added - ' . config('shop.currency.symbol', '$') . number_format($this->transaction->amount, 2))
            ->view('shop::emails.wallet-funds-added')
            ->with([
                'transaction' => $this->transaction,
                'user' => $this->transaction->wallet->user,
                'wallet' => $this->transaction->wallet,
            ]);
    }
}
