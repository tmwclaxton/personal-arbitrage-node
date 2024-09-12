<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RevolutPaymentListener extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'revolut:payment-listener';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen for incoming payments from Revolut and send a slack notification';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // trigger job
        $job = new \App\Jobs\RevolutPaymentListener();
        $job->handle();
    }
}
