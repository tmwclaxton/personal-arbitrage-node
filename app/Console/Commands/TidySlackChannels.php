<?php

namespace App\Console\Commands;

use App\Services\SlackService;
use Illuminate\Console\Command;

class TidySlackChannels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:tidy-slack-channels';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old auto-created slack channels';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $slackService = new SlackService();

        // grab bot user id
        $slackService->archiveOldBotChannels(null, 50);

    }
}
