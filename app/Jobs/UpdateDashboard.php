<?php

namespace App\Jobs;

use App\Models\AdminDashboard;
use App\WorkerClasses\LightningNode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateDashboard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // grab the first admin dashboard or create it
        $adminDashboard = AdminDashboard::all()->first();
        if (!$adminDashboard) {
            $adminDashboard = new AdminDashboard();
            // set payment methods to revolut and wise
            $adminDashboard->payment_methods = json_encode(["Revolut", "Wise"]);
            $adminDashboard->payment_currencies = json_encode(["EUR", "USD", "GBP"]);
        }
        $lightningNode = new LightningNode();
        $balanceArray = $lightningNode->getLightningWalletBalance();
        $adminDashboard->localBalance = $balanceArray['localBalance'];
        $adminDashboard->remoteBalance = $balanceArray['remoteBalance'];


        $adminDashboard->channelBalances = json_encode($balanceArray['channelBalances']);
        $adminDashboard->save();
    }
}
