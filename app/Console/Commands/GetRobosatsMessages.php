<?php

namespace App\Console\Commands;

use App\WorkerClasses\HelperFunctions;
use Illuminate\Console\Command;

class GetRobosatsMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:robosats-messages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the latest messages from Robosats chatrooms.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!(new HelperFunctions())->normalUmbrelCommandCheck()) {
            return;
        }
        $job = new \App\Jobs\GetRobosatsMessages();
        $job->handle();
    }
}
