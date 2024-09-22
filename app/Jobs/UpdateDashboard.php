<?php

namespace App\Jobs;

use App\Models\AdminDashboard;
use App\WorkerClasses\HelperFunctions;
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

    // timeout 180 seconds
    public int $timeout = 180;
    public function handle(): void
    {
        // grab the first admin dashboard or create it
        $adminDashboard = AdminDashboard::all()->first();

        $lightningNode = new LightningNode();
        $balanceArray = $lightningNode->getLightningWalletBalance();
        $adminDashboard->localBalance = $balanceArray['localBalance'];
        $adminDashboard->remoteBalance = $balanceArray['remoteBalance'];
        $adminDashboard->channelBalances = json_encode($balanceArray['channelBalances']);
        $helpFunction = new HelperFunctions();
        $calculations = $helpFunction->calcSatsInTransit();
        $adminDashboard->bond_sats = $calculations['bondSatoshis'];
        $adminDashboard->escrow_sats = $calculations['escrowSatoshis'];
        $adminDashboard->save();
    }
}
