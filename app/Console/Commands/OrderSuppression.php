<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class OrderSuppression extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:order-suppression {type} {currency} {premium}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Suppress orders according to the criteria';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $job = new \App\Jobs\OrderSuppression($this->argument('type'), $this->argument('currency'), $this->argument('premium'));
        $job->handle();
    }
}
