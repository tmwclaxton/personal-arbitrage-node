<?php

namespace App\Console\Commands;

use App\Models\AdminDashboard;
use App\WorkerClasses\LightningNode;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateInvoice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-invoice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $krakenService = new \App\Services\KrakenService();
        $slackService = new \App\Services\SlackService();

        $btcBalance = $krakenService->getBTCBalance();
        $btc = $btcBalance->jsonSerialize();
        // ensure satoshis is an integer
        $satoshis = intval($btc * 100000000) - 2000; // possible fees?

        $adminDashboard = AdminDashboard::all()->first();
        $remoteBalance = $adminDashboard->remoteBalance;
        $localBalance = $adminDashboard->localBalance;
        if ($satoshis > $remoteBalance - 200000) {
            $satoshis = $remoteBalance - 200000;
        }
        $idealLightningNodeBalance = $adminDashboard->ideal_lightning_node_balance;

        $helpFunction = new \App\WorkerClasses\HelperFunctions();
        $satsInTransitArray = $helpFunction->calcSatsInTransit();
        // we need to remove the sats in transit from the ideal balance
        $idealLightningNodeBalance -= $satsInTransitArray['bondSatoshis'] + $satsInTransitArray['escrowSatoshis'];

        if ($localBalance + $satoshis > $idealLightningNodeBalance) {
            $satoshis = $idealLightningNodeBalance - $localBalance;
            if ($satoshis <= 0) {
                $slackService->sendMessage('You have already reached the ideal balance');
                return;
            }
        }

        // if the satoshis is less than 2000, don't create an invoice
        if ($satoshis < 2000) {
            $slackService->sendMessage('Not enough BTC to create an invoice');
            return;
        }

        $lightningNode = new LightningNode();
        $invoice = $lightningNode->createInvoice($satoshis, 'Kraken BTC Withdrawal of ' . $satoshis . ' sats at ' . Carbon::now()->toDateTimeString());
        $slackService->sendMessage($invoice);
    }
}
