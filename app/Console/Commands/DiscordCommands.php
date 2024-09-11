<?php

namespace App\Console\Commands;

use App\Jobs\ConfirmPayment;
use App\Models\AdminDashboard;
use App\Models\DiscordMessage;
use App\Models\Offer;
use App\Services\SlackService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DiscordCommands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh:discord-commands';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grab the latest discord commands from the discord server';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        // kick off the job
        $job = new \App\Jobs\DiscordCommands();
        $job->handle();

    }
}
