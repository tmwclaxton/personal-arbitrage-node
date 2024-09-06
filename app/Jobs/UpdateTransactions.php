<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\WorkerClasses\Robosats;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateTransactions implements ShouldQueue
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


    // timeout 180 seconds
    public int $timeout = 180;

    public function handle(): void
    {
        // update all current transactions where status != 14, 12, 17, 18, 99
        $transactions = Transaction::whereNotIn('status', [5, 14, 12, 17, 18, 99])->get();
        foreach ($transactions as $transaction) {
            $offer = $transaction->offer;
            $robosats = new Robosats();
            $robosats->updateTransactionStatus($offer);
        }
    }

}
