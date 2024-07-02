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
    protected $signature = 'retire:offer';

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
        // retire all offers that have passed their expiration date
        $offers = Offer::where('expires_at', '<', now())->get();
        foreach ($offers as $offer) {
            $offer->robosatsId = null;
            $offer->save();
        }
    }
}
