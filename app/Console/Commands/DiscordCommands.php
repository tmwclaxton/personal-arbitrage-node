<?php

namespace App\Console\Commands;

use App\Jobs\ConfirmPayment;
use App\Models\AdminDashboard;
use App\Models\DiscordMessage;
use App\Models\Offer;
use App\Services\DiscordService;
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
        $commands = [
            '!panic',
            '!calm',
            '!confirm'
        ];
        $discordService = new DiscordService();
        $latestMessages = $discordService->getLatestMessages();
        foreach ($latestMessages as $message) {
            // check if message already exists in the database
            if (DiscordMessage::where('discord_id', $message['id'])->exists()) {
                continue;
            }

            $discordMessage = new DiscordMessage([
                'discord_id' => $message['id'],
                'content' => $message['content'],
                'author_id' => $message['author']['id'],
                'channel_id' => $message['channel_id'],
                'updated_at' => Carbon::parse($message['timestamp'])
            ]);
            $discordMessage->save();

            // if the message was within the last 5 minutes check if it was a command (starts with /)
            if (Carbon::parse($message['timestamp'])->diffInMinutes(Carbon::now()) < 1 && strpos($message['content'], '!') === 0) {

                // check if the command is in the list of commands
                $firstWord = explode(' ', $message['content'])[0];
                if (in_array($firstWord, $commands)) {
                    // if it is, send a message to the discord channel
                    $adminDashboard = AdminDashboard::all()->first();
                    switch ($firstWord) {
                        case '!panic':
                            $adminDashboard->panicButton = true;
                            $adminDashboard->save();
                            break;
                        case '!calm':
                            $adminDashboard->panicButton = false;
                            $adminDashboard->save();
                            break;
                        case '!confirm':
                            $secondWord = explode(' ', $message['content'])[1];
                            $offer = Offer::where('robosatsId', $secondWord)->first();
                            ConfirmPayment::dispatch($offer, $adminDashboard);
                            break;
                        default:
                            $discordService->sendMessage('Command not recognized');
                            break;

                    }
                    $discordService->sendMessage('Executing command: ' . $message['content']);
                }

            }
        }

    }
}
