<?php

use App\WorkerClasses\Robosats;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


// Artisan::command('refresh:robosat-offers', function () {
//
// })->purpose('refresh robosat offers')->everyMinute();

Schedule::command('refresh:robosat-offers')
    ->description('refresh robosat offers')
    ->everyMinute();

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
})->everyMinute();
