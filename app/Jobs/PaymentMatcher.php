<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\SlackService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;

class PaymentMatcher implements ShouldQueue
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

        // search for payments with a transaction_id of null and where created_at is less than 1 hour ago
        $payments = Payment::where('transaction_id', null)
            ->where('created_at', '>', Carbon::now()->subHour(24))
            ->get();

        $slackService = new SlackService();
        foreach ($payments as $payment) {
            // search through offers for an offer with a status of 9 / 10 and a matching accepted offer amount
            $offers = \App\Models\Offer::where(function ($query) use ($payment) {
                $query->where('status', 9)
                    ->orWhere('status', 10);
            })
                ->where('accepted_offer_amount', $payment->payment_amount)
                ->where('currency', $payment->payment_currency)
                ->get();

            if ($offers->count() > 1) {
                $message = '*Warning*: Multiple offers found for payment ' . $payment->id . ' of ' . $payment->payment_amount . ' ' . $payment->payment_currency;
                $this->sendUniqueMessage($slackService, $payment->id, $message);
            } elseif ($offers->count() === 0) {
                $message = '*Warning*: No offers found for payment ' . $payment->id . ' of ' . $payment->payment_amount . ' ' . $payment->payment_currency;
                $this->sendUniqueMessage($slackService, $payment->id, $message);
            } else {
                $offerId = $offers->first()->id;
                $offer = \App\Models\Offer::find($offerId);
                $robot = $offer->robots()->first();
                $transaction = $offer->transaction()->first();
                $payment->transaction_id = $transaction->id;

                // default confirm at 10 minutes in the future timestamp
                $autoConfirmAt = Carbon::now()->addMinutes(10);


                $message = "Found a matching order for the payment of " . $payment->payment_amount . " " . $payment->payment_currency .
                    ", see ID: " . $offer->id . " and transaction ID: " . $offer->transaction()->first()->id;

                if ($payment->payment_reference !== null && $payment->payment_reference !== "") {
                    // remove any non-numeric characters
                    $reference = preg_replace('/[^0-9]/', '', $payment->payment_reference);
                    if ($reference !== "") {
                        // if the reference is equal to robosatsID, then we can auto confirm in 2 minutes
                        if (intval($reference) === intval($offer->id)) {
                            $autoConfirmAt = Carbon::now()->addMinutes(5);
                            $message .= ". Additionally, the reference matches the internal offer ID";
                        }
                    }
                }


                // if autoConfirm is on add message
                if ($adminDashboard->autoConfirm) {
                    $message .= ". Auto confirming in " . $autoConfirmAt->diffForHumans();
                    $robosatsService = new \App\WorkerClasses\Robosats();
                    $robosatsService->webSocketCommunicate($offer, $robot, "Your payment of " . $payment->payment_amount . " " . $payment->payment_currency . " has been received. Please wait while I confirm the transaction (~" . $autoConfirmAt->diffForHumans() . ")");
                }

                $this->sendUniqueMessage($slackService, $payment->id, $message);

                if ($adminDashboard->autoConfirm) {
                    // set the auto confirm at timestamp on offer
                    $offer->auto_confirm_at = $autoConfirmAt;
                    $offer->save();
                }

                $payment->save();

                // currency conversion job
                $job = new \App\Jobs\CurrencyConverter();
                $job->handle();
            }
        }
    }

    /**
     * Send a unique message using Redis to avoid duplicates.
     */
    private function sendUniqueMessage($slackService, $paymentId, $message): void
    {
        $redisKey = 'payment_message_' . $paymentId;
        $cachedMessage = Redis::get($redisKey);

        if ($cachedMessage !== $message) {
            $slackService->sendMessage($message);
            Redis::set($redisKey, $message);
        }
    }
}
