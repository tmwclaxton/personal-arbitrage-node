<?php

namespace App\Console\Commands;

use App\Models\BtcFiat;
use App\WorkerClasses\Robosats;
use Illuminate\Console\Command;

class UpdateFiats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh:fiats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh fiats';

    /**
     * Execute the console command.
     */
    public function handle()
    {
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
