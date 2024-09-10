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
use Illuminate\Support\Facades\Schema;

// check if the admin dashboard table exists first
if (Schema::hasTable('admin_dashboards')) {
    $adminDashboard = AdminDashboard::all()->first();
    if (!$adminDashboard) {
        $adminDashboard = new AdminDashboard();
        // set payment methods to revolut and wise
        // $adminDashboard->payment_methods = json_encode(["Revolut", "Wise"]);
        // $adminDashboard->payment_currencies = json_encode(["EUR", "USD", "GBP"]);
    }

    // ping umbrel check
    if (isset($adminDashboard, $adminDashboard->umbrel_ip, $adminDashboard->umbrel_password)) {
        Schedule::command('app:umbrel-token-reset')
            ->description('reset umbrel token')
            ->everyFiveMinutes()->withoutOverlapping(1);
    }

    if (isset($adminDashboard, $adminDashboard->umbrel_ip, $adminDashboard->umbrel_token)) {
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
            ->everyMinute()->withoutOverlapping(1);

        // auto confirm final
        Schedule::command('auto:confirm-final')
            ->description('auto confirm final')
            ->everyMinute()->withoutOverlapping(1);



        // auto:create
        Schedule::command('auto:create')
            ->description('auto create')
            ->everyFiveMinutes()->withoutOverlapping(1);

    }

    if (isset($adminDashboard, $adminDashboard->kraken_api_key, $adminDashboard->kraken_private_key)) {
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
    }


    if (isset($adminDashboard, $adminDashboard->slack_app_id, $adminDashboard->slack_client_id, $adminDashboard->slack_client_secret, $adminDashboard->slack_signing_secret, $adminDashboard->slack_bot_token)) {
        Schedule::command('refresh:discord-commands')
            ->description('refresh discord commands')
            ->everyTenSeconds()->withoutOverlapping(1);
    }


    // app:warning-system
    Schedule::command('app:warning-system')
        ->description('app warning system')
        ->everyMinute()->withoutOverlapping(1);

    // scheduler
    Schedule::command('app:scheduler')
        ->description('scheduler')
        ->everyMinute()->withoutOverlapping(1);
}



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

// Schedule::command('claim:compensation')
//     ->description('claim compensation')
//     ->hourly();