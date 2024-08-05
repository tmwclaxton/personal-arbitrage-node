<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SittingOfferDetailer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sitting-offer-detailer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $job = new \App\Jobs\SittingOfferDetailer();
        $job->handle();
    }
}
