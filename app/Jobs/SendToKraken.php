<?php

namespace App\Jobs;

use App\Services\KrakenService;
use App\Services\RevolutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendToKraken implements ShouldQueue
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
        if (!$adminDashboard->autoTopup || $adminDashboard->panicButton) {
            return;
        }

        $revolutService = new RevolutService();
        $revolutService->sendAllToAccount(env('REVOLUT_RECIPIENT_ACCOUNT_ID_KRAKEN_GBP'), "GBP");
        $revolutService->sendAllToAccount(env('REVOLUT_RECIPIENT_ACCOUNT_ID_KRAKEN_EUR'), "EUR", env('REVOLUT_EUR_REFERENCE'));

    }
}
