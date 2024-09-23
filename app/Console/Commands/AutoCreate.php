<?php

namespace App\Console\Commands;

use App\WorkerClasses\HelperFunctions;
use Illuminate\Console\Command;

class AutoCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto create offers based on templates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!(new HelperFunctions())->normalUmbrelCommandCheck()) {
            return;
        }
        $job = new \App\Jobs\AutoCreate();
        $job->handle();
    }
}
