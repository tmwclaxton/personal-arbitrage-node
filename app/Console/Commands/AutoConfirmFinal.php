<?php

namespace App\Console\Commands;

use App\WorkerClasses\HelperFunctions;
use Illuminate\Console\Command;

class AutoConfirmFinal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:confirm-final';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Final confirmation of payment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!(new HelperFunctions())->normalUmbrelCommandCheck()) {
            return;
        }
        $job = new \App\Jobs\AutoConfirmFinal();
        $job->handle();
    }
}
