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
    protected $description = 'Pay bond for offers';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        // every second check status of offer
        $adminDashboard = AdminDashboard::all()->first();
        if ($adminDashboard->panicButton || !$adminDashboard->autoBond) {
            // throw an exception
            return;
        }

        $offers = Offer::where([
            ['status', '!=', 99], ['status', '!=', 5],
            ['status', '!=', 14], ['expires_at', '>', now()]
        ])->get();
        foreach ($offers as $offer) {

            // if status is 3 then dispatch a bond job
            if (($offer->status == 3 || ($offer->my_offer && $offer->status == 0))
                && $adminDashboard->autoBond) {
                $job = new \App\Jobs\PayBond($offer, $adminDashboard);
                $job->handle();
            }

            $offer->save();

        }
    }
}
