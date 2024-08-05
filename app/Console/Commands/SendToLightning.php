<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendToLightning extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-to-lightning';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send btc from kraken to lightning node';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $job = new \App\Jobs\SendToLightning();
        $job->handle();
    }
}
