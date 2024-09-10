<?php

namespace App\Console\Commands;

use App\Http\Controllers\OfferController;
use App\Jobs\releaseOffer;
use App\Models\AdminDashboard;
use App\Models\Offer;
use App\Models\Transaction;
use App\Services\DiscordService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;

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
        if (!isset($adminDashboard->umbrel_ip, $adminDashboard->umbrel_token)) {
            return 0;
        }
        if (!$adminDashboard->autoAccept) {
            return 0;
        }
        $maxConcurrentTransactions = $adminDashboard->max_concurrent_transactions;
        $offers = Offer::where([['status', '<=', 11],['my_offer', '=', false],['accepted', '=', true]])->get();
        $count = $offers->count();
        if ($count >= $maxConcurrentTransactions) {
            // (new DiscordService())->sendMessage('Max concurrent transactions reached');
            return 0;
        }
        // calculate difference
        $difference = $maxConcurrentTransactions - $count;
        // $offers = (new \App\Http\Controllers\OfferController)->getOffersInternal($adminDashboard);

        $sellPremium = $adminDashboard->sell_premium;

        // where status != 14, 12, 17, 18, 99, 4, 5, 2
        $offers = Offer::where([['accepted', '=', false],['premium', '>=', $sellPremium], ['type', 'sell'], ['expires_at', '>', now()]])
            ->orderBy('accepted', 'desc')
            ->orderBy('max_satoshi_amount_profit', 'desc')
            ->orderBy('satoshi_amount_profit', 'desc')
            ->orderBy('premium', 'desc')
            ->get();



        $paymentMethods = json_decode($adminDashboard->payment_methods);

        foreach ($offers as $offer) {
            // check if offer has already been accepted
            if ($offer->accepted) {
                $offers = $offers->filter(function ($value, $key) use ($offer) {
                    return $value->id != $offer->id;
                });
            }

            // check if any of the payment methods are in the admin dashboard payment methods, if not remove the offer
            $found = false;
            if ($adminDashboard == null) {
                $paymentMethods = [];
            }
            foreach (json_decode($offer->payment_methods) as $paymentMethod) {
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


            // grab admin dashboard
            $adminDashboard = AdminDashboard::all()->first();
            $channelBalances = json_decode($adminDashboard->channelBalances, true);

            // grab the largest amount we can accept whether it is range or not
            $calculations = (new OfferController())->calculateLargestAmount($offer, $channelBalances);
            // if not array or if estimated_offer_amount is null or 0, remove the offer
            if (is_array($calculations) && $calculations['estimated_offer_amount'] > 0) {
                $offer->estimated_profit_sat = $calculations['estimated_profit_sats'];
                $offer->estimated_offer_amount_sat = $calculations['estimated_offer_amount_sats'];
                $offer->estimated_offer_amount = $calculations['estimated_offer_amount'];
            } else {
                // remove the offer
                $offers = $offers->filter(function ($value, $key) use ($offer) {
                    return $value->id != $offer->id;
                });
            }
        }

        // grab ongoing transactions
        $transactions = Transaction::where('status', '<', 14)->get();
        // grab offer ids from ongoing transactions
        $offerIds = $transactions->pluck('offer_id')->toArray();
        // we don't want to accept any offers that are for the same amount & currency as an ongoing transaction
        $onGoingOffers = Offer::whereIn('id', $offerIds)->get();
        foreach ($onGoingOffers as $onGoingOffer) {
            $onGoingOfferAmount = $onGoingOffer->accepted_offer_amount;
            $onGoingOfferCurrency = $onGoingOffer->currency;
            // remove the any offers that are for the same amount & currency as an ongoing transaction
            $offers = $offers->filter(function ($value, $key) use ($onGoingOfferAmount, $onGoingOfferCurrency) {
                return $value->estimated_offer_amount != $onGoingOfferAmount || $value->currency != $onGoingOfferCurrency;
            });
        }



        // remove any offers who estimated_profit_sat is less than AdminDashboard->min_satoshi_profit
        $offers = $offers->filter(function ($value, $key) use ($adminDashboard) {
            return $value->estimated_profit_sat >= $adminDashboard->min_satoshi_profit;
        });

        if ($offers->count() == 0) {
            return 0;
        }

        // First, collect the premiums and estimated profits to calculate min and max
        $premiums = $offers->pluck('premium')->toArray();
        $profits = $offers->pluck('estimated_profit_sats')->toArray();

        $minPremium = min($premiums);
        $maxPremium = max($premiums);
        $minProfit = min($profits);
        $maxProfit = max($profits);

        // Score the offers using premium and estimated profit
        foreach ($offers as $offer) {
            $estimatedProfit = $offer->estimated_profit_sats;
            $premium = $offer->premium;

            // Normalize the premium and estimated profit
            $normalizedPremium = ($maxPremium - $minPremium) == 0 ? 0 : ($premium - $minPremium) / ($maxPremium - $minPremium);
            $normalizedProfit = ($maxProfit - $minProfit) == 0 ? 0 : ($estimatedProfit - $minProfit) / ($maxProfit - $minProfit);

            // Calculate the score
            $offer->score = 0.5 * $normalizedPremium + 0.5 * $normalizedProfit;
        }

        // Sort the offers by score
        $offers = $offers->sortByDesc('score');

        // Optional: If you want to reindex the array to ensure continuous numeric indexing
        $offers = $offers->values();

        // grab the first $difference offers
        $offers = $offers->take($difference);

        // chain 2 jobs, one to create the robots and one to accept the offers
        foreach ($offers as $offer) {
            $adminDashboard = AdminDashboard::all()->first();

            if ($offer->job_locked) {
                continue;
            }
            $discordService = new DiscordService();
            $discordService->sendMessage('Auto accepting offer ' . $offer->robosatsId . ' in 1 minutes for ' . $offer->estimated_offer_amount . ' ' . $offer->currency . ' at ' . $offer->premium . '% premium');
            // reset offer
            $offer = Offer::find($offer->id);
            $offer->job_locked = true;
            // 2 minutes from now
            $offer->auto_accept_at = Carbon::now()->addMinutes(1);
            $offer->save();


        }



    }
}
