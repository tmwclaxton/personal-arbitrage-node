<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BtcPurchaseDetailer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'btc:purchase-detailer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fill in the details of a btc purchase and match it with the corresponding payment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $job = new \App\Jobs\BtcPurchaseDetailer();
        $job->handle();
    }
}
