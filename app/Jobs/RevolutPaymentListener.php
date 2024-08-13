<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\DiscordService;
use App\Services\RevolutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class RevolutPaymentListener implements ShouldQueue
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

        // $revolutService = new RevolutService();
        // $transactions = $revolutService->getTransactions();




        // convert transactions to an array
        $transactions = json_decode(json_encode($transactions), true);

        // iterate through the transactions and create a payment object for each
        foreach ($transactions as $transaction) {
            if ($transaction['state'] !== 'completed'
                || $transaction['completed_at'] < Carbon::now()->subHour(1) || $transaction['legs'][0]['amount'] < 0) {
                continue;
            }
            // check if transfer / topup
            if (!in_array($transaction['type'], ['transfer', 'topup'])) {
                continue;
            }

            $payment = new \App\Models\Payment();
            $payment->payment_method = 'Revolut';
            $payment->platform_transaction_id = $transaction['id'];

            if (Payment::where('platform_transaction_id', $payment->platform_transaction_id)->exists()) {
                continue;
            }

            $payment->payment_currency = $transaction['legs'][0]['currency'];
            $payment->payment_amount = $transaction['legs'][0]['amount'];
            $payment->platform_account_id = $transaction['legs'][0]['account_id'];
            $payment->platform_description = $transaction['legs'][0]['description'];
            $payment->platform_entity = json_encode($transaction);

            $payment->save();

            $discordService = new DiscordService();
            $discordService->sendMessage('Payment received: ' . $payment->payment_amount . ' ' . $payment->payment_currency . ' on Revolut');




        }

    }
}
