<?php

namespace App\Http\Controllers;

use App\Models\AdminDashboard;
use App\Models\BtcFiat;
use App\Models\Offer;
use App\Models\RobosatsChatMessage;
use App\Models\Transaction;
use App\Services\DiscordService;
use App\WorkerClasses\HelperFunctions;
use App\WorkerClasses\LightningNode;
use App\WorkerClasses\Robosats;
use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Inertia\Inertia;

class OfferController extends Controller
{
    public function getOffersInternal($adminDashboard)
    {
        $sellPremium = $adminDashboard->sell_premium;
        $buyPremium = $adminDashboard->buy_premium;

        // where status != 14, 12, 17, 18, 99, 4, 5, 2
        $offers = Offer::where([['accepted', '=', true], ['status', '!=', 99], ['status', '!=', 5], ['status', '!=', 14]])
            ->orWhere([['accepted', '=', false],['premium', '>=', $sellPremium], ['type', 'sell']])
            ->orWhere([['accepted', '=', false],['premium', '<=', $buyPremium], ['type', 'buy']])
            ->orWhere([['accepted', '=', false],['robotTokenBackup', '!=', null], ['robosatsIdStorage', '=', null], ['expires_at', '>', now()]])
            ->orderBy('accepted', 'desc')
            ->orderBy('my_offer', 'desc')
            ->orderBy('max_satoshi_amount_profit', 'desc')
            ->orderBy('satoshi_amount_profit', 'desc')
            ->orderBy('premium', 'desc')
            ->get();

        return $offers;
    }

    public function getInfo()
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
            $adminDashboard->channelBalances = json_encode($balanceArray['channelBalances']);
            $adminDashboard->save();
        }


        $offers = $this->getOffersInternal($adminDashboard);
        $paymentMethods = json_decode($adminDashboard->payment_methods);


        // change the expires_at to a human readable format
        foreach ($offers as $offer) {
            $offer->expires_at = Carbon::parse($offer->expires_at)->diffForHumans();
            $offer->updated_at_readable = Carbon::parse($offer->updated_at)->diffForHumans();
            if ($offer->auto_accept_at) {
                $offer->auto_accept_at = Carbon::parse($offer->auto_accept_at)->diffForHumans();
            }
            if ($offer->auto_confirm_at) {
                $offer->auto_confirm_at = Carbon::parse($offer->auto_confirm_at)->diffForHumans();
            }
            // round amount to 2 decimal places
            $offer->amount = number_format($offer->amount, 2);
            $offer->accepted_offer_amount = number_format($offer->accepted_offer_amount, 2) . ' ' . $offer->currency;
            // round min_amount to 2 decimal places and max amount to 2 decimal places
            $offer->min_amount = number_format($offer->min_amount, 2);
            $offer->max_amount = number_format($offer->max_amount, 2);
            // add a percentage to the premium
            $offer->premium = $offer->premium . '%';
            $offer->payment_methods = json_decode($offer->payment_methods);
            $offer->escrow_duration = CarbonInterval::seconds($offer->escrow_duration)->cascade()->forHumans();



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
            if ($offer->accepted || ($offer->robosatsIdStorage == null && $offer->robotTokenBackup != null)) {
                $transaction = Transaction::where('offer_id', $offer->id)->first();
                $offer->transaction = $transaction;
            }


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
            'adminDashboard' => $adminDashboard,
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

    public function insertOffer($offer, $provider): Offer
    {

        $allFiats = BtcFiat::all();

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

        // buy is 1 and sell is 2 // if we are the taker
        if ($offer['is_maker'] = 0) {
            $offer['type'] = $offer['type'] == 1 ? 'sell' : 'buy';
        } else {
            $offer['type'] = $offer['type'] == 1 ? 'buy' : 'sell';
        }

        // convert the expires_at i.e. "2024-06-28T06:24:07.984166Z" to correct format
        $offer['expires_at'] = date('Y-m-d H:i:s', strtotime($offer['expires_at']));

        // convert the created_at i.e. "2024-06-28T06:24:07.984166Z" to correct format
        $offer['created_at'] = date('Y-m-d H:i:s', strtotime($offer['created_at']));

        // if payment_method is given, change to payment_methods
        if (isset($offer['payment_method'])) {
            $offer['payment_methods'] = [$offer['payment_method']];
            unset($offer['payment_method']);
        }

        // if the items Instant and Sepa are in the payment_methods, remove them and replace them with 'Instant SEPA'
        if (in_array('Instant', $offer['payment_methods']) && in_array('SEPA', $offer['payment_methods'])) {
            // remove the Instant and Sepa from the payment_methods
            $offer['payment_methods'] = array_diff($offer['payment_methods'], ['Instant', 'SEPA']);
            // add 'Instant SEPA' to the payment_methods
            $offer['payment_methods'][] = 'Instant SEPA';
        }

        // if the items Paypal Friends & Family (all separate) are in the payment_methods, remove them and replace them with 'Paypal Friends & Family'
        if (in_array('Paypal', $offer['payment_methods']) && in_array('Friends', $offer['payment_methods']) && in_array('Family', $offer['payment_methods'])) {
            // remove the Paypal Friends & Family from the payment_methods
            $offer['payment_methods'] = array_diff($offer['payment_methods'], ['Paypal', 'Friends', 'Family', '&']);
            // add 'Paypal Friends & Family' to the payment_methods
            $offer['payment_methods'][] = 'Paypal Friends & Family';
        }

        // if ["Amazon", "IT", "GiftCard"]
        if (in_array('Amazon', $offer['payment_methods']) && in_array('IT', $offer['payment_methods']) && in_array('GiftCard', $offer['payment_methods'])) {
            // remove the Amazon IT GiftCard from the payment_methods
            $offer['payment_methods'] = array_diff($offer['payment_methods'], ['Amazon', 'IT', 'GiftCard']);
            // add 'Amazon IT GiftCard' to the payment_methods
            $offer['payment_methods'][] = 'Amazon IT GiftCard';
        }

        // ["Amazon", "DE", "GiftCard"]
        if (in_array('Amazon', $offer['payment_methods']) && in_array('DE', $offer['payment_methods']) && in_array('GiftCard', $offer['payment_methods'])) {
            // remove the Amazon DE GiftCard from the payment_methods
            $offer['payment_methods'] = array_diff($offer['payment_methods'], ['Amazon', 'DE', 'GiftCard']);
            // add 'Amazon DE GiftCard' to the payment_methods
            $offer['payment_methods'][] = 'Amazon DE GiftCard';
        }

        // convert the payment_methods to a json array without a key
        $offer['payment_methods'] = json_encode(array_values($offer['payment_methods']));

        if (array_key_exists('price_now', $offer)) {
            $offer['price'] = $offer['price_now'];
            unset($offer['price_now']);
        }


        if ($allFiats && $allFiats->count() > 0 && isset($offer['price']) && $offer['price'] > 0) {
            // grab currency from offer and find the price in btc using allFiats
            $btcPrice = $allFiats->where('currency', $offer['currency'])->first();
            // once a ranged offer is accepted, the amount is set to whatever we are selling
            if ($offer['amount']) {
                $offer['satoshis_now'] = intval(str_replace(',', '', $offer['amount'])) / $offer['price'] * 100000000;
                $offer['satoshis_now'] = intval(str_replace(',', '', number_format($offer['satoshis_now'], 0)));
                $offer['satoshi_amount_profit'] = intval(str_replace(',', '', $offer['amount'])) / $btcPrice->price * 100000000;
                $offer['satoshi_amount_profit'] = intval(str_replace(',', '', number_format($offer['satoshi_amount_profit'], 0))) - $offer['satoshis_now'];
            }
            if ($offer['min_amount'] && $offer['max_amount']) {
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

        if (array_key_exists('bond_invoice', $offer)) {
            $bond_invoice = $offer['bond_invoice'];
            // remove the bond_invoice from the offer
            unset($offer['bond_invoice']);
            unset($offer['bond_satoshis']);
        }

        // iterate through each key in the offer and set corresponding attributes
        $newOffer = new Offer();

        foreach ($offer as $key => $value) {
            $newOffer->$key = $value;
        }

        // save or update the offer
        if (Offer::where('robosatsId', $offer['robosatsId'])->exists()) {
            Offer::where('robosatsId', $offer['robosatsId'])->update($offer);
        } else {
            $newOffer->save();
        }

        $offer = Offer::where('robosatsId', $offer['robosatsId'])->first();

        return $offer;
    }

    public function calculateLargestAmount($offer, $channelBalances) {
        // grab the offer price amount or max amount
        if ($offer->has_range) {
            if (!isset($offer->min_satoshi_amount) || !isset($offer->max_satoshi_amount)) {
                (new DiscordService)->sendMessage('Error: Offer has range but no min or max amount');
                return 'Offer has range but no min or max amount';
            }
            $variationAmounts = [
                $offer->min_satoshi_amount,
                $offer->min_satoshi_amount + ($offer->max_satoshi_amount - $offer->min_satoshi_amount) / 8,
                $offer->min_satoshi_amount + ($offer->max_satoshi_amount - $offer->min_satoshi_amount) / 4,
                $offer->min_satoshi_amount + ($offer->max_satoshi_amount - $offer->min_satoshi_amount) * 3 / 8,
                $offer->min_satoshi_amount + ($offer->max_satoshi_amount - $offer->min_satoshi_amount) / 2,
                $offer->min_satoshi_amount + ($offer->max_satoshi_amount - $offer->min_satoshi_amount) * 5 / 8,
                $offer->min_satoshi_amount +  ($offer->max_satoshi_amount - $offer->min_satoshi_amount) * 3 / 4,
                $offer->min_satoshi_amount + ($offer->max_satoshi_amount - $offer->min_satoshi_amount) * 7 / 8,
                $offer->max_satoshi_amount
            ];
        } else {
            if (!isset($offer->satoshis_now)) {
                (new DiscordService)->sendMessage('Error: Offer has no amount');
                return 'Offer has no amount';
            }
            $variationAmounts = [$offer->satoshis_now];
        }

        // THIS FILTERS OUT ANY VARIATION AMOUNTS THAT ARE GREATER THAN THE MAX SATOSHI AMOUNT
        $adminDashboard = AdminDashboard::all()->first();
        $max_satoshi_amount = $adminDashboard->max_satoshi_amount;
        // remove any variation amounts that are greater than the max_satoshi_amount
        $variationAmounts = array_filter($variationAmounts, function ($variationAmount) use ($max_satoshi_amount) {
            return $variationAmount <= $max_satoshi_amount;
        });
        //////

        // foreach $variationAmounts try to find the largest offer that can be accepted
        $largestAmountSat = 0;
        // order the variation amounts from largest to smallest
        $variationAmounts = array_reverse($variationAmounts);
        foreach ($variationAmounts as $variationAmount) {
            $openChannels = 0;
            foreach ($channelBalances as $channelBalance) {
                // set variation amount to an integer i.e. no decimal places
                $variationAmount = (int) $variationAmount;
                // localBalance is our send capacity
                if ((int) $channelBalance['localBalance'] > $variationAmount + 100000 ) {
                    // dd($channelBalance);
                    $openChannels++;

                }
            }
            if ($openChannels > 0) {
                $largestAmountSat = $variationAmount;
                // break out of both loops
                break;
            }
        }

        if ($largestAmountSat == 0) {
            // (new DiscordService)->sendMessage('Error: Insufficient balance (ps need 100000 extra for fees for bond and potentially fees)');
            return [
                'estimated_offer_amount_sats' => 0,
                'estimated_offer_amount' => 0,
                'estimated_profit_sats' => 0
            ];
        }

        $estimated_offer_amount_sat = $offer->range ? $offer->satoshis_now : $largestAmountSat;
        // convert largest amount back to fiat
        $helpFunction = new HelperFunctions();
        $estimated_offer_amount = $offer->range ?
            round($helpFunction->satoshiToFiat($offer->satoshis_now, $offer->price), 0) :
            round($helpFunction->satoshiToFiat($largestAmountSat, $offer->price), 0) ;


        $btcFiats = BtcFiat::all();
        $btcFiat = $btcFiats->where('currency', $offer->currency)->first();
        // check estimated profit
        if ($offer->has_range) {
            $currentRealPrice = $btcFiat->price;
            $estimated_profit_sats = -$estimated_offer_amount_sat * (($currentRealPrice - $offer->price) / $currentRealPrice);
        } else {
            $estimated_profit_sats = $offer->satoshi_amount_profit;
        }

        return [
            'estimated_offer_amount_sats' =>  $estimated_offer_amount_sat,
            'estimated_offer_amount' => $estimated_offer_amount,
            'estimated_profit_sats' => $estimated_profit_sats
        ];
    }

    public function chatRoom($offerId)
    {
        $offer = Offer::find($offerId);
        $messages = RobosatsChatMessage::where('offer_id', $offer->id)->get();
        return Inertia::render('ChatRoom', [
            'offer' => $offer,
            'messages' => $messages
        ]);
    }

    public function sendMessage(Request $request)
    {
        $message = $request->message;
        $offerId = $request->offer_id;
        $offer = Offer::find($offerId);
        $robot = $offer->robots()->first();
        $robosats = new Robosats();
        $robosats->webSocketCommunicate($offer, $robot, $message);
        return response()->json(['message' => 'Message sent']);
    }

    public function createRobot(Request $request) {
        $offerId = request('offer_id');
        $offer = Offer::find($offerId);

        $robosats = new Robosats();
        $response = $robosats->createRobot($offer);
        return $response;
    }

    public function acceptOffer(Request $request) {
        $robosats = new Robosats();
        $offerId = request('offer_id');
        $offer = Offer::find($offerId);
        $response = $robosats->acceptOffer($offer->robosatsId);
        return $response;
    }

    public function payBond(Request $request) {
        $offerId = request('offer_id');
        $transaction = Transaction::where('offer_id', $offerId)->first();
        $invoice = $transaction->bond_invoice;
        $lightningNode = new LightningNode();
        $response = $lightningNode->payInvoice($invoice);
        return $response;
    }

    public function payEscrow(Request $request) {
        // grab offer_id and transaction_id
        $offerId = request('offer_id');
        $transaction = Transaction::where('offer_id', $offerId)->first();
        $escrowInvoice = $transaction->escrow_invoice;
        // dd($escrowInvoice);
        $lightningNode = new LightningNode();
        $response = $lightningNode->payInvoice($escrowInvoice);
        return $response;
    }

    public function confirmPayment(Request $request) {
        $offerId = request('offer_id');
        $offer = Offer::find($offerId);
        $transaction = Transaction::where('offer_id', $offerId)->first();
        $robosats = new Robosats();
        $response = $robosats->confirmReceipt($offer, $transaction);
        return $response;
    }

    public function claimRewards() {
        $robosats = new Robosats();
        $robots = Robot::where('earned_rewards', '>', 0)->get();
        foreach ($robots as $robot) {
            $response = $robosats->claimCompensation($robot);
        }
        return response()->json(
            ['message' => 'Rewards claimed for' . count($robots) . ' robots',
                'robots' => $robots]
        );
    }

    public function sendPaymentHandle(Request $request) {
        $offerId = request('offer_id');
        $offer = Offer::find($offerId);
        $robosats = new Robosats();
        $robosats->sendHandle($offer);
    }

    public function autoAccept(Request $request) {
        $adminDashboard = AdminDashboard::all()->first();
        $offerId = request('offer_id');
        $offer = Offer::where('id', $offerId)->first();
        Bus::chain([
            new \App\Jobs\CreateRobots($offer, $adminDashboard),
            new \App\Jobs\AcceptSellOffer($offer, $adminDashboard)
        ])->dispatch();
    }

    public function collaborativeCancel(Request $request) {
        $offerId = request('offer_id');
        $offer = Offer::find($offerId);
        $robosats = new Robosats();
        $response = $robosats->collaborativeCancel($offer);
        return $response;
    }

    public function completedOffers()
    {
        $offers = Offer::where('status', '=' , 14);
        return Inertia::render('CompletedOffers', [
            'offers' => $offers->paginate(25)->setPath(route('offers.completed'))->through(fn($offer)=>[
                "Token Backup" => $offer->robotTokenBackup,
                "Accepted Offer Amount" => $offer->accepted_offer_amount,
                "Accepted Offer Amount Satoshis" => $offer->accepted_offer_amount_satoshis,
                "Accepted Offer Amount Profit" => $offer->accepted_offer_amount_profit,
                "Accepted Offer Amount Profit Satoshis" => $offer->accepted_offer_amount_profit_satoshis,
                "Currency" => $offer->currency,
                "Btc Price" => $offer->price,
                "My Offer" => $offer->my_offer,
                "Type" => $offer->type,
                "Created At" => $offer->created_at,
            ])->withQueryString(),
        ]);
    }
}
