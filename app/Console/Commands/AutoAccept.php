<?php

namespace App\Console\Commands;

use App\Models\AdminDashboard;
use Illuminate\Console\Command;

class AutoAccept extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:accept';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find sell offers worth accepting';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $adminDashboard = AdminDashboard::all()->first();
        $maxConcurrentTransactions = $adminDashboard->max_concurrent_transactions;
    }
}
