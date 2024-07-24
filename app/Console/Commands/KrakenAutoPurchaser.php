<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class KrakenAutoPurchaser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kraken:auto-purchaser';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically purchase BTC from Kraken and send to Lightning Node';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $job = new \App\Jobs\KrakenAutoPurchaser();
        $job->handle();
    }
}
