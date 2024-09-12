<?php

namespace App\Console\Commands;

use App\Jobs\PayBond;
use App\Jobs\PayEscrow;
use App\Jobs\SendPaymentHandle;
use App\Models\AdminDashboard;
use App\Models\Offer;
use App\Services\SlackService;
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
     */
    public function handle()
    {

        //    public static $statusText = [
        //         0 => 'Waiting for maker bond',
        //         1 => 'Public',
        //         2 => 'Paused',
        //         3 => 'Waiting for taker bond',
        //         4 => 'Cancelled',
        //         5 => 'Expired',
        //         6 => 'Waiting for trade collateral and buyer invoice',
        //         7 => 'Waiting only for seller trade collateral',
        //         8 => 'Waiting only for buyer invoice',
        //         9 => 'Sending fiat - In chatroom',
        //         10 => 'Fiat sent - In chatroom',
        //         11 => 'In dispute',
        //         12 => 'Collaboratively cancelled',
        //         13 => 'Sending satoshis to buyer',
        //         14 => 'Sucessful trade',
        //         15 => 'Failed lightning network routing',
        //         16 => 'Wait for dispute resolution',
        //         17 => 'Maker lost dispute',
        //         18 => 'Taker lost dispute',
        //         99 => 'Collaboratively cancelled',
        //     ];


        // every second check status of offer
        $adminDashboard = AdminDashboard::all()->first();
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
                PayBond::dispatch($offer, $adminDashboard);
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
                $slackService = new SlackService();
                $slackService->sendMessage($offer->slack_channel_id, "Counterparty claims to have sent fiat. Please confirm.");
            }
            if ($offer->status == 11 || $offer->status == 16) {
                // send discord message or check programmatically
                (new \App\Services\SlackService)->sendMessage('Offer ' . $offer->robosatsId . ' is in dispute');
            }

            $offer->save();
        }
    }
}
