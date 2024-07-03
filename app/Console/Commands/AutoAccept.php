<?php

namespace App\Console\Commands;

use App\Http\Controllers\OfferController;
use App\Models\AdminDashboard;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AutoAccept extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:accept';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find sell offers worth accepting';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $adminDashboard = AdminDashboard::all()->first();
        $maxConcurrentTransactions = $adminDashboard->max_concurrent_transactions;
        $transactions = Transaction::where('status', '<=', 11)->get();
        $transactionsCount = $transactions->count();
        if ($transactionsCount > $maxConcurrentTransactions) {
            return;
        }
        // calculate difference
        $difference = $maxConcurrentTransactions - $transactionsCount;
        $offers = (new \App\Http\Controllers\OfferController)->getOffersInternal($adminDashboard);


        foreach ($offers as $offer) {
            // check if any of the payment methods are in the admin dashboard payment methods, if not remove the offer
            $found = false;
            if ($paymentMethods == null) {
                $paymentMethods = [];
            }
            foreach ($offer->payment_methods as $paymentMethod) {
                if (in_array($paymentMethod, $paymentMethods)) {
                    $found = true;
                }
            }
            if (!$found) {
                $offers = $offers->filter(function ($value, $key) use ($offer) {
                    return $value->id != $offer->id;
                });
            }

            // check if the currency is in the admin dashboard currency, if not remove the offer
            if (!in_array($offer->currency, json_decode($adminDashboard->payment_currencies))) {
                $offers = $offers->filter(function ($value, $key) use ($offer) {
                    return $value->id != $offer->id;
                });
            }

        }

        // // score the offers using premium and satoshi profit
        // $offers = $offers->map(function ($offer) {
        //
        //
        //
        //     $offer->score =
        //     return $offer;
        // });



    }
}
