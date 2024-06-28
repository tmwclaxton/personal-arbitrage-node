<?php

use App\Models\AdminDashboard;
use App\Models\Transaction;
use App\WorkerClasses\LightningNode;
use App\WorkerClasses\Robosats;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


// Artisan::command('refresh:robosat-offers', function () {
//
// })->purpose('refresh robosat offers')->everyMinute();

Schedule::command('refresh:robosat-offers')
    ->description('refresh robosat offers')
    ->everyTwoMinutes();

Schedule::call(function () {
    $robosats = new Robosats();
    $prices = $robosats->getCurrentPrices();
    foreach ($prices as $price) {
        $btcFiat = new \App\Models\BtcFiat();
        // if the currency is already in the database, update it
        $btcFiat->updateOrCreate(
            ['currency' => $price['code']],
            ['price' => $price['price']]
        );
    }
})->everyThreeMinutes();

Schedule::call(function () {
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
})->everyMinute();

Schedule::call(function () {
    // update all current transactions
    $transactions = Transaction::all();
    foreach ($transactions as $transaction) {
        $offer = $transaction->offer;
        $robosatsId = $offer->robosatsId;
        $robosats = new Robosats();
        $response = $robosats->updateTransactionStatus($robosatsId, $transaction->id);
    }
})->everyMinute();


