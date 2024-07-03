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
    ->everyMinute();

Schedule::command('refresh:fiats')
    ->description('refresh fiats')
    ->everyMinute();

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

Schedule::command('retire:offers')
    ->description('retire offers')
    ->everyFifteenMinutes();

Schedule::command('auto:jobs')
    ->description('auto jobs')
    ->everyTwentySeconds();


Schedule::command('refresh:discord-commands')
    ->description('refresh discord commands')
    ->everyTenSeconds();
