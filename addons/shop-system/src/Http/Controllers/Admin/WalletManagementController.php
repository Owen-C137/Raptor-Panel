<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Admin;

use Illuminate\Http\Request;
use PterodactylAddons\ShopSystem\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\UserWallet;
use PterodactylAddons\ShopSystem\Models\WalletTransaction;
use PterodactylAddons\ShopSystem\Services\WalletService;
use Pterodactyl\Models\User;

class WalletManagementController extends Controller
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Display all user wallets
     */
    public function index(Request $request)
    {
        $wallets = UserWallet::with(['user', 'transactions'])
            ->when($request->search, function ($query, $search) {
                return $query->whereHas('user', function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->paginate(25);

        return view('shop::admin.wallets.index', compact('wallets'));
    }

    /**
     * Display a specific user's wallet
     */
    public function show(User $user)
    {
        $wallet = $this->walletService->getWallet($user->id);
        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('shop::admin.wallets.show', compact('user', 'wallet', 'transactions'));
    }

    /**
     * Add credit to user wallet
     */
    public function addCredit(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255'
        ]);

        $wallet = $this->walletService->getWallet($user->id);
        $transaction = $this->walletService->addFunds(
            $wallet, 
            $request->amount, 
            $request->reason,
            'admin_credit'
        );

        return redirect()
            ->route('admin.shop.wallets.show', $user)
            ->with('success', "Added {$request->amount} credit to {$user->username}'s wallet");
    }

    /**
     * Deduct credit from user wallet
     */
    public function deductCredit(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255'
        ]);

        try {
            $wallet = $this->walletService->getWallet($user->id);
            $transaction = $this->walletService->deductFunds(
                $wallet, 
                $request->amount, 
                $request->reason,
                'admin_debit'
            );

            return redirect()
                ->route('admin.shop.wallets.show', $user)
                ->with('success', "Deducted {$request->amount} from {$user->username}'s wallet");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Display user wallet transactions
     */
    public function transactions(User $user)
    {
        $wallet = $this->walletService->getWallet($user->id);
        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('shop::admin.wallets.transactions', compact('user', 'wallet', 'transactions'));
    }
}
