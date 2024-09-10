<?php

namespace App\Jobs;

use App\Models\BtcFiat;
use App\Models\BtcPurchase;
use App\Models\Payment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class BtcPurchaseDetailer implements ShouldQueue
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
        $krakenService = new \App\Services\KrakenService();
        $btcPurchases = BtcPurchase::whereNull('ref_id')->get();
        $response  = $krakenService->getClient()->getClosedOrders();

        $adminDashboard = \App\Models\AdminDashboard::all()->first();
        if ($adminDashboard->panicButton) {
            return;
        }

        foreach ($response->closed as $txId => $order) {
            // check if txId exists in btcPurchases
            $btcPurchase = $btcPurchases->where('tx_id', $txId)->first();
            if ($btcPurchase) {
                $helperFunctions = new \App\WorkerClasses\HelperFunctions();
                $btcPurchase->ref_id = $order->refId;
                $btcPurchase->user_ref = $order->userRef;
                $btcPurchase->status = $order->status;
                $btcPurchase->reason = $order->reason;
                if ($order->openTimestamp > 0) {
                    $btcPurchase->open_timestamp = Carbon::createFromTimestamp(round($order->openTimestamp));
                }
                if ($order->startTimestamp > 0) {
                    $btcPurchase->start_timestamp = Carbon::createFromTimestamp(round($order->startTimestamp));
                }
                if ($order->expireTimestamp > 0) {
                    $btcPurchase->expire_timestamp = Carbon::createFromTimestamp(round($order->expireTimestamp));
                }
                if ($order->closeTimestamp > 0) {
                    $btcPurchase->close_timestamp = Carbon::createFromTimestamp(round($order->closeTimestamp));
                }

                $btcPurchase->description_pair = $order->description->pair;
                $btcPurchase->description_type = $order->description->type;
                $btcPurchase->description_order_type = $order->description->orderType;
                $btcPurchase->description_price = $helperFunctions->bigDecimalToDecimal($order->description->price);
                $btcPurchase->description_secondary_price = $helperFunctions->bigDecimalToDecimal($order->description->secondaryPrice);
                $btcPurchase->description_leverage = $order->description->leverage;
                $btcPurchase->description_order = $order->description->order;
                $btcPurchase->description_close = $order->description->close;

                $btcPurchase->volume = $helperFunctions->bigDecimalToDecimal($order->volume);
                $btcPurchase->volume_executed = $helperFunctions->bigDecimalToDecimal($order->volumeExecuted);
                $btcPurchase->cost = $helperFunctions->bigDecimalToDecimal($order->cost);
                $btcPurchase->fee = $helperFunctions->bigDecimalToDecimal($order->fee);
                $btcPurchase->price = $helperFunctions->bigDecimalToDecimal($order->price);
                $btcPurchase->stop_price = $helperFunctions->bigDecimalToDecimal($order->stopPrice);
                $btcPurchase->limit_price = $helperFunctions->bigDecimalToDecimal($order->limitPrice);
                $btcPurchase->miscellaneous = $order->miscellaneous;
                $btcPurchase->flags = json_encode($order->flags);
                $btcPurchase->trades = json_encode($order->trades);
                // have a look at the last 2 payments
                $payments = Payment::orderBy('created_at', 'desc')->take(2)->get();
                // payments can be in different currencies so we will use BtcFiat to convert to GBP
                $btcFiat = BtcFiat::all()->first();
                foreach ($payments as $payment) {
                    // $helperFunctions->convertCurrency(100, 'USD', 'GBP')
                    $convertedPayments[] = [
                        'id' => $payment->id,
                        'gbp_amount' => $helperFunctions->convertCurrency($payment->payment_amount, $payment->payment_currency, 'GBP'),
                        'created_at' => $payment->created_at
                    ];
                }

                if (isset($convertedPayments)) {
                    // if any of the payments are within £5 higher, or lower set payment_id to that of converted payment
                    foreach ($convertedPayments as $convertedPayment) {
                        if ($convertedPayment['gbp_amount'] >= $btcPurchase->cost - 5 && $convertedPayment['gbp_amount'] <= $btcPurchase->cost + 5) {
                            $btcPurchase->payment_id = $convertedPayment['id'];
                            break;
                        }
                    }
                }


                $btcPurchase->save();
            }
        }

    }
}
