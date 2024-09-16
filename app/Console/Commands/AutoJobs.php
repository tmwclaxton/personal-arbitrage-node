<?php

namespace App\Console\Commands;

use App\Jobs\PayBond;
use App\Jobs\PayEscrow;
use App\Jobs\SendPaymentHandle;
use App\Models\AdminDashboard;
use App\Models\Offer;
use App\Services\SlackService;
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

        // every second check status of offer
        $adminDashboard = AdminDashboard::all()->first();
        $slackService = new SlackService();
        $offers = Offer::where([['status', '!=', 99], ['status', '!=', 5], ['status', '!=', 14]])->get();
        foreach ($offers as $offer) {
            // if status is 0 and robosatsIdStorage is not null then continue
            $stop = false;
            if ($offer->job_last_status != null && ($offer->job_last_status >= $offer->status)) {
                $stop = true;
            }
            if ($offer->status == 0 && $offer->my_offer === false) {
                $stop = true;
            }
            if ($stop) {
                continue;
            }
            // don't run the job again from auto job
            $offer->job_last_status = $offer->status;
            $offer->save();

            // if status is less than 3 then it is not accepted
            if ($offer->status < 3) {
                $offer->accepted = false;
                $offer->save();
            }

            // if status is 3 then dispatch a bond job
            if (($offer->status == 3 || ($offer->my_offer && $offer->status == 0))
                && $adminDashboard->autoBond) {
                // we want to create a Slack channel for the offer if it doesn't exist
                $slackService = new SlackService();
                if ($offer->slack_channel_id === null) {
                    $channel_id = $slackService->createChannel("order-" . strval($offer->robosatsId));
                    $offer->slack_channel_id = $channel_id;
                    $offer->save();
                }
                PayBond::dispatch($offer, $adminDashboard);
            }

            if ($offer->accepted === false && $offer->status > 3 && $offer->my_offer === true) {
                $template = $offer->templates()->first();
                $template->last_created = now();
            }
            if ($offer->status > 3) {
                $offer->accepted = true;
                $offer->save();

            }
            if (($offer->status == 6 || $offer->status == 7) && $adminDashboard->autoEscrow) {
                PayEscrow::dispatch($offer, $adminDashboard);
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
