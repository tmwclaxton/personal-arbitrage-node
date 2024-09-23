<?php

use Illuminate\Support\Facades\Schedule;

// ping umbrel check
Schedule::command('app:umbrel-token-reset')
    ->description('reset umbrel token')
    ->everyFiveMinutes()->withoutOverlapping(1);

Schedule::command('refresh:dashboard')
    ->description('refresh dashboard')
    ->everyMinute()->withoutOverlapping(1);

Schedule::command('update:providers')
    ->description('refresh providers')
    ->everyFiveMinutes()->withoutOverlapping(1);

Schedule::command('refresh:transactions')
    ->description('refresh transactions')
    ->everyMinute()->withoutOverlapping(1);

Schedule::command('refresh:robots')
    ->description('refresh robots')
    ->hourly()->withoutOverlapping(1);

Schedule::command('claim:compensation')
    ->description('claim compensation')
    ->hourly();

Schedule::command('retire:offers')
    ->description('retire offers')
    ->everyFiveMinutes()->withoutOverlapping(1);

Schedule::command('auto:jobs')
    ->description('auto jobs')
    ->everyMinute()->withoutOverlapping(1);


Schedule::command('refresh:offers')
    ->description('refresh robosat offers')
    ->everyMinute()->withoutOverlapping(1);

Schedule::command('refresh:fiats')
    ->description('refresh fiats')
    ->everyMinute()->withoutOverlapping(1);

Schedule::command('auto:accept')
    ->description('auto accept')
    ->everyMinute()->withoutOverlapping(1);

// auto accept final
Schedule::command('auto:accept-final')
    ->description('auto accept final')
    ->everyMinute()->withoutOverlapping(1);


// get:robosats-messages
Schedule::command('get:robosats-messages')
    ->description('get robosats messages')
    ->everyTwentySeconds();

// auto confirm final
Schedule::command('auto:confirm-final')
    ->description('auto confirm final')
    ->everyMinute()->withoutOverlapping(1);



// auto:create
Schedule::command('auto:create')
    ->description('auto create')
    ->everyFiveMinutes()->withoutOverlapping(1);


Schedule::command('update:kraken-btc-balance')
    ->description('refresh kraken btc balance')
    ->everyFiveMinutes()->withoutOverlapping(1);

// kraken auto purchaser
Schedule::command('kraken:auto-purchaser')
    ->description('kraken auto purchaser')
    ->everyTenMinutes()->withoutOverlapping(1);

Schedule::command('btc:purchase-detailer')
    ->description('btc purchase detailer')
    ->everyTenMinutes()->withoutOverlapping(1);


Schedule::command('refresh:slack-commands')
    ->description('refresh slack commands')
    ->everyTenSeconds()->withoutOverlapping(1);


// app:warning-system
Schedule::command('app:warning-system')
    ->description('app warning system')
    ->everyMinute()->withoutOverlapping(1);

// scheduler
Schedule::command('app:scheduler')
    ->description('scheduler')
    ->everyMinute()->withoutOverlapping(1);




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

