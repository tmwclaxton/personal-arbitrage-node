<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AutoAcceptFinal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:accept-final';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto accept final';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $job = new \App\Jobs\AutoAcceptFinal();
        $job->handle();
    }
}
