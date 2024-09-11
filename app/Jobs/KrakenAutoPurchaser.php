<?php

namespace App\Jobs;

use App\Models\AdminDashboard;
use App\Services\SlackService;
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
        if (!$adminDashboard->autoTopup || $adminDashboard->panicButton) {
            return;
        }

        $lightningNode = new LightningNode();
        $kraken = new \App\Services\KrakenService();

        $response = $kraken->getGBPBalance();
        $slackService = new SlackService();
        if ($response->isGreaterThan(BigDecimal::of('10'))) {
            $slackService->sendMessage('Auto purchasing BTC with GBP from Kraken');

            $kraken->buyFullAmt("GBP", $kraken->getGBPBalance());
            sleep(5);
        }

        $response = $kraken->getEURBalance();
        $slackService = new SlackService();
        if ($response->isGreaterThan(BigDecimal::of('10'))) {
            $slackService->sendMessage('Auto purchasing BTC with EUR from Kraken');

            $kraken->buyFullAmt("EUR", $kraken->getEURBalance());
            sleep(5);
        }


        // !TODO: we need to support every currency on strike
        // $response = $kraken->getUSDBalance();
        // $slackService = new DiscordService();
        // if ($response->isGreaterThan(BigDecimal::of('10'))) {
        //     $slackService->sendMessage('Auto purchasing BTC with EUR from Kraken');
        //
        //     $kraken->buyFullAmt("USD", $kraken->getUSDBalance());
        //     sleep(5);
        // }

    }
}
