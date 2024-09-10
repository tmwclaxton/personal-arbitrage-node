<?php

namespace App\Jobs;

use App\Models\AdminDashboard;
use App\Models\Offer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;

class AutoAcceptFinal implements ShouldQueue
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
        $adminDashboard = AdminDashboard::all()->first();
        if (!$adminDashboard->autoAccept || $adminDashboard->panicButton) {
            return;
        }
        // get any offers that have auto_accept_at timestamp in the past
        $offers = Offer::where('auto_accept_at', '<=', Carbon::now())->get();
        foreach ($offers as $offer) {
            Bus::chain([
                new \App\Jobs\CreateRobots($offer, $adminDashboard),
                new \App\Jobs\AcceptSellOffer($offer, $adminDashboard),
                new releaseOffer($offer)
            ])->dispatch();
            $offer->auto_accept_at = null;
            $offer->save();
        }
    }
}
