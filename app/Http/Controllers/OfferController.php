<?php

namespace App\Http\Controllers;

use App\Models\AdminDashboard;
use App\Models\BtcFiat;
use App\Models\Offer;
use App\Models\Transaction;
use App\WorkerClasses\LightningNode;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class OfferController extends Controller
{

    private function getInfo()
    {

        $btcFiats = BtcFiat::where('currency', 'USD')->orWhere('currency', 'GBP')->orWhere('currency', 'EUR')->get();
        $allFiats = BtcFiat::all();
        $adminDashboard = AdminDashboard::all()->first();
        if (!$adminDashboard) {
            $adminDashboard = new AdminDashboard();
            $lightningNode = new LightningNode();
            $balanceArray = $lightningNode->getLightningWalletBalance();
            $adminDashboard->localBalance = $balanceArray['localBalance'];
            $adminDashboard->remoteBalance = $balanceArray['remoteBalance'];
            $adminDashboard->save();
        }

        $sellPremium = $adminDashboard->sell_premium;
        $buyPremium = $adminDashboard->buy_premium;
        $paymentMethods = json_decode($adminDashboard->payment_methods);

        $offers = Offer::where('accepted', '=', true)->orWhere([['accepted', '=', false],['premium', '>=', $sellPremium], ['type', 'sell']])->orWhere([['accepted', '=', false],['premium', '>=', $buyPremium], ['type', 'buy']])
            ->orderBy('accepted', 'desc')
            ->orderBy('max_satoshi_amount_profit', 'desc')
            ->orderBy('satoshi_amount_profit', 'desc')
            ->orderBy('premium', 'desc')
            ->get();
        // change the expires_at to a human readable format
        foreach ($offers as $offer) {
            $offer->expires_at = Carbon::parse($offer->expires_at)->diffForHumans();
            // round amount to 2 decimal places
            $offer->amount = number_format($offer->amount, 2);
            // round min_amount to 2 decimal places and max amount to 2 decimal places
            $offer->min_amount = number_format($offer->min_amount, 2);
            $offer->max_amount = number_format($offer->max_amount, 2);
            // add a percentage to the premium
            $offer->premium = $offer->premium . '%';
            $offer->payment_methods = json_decode($offer->payment_methods);
            // if array contains revolut else remove it
            // if (!in_array('Revolut', $offer->payment_methods)) {
            //     $offers = $offers->filter(function ($value, $key) use ($offer) {
            //         return $value->id != $offer->id;
            //     });
            // }

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


            // make human readable
            $offer->payment_methods = implode(', ', $offer->payment_methods);

            // if offer is accepted find the transaction
            if ($offer->accepted) {
                $transaction = Transaction::where('offer_id', $offer->id)->first();
                $offer->transaction = $transaction;
            }

            // if ($offer->status == 14 || $offer->transaction && $offer->transaction->status == 'Sucessful trade') { // they spelt successful wrong
            //     $offers = $offers->filter(function ($value, $key) use ($offer) {
            //         return $value->id != $offer->id;
            //     });
            // }

            // grab robots
            $offer->robots = $offer->robots()->get();
        }

        // convert the offers to an array
        $offersTemp = [];
        foreach ($offers as $offer) {
            $offersTemp[] = $offer;
        }
        $offers = $offersTemp;


        return [
            'btcFiats' => $btcFiats,
            'allFiats' => $allFiats,
            'adminDashboard' => $adminDashboard,
            'offers' => $offers
        ];
    }

    public function index()
    {
        $getInfo = $this->getInfo();
        $btcFiats = $getInfo['btcFiats'];
        $adminDashboard = $getInfo['adminDashboard'];
        $offers = $getInfo['offers'];

        return Inertia::render('Welcome', [
            'btcPrices' => $btcFiats,
            'offers' => $offers,
            'adminDashboard' => $adminDashboard
        ]);
    }

    public function getOffers()
    {
        $getInfo = $this->getInfo();
        $btcFiats = $getInfo['btcFiats'];
        $adminDashboard = $getInfo['adminDashboard'];
        $offers = $getInfo['offers'];

        return response()->json([
            'btcPrices' => $btcFiats,
            'offers' => $offers,
            'adminDashboard' => $adminDashboard
        ]);
    }
}
