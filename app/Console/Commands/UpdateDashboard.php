<?php

namespace App\Console\Commands;

use App\Models\AdminDashboard;
use App\WorkerClasses\HelperFunctions;
use App\WorkerClasses\LightningNode;
use Illuminate\Console\Command;

class UpdateDashboard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh:dashboard';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh dashboard';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // kick off the job
        $adminDashboard = AdminDashboard::all()->first();
        if (!$adminDashboard) {
            $adminDashboard = new AdminDashboard();
            // set name to random string
            $helperFunctions = new HelperFunctions();
            $adminDashboard->name = $helperFunctions->generateSlug(14);
            $adminDashboard->save();
        }
        // check if connected to Orchestrator
        // if ($adminDashboard->orchestrator) {

        // }
        //

        if ((new HelperFunctions())->normalUmbrelCommandCheck()) {
            $job = new \App\Jobs\UpdateDashboard();
            $job->handle();
        }
    }
}
