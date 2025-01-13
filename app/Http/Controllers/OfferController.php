<?php

namespace App\Http\Controllers;

use App\Jobs\ConfirmPayment;
use App\Jobs\PayBond;
use App\Jobs\PayEscrow;
use App\Jobs\SendPaymentHandle;
use App\Models\AdminDashboard;
use App\Models\BtcFiat;
use App\Models\Offer;
use App\Models\PaymentMethod;
use App\Models\RobosatsChatMessage;
use App\Models\Transaction;
use App\Services\SlackService;
use App\WorkerClasses\HelperFunctions;
use App\WorkerClasses\LightningNode;
use App\WorkerClasses\Robosats;
use App\WorkerClasses\RobosatsStatus;
use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Inertia\Inertia;

class OfferController extends Controller
{
    public function getOffersInternal($adminDashboard)
    {
        if ($adminDashboard == null) {
            $adminDashboard = new AdminDashboard();
            $adminDashboard->save();
        }

        $sellPremium = $adminDashboard->sell_premium;
        $buyPremium = $adminDashboard->buy_premium;
        $excludedStatuses = [99, 5, 14];

        // first check if there are any offers that are not in the excluded statuses
        if (Offer::all()->count() == 0) {
            return [];
        }

        $offers = Offer::whereNotIn('status', $excludedStatuses)
            ->where(function ($query) use ($sellPremium, $buyPremium) {
                $query->where('accepted', true)
                    ->orWhere(function ($subQuery) use ($sellPremium) {
                        $subQuery->where('accepted', false)
                            ->where('premium', '>=', $sellPremium)
                            ->where('type', 'sell');
                    })
                    ->orWhere(function ($subQuery) use ($buyPremium) {
                        $subQuery->where('accepted', false)
                            ->where('premium', '<=', $buyPremium)
                            ->where('type', 'buy');
                    })
                    ->orWhere('my_offer', true);
            })
            ->orderBy('accepted', 'desc')
            ->orderBy('my_offer', 'desc')
            ->orderBy('max_satoshi_amount_profit', 'desc')
            ->orderBy('satoshi_amount_profit', 'desc')
            ->orderBy('premium', 'desc')
            ->get();

        // remove expired offers
        $offers = $offers->filter(function ($offer) {
            return $offer->expires_at > now();
        });

        return $offers;
    }


    public function getInfo()
    {

        $btcFiats = BtcFiat::where('currency', 'USD')->orWhere('currency', 'GBP')->orWhere('currency', 'EUR')->get();
        $allFiats = BtcFiat::all();
        $adminDashboard = AdminDashboard::all()->first();

        if ($adminDashboard == null) {
            return [
                'btcFiats' => $btcFiats,
                'allFiats' => $allFiats,
                'adminDashboard' => $adminDashboard,
                'offers' => []
            ];
        }

        $offers = $this->getOffersInternal($adminDashboard);

        $paymentMethods = json_decode($adminDashboard->payment_methods);

        $currencies = json_decode($adminDashboard->payment_currencies);

        // change the expires_at to a human readable format
        foreach ($offers as $offer) {
            $this->prepareOffer($offers, $offer, $paymentMethods, $currencies);
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

        $helpFunction = new HelperFunctions();




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

    public function getOffer($offerId)
    {
        $offer = Offer::find($offerId);
        if ($offer == null) {
            return response()->json(['message' => 'Offer not found'], 404);
        }
        $offers = new Collection();
        $offers->push($offer);
        // set the unadulterated to the value of the offer
        $unadulteratedOffer = json_decode(json_encode($offer));
        $offer = $this->prepareOffer($offers, $offer, null, null);
        $chatMessages = RobosatsChatMessage::where('offer_id', $offerId)->get();

        return Inertia::render('OfferPage', [
            'offer' => $offer,
            'unadulteratedOffer' => $unadulteratedOffer,
            'robots' => $offer->robots,
            'transactions' => Transaction::where('offer_id', $offerId)->get(),
            'chatMessages' => $chatMessages
        ]);
    }

    public function insertOffer($offerDTO, $provider): Offer
    {

        $allFiats = BtcFiat::all();

        // change id in the offer to robosatsId
        $offerDTO['robosatsId'] = $offerDTO['id'];

        // remove id from the offer
        unset($offerDTO['id']);

        // change currency using Robosats::CURRENCIES
        $offerDTO['currency'] = Robosats::CURRENCIES[$offerDTO['currency']];

        // remove '/mainnet/' from the provider
        $provider = str_replace('Mainnet/', '', $provider);

        // lowercase the provider
        $provider = strtolower($provider);

        // // remove the '/' at the end of the provider
        $provider = rtrim($provider, '/');
        $offerDTO['provider'] = $provider;

        // convert the expires_at i.e. "2024-06-28T06:24:07.984166Z" to correct format
        $offerDTO['expires_at'] = date('Y-m-d H:i:s', strtotime($offerDTO['expires_at']));

        // convert the created_at i.e. "2024-06-28T06:24:07.984166Z" to correct format
        $offerDTO['created_at'] = date('Y-m-d H:i:s', strtotime($offerDTO['created_at']));

        // if payment_method is given, change to payment_methods
        if (isset($offerDTO['payment_method'])) {
            $offerDTO['payment_methods'] = [$offerDTO['payment_method']];
            unset($offerDTO['payment_method']);
        }

        // Step 1: Fetch payment methods from the database
        $paymentMethods = PaymentMethod::all();
        $paymentMethodsInternal = $paymentMethods->pluck('name')->toArray(); // List of all payment method names

        // Step 2: Combine the input payment methods into a single space-delimited string
        $paymentMethodsString = implode(' ', $offerDTO['payment_methods']);

        // Step 3: Initialize an array for normalized payment methods
        $normalizedPaymentMethods = [];

        // Step 4: Check each payment method from the database against the combined string
        foreach ($paymentMethodsInternal as $paymentMethod) {
            if (strpos($paymentMethodsString, $paymentMethod) !== false) {
                $normalizedPaymentMethods[] = $paymentMethod;
                // Remove the matched payment method from the string to avoid duplicate matches
                $paymentMethodsString = str_replace($paymentMethod, '', $paymentMethodsString);
            }
        }

        // Step 5: Update the offer with the final payment methods and whatever is left in the string

        $paymentMethodsLeft = explode(' ', $paymentMethodsString);
        $combinedPaymentMethods = array_merge($normalizedPaymentMethods, $paymentMethodsLeft);
        // remove any empty strings
        $combinedPaymentMethods = array_filter($combinedPaymentMethods, function($value) { return $value !== ''; });
        $offerDTO['payment_methods'] = $combinedPaymentMethods;

        // convert the payment_methods to a json array without a key
        $offerDTO['payment_methods'] = json_encode(array_values($offerDTO['payment_methods']));

        if (array_key_exists('price_now', $offerDTO)) {
            $offerDTO['price'] = $offerDTO['price_now'];
            unset($offerDTO['price_now']);
        }


        $existingOffer = Offer::where('robosatsId', $offerDTO['robosatsId'])->first();
        // if the offer is a buy offer (will show up as sell for the counterparty)
        // and it is our offer, then we need to change the profit to a negative number

        // buy is 1 and sell is 2 // if we are the taker

        // if offer doesn't exist then it's obviously not our offer so we can just set the type normally, otherwise if it is the type is flipped
        if ($existingOffer) {
            if ($existingOffer->my_offer) {
                $offerDTO["type"] = $offerDTO["type"] == 0 ? "buy" : "sell";
            } else {
                $offerDTO["type"] = $offerDTO["type"] == 1 ? "buy" : "sell";
            }
            $existingOffer->type = $offerDTO["type"];
            $existingOffer->save();
            $type = $existingOffer->type;
        } else {
            $offerDTO["type"] = $offerDTO["type"] == 1 ? "buy" : "sell";
            $type = $offerDTO["type"];
        }


        if ($allFiats && $allFiats->count() > 0 && isset($offerDTO['price']) && $offerDTO['price'] > 0) {
            // grab currency from offer and find the price in btc using allFiats
            $btcPrice = $allFiats->where('currency', $offerDTO['currency'])->first();
            // once a ranged offer is accepted, the amount is set to whatever we are selling
            if ($offerDTO['amount']) {
                $offerDTO['satoshis_now'] = $this->convertToSatoshis($offerDTO['amount'], $offerDTO['price']);
                $offerDTO['satoshi_amount_profit'] = $this->calculateProfit($offerDTO['amount'], $offerDTO['price'], $btcPrice->price, $type);
            }

            if ($offerDTO['min_amount'] && $offerDTO['max_amount']) {
                $offerDTO['min_satoshi_amount'] = $this->convertToSatoshis($offerDTO['min_amount'], $offerDTO['price']);
                $offerDTO['max_satoshi_amount'] = $this->convertToSatoshis($offerDTO['max_amount'], $offerDTO['price']);

                $offerDTO['min_satoshi_amount_profit'] = $this->calculateProfit($offerDTO['min_amount'], $offerDTO['price'], $btcPrice->price, $type);
                $offerDTO['max_satoshi_amount_profit'] = $this->calculateProfit($offerDTO['max_amount'], $offerDTO['price'], $btcPrice->price, $type);
            }


            // // find offer if it exists
            // $existingOffer = Offer::where('robosatsId', $offerDTO['robosatsId'])->first();
            // // if my_offer is true and type is buy, then we need to change the profit to a absolute number
            // if ($existingOffer && $existingOffer->my_offer && $existingOffer->type == "buy") {


        }




        if (array_key_exists('bond_invoice', $offerDTO)) {
            $bond_invoice = $offerDTO['bond_invoice'];
            // remove the bond_invoice from the offer
            unset($offerDTO['bond_invoice']);
            unset($offerDTO['bond_satoshis']);
        }

        // iterate through each key in the offer and set corresponding attributes
        $newOffer = new Offer();

        foreach ($offerDTO as $key => $value) {
            $newOffer->$key = $value;
        }

        // save or update the offer
        if (Offer::where('robosatsId', $offerDTO['robosatsId'])->exists()) {
            // if the offer has expired if the old offer's status is over 10, then give the offer 20 minutes before updating
            // so that we don't update a completed offer before it has been retired
            if ($newOffer->status == 5 && $offerDTO['status'] > 8 && $newOffer->updated_at->diffInMinutes(now()) > 20) {

            } else {
                Offer::where('robosatsId', $offerDTO['robosatsId'])->update($offerDTO);
            }
        } else {
            $newOffer->save();
        }

        $offer = Offer::where('robosatsId', $offerDTO['robosatsId'])->first();

        // it looks like status has been removed from the orderbook, but it is always 1 if an offer is in the orderbook, unless it is our offer, or if we have it recorded as higher than 1
        // then it needs updated from the transaction side (robot token in header) and the orderbook is probably out of date
        if (!$offer->my_offer && $offer->status < 1) {
            $offer->status = 1;
            $offer->save();
        }

        return $offer;
    }

    public function calculateLargestAmount($offer, $channelBalances, $specificAmount = null, $ignoreMaxSatoshiAmount = false) {
        // grab the offer price amount or max amount
        if (!$specificAmount) {
            if ($offer->has_range) {
                if (!isset($offer->min_satoshi_amount) || !isset($offer->max_satoshi_amount)) {
                    // this error can happen when we are creating an offer but it hasn't been updated just yet
                    //(new SlackService)->sendMessage('Error: Offer has range but no min or max amount');
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
                    // this error can happen when we are creating an offer but it hasn't been updated just yet
                    // (new SlackService)->sendMessage('Error: Offer has no amount');
                    return 'Offer has no amount';
                }
                $variationAmounts = [$offer->satoshis_now];
            }
        } else {
            $variationAmounts = [$specificAmount];
        }

        if (!$ignoreMaxSatoshiAmount) {
            // THIS FILTERS OUT ANY VARIATION AMOUNTS THAT ARE GREATER THAN THE MAX SATOSHI AMOUNT
            $adminDashboard = AdminDashboard::all()->first();
            $max_satoshi_amount = $adminDashboard->max_satoshi_amount;

            // remove any variation amounts that are greater than the max_satoshi_amount and if there are no variation amounts left after filtering alert the user
            $variationAmounts = array_filter($variationAmounts, function ($variationAmount) use ($max_satoshi_amount) {
                return $variationAmount <= $max_satoshi_amount;
            });

            if (count($variationAmounts) == 0) {
                // (new SlackService)->sendMessage('Error: No variation amounts left after filtering');
                return 'No variation amounts left after filtering.  Increase max_satoshi_amount or accept smaller offers';
            }
        }

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
            // (new SlackService)->sendMessage('Error: Insufficient balance (ps need 100000 extra for fees for bond and potentially fees)');
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
            // $estimated_profit_sats = $estimated_offer_amount_sat * (($offer->price - $currentRealPrice) / $currentRealPrice);
            $estimated_profit_sats = $this->calculateProfit($estimated_offer_amount, $offer->price, $currentRealPrice, $offer->type);

        } else {
            $estimated_profit_sats = $offer->satoshi_amount_profit;
        }



        // if


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
        $response = $robosats->createRobots($offer);
        return $response;
    }

    public function acceptOffer(Request $request) {
        // grab amount
        //validate the request
        $request->validate([
            'offer_id' => 'required',
            'amount' => 'nullable|numeric',
        ]);
        $amount = request('amount');

        $robosats = new Robosats();
        $offerId = request('offer_id');
        $offer = Offer::find($offerId);

        $response = $robosats->acceptOffer($offer->robosatsId, $amount);
        return $response;
    }

    public function payBond(Request $request) {
        $offerId = request('offer_id');
        $offer = Offer::find($offerId);
        $adminDashboard = AdminDashboard::all()->first();
        PayBond::dispatch($offer, $adminDashboard);

        return response()->json(['message' => 'Bond payment being processed']);
    }

    public function updateInvoice(Request $request) {
        $offerId = request('offer_id');
        $offer = Offer::find($offerId);
        $robosats = new Robosats();
        $response = $robosats->updateInvoice($offer);
        return $response;
    }

    public function payEscrow(Request $request) {
        // grab offer_id and transaction_id
        $offerId = request('offer_id');
        $offer = Offer::find($offerId);
        $adminDashboard = AdminDashboard::all()->first();
        PayEscrow::dispatch($offer, $adminDashboard);
        return response()->json(['message' => 'Escrow payment being processed']);
    }

    public function confirmPayment(Request $request) {
        $offerId = request('offer_id');
        $offer = Offer::find($offerId);
        // $transaction = Transaction::where('offer_id', $offerId)->first();
        // $robosats = new Robosats();
        // $response = $robosats->confirmReceipt($offer, $transaction);

        $adminDashboard = AdminDashboard::all()->first();
        ConfirmPayment::dispatch($offer, $adminDashboard);

        // return $response;

        return response()->json(['message' => 'Payment confirmation being processed']);
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
        $adminDashboard = AdminDashboard::all()->first();
        // SendPaymentHandle::dispatch($offer, $adminDashboard);

        $job = new \App\Jobs\SendPaymentHandle($offer, $adminDashboard);
        $job->handle();
    }

    public function autoAccept(Request $request) {
        // validate the request
        $request->validate([
            'offer_id' => 'required',
            'amount' => 'nullable|numeric',
        ]);

        $amount = request('amount');
        $offerId = request('offer_id');
        $adminDashboard = AdminDashboard::all()->first();
        $offer = Offer::where('id', $offerId)->first();



        Bus::chain([
            new \App\Jobs\CreateRobots($offer, $adminDashboard),
            new \App\Jobs\AcceptSellOffer($offer, $adminDashboard, $amount),
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
        $offers = Offer::where('status', '=' , 14)->orderByDesc('created_at');
        return Inertia::render('CompletedOffers', [
            'offers' => $offers->paginate(25)->setPath(route('offers.completed'))->through(fn($offer)=>[
                "id" => $offer->id,
                "Token Backup" => $offer->robotTokenBackup,
                "Accepted Offer Amount" => round($offer->accepted_offer_amount, 2),
                "Accepted Offer Amount Satoshis" => $offer->accepted_offer_amount_sat,
                "Accepted Offer Amount Profit Satoshis" => $offer->accepted_offer_profit_sat,
                "Currency" => $offer->currency,
                "Btc Price" => $offer->price,
                "My Offer" => boolval($offer->my_offer),
                "Type" => $offer->type,
                "Created At" => $offer->created_at,
            ])->withQueryString(),
        ]);
    }

    function convertToSatoshis($amount, $price): int
    {
        return intval(str_replace(',', '', number_format((intval(str_replace(',', '', $amount)) / $price) * 100000000, 0)));
    }

    function calculateProfit($amount, $price, $btcPrice, $type = null): int
    {
        $satoshis = $this->convertToSatoshis($amount, $price);
        $actualSatoshis = $this->convertToSatoshis($amount, $btcPrice);
        $calculation = $actualSatoshis - $satoshis;

        if (isset($type) && $type == "buy" ) {
            $calculation = $calculation * -1;
        }

        return $calculation;
    }

    private function prepareOffer(mixed &$offers,
                                  mixed $offer,
                                  mixed $paymentMethods,
                                    mixed $currencies): mixed
    {
        if (isset($offer->expires_at)) {
            $offer->expires_at = Carbon::parse($offer->expires_at)->diffForHumans();
        }
        if (isset($offer->updated_at)) {
            $offer->updated_at_readable = Carbon::parse($offer->updated_at)->diffForHumans();
        }
        if (isset($offer->auto_accept_at)) {
            $offer->auto_accept_at = Carbon::parse($offer->auto_accept_at)->diffForHumans();
        }
        if (isset($offer->auto_confirm_at)) {
            $offer->auto_confirm_at = Carbon::parse($offer->auto_confirm_at)->diffForHumans();
        }
        // round amount to 2 decimal places
        $offer->amount = number_format($offer->amount, 2);
        $offer->accepted_offer_amount = number_format($offer->accepted_offer_amount, 2) . ' ' . $offer->currency;
        // round min_amount to 2 decimal places and max amount to 2 decimal places
        $offer->min_amount = number_format($offer->min_amount, 2);
        $offer->max_amount = number_format($offer->max_amount, 2);
        // ensure premium has a sign
        $offer->premium = $offer->premium > 0 ? '+' . $offer->premium : $offer->premium;
        // add a percentage to the premium
        $offer->premium = $offer->premium . '%';


        $offer->payment_methods = json_decode($offer->payment_methods);
        // convert escrow_duration to hours
        $offer->escrow_duration = round($offer->escrow_duration / 3600, 2);

        // status message isn't always correct so we will use status and RobosatsStatuses
        $offer->status_message = (RobosatsStatus::getStatusTexts()[$offer['status']]);

        // check if any of the payment methods are in the admin dashboard payment methods, if not remove the offer
        $found = false;
        if ($paymentMethods == null) {
            $paymentMethods = [];
        }
        foreach ($offer->payment_methods as $paymentMethod) {
            if (in_array($paymentMethod, $paymentMethods) || $offer->my_offer) {
                $found = true;
            }
        }
        if (!$found) {
            // check if the offer is accepted or it is my_offer
            if (!$offer->accepted && !$offer->my_offer) {
                $offers = $offers->filter(function ($value, $key) use ($offer) {
                    return $value->id != $offer->id;
                });
            }
        }

        // check if currency is in the admin dashboard currencies, if not remove the offer
        $found = false;
        if ($currencies == null) {
            $currencies = [];
        }
        foreach ($currencies as $currency) {
            if ($currency == $offer->currency) {
                $found = true;
            }
        }
        if (!$found) {
            if (!$offer->accepted && !$offer->my_offer) {
                $offers = $offers->filter(function ($value, $key) use ($offer) {
                    return $value->id != $offer->id;
                });
            }
        }

        // make human readable
        // $offer->payment_methods = implode(', ', $offer->payment_methods);

        // if offer is accepted find the transaction
        if ($offer->accepted || ($offer->robosatsIdStorage == null && $offer->robotTokenBackup != null)) {
            $transaction = Transaction::where('offer_id', $offer->id)->first();
            $offer->transaction = $transaction;
        }


        // grab robots
        $offer->robots = $offer->robots()->get();

        return $offer;
    }

    public function manuallyUpdateOffer(Request $request) {
        $offer = $request->offer;
        $offer_id = $request->offer_id;

        // iterate through each key in the offer and set corresponding attributes
        $oldOffer = Offer::find($offer_id);

        // remove id from the offer
        unset($offer['id']);
        // remove robosatsId from the offer
        unset($offer['robosatsId']);

        // iterate through each key in oldOffer and set corresponding attributes
        foreach ($offer as $key => $value) {
            // check if key does exist
            if (key_exists($key, $offer)) {
                $oldOffer->$key = $value;
            }
        }
        $oldOffer->save();

    }

    public function togglePauseOffer(Request $request) {
        $offer = Offer::find($request->offer_id);
        $robosats = new Robosats();
        $response = $robosats->togglePauseOffer($offer);
        return $response;
    }

//    public function resumeOffer(Request $request) {
//        $offer = Offer::find($request->offer_id);
//        $robosats = new Robosats();
//        $response = $robosats->resumeOffer($offer);
//        return $response;
//    }

    public function cancelOffer(Request $request) {
        $offer = Offer::find($request->offer_id);
        $robosats = new Robosats();
        $response = $robosats->cancelOffer($offer);
        return $response;
    }


}
