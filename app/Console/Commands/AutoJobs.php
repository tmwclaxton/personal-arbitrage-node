<?php

namespace App\Console\Commands;

use App\Jobs\PayBond;
use App\Jobs\PayEscrow;
use App\Jobs\GenerateInvoice;
use App\Jobs\SendPaymentHandle;
use App\Models\AdminDashboard;
use App\Models\Offer;
use App\Services\SlackService;
use App\WorkerClasses\HelperFunctions;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;

class AutoJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:jobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all the jobs that are needed to be run';

    /**
     * Execute the console command.
     * @throws GuzzleException
     */
    public function handle()
    {
        if (!(new HelperFunctions())->normalUmbrelCommandCheck()) {
            return;
        }

        // every second check status of offer
        $adminDashboard = AdminDashboard::all()->first();
        $slackService = new SlackService();
        $offers = Offer::where([['status', '!=', 99], ['status', '!=', 5], ['status', '!=', 14]])->get();
        foreach ($offers as $offer) {
            // if status is 0 and robosatsIdStorage is not null then continue
            $stop = false;
            // if ($offer->job_last_status != null && ($offer->job_last_status == $offer->status)) {
            //     $stop = true;
            // }
            if ($offer->status == 0 && !$offer->my_offer) {
                $stop = true;
            }
            if ($stop) {
                continue;
            }

            // rename slack channel
            if ($offer->job_last_status !== $offer->status) {

                if (isset($offer->slack_channel_id)) {
                    $slackService = new SlackService();
                    $sub = substr($offer->provider, 0, 3);
                    $statusMessage = str_replace(' ', '-', $offer->status_message);
                    $name = $sub . "-order-" . strval($offer->robosatsId) . "-" . $statusMessage;
                    $slackService->renameChannel($name, $offer->slack_channel_id);
                }
            }

            // don't run the job again from auto job
            $offer->job_last_status = $offer->status;
            $offer->save();

            // if status is less than 3 then it is not accepted
            if ($offer->status < 3) {
                $offer->accepted = false;
                $offer->save();
            }

            if (($offer->status < 3 && $offer->my_offer  || (!$offer->my_offer && ($offer->status == 3 || $offer->status > 6 && $offer->status < 14)))) {

                    // we want to create a Slack channel for the offer if it doesn't exist
                    $slackService = new SlackService();
                    if ($offer->slack_channel_id === null && $offer->robosatsId < 200000 && isset($offer->currency)) {
                        // first 3 letters of the provider then the robosatsId
                        $providerSub = substr($offer->provider, 0, 3);
                        $statusWithoutSpaces = str_replace(' ', '-', $offer->status_message);
                        $channel_id = $slackService->createChannel(
                            $providerSub . "-order-" . strval($offer->robosatsId) . "-" . $statusWithoutSpaces);
                        $offer->slack_channel_id = $channel_id;
                        $offer->save();

                        // send a message to the channel describing the offer
                        $message = "This " . $offer->type . " offer is ";

                        if ($offer->has_range) {
                            $message .= 'between ' . round($offer->min_amount,2) . ' and ' . round($offer->max_amount,2) . ' ' . $offer->currency . ' with a premium of ' . $offer->premium . '%';
                        } else {
                            $message .= 'for ' . round($offer->amount,2) . ' ' . $offer->currency . ' with a premium of ' . $offer->premium . '%';
                        }
                        $slackService->sendMessage($message, $channel_id);



                }
            }

            // if status is 3 then dispatch a bond job
            if ( ((!$offer->my_offer && $offer->status == 3) || ($offer->my_offer && $offer->status == 0)) && $adminDashboard->autoBond) {
                PayBond::dispatch($offer, $adminDashboard);
            }

            if ($offer->accepted === false && $offer->status > 3 && $offer->my_offer === true) {
                $template = $offer->templates()->first();
                $template->last_created = now();
                $template->save();
            }
            if ($offer->status > 3) {
                $offer->accepted = true;
                // if there is a template then update the last_accepted field
                if ($offer->templates()->first()) {
                    $template = $offer->templates()->first();
                    $template->last_accepted = now();
                    $template->save();
                }
                $offer->save();
            }

            // these jobs are best effort, they can't be guaranteed to run again if they fail, so there are backup jobs in console.php
            if ($offer->type === "sell" && ($offer->status == 6 || $offer->status == 7) && $adminDashboard->autoEscrow) {
                PayEscrow::dispatch($offer, $adminDashboard);
            }

            if ($offer->type === "buy" && ($offer->status == 6 || $offer->status == 8) && $adminDashboard->autoInvoice) {
                GenerateInvoice::dispatch($offer, $adminDashboard);
            }

            if ($offer->status == 9 && $adminDashboard->autoMessage) {
                SendPaymentHandle::dispatch($offer, $adminDashboard);
            }
            if ($offer->status == 10) {
                $slackService->sendMessage("Counterparty claims to have sent fiat. Please confirm.", $offer->slack_channel_id);
            }
            if ($offer->status == 11 || $offer->status == 16) {
                // send discord message or check programmatically
                $slackService->sendMessage("Offer " . $offer->robosatsId . " is in dispute", $offer->slack_channel_id);
            }

            $offer->save();
        }
    }
}
