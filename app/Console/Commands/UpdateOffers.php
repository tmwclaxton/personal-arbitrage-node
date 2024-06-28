<?php

namespace App\Console\Commands;

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
    protected $signature = 'refresh:robosat-offers';

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

        $negativeBuyOffers = $robosats->getNegativePremiumBuyOffers($response['buyOffers'], '0');
        $positiveSellOffers = $robosats->getPositivePremiumSellOffers($response['sellOffers'], '2');

        // combine the offers
        $allOffers = array_merge($negativeBuyOffers, $positiveSellOffers);

        // grab all the offers from the database and check if they aren't in allOffers and delete them
        $dbOffers = Offer::all();
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
            if (!$found && !$dbOffer->accepted) {
                $dbOffer->delete();
            }
        }

        // create buy offers in the database
        foreach ($allOffers as $provider => $offers) {
            $provider = ucfirst($provider);
            foreach ($offers as $offer) {
                // change id in the offer to robosatsId
                $offer['robosatsId'] = $offer['id'];

                // remove id from the offer
                unset($offer['id']);

                // change currency using Robosats::CURRENCIES
                $offer['currency'] = Robosats::CURRENCIES[$offer['currency']];

                // remove '/mainnet/' from the provider
                $provider = str_replace('Mainnet/', '', $provider);

                // lowercase the provider
                $provider = strtolower($provider);

                // // remove the '/' at the end of the provider
                $provider = rtrim($provider, '/');
                $offer['provider'] = $provider;

                // buy is 1 and sell is 2
                $offer['type'] = $offer['type'] == 1 ? 'buy' : 'sell';

                // convert the expires_at i.e. "2024-06-28T06:24:07.984166Z" to correct format
                $offer['expires_at'] = date('Y-m-d H:i:s', strtotime($offer['expires_at']));

                // convert the created_at i.e. "2024-06-28T06:24:07.984166Z" to correct format
                $offer['created_at'] = date('Y-m-d H:i:s', strtotime($offer['created_at']));

                // convert the payment_methods to a json array
                $offer['payment_methods'] = json_encode($offer['payment_methods']);

                // iterate through each key in the offer and set corresponding attributes
                $newOffer = new Offer();

                foreach ($offer as $key => $value) {
                    $newOffer->$key = $value;
                }
                // $newOffer->provider = $provider;

                // save or update the offer
                if (Offer::where('robosatsId', $offer['robosatsId'])->exists()) {
                    Offer::where('robosatsId', $offer['robosatsId'])->update($offer);
                } else {
                    $newOffer->save();
                }
            }
        }
    }
}
