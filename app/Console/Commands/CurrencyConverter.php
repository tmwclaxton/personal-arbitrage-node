<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CurrencyConverter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:converter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert currency from EUR to GBP and USD to GBP in both Revolut and Wise';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // currency conversion job
        $job = new \App\Jobs\CurrencyConverter();
        $job->handle();
    }
}
