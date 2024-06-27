<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


// Artisan::command('refresh:robosat-offers', function () {
//
// })->purpose('refresh robosat offers')->everyMinute();

Schedule::command('refresh:robosat-offers')
    ->description('refresh robosat offers')
    ->everyMinute();
