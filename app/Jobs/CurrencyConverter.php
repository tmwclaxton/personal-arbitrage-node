<?php

namespace App\Jobs;

use App\Services\DiscordService;
use App\Services\RevolutService;
use App\Services\WiseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CurrencyConverter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        $adminDashboard = \App\Models\AdminDashboard::all()->first();
        if ($adminDashboard->panicButton) {
            return;
        }

        //!TODO: Add currency exchange event recording

        $revolutService = new RevolutService();
        $revolutService->currencyExchangeAll("EUR", "GBP");
        $revolutService->currencyExchangeAll("USD", "GBP");
        $wiseService = new WiseService();
        $wiseService->currencyExchangeAll("EUR", "GBP");
        $wiseService->currencyExchangeAll("USD", "GBP");


    }
}
