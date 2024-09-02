<?php

namespace App\Jobs;

use App\Http\Controllers\OfferController;
use App\Models\AdminDashboard;
use App\Models\Offer;
use App\Models\Transaction;
use App\WorkerClasses\Robosats;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateOffers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    // timeout 180 seconds
    public int $timeout = 180;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // delete any offers that are accepted equals false
        // Offer::where('accepted', false)->delete();

        // or if they are expired
        // Offer::where('expires_at', '<', now())->delete(); and the transaction also was not accepted
        // Offer::where('expires_at', '<', now())
        //     ->where('accepted', false)
        //     ->delete();

        // grab transactions
        $transactions = Transaction::all();
        // grab ids by plucking the id from the transactions
        $ids = $transactions->pluck('offer_id')->toArray();
        // grab offers that are not in the transactions
        Offer::whereNotIn('id', $ids)
            ->where([['accepted', '=', false], ['my_offer', '=', false], ['expires_at', '<', now()], ['status', '<', 6]])
            ->delete();


        $robosats = new Robosats();
        $response = $robosats->getBookOffers();

        $adminDashboard = AdminDashboard::all()->first();
        if (!$adminDashboard) {
            $adminDashboard = new AdminDashboard();
        }

        // $negativeBuyOffers = $robosats->getNegativePremiumBuyOffers($response['buyOffers'],  $adminDashboard->buy_premium);
        // $positiveSellOffers = $robosats->getPositivePremiumSellOffers($response['sellOffers'],  $adminDashboard->sell_premium);
        // $allOffers = $positiveSellOffers;

        $allOffers = $robosats->getAllOffers($response['buyOffers'], $response['sellOffers']);


        // grab all the offers from the database and check if they aren't in allOffers and delete them
        $dbOffers = Offer::where('robosatsIdStorage', '=', null)->get();
        foreach ($dbOffers as $dbOffer) {
            $found = false;
            foreach ($allOffers as $provider => $offers) {
                foreach ($offers as $offer) {
                    if ($dbOffer->robosatsId == $offer['id']) {
                        $found = true;
                        break;
                    }
                }
                if ($found) {
                    break;
                }
            }
            // not found, not accept, last updated is more than 10 minutes ago || past the expiration date and not accepted
            if (!$found && !$dbOffer->accepted && !$dbOffer->my_offer && $dbOffer->updated_at->diffInMinutes(now()) > 5 ||
                !$found &&  !$dbOffer->accepted && !$dbOffer->my_offer && $dbOffer->expires_at < now() ) {
                // check if there is a transaction associated with the offer
                if ($dbOffer->transaction) {
                    $dbOffer->status = 5;
                    $dbOffer->status_message = 'Offer expired but has a transaction';
                    $dbOffer->save();
                    $dbOffer->transaction->status = 5;
                    $dbOffer->transaction->status_message = 'Offer expired but has a transaction';
                    $dbOffer->transaction->save();
                    continue;
                }
                $dbOffer->delete();
            }
        }


        // create buy offers in the database
        foreach ($allOffers as $provider => $offers) {
            $provider = ucfirst($provider);
            foreach ($offers as $offer) {
                (new OfferController)->insertOffer($offer, $provider);
            }
        }
    }
}
