<?php

namespace App\Jobs;

use App\Models\AdminDashboard;
use App\Models\DiscordMessage;
use App\Models\Offer;
use App\Models\RobosatsChatMessage;
use App\Services\DiscordService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;

class DiscordCommands implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $commands = [
            '!help',
            '!panic',
            '!calm',
            '!confirm',
            '!resetRevolut',
            '!getRevPayToken',
            '!getRevReadToken',
            '!chat',
            '!viewChat',
            '!collaborativeCancel',
            // '!toggleAutoAccept',
            // '!toggleAutoBond',
            // '!toggleAutoEscrow',
            // '!toggleAutoChat',
            // '!toggleAutoTopup',
            // '!setSellPremium',
            // '!setBuyPremium',
            // '!setConcurrentTransactions',
            // '!setMinSatProfit',
            // '!setMaxSatAmount',
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
                    $discordService->sendMessage('Executing command: ' . $message['content']);

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
                        case '!resetRevolut':
                            // unset revolut_auth_code_request in Redis
                            Redis::del('revolut_auth_code_request');
                            break;
                        case '!getRevPayToken':
                            $revolutService = new \App\Services\RevolutService();
                            $revArray = $revolutService->getPayToken();
                            $discordService->sendMessage('Reset RevToken at: ' . $revArray['url']);

                            break;
                        case '!getRevReadToken':
                            $revolutService = new \App\Services\RevolutService();
                            $revArray = $revolutService->getReadToken();
                            $discordService->sendMessage('Reset RevToken at: ' . $revArray['url']);
                            break;
                        case '!chat':
                            // grab offer id then message //!chat 6960 hello?
                            $offerId = explode(' ', $message['content'])[1];
                            $messageContent = explode(' ', $message['content'], 3)[2];
                            $offer = Offer::where('robosatsId', $offerId)->first();
                            $robot = $offer->robots()->first();
                            $robosats = new \App\WorkerClasses\Robosats();
                            $robosats->webSocketCommunicate($offer, $robot, $messageContent);
                            break;
                        case '!viewChat':
                            // grab offer id //!viewChat 6960
                            $offerId = explode(' ', $message['content'])[1];
                            $offer = Offer::where('robosatsId', $offerId)->first();
                            $chatMessages = RobosatsChatMessage::where('offer_id', $offer->id)->get();
                            $messages = "";
                            foreach ($chatMessages as $chatMessage) {
                                $messages = $messages . "**" . $chatMessage->user_nick . "**: " . $chatMessage->message . " \n";
                            }
                            $discordService->sendMessage($messages);
                            break;
                        case '!collaborativeCancel':
                            // grab offer id //!collaborativeCancel 6960
                            $offerId = explode(' ', $message['content'])[1];
                            $offer = Offer::where('robosatsId', $offerId)->first();
                            $robosats = new \App\WorkerClasses\Robosats();
                            $response = $robosats->collaborativeCancel($offer);
                            break;
                        default:
                            $discordService->sendMessage('Command not recognized');
                            break;

                    }
                }

            }
        }
    }
}
