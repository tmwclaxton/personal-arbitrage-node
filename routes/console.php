<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('logs:clear', function() {

    exec('echo "" > ' . storage_path('logs/laravel.log'));

    exec('echo "" > ' . storage_path('logs/worker.log'));

    $this->comment('Logs have been cleared!');

})->describe('Clear log files');


// ping umbrel check
Schedule::command('app:umbrel-token-reset')
    ->description('reset umbrel token')
    ->everyMinute()->withoutOverlapping(1);

Schedule::command('app:refresh-dashboard')
    ->description('refresh dashboard')
    ->everyMinute()->withoutOverlapping(1);

Schedule::command('app:refresh-providers')
    ->description('refresh providers')
    ->everyFiveMinutes()->withoutOverlapping(1);

Schedule::command('app:refresh-transactions')
    ->description('refresh transactions')
    ->everyMinute()->withoutOverlapping(1);

Schedule::command('app:refresh-robots')
    ->description('refresh robots')
    ->hourly()->withoutOverlapping(1);

Schedule::command('app:claim-compensation')
    ->description('claim compensation')
    ->hourly();

Schedule::command('app:retire-offers')
    ->description('retire offers')
    ->everyFiveMinutes()->withoutOverlapping(1);

Schedule::command('app:auto-jobs')
    ->description('auto jobs')
    ->everyMinute()->withoutOverlapping(1);


Schedule::command('app:refresh-offers')
    ->description('refresh robosat offers')
    ->everyMinute()->withoutOverlapping(1);

Schedule::command('app:refresh-fiats')
    ->description('refresh fiats')
    ->everyMinute()->withoutOverlapping(1);

// this job add a timestamp for when the auto accept final job should actually accept the offer
Schedule::command('app:auto-accept')
    ->description('auto accept')
    ->everyThreeMinutes()->withoutOverlapping(1);

// auto accept final
Schedule::command('app:auto-accept-final')
    ->description('auto accept final')
    ->everyMinute()->withoutOverlapping(1);


// get:robosats-messages
Schedule::command('app:refresh-robosats-messages')
    ->description('get robosats messages')
    ->everyTwentySeconds();

// auto confirm final
Schedule::command('app:auto-confirm-final')
    ->description('auto confirm final')
    ->everyMinute()->withoutOverlapping(1);



// auto:create
Schedule::command('app:auto-create')
    ->description('auto create')
    ->everyFiveMinutes()->withoutOverlapping(1);


Schedule::command('app:refresh-kraken-btc-balance')
    ->description('refresh kraken btc balance')
    ->everyFiveMinutes()->withoutOverlapping(1);

// kraken auto purchaser
Schedule::command('app:kraken-auto-purchaser')
    ->description('kraken auto purchaser')
    ->everyTenMinutes()->withoutOverlapping(1);

Schedule::command('app:btc-purchase-detailer')
    ->description('btc purchase detailer')
    ->everyTenMinutes()->withoutOverlapping(1);


Schedule::command('app:refresh-slack-commands')
    ->description('refresh slack commands')
    ->everyMinute()->withoutOverlapping(1);


// app:warning-system
Schedule::command('app:warning-system')
    ->description('app warning system')
    ->everyMinute()->withoutOverlapping(1);

// scheduler
Schedule::command('app:scheduler')
    ->description('scheduler')
    ->everyMinute()->withoutOverlapping(1);

// paybond
// Schedule::command('pay:bond')
//     ->description('pay bond')
//     ->everyMinute()->withoutOverlapping(1);

// pay bonds when they are due
// Schedule::call(function () {
//     $offers = \App\Models\Offer::where('status', 3)->get();
// })->everyMinute()->withoutOverlapping(1);


// !! DO NOT REMOVE THIS JOB || NOR CHANGE THE FREQUENCY
//Schedule::command('app:revolut-login')
//    ->description('app revolut login')
//    ->everyFiveMinutes()->withoutOverlapping(1);
//
//Schedule::command('app:update-apps')
//    ->description('app update apps')
//    ->everyThirtyMinutes()->withoutOverlapping(1);
// !! with mitmproxy we now need to trigger the revolut login job whenever we are waiting for a payment

// every minute trigger a revolut payment listener job
//Schedule::command('revolut:payment-listener')
//    ->description('revolut payment listener')
//    ->everyMinute()->withoutOverlapping(1);

// every minute trigger a revolut payment listener job
//Schedule::command('wise:payment-listener')
//    ->description('wise payment listener')
//    ->everyMinute()->withoutOverlapping(1);


// payment matcher
//Schedule::command('payment:matcher')
//    ->description('payment matcher')
//    ->everyMinute()->withoutOverlapping(1);

// currency converter
//Schedule::command('currency:converter')
//    ->description('currency converter')
//    ->everyFiveMinutes()->withoutOverlapping(1);
// Schedule::command('app:send-to-lightning')
//     ->description('Send btc from kraken to lightning node')
//     ->everyThreeHours()->withoutOverlapping(1);

