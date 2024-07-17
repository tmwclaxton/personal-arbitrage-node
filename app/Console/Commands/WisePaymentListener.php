<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WisePaymentListener extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wise:payment-listener';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen for new Wise payments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // dispatch a new job to listen for new Wise payments
        $job = new \App\Jobs\WisePaymentListener();
        $job->handle();
    }
}
