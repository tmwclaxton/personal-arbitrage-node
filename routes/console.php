<?php

use App\Jobs\PayBond;
use App\Jobs\PayEscrow;
use App\Jobs\SendPaymentHandle;
use App\Models\AdminDashboard;
use App\Models\Offer;
use App\Models\Robot;
use App\Models\Transaction;
use App\WorkerClasses\LightningNode;
use App\WorkerClasses\Robosats;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;



Schedule::command('refresh:dashboard')
    ->description('refresh dashboard')
    ->everyMinute()->withoutOverlapping(1);

Schedule::command('refresh:transactions')
    ->description('refresh transactions')
    ->everyTwentySeconds()->withoutOverlapping(1);

Schedule::command('refresh:robots')
    ->description('refresh robots')
    ->hourly()->withoutOverlapping(1);

// Schedule::command('claim:compensation')
//     ->description('claim compensation')
//     ->hourly();

Schedule::command('retire:offers')
    ->description('retire offers')
    ->everyFifteenMinutes()->withoutOverlapping(1);

Schedule::command('auto:jobs')
    ->description('auto jobs')
    ->everyMinute()->withoutOverlapping(1);

Schedule::command('refresh:discord-commands')
    ->description('refresh discord commands')
    ->everyTenSeconds()->withoutOverlapping(1);

Schedule::command('refresh:offers')
    ->description('refresh robosat offers')
    ->everyMinute()->withoutOverlapping(1);

Schedule::command('refresh:fiats')
    ->description('refresh fiats')
    ->everyMinute()->withoutOverlapping(1);

//     \Illuminate\Support\Facades\Artisan::call('auto:accept');

Schedule::command('auto:accept')
    ->description('auto accept')
    ->everyThreeMinutes()->withoutOverlapping(1);

// auto accept final
Schedule::command('auto:accept-final')
    ->description('auto accept final')
    ->everyMinute()->withoutOverlapping(1);

// app:warning-system
Schedule::command('app:warning-system')
    ->description('app warning system')
    ->everyMinute()->withoutOverlapping(1);

// refresh revolut token
Schedule::command('refresh:revolut-token')
    ->description('refresh revolut token')
    ->everyTenMinutes()->withoutOverlapping(1);

// every minute trigger a revolut payment listener job
Schedule::command('revolut:payment-listener')
    ->description('revolut payment listener')
    ->everyMinute()->withoutOverlapping(1);

// every minute trigger a revolut payment listener job
Schedule::command('wise:payment-listener')
    ->description('wise payment listener')
    ->everyMinute()->withoutOverlapping(1);

// payment matcher
Schedule::command('payment:matcher')
    ->description('payment matcher')
    ->everyMinute()->withoutOverlapping(1);

// currency converter
Schedule::command('currency:converter')
    ->description('currency converter')
    ->everyMinute()->withoutOverlapping(1);

// kraken auto purchaser
// Schedule::command('kraken:auto-purchaser')
//     ->description('kraken auto purchaser')
//     ->everyTenMinutes()->withoutOverlapping(1);

// Schedule::call(function () {
//     $revolutService = new \App\Services\RevolutService();
//     $revolutService->sendAllToPersonal();
// })->everyMinute();
