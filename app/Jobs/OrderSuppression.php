<?php

namespace App\Jobs;

use App\Models\Offer;
use App\WorkerClasses\Robosats;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderSuppression implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;


    private int $premium = 1;
    private string $type = 'sell';
    private string $currency = 'EUR';

    /**
     * Create a new job instance.
     */
    public function __construct($type, $currency, $premium)
    {
        $this->premium = $premium;
        $this->type = $type;
        $this->currency = $currency;
    }

    /**
     * Execute the job.
     */
    public function handle(): string
    {
        // go through all offers and return offers that fit the criteria
        if ($this->type == 'sell') {
            $offers = Offer::where('type', $this->type )
                ->where('currency', $this->currency)
                ->where('premium', '>=', $this->premium)
                ->where('status', 1)
                ->where('expires_at', '>', now())
                ->where('my_offer', 0)
                ->get();
        } else {
            $offers = Offer::where('type', $this->type )
                ->where('currency', $this->currency)
                ->where('premium', '<=', $this->premium)
                ->where('status', 1)
                ->where('expires_at', '>', now())
                ->where('my_offer', 0)
                ->get();
        }

        // foreach offer, create robots then accept the offer
        // reverse offers
        $suppression_count = 0;
        foreach ($offers as $offer) {
            // create robots
            $robosats = new Robosats();
            $robots = $robosats->createRobots();
//            dd($robots->count(), $robots[0]);

            // accept the offer
            $url = $robosats->getHost() . '/mainnet/' . $offer->provider . '/api/order/?order_id=' . $offer->robosatsId;
            $response = Http::withHeaders($robosats->getHeaders(null, $robots))->timeout(30)->post($url, ['action' => 'take', 'amount' => $offer->max_amount]);
            // if response is 403 then retire the offer

            $suppression_count++;




        }

        // log the suppression
//        Log::info('Order suppression: ' . $this->type . ' ' . $this->currency . ' ' . $this->premium . ' ' . $suppression_count);
        // log the ids of the offers that were suppressed
//        dd( 'Order suppression: ' . json_encode($offers->pluck('robosatsId')->toArray()));
    }
}
