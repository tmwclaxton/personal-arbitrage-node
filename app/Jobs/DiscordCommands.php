<?php

namespace App\Jobs;

use App\Models\AdminDashboard;
use App\Models\DiscordMessage;
use App\Models\Offer;
use App\Models\RevolutAccessToken;
use App\Models\RobosatsChatMessage;
use App\Services\SlackService;
use App\WorkerClasses\LightningNode;
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
            '!toggleAutoAccept',
            '!toggleAutoBond',
            '!toggleAutoEscrow',
            '!toggleAutoChat',
            '!toggleAutoTopup',
            '!toggleAutoConfirm',
            '!autoSchedule',
            '!autoCreate',
            '!setSellPremium',
            '!setBuyPremium',
            '!setConcurrentTransactions',
            '!setMinSatProfit',
            '!setMaxSatAmount',
            '!generateDepositAddress',
        ];
        $slackService = new SlackService();
        $latestMessages = $slackService->getLatestMessages();
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
                    $slackService->sendMessage('Executing command: ' . $message['content']);

                    // if it is, send a message to the discord channel
                    $adminDashboard = AdminDashboard::all()->first();
                    switch ($firstWord) {
                        case '!help':

                            $commandsFormatted = "";
                            foreach ($commands as $command) {
                                $commandsFormatted = $commandsFormatted . $command . "\n";
                            }
                            $slackService->sendMessage("Available commands: \n" . $commandsFormatted);

                            break;
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
                        case '!autoSchedule':
                            $adminDashboard->scheduler = !$adminDashboard->scheduler;
                            $adminDashboard->save();
                            $slackService->sendMessage('Auto schedule is now ' . ($adminDashboard->scheduler ? 'on' : 'off'));
                            break;
                        case '!autoCreate':
                            $adminDashboard->autoCreate = !$adminDashboard->autoCreate;
                            $adminDashboard->save();
                            $slackService->sendMessage('Auto create is now ' . ($adminDashboard->autoCreate ? 'on' : 'off'));
                            break;
                        case '!resetRevolut':
                            // unset revolut_auth_code_request in Redis
                            Redis::del('revolut_auth_code_request');
                            break;
                        case '!getRevPayToken':
                            Redis::del('revolut_auth_code_request');
                            $revToken = RevolutAccessToken::where('type', 'PAY')->first();
                            if ($revToken) {
                                $revToken->delete();
                            }
                            $revolutService = new \App\Services\RevolutService();
                            $revArray = $revolutService->getPayToken();

                            break;
                        case '!getRevReadToken':
                            Redis::del('revolut_auth_code_request');
                            // delete the read token
                            $revToken = RevolutAccessToken::where('type', 'READ')->first();
                            if ($revToken) {
                                $revToken->delete();
                            }
                            $revolutService = new \App\Services\RevolutService();
                            $revArray = $revolutService->getReadToken();
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
                            $slackService->sendMessage($messages);
                            break;
                        case '!collaborativeCancel':
                            // grab offer id //!collaborativeCancel 6960
                            $offerId = explode(' ', $message['content'])[1];
                            $offer = Offer::where('robosatsId', $offerId)->first();
                            $robosats = new \App\WorkerClasses\Robosats();
                            $response = $robosats->collaborativeCancel($offer);
                            break;
                        case '!toggleAutoAccept':
                            $adminDashboard->autoAccept = !$adminDashboard->autoAccept;
                            $adminDashboard->save();
                            $slackService->sendMessage('Auto accept is now ' . ($adminDashboard->autoAccept ? 'on' : 'off'));
                            break;
                        case '!toggleAutoBond':
                            $adminDashboard->autoBond = !$adminDashboard->autoBond;
                            $adminDashboard->save();
                            $slackService->sendMessage('Auto bond is now ' . ($adminDashboard->autoBond ? 'on' : 'off'));
                            break;
                        case '!toggleAutoEscrow':
                            $adminDashboard->autoEscrow = !$adminDashboard->autoEscrow;
                            $adminDashboard->save();
                            $slackService->sendMessage('Auto escrow is now ' . ($adminDashboard->autoEscrow ? 'on' : 'off'));
                            break;
                        case '!toggleAutoChat':
                            $adminDashboard->autoChat = !$adminDashboard->autoChat;
                            $adminDashboard->save();
                            $slackService->sendMessage('Auto chat is now ' . ($adminDashboard->autoChat ? 'on' : 'off'));
                            break;
                        case '!toggleAutoTopup':
                            $adminDashboard->autoTopup = !$adminDashboard->autoTopup;
                            $adminDashboard->save();
                            $slackService->sendMessage('Auto topup is now ' . ($adminDashboard->autoTopup ? 'on' : 'off'));
                            break;
                        case '!toggleAutoConfirm':
                            $adminDashboard->autoConfirm = !$adminDashboard->autoConfirm;
                            $adminDashboard->save();
                            $slackService->sendMessage('Auto confirm is now ' . ($adminDashboard->autoConfirm ? 'on' : 'off'));
                            break;
                        case '!setSellPremium':
                            $adminDashboard->sell_premium = intval(explode(' ', $message['content'])[1]);
                            // ensure the value is a float and positive
                            if ($adminDashboard->sell_premium < 0) {
                                $slackService->sendMessage('Invalid value for sell premium ' . $adminDashboard->sell_premium);
                                break;
                            }
                            $adminDashboard->save();
                            $slackService->sendMessage('Sell premium set to ' . $adminDashboard->sell_premium);
                            break;
                        case '!setBuyPremium':
                            $adminDashboard->buy_premium = intval(explode(' ', $message['content'])[1]);
                            // ensure the value is a float and negative
                            if ($adminDashboard->buy_premium > 0) {
                                $slackService->sendMessage('Invalid value for buy premium');
                                break;
                            }
                            $adminDashboard->save();
                            $slackService->sendMessage('Buy premium set to ' . $adminDashboard->buy_premium);
                            break;
                        case '!setConcurrentTransactions':
                            $adminDashboard->max_concurrent_transactions = intval(explode(' ', $message['content'])[1]);
                            // ensure the value is an integer and positive
                            if ($adminDashboard->max_concurrent_transactions < 0) {
                                $slackService->sendMessage('Invalid value for concurrent transactions');
                                break;
                            }
                            $adminDashboard->save();
                            $slackService->sendMessage('Concurrent transactions set to ' . $adminDashboard->max_concurrent_transactions);
                            break;
                        case '!setMinSatProfit':
                            $adminDashboard->min_satoshi_profit = intval(explode(' ', $message['content'])[1]);
                            // ensure the value is an integer and positive
                            if ($adminDashboard->min_satoshi_profit < 0) {
                                $slackService->sendMessage('Invalid value for minimum satoshi profit');
                                break;
                            }
                            $adminDashboard->save();
                            $slackService->sendMessage('Minimum satoshi profit set to ' . $adminDashboard->min_satoshi_profit);
                            break;
                        case '!setMaxSatAmount':
                            $adminDashboard->max_satoshi_amount = intval(explode(' ', $message['content'])[1]);
                            // ensure the value is an integer and positive
                            if ($adminDashboard->max_satoshi_amount < 0) {
                                $slackService->sendMessage('Invalid value for maximum satoshi amount');
                                break;
                            }
                            $adminDashboard->save();
                            $slackService->sendMessage('Maximum satoshi amount set to ' . $adminDashboard->max_satoshi_amount);
                            break;
                        case '!generateDepositAddress':
                            $krakenService = new \App\Services\KrakenService();
                            $btcBalance = $krakenService->getBTCBalance();
                            $btc = $btcBalance->jsonSerialize();
                            // ensure satoshis is an integer
                            $satoshis = intval($btc * 100000000) - 2000; // possible fees?

                            $adminDashboard = AdminDashboard::all()->first();
                            $remoteBalance = $adminDashboard->remoteBalance;
                            $localBalance = $adminDashboard->localBalance;
                            if ($satoshis > $remoteBalance - 200000) {
                                $satoshis = $remoteBalance - 200000;
                            }
                            $idealLightningNodeBalance = $adminDashboard->ideal_lightning_node_balance;
                            if ($localBalance + $satoshis > $idealLightningNodeBalance) {
                                $satoshis = $idealLightningNodeBalance - $localBalance;
                                if ($satoshis <= 0) {
                                    $slackService->sendMessage('You have already reached the ideal balance');
                                    break;
                                }
                            }

                            // if the satoshis is less than 2000, don't create an invoice
                            if ($satoshis < 2000) {
                                $slackService->sendMessage('Not enough BTC to create an invoice');
                                break;
                            }


                            $lightningNode = new LightningNode();
                            $invoice = $lightningNode->createInvoice($satoshis, 'Kraken BTC Withdrawal of ' . $btcBalance . ' BTC at ' . Carbon::now()->toDateTimeString());
                            $slackService->sendMessage($invoice);
                            break;


                        default:
                            $slackService->sendMessage('Command not recognized');
                            break;

                    }
                }

            }
        }
    }
}
