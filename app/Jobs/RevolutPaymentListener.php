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

        $mitmService = new \App\Services\MitmService();
        $transactions = $mitmService->grabTransactions();

        // iterate through the transactions and create a payment object for each
        foreach ($transactions as $transaction) {
            if ($transaction['state'] !== 'COMPLETED'
                || Carbon::createFromTimestamp($transaction['completedDate'])->lt(Carbon::now()->subHour(1))
                || $transaction['amount'] < 0) {
                continue;
            }
            // check if transfer / topup
            if (!in_array($transaction['type'], ['TRANSFER', 'TOPUP'])) {
                continue;
            }

            $payment = new \App\Models\Payment();
            $payment->payment_method = 'Revolut';
            $payment->platform_transaction_id = $transaction['id'];
            if (isset($transaction['comment'])) {
                $payment->payment_reference = $transaction['comment'];
            }
            if (Payment::where('platform_transaction_id', $payment->platform_transaction_id)->exists()) {
                continue;
            }

            $payment->payment_currency = $transaction['currency'];
            $payment->payment_amount = $transaction['amount'] / 100;
            $payment->platform_account_id = $transaction['account']['id'];
            $payment->platform_description = $transaction['description'];
            # grab the unix completedDate and convert it to a Carbon instance and set it as the payment_date
            $payment->payment_date = Carbon::createFromTimestamp($transaction['completedDate']);
            $payment->platform_entity = json_encode($transaction);



            $payment->save();

            $discordService = new DiscordService();
            $message = 'Payment received: ' . $payment->payment_amount . ' ' . $payment->payment_currency . ' on Revolut';
            # if there is a description, append it to the message
            if ($payment->platform_description) {
                $message .= ' with description: ' . $payment->platform_description;
            }
            # if there is a payment reference, append it to the message
            if ($payment->payment_reference) {
                $message .= ' with reference: ' . $payment->payment_reference;
            }
            $discordService->sendMessage($message);

        }

        $transactions = $mitmService->getBalances();
        $adminDashboard->revolut_balance = json_encode($transactions);

    }
}
