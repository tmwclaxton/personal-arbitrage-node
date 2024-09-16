<?php

namespace App\Console\Commands;

use App\Jobs\ConfirmPayment;
use App\Models\AdminDashboard;
use App\Models\SlackMessage;
use App\Models\Offer;
use App\Services\SlackService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SlackCommands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh:slack-commands';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grab the latest slack commands from the slack server';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        // kick off the job
        $job = new \App\Jobs\SlackCommands();
        $job->handle();

    }
}
