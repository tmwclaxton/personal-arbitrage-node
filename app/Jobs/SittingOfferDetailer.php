<?php

namespace App\Jobs;

use App\Http\Controllers\OfferController;
use App\Models\AdminDashboard;
use App\Models\Offer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SittingOfferDetailer implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // grab all sitting offers that are not yet accepted
        $sittingOffers = Offer::where([['my_offer', '=', 1], ['expires_at', '>', now()]])
            ->get();

        foreach ($sittingOffers as $sittingOffer) {
            if ($sittingOffer->status > 1) {
                $adminDashboard = AdminDashboard::all()->first();

                $channelBalances = json_decode($adminDashboard->channelBalances, true);

                // grab the largest amount we can accept whether it is range or not
                $calculations = (new OfferController())->calculateLargestAmount($sittingOffer, $channelBalances);
                if (is_array($calculations) && $calculations['estimated_offer_amount'] > 0) {
                    $sittingOffer->accepted_offer_amount_sat = $calculations['estimated_offer_amount_sats'];
                    $sittingOffer->accepted_offer_amount = $calculations['estimated_offer_amount'];
                    $sittingOffer->accepted_offer_profit_sat = $calculations['estimated_profit_sats'];
                    // round satoshi to 0 decimal places
                    $sittingOffer->accepted_offer_profit_sat = round($sittingOffer->accepted_offer_profit_sat, 0);
                    $sittingOffer->accepted_offer_amount_sat = round($sittingOffer->accepted_offer_amount_sat, 0);
                }
                // $sittingOffer->accepted = true;
                $sittingOffer->save();
            }
        }
    }
}
