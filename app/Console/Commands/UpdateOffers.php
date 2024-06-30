<?php

namespace App\Console\Commands;

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
            // not found, not accept, last updated is more than 1 hour ago || past the expiration date and not accepted
            if (!$found && !$dbOffer->accepted && $dbOffer->updated_at->diffInHours(now()) > 1 || $dbOffer->expires_at < now() && !$dbOffer->accepted) {
                $dbOffer->delete();
            }
        }

        $allFiats = BtcFiat::all();

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

                if ($allFiats && $allFiats->count() > 0 && isset($offer['price']) && $offer['price'] > 0) {
                    // grab currency from offer and find the price in btc using allFiats
                    $btcPrice = $allFiats->where('currency', $offer['currency'])->first();
                    if (!$offer['has_range']) {
                        $offer['satoshis_now'] = intval(str_replace(',', '', $offer['amount'])) / $offer['price'] * 100000000;
                        $offer['satoshis_now'] = intval(str_replace(',', '', number_format($offer['satoshis_now'], 0)));
                        $offer['satoshi_amount_profit'] = intval(str_replace(',', '', $offer['amount'])) / $btcPrice->price * 100000000;
                        $offer['satoshi_amount_profit'] = intval(str_replace(',', '', number_format($offer['satoshi_amount_profit'], 0))) - $offer['satoshis_now'];
                        // dd($offer['satoshi_amount_profit']);
                    } else {
                        $offer['min_satoshi_amount'] = intval(str_replace(',', '', $offer['min_amount'])) / $offer['price']  * 100000000;
                        $offer['min_satoshi_amount'] = intval(str_replace(',', '', number_format($offer['min_satoshi_amount'], 0)));
                        $offer['max_satoshi_amount'] = intval(str_replace(',', '', $offer['max_amount'])) / $offer['price']  * 100000000;
                        $offer['max_satoshi_amount'] = intval(str_replace(',', '', number_format($offer['max_satoshi_amount'], 0)));

                        // calculate the profit by using the bitcoin price and subtracting the value calculated from the price they are offering
                        $actualMinSatoshiAmount = intval(str_replace(',', '', $offer['min_amount'])) / $btcPrice->price * 100000000;
                        $actualMinSatoshiAmount = intval(str_replace(',', '', number_format($actualMinSatoshiAmount, 0)));
                        $offer['min_satoshi_amount_profit'] = $actualMinSatoshiAmount - $offer['min_satoshi_amount'];

                        $actualMaxSatoshiAmount = intval(str_replace(',', '', $offer['max_amount'])) / $btcPrice->price * 100000000;
                        $actualMaxSatoshiAmount = intval(str_replace(',', '', number_format($actualMaxSatoshiAmount, 0)));
                        $offer['max_satoshi_amount_profit'] = $actualMaxSatoshiAmount - $offer['max_satoshi_amount'];

                    }

                }

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
