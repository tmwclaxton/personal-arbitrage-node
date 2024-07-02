<?php

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


