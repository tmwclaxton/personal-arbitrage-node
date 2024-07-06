<?php

namespace App\Console\Commands;

use App\Http\Controllers\OfferController;
use App\Models\AdminDashboard;
use App\Models\BtcFiat;
use App\Models\Offer;
use App\WorkerClasses\Robosats;
use Illuminate\Console\Command;

class UpdateOffers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh:offers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh Robosat offers';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        // kick off the job
        $job = new \App\Jobs\UpdateOffers();
        $job->handle();

    }

}
