<?php

namespace App\Jobs;

use AllowDynamicProperties;
use App\Models\AdminDashboard;
use App\Models\SlackMessage;
use App\Models\Offer;
use App\Models\RobosatsChatMessage;
use App\Services\SlackService;
use App\WorkerClasses\LightningNode;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;

#[AllowDynamicProperties] class SlackCommands implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     * @throws GuzzleException
     */
    public function handle(): void
    {
        $commands = [
            '!help',
            '!panic',
            '!calm',
            '!confirm',
            '!togglePause',
            '!chat',
            '!viewChat',
            '!collaborativeCancel',
            '!toggleAutoAccept',
            '!toggleAutoBond',
            '!toggleAutoEscrow',
            '!toggleAutoChat',
            '!toggleAutoTopup',
            '!toggleAutoConfirm',
            '!toggleAutoSchedule',
            '!toggleAutoCreate',
            '!setSellPremium',
            '!setBuyPremium',
            '!setConcurrentTransactions',
            '!setMinSatProfit',
            '!setMaxSatAmount',
            '!generateDepositAddress',
            '!listProfitableOffers',
            '!acceptSpecificOffer',
        ];
        $slackService = new SlackService();
        $adminDashboard = AdminDashboard::all()->first();
        $channelId = $adminDashboard->slack_main_channel_id;
        $offerChannelIds = Offer::where('slack_channel_id', '!=', null)->pluck('slack_channel_id')->toArray();
        $combinedChannelIds = array_merge([$channelId], $offerChannelIds);

        foreach ($combinedChannelIds as $channelId) {
            $messages = $slackService->getLatestMessages($channelId);
            foreach ($messages as $message) {

                // check if message already exists in the database
                if ($message->getClientMsgId() === null || SlackMessage::where('slack_id', $message->getClientMsgId())->exists() || $message->getBotId() !== null) {
                    continue;
                }

                $slackMessage = new SlackMessage([
                    'slack_id' => $message->getClientMsgId(),
                    'content' => $message->getText(),
                    'channel_id' => $channelId,
                ]);

                $slackMessage->save();

                // if the message was within the last 5 minutes check if it was a command (starts with /)
                if (Carbon::parse($slackMessage['created_at'])->diffInMinutes(Carbon::now()) < 1 && strpos($slackMessage['content'], '!') === 0) {

                    // check if the command is in the list of commands
                    $firstWord = explode(' ', $slackMessage['content'])[0];
                    if (in_array($firstWord, $commands)) {
                        $slackService->sendMessage('Executing command: ' . $slackMessage['content']);

                        // if it is, send a message to the slack channel
                        $adminDashboard = AdminDashboard::all()->first();
                        switch ($firstWord) {
                            case '!help':

                                $commandsFormatted = "";
                                foreach ($commands as $command) {
                                    $commandsFormatted = $commandsFormatted . $command . "\n";
                                }
                                $slackService->sendMessage("Available commands: \n" . $commandsFormatted, $channelId);

                                break;
                            case '!panic':
                                $adminDashboard->panicButton = true;
                                $adminDashboard->save();
                                $adminDashboardController = new \App\Http\Controllers\AdminDashboardController();
                                $adminDashboardController->panic();
                                break;
                            case '!calm':
                                $adminDashboard->panicButton = false;
                                $adminDashboard->save();
                                $adminDashboardController = new \App\Http\Controllers\AdminDashboardController();
                                $adminDashboardController->calm();
                                break;
                            case '!confirm':
                                $secondWord = explode(' ', $slackMessage['content'])[1];
                                $offer = Offer::where('robosatsId', $secondWord)->first();
                                ConfirmPayment::dispatch($offer, $adminDashboard);
                                break;
                            case '!togglePause':
                                // grab the offer id
                                $offerId = explode(' ', $slackMessage['content'])[1];
                                $offer = Offer::where('robosatsId', $offerId)->first();
                                $robosats = new \App\WorkerClasses\Robosats();
                                $robosats->togglePauseOffer($offer);
                                break;
                            case '!toggleAutoSchedule':
                                $adminDashboard->scheduler = !$adminDashboard->scheduler;
                                $adminDashboard->save();
                                $slackService->sendMessage('Auto schedule is now ' . ($adminDashboard->scheduler ? 'on' : 'off'), $channelId);
                                break;
                            case '!toggleAutoCreate':
                                $adminDashboard->autoCreate = !$adminDashboard->autoCreate;
                                $adminDashboard->save();
                                $slackService->sendMessage('Auto create is now ' . ($adminDashboard->autoCreate ? 'on' : 'off'), $channelId);
                                break;
                            case '!chat':
                                // grab offer id then message //!chat 6960 hello?
                                $offerId = explode(' ', $slackMessage['content'])[1];
                                $messageContent = explode(' ', $slackMessage['content'], 3)[2];
                                $offer = Offer::where('robosatsId', $offerId)->first();
                                $robot = $offer->robots()->first();
                                $robosats = new \App\WorkerClasses\Robosats();
                                $robosats->webSocketCommunicate($offer, $robot, $messageContent);
                                break;
                            case '!viewChat':
                                // grab offer id //!viewChat 6960
                                $offerId = explode(' ', $slackMessage['content'])[1];
                                $offer = Offer::where('robosatsId', $offerId)->first();
                                $chatMessages = RobosatsChatMessage::where('offer_id', $offer->id)->get();
                                $messages = "";
                                foreach ($chatMessages as $chatMessage) {
                                    $messages = $messages . "*" . $chatMessage->user_nick . "*: " . $chatMessage->message . " \n";
                                }
                                $slackService->sendMessage($messages);
                                break;
                            case '!collaborativeCancel':
                                // grab offer id //!collaborativeCancel 6960
                                $offerId = explode(' ', $slackMessage['content'])[1];
                                $offer = Offer::where('robosatsId', $offerId)->first();
                                $robosats = new \App\WorkerClasses\Robosats();
                                $response = $robosats->collaborativeCancel($offer);
                                break;
                            case '!toggleAutoAccept':
                                $adminDashboard->autoAccept = !$adminDashboard->autoAccept;
                                $adminDashboard->save();
                                $slackService->sendMessage('Auto accept is now ' . ($adminDashboard->autoAccept ? 'on' : 'off'), $channelId);
                                break;
                            case '!toggleAutoBond':
                                $adminDashboard->autoBond = !$adminDashboard->autoBond;
                                $adminDashboard->save();
                                $slackService->sendMessage('Auto bond is now ' . ($adminDashboard->autoBond ? 'on' : 'off'), $channelId);
                                break;
                            case '!toggleAutoEscrow':
                                $adminDashboard->autoEscrow = !$adminDashboard->autoEscrow;
                                $adminDashboard->save();
                                $slackService->sendMessage('Auto escrow is now ' . ($adminDashboard->autoEscrow ? 'on' : 'off'), $channelId);
                                break;
                            case '!toggleAutoChat':
                                $adminDashboard->autoChat = !$adminDashboard->autoChat;
                                $adminDashboard->save();
                                $slackService->sendMessage('Auto chat is now ' . ($adminDashboard->autoChat ? 'on' : 'off'), $channelId);
                                break;
                            case '!toggleAutoTopup':
                                $adminDashboard->autoTopup = !$adminDashboard->autoTopup;
                                $adminDashboard->save();
                                $slackService->sendMessage('Auto topup is now ' . ($adminDashboard->autoTopup ? 'on' : 'off'), $channelId);
                                break;
                            case '!toggleAutoConfirm':
                                $adminDashboard->autoConfirm = !$adminDashboard->autoConfirm;
                                $adminDashboard->save();
                                $slackService->sendMessage('Auto confirm is now ' . ($adminDashboard->autoConfirm ? 'on' : 'off'), $channelId);
                                break;
                            case '!setSellPremium':
                                $adminDashboard->sell_premium = intval(explode(' ', $slackMessage['content'])[1]);
                                // ensure the value is a float and positive
                                if ($adminDashboard->sell_premium < 0) {
                                    $slackService->sendMessage('Invalid value for sell premium ' . $adminDashboard->sell_premium, $channelId);
                                    break;
                                }
                                $adminDashboard->save();
                                $slackService->sendMessage('Sell premium set to ' . $adminDashboard->sell_premium, $channelId);
                                break;
                            case '!setBuyPremium':
                                $adminDashboard->buy_premium = intval(explode(' ', $slackMessage['content'])[1]);
                                // ensure the value is a float and negative
                                if ($adminDashboard->buy_premium > 0) {
                                    $slackService->sendMessage('Invalid value for buy premium', $channelId);
                                    break;
                                }
                                $adminDashboard->save();
                                $slackService->sendMessage('Buy premium set to ' . $adminDashboard->buy_premium, $channelId);
                                break;
                            case '!setConcurrentTransactions':
                                $adminDashboard->max_concurrent_transactions = intval(explode(' ', $slackMessage['content'])[1]);
                                // ensure the value is an integer and positive
                                if ($adminDashboard->max_concurrent_transactions < 0) {
                                    $slackService->sendMessage('Invalid value for concurrent transactions', $channelId);
                                    break;
                                }
                                $adminDashboard->save();
                                $slackService->sendMessage('Concurrent transactions set to ' . $adminDashboard->max_concurrent_transactions, $channelId);
                                break;
                            case '!setMinSatProfit':
                                $adminDashboard->min_satoshi_profit = intval(explode(' ', $slackMessage['content'])[1]);
                                // ensure the value is an integer and positive
                                if ($adminDashboard->min_satoshi_profit < 0) {
                                    $slackService->sendMessage('Invalid value for minimum satoshi profit', $channelId);
                                    break;
                                }
                                $adminDashboard->save();
                                $slackService->sendMessage('Minimum satoshi profit set to ' . $adminDashboard->min_satoshi_profit, $channelId);
                                break;
                            case '!setMaxSatAmount':
                                $adminDashboard->max_satoshi_amount = intval(explode(' ', $slackMessage['content'])[1]);
                                // ensure the value is an integer and positive
                                if ($adminDashboard->max_satoshi_amount < 0) {
                                    $slackService->sendMessage('Invalid value for maximum satoshi amount', $channelId);
                                    break;
                                }
                                $adminDashboard->save();
                                $slackService->sendMessage('Maximum satoshi amount set to ' . $adminDashboard->max_satoshi_amount, $channelId);
                                break;
                            case '!generateDepositAddress':
                                //!todo use the command GenerateInvoice and remove the code below
                                // kick off the job
                                GenerateInvoice::dispatch($adminDashboard);
                                break;
                            case '!listProfitableOffers':
                                $offerController = new \App\Http\Controllers\OfferController();
                                $offers = $offerController->getOffersInternal($adminDashboard, 0.5, -0.5);

                                // offer is a collection, remove any offers which are my_offer true
                                $offers = $offers->filter(function ($offer) {
                                    return !$offer->my_offer;
                                });
                                // remove any offers which are accepted
                                $offers = $offers->filter(function ($offer) {
                                    return !$offer->accepted;
                                });

                                $messages = [];
                                foreach ($offers as $offer) {
                                    // if it is a min - max offer
                                    if ($offer->has_range) {
                                        $messages[] = "ID: " . $offer->robosatsId . " | " . $offer->type . " BTC | " . $offer->min_amount . " - " . $offer->max_amount . " " . $offer->currency . " | Premium: " . $offer->premium
                                          .  " | " . json_encode($offer->payment_methods);
                                    } else {
                                        $messages[] = "ID: " . $offer->robosatsId . " | " . $offer->type . " BTC | " . $offer->amount  . " " . $offer->currency . " | Premium: " . $offer->premium
                                            .  " | " . json_encode($offer->payment_methods);
                                    }
                                }

                                // reverse order
                                $messages = array_reverse($messages);

                                $slackService->sendMessage(implode("\n\n", $messages), $channelId);
                                break;

                            case '!acceptSpecificOffer':
                                $offerId = explode(' ', $slackMessage['content'])[1];
                                $offer = Offer::where('robosatsId', $offerId)->first();

                                $slackService = new SlackService();
                                $slackService->sendMessage('Auto accepting offer ' . $offer->robosatsId . ' in 1 minute.', $adminDashboard->slack_main_channel_id);

                                $offer->auto_accept_at = Carbon::now()->addMinutes(1);
                                $offer->save();

                                break;

                            default:
                                $slackService->sendMessage('Command not recognized', $channelId);
                                break;

                        }
                    }
                    else {
                        $slackService->sendMessage('Command not recognized', $channelId);
                    }

                }


            }
            sleep(3);
        }
    }
}
