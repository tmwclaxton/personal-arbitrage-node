<?php

namespace App\Console\Commands;

use App\WorkerClasses\HelperFunctions;
use Illuminate\Console\Command;

class AutoAcceptFinal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auto-accept-final';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto accept final';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!(new HelperFunctions())->normalUmbrelCommandCheck()) {
            return;
        }
        $job = new \App\Jobs\AutoAcceptFinal();
        $job->handle();
    }
}
