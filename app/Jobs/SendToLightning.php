<?php

namespace App\Jobs;

use App\Models\AdminDashboard;
use Brick\Math\BigDecimal;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendToLightning implements ShouldQueue
{
    use Queueable;

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

        $adminDashboard = AdminDashboard::all()->first();
        // check if autoTopUp is enabled
        if (!$adminDashboard->autoTopup || $adminDashboard->panicButton) {
            return;
        }

        $lightningNode = new \App\WorkerClasses\LightningNode();
        $kraken = new \App\Services\KrakenService();
        // kraken get BTC balance
        $btcBalance = $kraken->getBTCBalance();
        // if BTC balance greater than 0 send to lightning node
        if ($btcBalance->isGreaterThan(BigDecimal::of('0.015'))) {
            $kraken->sendFullAmtToLightning();
        }

        sleep(5);
        $balance = $lightningNode->getLightningWalletBalance();
        if ($balance['localBalance'] < 600000) {
            $slackService = new \App\Services\SlackService();
            $slackService->sendMessage('Send money to Kraken!');
        }
    }
}
