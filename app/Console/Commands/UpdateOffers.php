<?php

namespace App\Console\Commands;

use App\Http\Controllers\OfferController;
use App\Models\AdminDashboard;
use App\Models\BtcFiat;
use App\Models\Offer;
use App\WorkerClasses\Robosats;
use Illuminate\Console\Command;

class UpdateOffers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh:offers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh Robosat offers';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        // delete any offers that are accepted equals false
        // Offer::where('accepted', false)->delete();

        // or if they are expired
        // Offer::where('expires_at', '<', now())->delete(); and the transaction also was not accepted
        Offer::where('expires_at', '<', now())->where('accepted', false)->delete();

        $robosats = new Robosats();
        $response = $robosats->getBookOffers();

        $adminDashboard = AdminDashboard::all()->first();
        if (!$adminDashboard) {
            $adminDashboard = new AdminDashboard();
        }

        $negativeBuyOffers = $robosats->getNegativePremiumBuyOffers($response['buyOffers'],  $adminDashboard->buy_premium);
        $positiveSellOffers = $robosats->getPositivePremiumSellOffers($response['sellOffers'],  $adminDashboard->sell_premium);

        // combine the offers
        // $allOffers = array_merge($negativeBuyOffers, $positiveSellOffers);
        $allOffers = $positiveSellOffers;
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
            if (!$found && !$dbOffer->accepted && $dbOffer->updated_at->diffInMinutes(now()) > 10 || $dbOffer->expires_at < now() && !$dbOffer->accepted) {
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
