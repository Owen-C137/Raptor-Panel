<?php

namespace PterodactylAddons\ShopSystem\Commands;

use Illuminate\Console\Command;
use PterodactylAddons\ShopSystem\Models\UserWallet;
use PterodactylAddons\ShopSystem\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class FixWalletBalancesCommand extends Command
{
    /**
     * The signature of the command.
     */
    protected $signature = 'shop:fix-wallet-balances {--dry-run : Show what would be changed without making changes}';

    /**
     * The description of the command.
     */
    protected $description = 'Fix corrupted wallet balances by recalculating from transaction history';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('🛠️ <fg=cyan>Shop System: Wallet Balance Repair Tool</fg=cyan>');
        $this->line('');

        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        } else {
            $this->warn('⚠️  LIVE MODE - Wallet balances will be updated');
            if (!$this->confirm('Are you sure you want to proceed?')) {
                $this->error('Operation cancelled by user');
                return 1;
            }
        }

        $this->line('');

        // Get all wallets
        $wallets = UserWallet::with(['user', 'transactions' => function ($query) {
            $query->orderBy('created_at');
        }])->get();

        $this->info("📊 Found {$wallets->count()} wallets to process");
        $this->line('');

        $totalFixed = 0;
        $totalCorruption = 0;

        foreach ($wallets as $wallet) {
            $this->processWallet($wallet, $dryRun, $totalFixed, $totalCorruption);
        }

        $this->line('');
        $this->line('📈 <fg=cyan>SUMMARY</fg=cyan>');
        $this->line("   Wallets processed: {$wallets->count()}");
        $this->line("   Wallets fixed: {$totalFixed}");
        $this->line("   Total corruption detected: £" . number_format($totalCorruption, 2));

        if ($dryRun) {
            $this->line('');
            $this->warn('🔍 This was a dry run - no changes were made');
            $this->line('   Run without --dry-run to apply fixes');
        } else {
            $this->line('');
            $this->info('✅ Wallet balance repair completed');
        }

        return 0;
    }

    /**
     * Process a single wallet and fix its balance if needed.
     */
    private function processWallet(UserWallet $wallet, bool $dryRun, int &$totalFixed, float &$totalCorruption): void
    {
        $username = $wallet->user->username ?? 'Unknown';
        $storedBalance = $wallet->balance;

        // Calculate correct balance from transactions
        $calculatedBalance = 0;
        $transactionCount = 0;
        $issues = [];

        foreach ($wallet->transactions as $transaction) {
            $transactionCount++;

            // Check for double-negative amounts (the bug we're fixing)
            if ($transaction->amount < 0 && in_array($transaction->type, ['debit', 'payment'])) {
                $issues[] = "Negative amount for debit: {$transaction->amount}";
            }

            // Check for wrong transaction types
            if ($transaction->amount < 0 && in_array($transaction->type, ['credit', 'topup', 'refund'])) {
                $issues[] = "Negative amount for credit: {$transaction->amount}";
            }

            // Calculate balance based on transaction type, not just amount sign
            if (in_array($transaction->type, ['credit', 'topup', 'refund', 'transfer_in'])) {
                $calculatedBalance += abs($transaction->amount);
            } else {
                $calculatedBalance -= abs($transaction->amount);
            }
        }

        $difference = $calculatedBalance - $storedBalance;
        $hasIssues = count($issues) > 0 || abs($difference) > 0.01;

        if ($hasIssues) {
            $this->line("👤 <fg=yellow>{$username}</fg=yellow> (Wallet #{$wallet->id})");
            $this->line("   💰 Stored: £" . number_format($storedBalance, 2) . " | Calculated: £" . number_format($calculatedBalance, 2));
            
            if (abs($difference) > 0.01) {
                $this->line("   🚨 Difference: £" . number_format($difference, 2));
                $totalCorruption += abs($difference);
            }

            if (count($issues) > 0) {
                $this->line("   🔧 Transaction issues found:");
                foreach ($issues as $issue) {
                    $this->line("      - {$issue}");
                }
            }

            if (!$dryRun && abs($difference) > 0.01) {
                // Fix the wallet balance
                $wallet->update(['balance' => $calculatedBalance]);
                $this->line("   ✅ <fg=green>Fixed wallet balance</fg=green>");
                $totalFixed++;
            } else if ($dryRun && abs($difference) > 0.01) {
                $this->line("   🔍 <fg=cyan>Would fix wallet balance</fg=cyan>");
                $totalFixed++;
            }

            $this->line('');
        }
    }
}