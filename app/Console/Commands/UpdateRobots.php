<?php

namespace App\Console\Commands;

use App\Models\Offer;
use App\Models\Robot;
use App\WorkerClasses\HelperFunctions;
use App\WorkerClasses\Robosats;
use Illuminate\Console\Command;

class UpdateRobots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:refresh-robots';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh robots';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ((new HelperFunctions())->normalUmbrelCommandCheck()) {
            // kick off the job
            $job = new \App\Jobs\UpdateRobots();
            $job->handle();
        }
    }
}
