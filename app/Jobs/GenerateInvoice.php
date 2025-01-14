<?php

namespace App\Jobs;

use App\WorkerClasses\Robosats;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateInvoice implements ShouldQueue
{
    use Queueable;
    public int $timeout = 300;
    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->adminDashboard->panicButton) {
            // kick off the job
            $job = new \App\Jobs\GenerateInvoice();
            $job->handle();
        } else {
            // throw an exception
            throw new \Exception('Panic button is enabled - GenerateInvoice.php');
        }
    }
}
