<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PaymentMatcher extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:matcher';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Match payments with offers and transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // kick off the job
        $job = new \App\Jobs\PaymentMatcher();
        $job->handle();
    }
}
