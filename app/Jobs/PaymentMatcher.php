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
                $payment->transaction_id = $offer->transaction()->first()->id;
                $payment->save();
                $message = 'Found a matching order for the payment of ' . $payment->payment_amount . ' ' . $payment->payment_currency .
                    ', see offer ID: ' . $offer->id . ' and transaction ID: ' . $offer->transaction()->first()->id;
                $this->sendUniqueMessage($discordService, $payment->id, $message);

                // // currency conversion job
                // $job = new \App\Jobs\CurrencyConverter();
                // $job->handle();
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
