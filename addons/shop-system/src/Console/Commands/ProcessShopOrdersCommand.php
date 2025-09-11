<?php

namespace PterodactylAddons\ShopSystem\Console\Commands;

use PterodactylAddons\ShopSystem\Jobs\ProcessOrderRenewalsJob;
use PterodactylAddons\ShopSystem\Jobs\SuspendOverdueOrdersJob;
use PterodactylAddons\ShopSystem\Jobs\TerminateOverdueOrdersJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessShopOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'shop:process-orders {--force : Force processing even if already run today}';

    /**
     * The console command description.
     */
    protected $description = 'Process shop orders for renewals, suspensions, and terminations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting shop order processing...');

        try {
            // Process order renewals (due today)
            $this->info('Processing order renewals...');
            ProcessOrderRenewalsJob::dispatch();
            $this->line('✓ Order renewals job dispatched');

            // Suspend overdue orders
            $this->info('Processing overdue order suspensions...');
            SuspendOverdueOrdersJob::dispatch();
            $this->line('✓ Overdue suspensions job dispatched');

            // Terminate long-overdue orders
            $this->info('Processing order terminations...');
            TerminateOverdueOrdersJob::dispatch();
            $this->line('✓ Order terminations job dispatched');

            $this->info('All shop order processing jobs have been dispatched successfully!');

            Log::info('Shop order processing command completed successfully');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to process shop orders: ' . $e->getMessage());
            
            Log::error('Shop order processing command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
