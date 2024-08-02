<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendToKraken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kraken:send-money';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send money to Kraken from Revolut and Wise';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $job = new \App\Jobs\SendToKraken();
        $job->handle();
    }
}
