<?php

namespace App\Console\Commands;

use App\Models\AdminDashboard;
use App\WorkerClasses\HelperFunctions;
use Illuminate\Console\Command;

class UpdateKrakenBtcBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:kraken-btc-balance';

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
        if (!(new HelperFunctions())->krakenCommandCheck()) {
            return;
        }
        $adminDashboard = AdminDashboard::all()->first();
        $krakenService = new \App\Services\KrakenService();
        $btcBalance = $krakenService->getBTCBalance();
        $helperFunctions = new \App\WorkerClasses\HelperFunctions();
        // convert bigdecimal to decimal
        $btcBalance = $helperFunctions->bigDecimalToDecimal($btcBalance);
        // convert btc balance to satoshi
        $satoshiBal = $helperFunctions->btcToSatoshi($btcBalance);

        if ($satoshiBal === null) {
            $adminDashboard->kraken_btc_balance = 0;
        } else {
            $adminDashboard->kraken_btc_balance = $satoshiBal;
        }
        $adminDashboard->save();
    }
}
