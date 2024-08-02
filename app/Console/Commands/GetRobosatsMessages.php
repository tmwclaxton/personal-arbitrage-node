<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GetRobosatsMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:robosats-messages';

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
        $job = new \App\Jobs\GetRobosatsMessages();
        $job->handle();
    }
}
