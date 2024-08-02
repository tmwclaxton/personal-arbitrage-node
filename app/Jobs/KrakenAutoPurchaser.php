<?php

namespace App\Jobs;

use App\Models\AdminDashboard;
use App\Services\DiscordService;
use App\WorkerClasses\LightningNode;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class KrakenAutoPurchaser implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;


    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     * @throws GuzzleException|MathException
     */
    public function handle(): void
    {

        $adminDashboard = AdminDashboard::all()->first();
        // check if autoTopUp is enabled
        if (!$adminDashboard->autoTopUp || $adminDashboard->panicButton) {
            return;
        }

        $lightningNode = new LightningNode();
        $kraken = new \App\Services\KrakenService();
        $response = $kraken->getGBPBalance();
        $discordService = new DiscordService();
        if ($response->isGreaterThan(BigDecimal::of('10'))) {
            $discordService->sendMessage('Auto purchasing BTC from Kraken');

            $kraken->buyFullAmt();
            sleep(5);
            // $kraken->sendFullAmtToLightning();
        }


        // kraken get BTC balance
        $btcBalance = $kraken->getBTCBalance();
        // if BTC balance greater than 0 send to lightning node
        if ($btcBalance->isGreaterThan(BigDecimal::of('0'))) {
            $kraken->sendFullAmtToLightning();
        }

        sleep(5);
        $balance = $lightningNode->getLightningWalletBalance();
        if ($balance['localBalance'] < 600000) {
            $discordService->sendMessage('Send money to Kraken!');
        }
    }
}
