<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\DiscordService;
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
            ->where('created_at', '>', Carbon::now()->subHour(1))
            ->get();

        $discordService = new DiscordService();
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
                $message = '**Warning**: Multiple offers found for payment ' . $payment->id . ' of ' . $payment->payment_amount . ' ' . $payment->payment_currency;
                $this->sendUniqueMessage($discordService, $payment->id, $message);
            } elseif ($offers->count() === 0) {
                $message = '**Warning**: No offers found for payment ' . $payment->id . ' of ' . $payment->payment_amount . ' ' . $payment->payment_currency;
                $this->sendUniqueMessage($discordService, $payment->id, $message);
            } else {
                $offer = $offers->first();
                $transaction = $offer->transaction()->first();
                $payment->transaction_id = $transaction->id;
                $payment->save();

                // default confirm at 10 minutes in the future timestamp
                $autoConfirmAt = Carbon::now()->addMinutes(10);
                $reference = "";
                switch ($payment->payment_method) {
                    case 'Revolut':
                        $platformEntity = json_decode($payment->platform_entity);
                        $reference = $platformEntity->reference;
                        break;
                    case 'Wise':
                        //{"id": "TU9ORVRBUllfQUNUSVZJVFk6OjU1Njk4NjIxOjpUUkFOU0ZFUjo6MTE2NDEyNzU5Mw==", "type": "TRANSFER", "title": "<strong>Toby Claxton</strong>", "amount": "20", "sender": "Toby Claxton", "status": "COMPLETED", "currency": "GBP", "resource": {"id": "1164127593", "type": "TRANSFER"}, "createdOn": "2024-07-31T18:50:58.783Z", "updatedOn": "2024-07-31T18:51:05.186Z", "description": "", "primaryAmount": "<positive>+ 20 GBP</positive>", "formattedAmount": "20 GBP", "secondaryAmount": ""}
                        // $platformEntity = json_decode($payment->platform_entity);
                        // $reference = $platformEntity->resource->id;
                        //!TODO: Wise doesn't give us the reference in the main object!
                        $discordService->sendMessage('Wise method not implemented yet');
                        return;
                    default:
                        $discordService->sendMessage('Unknown payment method: ' . $payment->payment_method);
                        return;
                }

                // if the reference is equal to robosatsID, then we can auto confirm in 2 minutes
                if (intval($reference) === intval($offer->robosatsId)) {
                    $autoConfirmAt = Carbon::now()->addMinutes(5);
                }

                $message = "Found a matching order for the payment of " . $payment->payment_amount . " " . $payment->payment_currency .
                    ", see robosats ID: " . $offer->robosatsId . " and transaction ID: " . $offer->transaction()->first()->id;
                    // ". Auto confirming in " . $autoConfirmAt->diffForHumans();

                // if autoConfirm is on add message
                if ($adminDashboard->autoConfirm) {
                    $message .= ". Auto confirming in " . $autoConfirmAt->diffForHumans();
                    $robosatsService = new \App\WorkerClasses\Robosats();
                    $robosatsService->webSocketCommunicate($offer, $transaction, "Your payment has been received. Please wait while we confirm the transaction (~10 minutes).");
                }

                $this->sendUniqueMessage($discordService, $payment->id, $message);

                if ($adminDashboard->autoConfirm) {
                    // set the auto confirm at timestamp on offer
                    $offer->auto_confirm_at = $autoConfirmAt;
                    $offer->save();
                }

                // currency conversion job
                $job = new \App\Jobs\CurrencyConverter();
                $job->handle();
            }
        }
    }

    /**
     * Send a unique message using Redis to avoid duplicates.
     */
    private function sendUniqueMessage($discordService, $paymentId, $message): void
    {
        $redisKey = 'payment_message_' . $paymentId;
        $cachedMessage = Redis::get($redisKey);

        if ($cachedMessage !== $message) {
            $discordService->sendMessage($message);
            Redis::set($redisKey, $message);
        }
    }
}
