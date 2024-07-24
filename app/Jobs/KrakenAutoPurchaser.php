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
        if (!$adminDashboard->autoTopUp) {
            return;
        }

        $lightningNode = new LightningNode();
        $balance = $lightningNode->getLightningWalletBalance();
        if ($balance['localBalance'] < 600000) {
            $kraken = new \App\Services\KrakenService();
            $response = $kraken->getGBPBalance();
            if ($response->isGreaterThan(BigDecimal::of('10'))) {
                $kraken->buyFullAmt();
                sleep(5);
                $kraken->sendFullAmtToLightning();
            } else {
                $discordService = new DiscordService();
                $discordService->sendMessage('Send money to Kraken!');
            }
        }
    }
}
