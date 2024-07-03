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

Schedule::command('refresh:offers')
    ->description('refresh robosat offers')
    ->everyTwoMinutes();

Schedule::command('refresh:fiats')
    ->description('refresh fiats')
    ->everyThreeMinutes();

Schedule::command('refresh:dashboard')
    ->description('refresh dashboard')
    ->everyMinute();

Schedule::command('refresh:transactions')
    ->description('refresh transactions')
    ->everyMinute();


Schedule::command('refresh:robots')
    ->description('refresh robots')
    ->everyMinute();


Schedule::command('claim:compensation')
    ->description('claim compensation')
    ->everySixHours();

Schedule::command('retire:offer')
    ->description('retire offer')
    ->everyFifteenMinutes();

//    public static $statusText = [
//         0 => 'Waiting for maker bond',
//         1 => 'Public',
//         2 => 'Paused',
//         3 => 'Waiting for taker bond',
//         4 => 'Cancelled',
//         5 => 'Expired',
//         6 => 'Waiting for trade collateral and buyer invoice',
//         7 => 'Waiting only for seller trade collateral',
//         8 => 'Waiting only for buyer invoice',
//         9 => 'Sending fiat - In chatroom',
//         10 => 'Fiat sent - In chatroom',
//         11 => 'In dispute',
//         12 => 'Collaboratively cancelled',
//         13 => 'Sending satoshis to buyer',
//         14 => 'Sucessful trade',
//         15 => 'Failed lightning network routing',
//         16 => 'Wait for dispute resolution',
//         17 => 'Maker lost dispute',
//         18 => 'Taker lost dispute',
//         99 => 'Collaboratively cancelled',
//     ];

Schedule::call(function () {
   // every second check status of offer
    $adminDashboard = AdminDashboard::all()->first();
    $offers = Offer::where([['status', '!=', 99], ['status', '!=', 5], ['status', '!=', 14]])->get();
    foreach ($offers as $offer) {
        // if status is 3 then dispatch a bond job
        if ($offer->job_last_status >= $offer->status) {
            continue;
        }
        $offer->job_last_status = $offer->status;
        if ($offer->status == 3 && $adminDashboard->autoBond) {
            PayBond::dispatch($offer);
        }
        if (($offer->status == 6 || $offer->status == 7) && $adminDashboard->autoEscrow) {
            PayEscrow::dispatch($offer);
        }
        if ($offer->status == 9 && $adminDashboard->autoMessage) {
            SendPaymentHandle::dispatch($offer);
        }
        if ($offer->status == 10) {
            // send discord message or check programatically

        }

        $offer->save();
    }
})->everyTenSeconds();

