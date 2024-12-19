<?php

namespace App\Console\Commands;

use App\Models\AdminDashboard;
use App\Models\Offer;
use Illuminate\Console\Command;

class PayEscrow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:pay-escrow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pay all escrows';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // every second check status of offer
        $adminDashboard = AdminDashboard::all()->first();
        if ($adminDashboard->panicButton || !$adminDashboard->autoEscrow) {
            // throw an exception
            return;
        }


        $offers = Offer::where([['status', '!=', 99], ['status', '!=', 5], ['status', '!=', 14]])->get();
        foreach ($offers as $offer) {

            // if status is 6 or 7 then pay escrow
            if ($offer->type === "sell" && ($offer->status == 6 || $offer->status == 7) && $adminDashboard->autoEscrow) {
                $job = new \App\Jobs\PayEscrow($offer, $adminDashboard);
                $job->handle();
            }

            $offer->save();

        }
    }
}
