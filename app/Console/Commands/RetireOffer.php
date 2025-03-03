<?php

namespace App\Console\Commands;

use App\Models\Offer;
use App\Models\PostedOfferTemplate;
use App\Services\SlackService;
use App\WorkerClasses\HelperFunctions;
use App\WorkerClasses\RobosatsStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RetireOffer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:retire-offers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Offers passed their expiration date are retired, by setting their robosatsId to null';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ((new HelperFunctions())->slackCommandCheck()) {
            $offers = Offer::where('expires_at', '<', now()->subMinutes(5))->where('robosatsIdStorage', '=', null)->get();
            $extraOffers = Offer::where('status', '=', 14)->where('robosatsIdStorage', '=', null)->get();
            $offers = $offers->merge($extraOffers);

            // remove any offers created within the last 5 minutes as their expiration date is not accurate till it has updated at least once
            $offers = $offers->filter(function ($offer) {
                return $offer->created_at->diffInMinutes(now()) > 5;
            });

            foreach ($offers as $offer) {

                $randomNumber = rand(5000000, 10000000);
                $offer->robosatsIdStorage = $offer->robosatsId;
                $offer->robosatsId = $randomNumber;
                // some orders seem to get stuck particulary when the maker has not paid the bond, so if an offer has expired and the status is less than TAK, set status to expired
                if ($offer->status <= RobosatsStatus::getStatus('TAK')) {
                    $offer->status = RobosatsStatus::getStatus('EXP');
                }

                $offer->save();
                // check if offer has posted_offer_template_slug
                if (isset($offer->posted_offer_template_slug)) {
                    $template = PostedOfferTemplate::where('slug', $offer->posted_offer_template_slug)->first();
                    $cooldown = $template->cooldown ?? 0;
                    $template->last_accepted =  Carbon::now()->subSeconds($cooldown);
                    $template->save();
                }

                // if there is a slack channel associated with the offer, archive it
                $slackService = new SlackService();
                // if the offer has a slack channel, archive it
                if ($offer->slack_channel_id) {
                    $slackService->deleteChannel($offer->slack_channel_id);
                }

                $offer->slack_channel_id = null;
                $offer->save();

            }
        }
    }
}
