<?php

namespace App\Console\Commands;

use App\Models\Offer;
use Illuminate\Console\Command;

class RetireOffer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retire:offers';

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
        // retire all offers that have passed their expiration date and their robosatsId is less than 20000
        $offers = Offer::where([['expires_at', '<', now()], ['robosatsId', '<', 50000]])->get();
        foreach ($offers as $offer) {
            $randomNumber = rand(1, 1000000000);
            $offer->robosatsIdStorage = $offer->robosatsId;
            $offer->robosatsId = $randomNumber;
            $offer->save();

            // robots
            $robots = $offer->robots;
            foreach ($robots as $robot) {
                $robot->offerIdStorage = $robot->offerId;
                $robot->offer_id = $randomNumber;
                $robot->save();
            }
        }
    }
}
