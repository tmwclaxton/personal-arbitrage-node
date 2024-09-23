<?php

namespace App\Console\Commands;

use App\Models\BtcFiat;
use App\WorkerClasses\HelperFunctions;
use App\WorkerClasses\Robosats;
use Illuminate\Console\Command;

class UpdateFiats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh:fiats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh fiats';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!(new HelperFunctions())->normalUmbrelCommandCheck()) {
            return;
        }
        // kick off the job
        $job = new \App\Jobs\UpdateFiats();
        $job->handle();

    }
}
