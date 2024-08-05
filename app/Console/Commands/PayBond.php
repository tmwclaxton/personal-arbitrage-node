<?php

namespace App\Console\Commands;

use App\Jobs\PayEscrow;
use App\Jobs\SendPaymentHandle;
use App\Models\AdminDashboard;
use App\Models\Offer;
use App\Models\Transaction;
use Illuminate\Console\Command;

class PayBond extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pay:bond';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
            // don't run the job again from auto job
            $offer->job_last_status = $offer->status;
            $offer->save();

            // if status is 3 then dispatch a bond job
            if (($offer->status == 3 || ($offer->is_maker && $offer->status == 0))
                && $adminDashboard->autoBond) {
                \App\Jobs\PayBond::dispatch($offer, $adminDashboard);
            }

            $offer->save();
        }
    }
}
