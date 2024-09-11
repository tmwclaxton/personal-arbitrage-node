<?php

namespace App\Jobs;

use App\Models\AdminDashboard;
use App\Models\Offer;
use App\WorkerClasses\Robosats;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;

class AutoConfirmFinal implements ShouldQueue
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
        if (!$adminDashboard->autoConfirm || $adminDashboard->panicButton) {
            return;
        }

        $offers = Offer::where('auto_confirm_at', '<=', Carbon::now())->whereNotNull('auto_confirm_at')->get();
        foreach ($offers as $offer) {
            $slackService = new \App\Services\SlackService();
            $slackService->sendMessage('Auto confirming offer ' . $offer->robosatsId);
            $transaction = $offer->transaction;
            $robosatsService = new Robosats();
            $robosatsService->confirmReceipt($offer, $transaction);
            $offer->auto_confirm_at = null;
            $offer->save();
        }
    }
}
