<?php

namespace App\Jobs;

use App\Models\AdminDashboard;
use App\Models\BtcFiat;
use App\WorkerClasses\Robosats;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateFiats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    // timeout 180 seconds
    public int $timeout = 180;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $adminDashboard = AdminDashboard::all()->first();
        if (!isset($adminDashboard->umbrel_ip, $adminDashboard->umbrel_token)) {
            return;
        }
        $robosats = new Robosats();
        $prices = $robosats->getCurrentPrices();
        if (!$prices) {
            return;
        }
        foreach ($prices as $price) {
            $btcFiat = new BtcFiat();
            // if the currency is already in the database, update it
            $btcFiat->updateOrCreate(
                ['currency' => $price['code']],
                ['price' => $price['price']]
            );
        }
    }
}
