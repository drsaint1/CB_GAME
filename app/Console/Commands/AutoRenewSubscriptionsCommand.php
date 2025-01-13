<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Api\subscriptionController;

class AutoRenewSubscriptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:auto-renew';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically renew subscriptions for users with sufficient wallet balance.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $subscriptionController = new SubscriptionController();
        $subscriptionController->autoRenewSubscriptions();

        // Output success message
        $this->info('Auto-renewal process completed.');
    }
}
