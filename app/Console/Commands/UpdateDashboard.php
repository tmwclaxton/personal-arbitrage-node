<?php

namespace App\Console\Commands;

use App\Models\AdminDashboard;
use App\WorkerClasses\LightningNode;
use Illuminate\Console\Command;

class UpdateDashboard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh:dashboard';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh dashboard';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // grab the first admin dashboard or create it
        $adminDashboard = AdminDashboard::all()->first();
        if (!$adminDashboard) {
            $adminDashboard = new AdminDashboard();
        }
        $lightningNode = new LightningNode();
        $balanceArray = $lightningNode->getLightningWalletBalance();
        $adminDashboard->localBalance = $balanceArray['localBalance'];
        $adminDashboard->remoteBalance = $balanceArray['remoteBalance'];
        $adminDashboard->save();
    }
}
