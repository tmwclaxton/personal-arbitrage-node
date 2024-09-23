<?php

namespace App\Console\Commands;

use App\WorkerClasses\HelperFunctions;
use Illuminate\Console\Command;

class UpdateProviders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:providers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update status of providers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ((new HelperFunctions())->normalUmbrelCommandCheck()) {
            $job = new \App\Jobs\UpdateProviders();
            $job->handle();
        }
    }
}
