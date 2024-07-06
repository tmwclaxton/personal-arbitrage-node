<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RefreshRevolutToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh:revolut-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh Revolut Token';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // kick off the job
        $job = new \App\Jobs\RefreshRevolutToken();
        $job->handle();
    }
}
